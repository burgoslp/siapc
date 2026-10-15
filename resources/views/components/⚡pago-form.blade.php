<?php
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Caja;
use App\Models\Cambio;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\Tasa;

new class extends Component
{
    public bool $abierto = false;
    public ?Cita $cita = null;
    public ?Caja $caja = null;

    // Datos del pago
    public $moneda = 'dolar';
    public $tasa_id = '';
    public $cambio_id = null;
    public $tasa_momento = null;
    public $monto = '';
    public $forma_pago = 'EFECTIVO';
    public $banco = '';
    public $referencia = '';
    public $notas = '';

    #[On('cobrar-cita')]
    public function abrir(int $citaId): void
    {
        // Verificar caja abierta
        $this->caja = Caja::abierta();
        if (!$this->caja) {
            session()->flash('error', 'No hay caja abierta. Debes abrir una caja para registrar pagos.');
            return;
        }

        // Cargar la cita
        $this->cita = Cita::with(['paciente', 'medico', 'pagos'])->find($citaId);
        if (!$this->cita) {
            return;
        }

        // No cobrar si ya está pagada
        if ($this->cita->estado_pago === 'PAGADA') {
            session()->flash('error', 'Esta cita ya está pagada.');
            return;
        }

        // Resetear
        $this->reset(['moneda', 'tasa_id', 'cambio_id', 'tasa_momento', 'monto', 'forma_pago', 'banco', 'referencia', 'notas']);
        $this->moneda = 'dolar';
        $this->forma_pago = 'EFECTIVO';

        // Autoseleccionar tasa predeterminada
        $tasaPredeterminada = Tasa::predeterminada();
        if ($tasaPredeterminada) {
            $this->tasa_id = $tasaPredeterminada->id;
            $this->cargarCambio();
        }

        // Autocompletar monto con el saldo pendiente (en USD)
        $this->monto = $this->cita->saldo_pendiente_usd;

        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    // ============================================================
    // Recalcular cuando cambia la moneda o tasa
    // ============================================================

    public function updatedMoneda(): void
    {
        $this->cargarCambio();
        $this->recalcularMontoInicial();
    }

    public function updatedTasaId(): void
    {
        $this->cargarCambio();
        $this->recalcularMontoInicial();
    }

    /**
     * Carga el cambio vigente de la tasa seleccionada en la moneda elegida.
     */
    private function cargarCambio(): void
    {
        if (!$this->tasa_id) {
            $this->cambio_id = null;
            $this->tasa_momento = null;
            return;
        }

        $tasa = Tasa::find($this->tasa_id);
        if (!$tasa) return;

        // Si la moneda es Bs, no necesitamos tasa para el monto directamente
        // pero igual guardamos la tasa dólar como referencia
        $monedaCambio = $this->moneda === 'bs' ? 'dolar' : $this->moneda;

        $cambio = $tasa->cambioActualDe($monedaCambio);

        if ($cambio) {
            $this->cambio_id = $cambio->id;
            $this->tasa_momento = $cambio->monto;
        } else {
            $this->cambio_id = null;
            $this->tasa_momento = null;
            $this->addError('tasa_id', "La tasa {$tasa->etiqueta} no tiene cotización en {$monedaCambio}.");
        }
    }

    /**
     * Cuando el usuario cambia de moneda, recalcula el monto
     * para que siga equivaliendo al saldo pendiente.
     */
    private function recalcularMontoInicial(): void
    {
        if (!$this->cita || !$this->tasa_momento) return;

        $saldoUsd = $this->cita->saldo_pendiente_usd;
        $tasa = (float) $this->tasa_momento;

        $this->monto = match ($this->moneda) {
            'dolar' => round($saldoUsd, 2),
            'bs'    => round($saldoUsd * $tasa, 2),
            'euro'  => $tasa > 0 ? round(($saldoUsd * $this->tasaDolarActual()) / $tasa, 2) : 0,
            default => $saldoUsd,
        };
    }

    /**
     * Obtiene la tasa dólar actual para conversiones cruzadas.
     */
    private function tasaDolarActual(): float
    {
        if (!$this->tasa_id) return 0;
        $tasa = Tasa::find($this->tasa_id);
        return (float) ($tasa?->cambioActualDe('dolar')?->monto ?? 0);
    }

    // ============================================================
    // Cálculos en tiempo real para mostrar en el modal
    // ============================================================

    #[Computed]
    public function montoBsCalculado(): float
    {
        $monto = (float) ($this->monto ?: 0);
        $tasa = (float) ($this->tasa_momento ?: 0);

        return match ($this->moneda) {
            'bs'    => $monto,
            'dolar' => round($monto * $tasa, 2),
            'euro'  => round($monto * $tasa, 2),
            default => 0,
        };
    }

    #[Computed]
    public function montoUsdCalculado(): float
    {
        $montoBs = $this->montoBsCalculado;
        $tasaDolar = $this->tasaDolarActual();

        if ($tasaDolar <= 0) return 0;

        return round($montoBs / $tasaDolar, 2);
    }

    #[Computed]
    public function saldoRestanteUsd(): float
    {
        if (!$this->cita) return 0;
        return max(0, round($this->cita->saldo_pendiente_usd - $this->montoUsdCalculado, 2));
    }

    #[Computed]
    public function requiereBanco(): bool
    {
        return in_array($this->forma_pago, ['TRANSFERENCIA', 'PAGO_MOVIL']);
    }

    #[Computed]
    public function requiereReferencia(): bool
    {
        return in_array($this->forma_pago, ['TRANSFERENCIA', 'PAGO_MOVIL', 'ZELLE', 'BINANCE']);
    }

    // ============================================================
    // Guardar
    // ============================================================

    public function guardar(): void
    {
        if (!$this->cita || !$this->caja) {
            session()->flash('error', 'Faltan datos para procesar el pago.');
            return;
        }

        // Reglas base
        $rules = [
            'moneda' => 'required|in:dolar,euro,bs',
            'tasa_id' => 'required|exists:tasas,id',
            'monto' => 'required|numeric|min:0.01',
            'forma_pago' => 'required|in:EFECTIVO,TRANSFERENCIA,PAGO_MOVIL,TARJETA_DEBITO,TARJETA_CREDITO,ZELLE,BINANCE,OTRO',
            'notas' => 'nullable|string',
        ];

        // Reglas condicionales
        if ($this->requiereBanco) {
            $rules['banco'] = 'required|string|max:100';
        } else {
            $rules['banco'] = 'nullable|string|max:100';
        }

        if ($this->requiereReferencia) {
            $rules['referencia'] = 'required|string|max:50';
        } else {
            $rules['referencia'] = 'nullable|string|max:50';
        }

        $datos = $this->validate($rules);

        // Validar monto no exceda saldo
        if ($this->montoUsdCalculado > $this->cita->saldo_pendiente_usd + 0.01) {
            $this->addError('monto', 'El monto excede el saldo pendiente de la cita.');
            return;
        }

        if (!$this->cambio_id || !$this->tasa_momento) {
            $this->addError('tasa_id', 'No se pudo cargar la tasa seleccionada.');
            return;
        }

        \DB::transaction(function () use ($datos) {
            // 1. Crear el pago
            $pago = Pago::create([
                'caja_id' => $this->caja->id,
                'cita_id' => $this->cita->id,
                'paciente_id' => $this->cita->paciente_id,
                'monto' => $datos['monto'],
                'tasa_id' => $this->tasa_id,
                'cambio_id' => $this->cambio_id,
                'tasa_momento' => $this->tasa_momento,
                'moneda' => $datos['moneda'],
                'forma_pago' => $datos['forma_pago'],
                'banco' => $datos['banco'] ?: null,
                'referencia' => $datos['referencia'] ?: null,
                'notas' => $datos['notas'] ?: null,
                'registrado_por' => auth()->id(),
            ]);

            // 2. Actualizar acumulador de la cita (en Bs)
            $this->cita->total_pagado = round(
                (float) $this->cita->total_pagado + $pago->monto_bs,
                2
            );
            $this->cita->save();

            // 3. Recargar la relación pagos para recalcular
            $this->cita->load('pagos');
            $this->cita->recalcularEstadoPago();
        });

        session()->flash('success', 'Pago registrado correctamente.');
        $this->abierto = false;

        // Recargar la página completa
        $this->redirect(route('facturacion.index'), navigate: false);
    }

    public function with(): array
    {
        return [
            'tasas' => Tasa::where('activa', true)->orderByDesc('predeterminado')->orderBy('nombre')->get(),
        ];
    }
};
?>

<div>
    @if ($abierto && $cita && $caja)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[95vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Registrar Pago</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Caja: <span class="font-mono">{{ $caja->codigo }}</span>
                            </p>
                        </div>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">

                    {{-- Info de la cita --}}
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Paciente:</span>
                            <span class="font-semibold text-gray-800">
                                {{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Total de la cita:</span>
                            <span class="font-bold text-gray-800">
                                ${{ number_format((float) $cita->total, 2) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-600">Pagado:</span>
                            <span class="text-gray-700">
                                ${{ number_format($cita->total_pagado_usd, 2) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-sm pt-2 border-t border-slate-200">
                            <span class="font-semibold text-gray-700">Saldo pendiente:</span>
                            <span class="font-bold text-amber-600 text-lg">
                                ${{ number_format($cita->saldo_pendiente_usd, 2) }}
                            </span>
                        </div>
                    </div>

                    {{-- Moneda y tasa --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        {{-- Moneda --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Moneda de pago *</label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="moneda" value="dolar" class="peer sr-only">
                                    <div class="px-2 py-2 border border-gray-300 rounded-lg text-xs text-center font-medium text-gray-600
                                                peer-checked:bg-blue-50 peer-checked:border-blue-400 peer-checked:text-blue-700
                                                hover:bg-gray-50 transition">
                                        USD
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="moneda" value="euro" class="peer sr-only">
                                    <div class="px-2 py-2 border border-gray-300 rounded-lg text-xs text-center font-medium text-gray-600
                                                peer-checked:bg-purple-50 peer-checked:border-purple-400 peer-checked:text-purple-700
                                                hover:bg-gray-50 transition">
                                        EUR
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="moneda" value="bs" class="peer sr-only">
                                    <div class="px-2 py-2 border border-gray-300 rounded-lg text-xs text-center font-medium text-gray-600
                                                peer-checked:bg-teal-50 peer-checked:border-teal-400 peer-checked:text-teal-700
                                                hover:bg-gray-50 transition">
                                        Bs
                                    </div>
                                </label>
                            </div>
                        </div>

                        {{-- Tasa --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tasa aplicada *</label>
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
                                    Tasa actual: Bs {{ number_format((float) $tasa_momento, 4) }}
                                </p>
                            @endif
                            @error('tasa_id') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Forma de pago y monto --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Forma de pago *</label>
                            <select wire:model.live="forma_pago"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="EFECTIVO">Efectivo</option>
                                <option value="TRANSFERENCIA">Transferencia</option>
                                <option value="PAGO_MOVIL">Pago Móvil</option>
                                <option value="TARJETA_DEBITO">Tarjeta Débito</option>
                                <option value="TARJETA_CREDITO">Tarjeta Crédito</option>
                                <option value="ZELLE">Zelle</option>
                                <option value="BINANCE">Binance</option>
                                <option value="OTRO">Otro</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Monto a pagar ({{ strtoupper($moneda) }}) *
                            </label>
                            <input type="number" step="0.01" min="0.01" wire:model.live="monto"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold">
                            @error('monto') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- Cálculos en tiempo real --}}
                    <div class="p-4 bg-teal-50 border border-teal-200 rounded-lg space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-teal-800">Equivale a:</span>
                            <span class="font-bold text-teal-900">
                                Bs {{ number_format($this->montoBsCalculado, 2) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-xs text-teal-700">
                            <span>En USD:</span>
                            <span>${{ number_format($this->montoUsdCalculado, 2) }}</span>
                        </div>
                        @if ($this->saldo_restante_usd > 0)
                            <div class="flex items-center justify-between text-xs text-teal-700 pt-2 border-t border-teal-200">
                                <span>Saldo restante después del pago:</span>
                                <span class="font-semibold">${{ number_format($this->saldo_restante_usd, 2) }}</span>
                            </div>
                        @endif
                    </div>

                    {{-- Banco (solo si aplica) --}}
                    @if ($this->requiereBanco)
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Banco *
                            </label>
                            <input type="text" wire:model="banco"
                                   placeholder="Ej: Banco de Venezuela, Banesco..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('banco') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- Referencia (solo si aplica) --}}
                    @if ($this->requiereReferencia)
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Referencia *
                            </label>
                            <input type="text" wire:model="referencia"
                                   placeholder="{{ $forma_pago === 'ZELLE' ? 'Email o confirmación' : ($forma_pago === 'BINANCE' ? 'ID de transacción' : 'Número de referencia') }}"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('referencia') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    @endif

                    {{-- Notas --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Notas (opcional)
                        </label>
                        <textarea wire:model="notas" rows="2"
                                  placeholder="Observaciones..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="button" wire:click="guardar"
                                wire:loading.attr="disabled"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span wire:loading.remove>Registrar Pago</span>
                            <span wire:loading>Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>