<?php

use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Cuenta;
use App\Models\Tasa;
use App\Models\MovimientoEntreCuenta;
use App\Models\MovimientoCuenta;

new class extends Component
{
    public bool $abierto = false;

    // Cuentas
    public $cuenta_origen_id = '';
    public $cuenta_destino_id = '';
    public $monto_origen = '';

    // Tasa (solo si hay cambio de moneda)
    public $tasa_id = '';
    public $cambio_id = null;
    public $tasa_momento = null;
    public $tasa_personalizada = false;
    public $tasa_manual = '';

    // Extras
    public $referencia = '';
    public $notas = '';

    #[On('nuevo-movimiento')]
    public function abrir($cuentaOrigenId = null): void
    {
        $this->reset();

        $this->cuenta_origen_id = $cuentaOrigenId ?: '';

        // Autoseleccionar tasa predeterminada
        $tasaPredeterminada = Tasa::predeterminada();
        if ($tasaPredeterminada) {
            $this->tasa_id = $tasaPredeterminada->id;
            $this->cargarCambio();
        }

        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    // ============================================================
    // CARGA DE CAMBIO
    // ============================================================

    public function updatedTasaId(): void
    {
        $this->cargarCambio();
    }

    public function updatedTasaPersonalizada(): void
    {
        if (!$this->tasa_personalizada) {
            $this->tasa_manual = '';
        }
    }

    private function cargarCambio(): void
    {
        if (!$this->tasa_id) {
            $this->cambio_id = null;
            $this->tasa_momento = null;
            return;
        }

        $tasa = Tasa::find($this->tasa_id);
        if (!$tasa) return;

        $cambio = $tasa->cambioActualDe('dolar');

        if ($cambio) {
            $this->cambio_id = $cambio->id;
            $this->tasa_momento = (float) $cambio->monto;
        } else {
            $this->cambio_id = null;
            $this->tasa_momento = null;
        }
    }

    // ============================================================
    // PROPIEDADES COMPUTADAS
    // ============================================================

    #[Computed]
    public function cuentaOrigen(): ?Cuenta
    {
        return $this->cuenta_origen_id ? Cuenta::find($this->cuenta_origen_id) : null;
    }

    #[Computed]
    public function cuentaDestino(): ?Cuenta
    {
        return $this->cuenta_destino_id ? Cuenta::find($this->cuenta_destino_id) : null;
    }

    #[Computed]
    public function tipoDetectado(): ?string
    {
        $origen = $this->cuentaOrigen;
        $destino = $this->cuentaDestino;

        if (!$origen || !$destino) return null;

        // Banco → Efectivo = RETIRO
        if ($origen->tipo === 'BANCO' && $destino->tipo === 'EFECTIVO') {
            return 'RETIRO';
        }

        // Efectivo → Banco = DEPOSITO
        if ($origen->tipo === 'EFECTIVO' && $destino->tipo === 'BANCO') {
            return 'DEPOSITO';
        }

        // Cualquier otro caso = TRANSFERENCIA
        return 'TRANSFERENCIA';
    }

    #[Computed]
    public function hayCambioMoneda(): bool
    {
        $origen = $this->cuentaOrigen;
        $destino = $this->cuentaDestino;

        if (!$origen || !$destino) return false;

        return $origen->moneda !== $destino->moneda;
    }

    #[Computed]
    public function tasaAplicada(): float
    {
        if ($this->tasa_personalizada && $this->tasa_manual > 0) {
            return (float) $this->tasa_manual;
        }
        return (float) ($this->tasa_momento ?? 0);
    }

    #[Computed]
    public function montoDestino(): float
    {
        $monto = (float) $this->monto_origen;
        $origen = $this->cuentaOrigen;
        $destino = $this->cuentaDestino;

        if (!$origen || !$destino) return 0;

        // Si no hay cambio de moneda, el monto es el mismo
        if (!$this->hayCambioMoneda) {
            return $monto;
        }

        $tasa = $this->tasaAplicada;
        if ($tasa <= 0) return 0;

        // Bs → USD/EUR
        if ($origen->moneda === 'bs' && in_array($destino->moneda, ['dolar', 'euro'])) {
            return round($monto / $tasa, 2);
        }

        // USD/EUR → Bs
        if (in_array($origen->moneda, ['dolar', 'euro']) && $destino->moneda === 'bs') {
            return round($monto * $tasa, 2);
        }

        // USD ↔ EUR: conversión cruzada
        if (in_array($origen->moneda, ['dolar', 'euro']) && in_array($destino->moneda, ['dolar', 'euro'])) {
            $montoBs = $monto * $tasa;
            return round($montoBs / $tasa, 2);
        }

        return 0;
    }

    #[Computed]
    public function comisionOrigen(): float
    {
        $cuenta = $this->cuentaOrigen;
        if (!$cuenta) return 0;

        $tipo = $this->tipoDetectado;

        // En depósitos, la cuenta origen es efectivo → no cobra
        if ($tipo === 'DEPOSITO') return 0;

        // En retiros y transferencias, la cuenta origen es banco → sí cobra
        $comision = $cuenta->comisionPara('TRANSFERENCIA');
        if (!$comision) return 0;

        return $comision->calcularComisionEgreso((float) $this->monto_origen);
    }

    #[Computed]
    public function comisionDestino(): float
    {
        return 0;
    }

    #[Computed]
    public function debitoTotal(): float
    {
        return round((float) $this->monto_origen + $this->comisionOrigen, 2);
    }

    #[Computed]
    public function creditoTotal(): float
    {
        return round($this->montoDestino - $this->comisionDestino, 2);
    }

    #[Computed]
    public function montoMaximo(): float
    {
        $origen = $this->cuentaOrigen;
        if (!$origen) return 0;

        $saldo = (float) $origen->saldo_actual;
        $tipo = $this->tipoDetectado;

        if ($tipo === 'DEPOSITO') {
            return $saldo;
        }

        $comision = $origen->comisionPara('TRANSFERENCIA');
        $porcentaje = $comision ? (float) $comision->porcentaje_egreso : 0;

        $factor = 1 + ($porcentaje / 100);
        if ($factor <= 0) return 0;

        return round($saldo / $factor, 2);
    }

    // ============================================================
    // GUARDAR
    // ============================================================

    public function guardar(): void
    {
        $origen = $this->cuentaOrigen;
        $destino = $this->cuentaDestino;

        if (!$origen || !$destino) {
            $this->addError('cuenta_destino_id', 'Debes seleccionar ambas cuentas.');
            return;
        }

        if ($origen->id === $destino->id) {
            $this->addError('cuenta_destino_id', 'La cuenta destino debe ser diferente a la origen.');
            return;
        }

        $monto = (float) $this->monto_origen;
        if ($monto <= 0) {
            $this->addError('monto_origen', 'El monto debe ser mayor a 0.');
            return;
        }

        // Validar saldo incluyendo comisión
        $comisionOrigen = $this->comisionOrigen;
        $debitoTotal = round($monto + $comisionOrigen, 2);
        $saldoDisponible = (float) $origen->saldo_actual;

        if ($debitoTotal > $saldoDisponible) {
            $faltante = round($debitoTotal - $saldoDisponible, 2);
            $this->addError('monto_origen',
                "Saldo insuficiente. El total a debitar es {$origen->moneda_etiqueta} "
                . number_format($debitoTotal, 2)
                . " (monto " . number_format($monto, 2)
                . " + comisión " . number_format($comisionOrigen, 2)
                . "). Faltan {$origen->moneda_etiqueta} "
                . number_format($faltante, 2) . "."
            );
            return;
        }

        // Validar tasa si hay cambio de moneda
        if ($this->hayCambioMoneda) {
            if ($this->tasaAplicada <= 0) {
                $this->addError('tasa_id', 'Debes seleccionar una tasa válida o ingresar una personalizada.');
                return;
            }
        }

        $montoDestino = $this->montoDestino;
        if ($montoDestino <= 0) {
            $this->addError('monto_origen', 'El monto a acreditar es 0. Verifica la tasa.');
            return;
        }

        $creditoTotal = $this->creditoTotal;
        if ($creditoTotal <= 0) {
            $this->addError('monto_origen', 'El monto a acreditar después de comisiones es 0.');
            return;
        }

        $tipo = $this->tipoDetectado;
        $hayCambioMoneda = $this->hayCambioMoneda;

        \DB::transaction(function () use ($origen, $destino, $monto, $montoDestino, $comisionOrigen, $debitoTotal, $creditoTotal, $tipo, $hayCambioMoneda) {
            // 1. Crear el movimiento entre cuentas
            $movimiento = MovimientoEntreCuenta::create([
                'tipo' => $tipo,
                'cuenta_origen_id' => $origen->id,
                'cuenta_destino_id' => $destino->id,
                'monto_origen' => $monto,
                'moneda_origen' => $origen->moneda,
                'monto_destino' => $montoDestino,
                'moneda_destino' => $destino->moneda,
                'hay_cambio_moneda' => $hayCambioMoneda,
                'tasa_id' => $hayCambioMoneda ? $this->tasa_id : null,
                'cambio_id' => $hayCambioMoneda ? $this->cambio_id : null,
                'tasa_momento' => $hayCambioMoneda ? $this->tasaAplicada : null,
                'tasa_personalizada' => $hayCambioMoneda && $this->tasa_personalizada,
                'comision_origen_porcentaje' => 0,
                'comision_origen_monto_fijo' => 0,
                'comision_origen_monto' => $comisionOrigen,
                'comision_destino_porcentaje' => 0,
                'comision_destino_monto_fijo' => 0,
                'comision_destino_monto' => 0,
                'debito_total' => $debitoTotal,
                'credito_total' => $creditoTotal,
                'referencia' => $this->referencia ?: null,
                'notas' => $this->notas ?: null,
                'registrado_por' => auth()->id(),
            ]);

            // 2. Movimiento EGRESO en cuenta origen
            MovimientoCuenta::registrar(
                cuentaId: $origen->id,
                tipo: 'EGRESO',
                origenTipo: $tipo,
                origenId: $movimiento->id,
                movimientoEntreCuentaId: $movimiento->id,
                monto: $debitoTotal,
                descripcion: "{$tipo} a {$destino->nombre} (ref #{$movimiento->id})"
            );

            // 3. Movimiento INGRESO en cuenta destino
            MovimientoCuenta::registrar(
                cuentaId: $destino->id,
                tipo: 'INGRESO',
                origenTipo: $tipo,
                origenId: $movimiento->id,
                movimientoEntreCuentaId: $movimiento->id,
                monto: $creditoTotal,
                descripcion: "{$tipo} desde {$origen->nombre} (ref #{$movimiento->id})"
            );
        });

        session()->flash('success', "{$tipo} registrado correctamente.");
        $this->dispatch('movimiento-registrado');
        $this->abierto = false;
    }

    public function with(): array
    {
        $origen = $this->cuentaOrigen;

        // Filtrar cuentas destino (excluir la cuenta origen)
        $cuentasDestinoQuery = Cuenta::where('activa', true);

        if ($origen) {
            $cuentasDestinoQuery->where('id', '!=', $origen->id);
        }

        return [
            'cuentas' => Cuenta::where('activa', true)->orderBy('nombre')->get(),
            'cuentasDestino' => $origen ? $cuentasDestinoQuery->orderBy('nombre')->get() : collect(),
            'tasas' => Tasa::where('activa', true)->orderByDesc('predeterminado')->orderBy('nombre')->get(),
        ];
    }
};
?>

<div>
    @if ($abierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[95vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-cyan-100 text-cyan-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">
                                @if ($this->tipoDetectado === 'RETIRO')
                                    Nuevo Retiro
                                @elseif ($this->tipoDetectado === 'DEPOSITO')
                                    Nuevo Depósito
                                @elseif ($this->tipoDetectado === 'TRANSFERENCIA')
                                    Nueva Transferencia
                                @else
                                    Nuevo Movimiento
                                @endif
                            </h3>
                            @if ($this->cuentaOrigen && $this->cuentaDestino)
                                <p class="text-xs text-gray-500">
                                    {{ $this->cuentaOrigen->nombre }} → {{ $this->cuentaDestino->nombre }}
                                    @if ($this->hayCambioMoneda)
                                        · <span class="text-cyan-600 font-semibold">Cambio de moneda</span>
                                    @endif
                                </p>
                            @else
                                <p class="text-xs text-gray-500">Mueve dinero entre cuentas</p>
                            @endif
                        </div>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5 space-y-4">

                    {{-- Info contextual --}}
                    @if ($this->tipoDetectado)
                        <div class="p-3 rounded-lg text-xs
                            @if ($this->tipoDetectado === 'RETIRO') bg-amber-50 border border-amber-200 text-amber-800
                            @elseif ($this->tipoDetectado === 'DEPOSITO') bg-emerald-50 border border-emerald-200 text-emerald-800
                            @else bg-cyan-50 border border-cyan-200 text-cyan-800
                            @endif">
                            @if ($this->tipoDetectado === 'RETIRO')
                                <strong>Retiro:</strong> saca dinero del banco hacia efectivo.
                            @elseif ($this->tipoDetectado === 'DEPOSITO')
                                <strong>Depósito:</strong> lleva efectivo al banco.
                            @else
                                <strong>Transferencia:</strong> mueve dinero entre cuentas bancarias o virtuales.
                            @endif
                        </div>
                    @endif

                    {{-- Cuenta origen --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Cuenta Origen *</label>
                        <select wire:model.live="cuenta_origen_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Seleccione una cuenta</option>
                            @foreach ($cuentas as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->nombre }} ({{ $c->tipo_etiqueta }}) — {{ $c->saldo_formateado }}
                                </option>
                            @endforeach
                        </select>
                        @error('cuenta_origen_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

                        @if ($this->cuentaOrigen)
                            <p class="text-xs text-gray-500 mt-1">
                                Saldo disponible: <strong>{{ $this->cuentaOrigen->saldo_formateado }}</strong>
                            </p>
                        @endif
                    </div>

                    {{-- Cuenta destino --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Cuenta Destino *</label>
                        <select wire:model.live="cuenta_destino_id"
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <option value="">Seleccione una cuenta</option>
                            @foreach ($cuentasDestino as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->nombre }} ({{ $c->tipo_etiqueta }}) — {{ $c->moneda_etiqueta }}
                                </option>
                            @endforeach
                        </select>
                        @error('cuenta_destino_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Monto --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Monto ({{ $this->cuentaOrigen?->moneda_etiqueta ?? '?' }}) *
                        </label>
                        <input type="number" step="0.01" min="0.01"
                               wire:model.live.debounce.300ms="monto_origen"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold">
                        @error('monto_origen') <span class="text-xs text-red-500">{{ $message }}</span> @enderror

                        @if ($this->cuentaOrigen)
                            <p class="text-xs text-gray-500 mt-1">
                                Máximo: <strong class="text-teal-700">
                                    {{ $this->cuentaOrigen->moneda_etiqueta }} {{ number_format($this->montoMaximo, 2) }}
                                </strong>
                            </p>
                        @endif
                    </div>

                    {{-- Tasa (solo si hay cambio de moneda) --}}
                    @if ($this->hayCambioMoneda)
                        <div class="border-t border-gray-100 pt-4">
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase">
                                    Tasa de Cambio *
                                </label>
                                <label class="flex items-center cursor-pointer text-xs">
                                    <input type="checkbox" wire:model.live="tasa_personalizada"
                                           class="w-3.5 h-3.5 rounded border-gray-300 text-cyan-600 focus:ring-cyan-500">
                                    <span class="ml-1.5 text-gray-600">Usar tasa personalizada</span>
                                </label>
                            </div>

                            @if (!$tasa_personalizada)
                                <select wire:model.live="tasa_id"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="">Seleccione una tasa</option>
                                    @foreach ($tasas as $t)
                                        <option value="{{ $t->id }}">
                                            {{ $t->etiqueta }}
                                            @if ($t->predeterminado) (predeterminada) @endif
                                        </option>
                                    @endforeach
                                </select>
                                @if ($tasa_momento)
                                    <p class="text-xs text-gray-500 mt-1">
                                        Tasa actual: <strong>Bs {{ number_format($tasa_momento, 2) }}</strong>
                                    </p>
                                @endif
                            @else
                                <input type="number" step="0.0001" min="0.0001"
                                       wire:model.live.debounce.300ms="tasa_manual"
                                       placeholder="Ej: 900.00"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <p class="text-xs text-gray-500 mt-1">
                                    Ingresa la tasa negociada manualmente.
                                </p>
                            @endif
                            @error('tasa_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- Resumen del cálculo --}}
                    @if ($this->cuentaOrigen && $this->cuentaDestino && $monto_origen > 0)
                        <div class="p-4 bg-cyan-50 border border-cyan-200 rounded-lg space-y-2 text-sm">
                            <div class="flex items-center justify-between">
                                <span class="text-cyan-800">Monto a mover:</span>
                                <span class="font-semibold text-cyan-900">
                                    {{ $this->cuentaOrigen->moneda_etiqueta }} {{ number_format((float) $monto_origen, 2) }}
                                </span>
                            </div>

                            @if ($this->comisionOrigen > 0)
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-cyan-700">Comisión origen:</span>
                                    <span class="text-red-600">
                                        -{{ $this->cuentaOrigen->moneda_etiqueta }} {{ number_format($this->comisionOrigen, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between font-semibold border-t border-cyan-200 pt-2">
                                    <span class="text-cyan-800">Total a debitar:</span>
                                    <span class="text-cyan-900">
                                        {{ $this->cuentaOrigen->moneda_etiqueta }} {{ number_format($this->debitoTotal, 2) }}
                                    </span>
                                </div>
                            @endif

                            @if ($this->hayCambioMoneda)
                                <div class="flex items-center justify-between text-xs pt-2 border-t border-cyan-200">
                                    <span class="text-cyan-700">Tasa aplicada:</span>
                                    <span class="text-cyan-900 font-semibold">
                                        Bs {{ number_format($this->tasaAplicada, 2) }}
                                    </span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-cyan-700">Monto convertido:</span>
                                    <span class="text-cyan-900 font-semibold">
                                        {{ $this->cuentaDestino->moneda_etiqueta }} {{ number_format($this->montoDestino, 2) }}
                                    </span>
                                </div>
                            @endif

                            <div class="flex items-center justify-between font-bold text-base pt-2 border-t-2 border-cyan-300">
                                <span class="text-cyan-900">Acredita en destino:</span>
                                <span class="text-cyan-700">
                                    {{ $this->cuentaDestino->moneda_etiqueta }} {{ number_format($this->creditoTotal, 2) }}
                                </span>
                            </div>
                        </div>
                    @endif

                    {{-- Referencia y notas --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Referencia</label>
                            <input type="text" wire:model="referencia"
                                   placeholder="Número de operación"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Notas</label>
                            <input type="text" wire:model="notas"
                                   placeholder="Observaciones"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                wire:loading.attr="disabled"
                                class="px-5 py-2 bg-cyan-600 hover:bg-cyan-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span wire:loading.remove>
                                @if ($this->tipoDetectado === 'RETIRO')
                                    Registrar Retiro
                                @elseif ($this->tipoDetectado === 'DEPOSITO')
                                    Registrar Depósito
                                @else
                                    Registrar Transferencia
                                @endif
                            </span>
                            <span wire:loading>Procesando...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>