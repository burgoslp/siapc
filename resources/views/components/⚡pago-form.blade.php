<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Caja;
use App\Models\Cita;
use App\Models\Pago;
use App\Models\Tasa;

new class extends Component
{
    public bool $abierto = false;
    public ?Cita $cita = null;
    public ?Caja $caja = null;

    // Array de líneas de pago
    public array $lineas = [];

    public $cuentasDisponibles = [];

    #[On('cobrar-cita')]
    public function abrir(int $citaId): void
    {
        $this->caja = Caja::abierta();
        if (!$this->caja || $this->caja->estaVencida()) {
            session()->flash('error', 'Debes abrir una caja válida para registrar pagos.');
            return;
        }

        $this->cita = Cita::with(['paciente', 'medico', 'pagos'])->find($citaId);
        if (!$this->cita || $this->cita->estado_pago === 'PAGADA') {
            session()->flash('error', 'No se puede cobrar esta cita.');
            return;
        }

        $tasaPredeterminada = Tasa::predeterminada();
        $cambioActual = $tasaPredeterminada?->cambioActualDe('dolar');

        // Inicializar con 1 línea autocompletada
        $this->lineas = [
            [
                'moneda' => 'dolar',
                'tasa_id' => $tasaPredeterminada?->id,
                'cambio_id' => $cambioActual?->id,
                'tasa_momento' => $cambioActual?->monto,
                'monto' => $this->cita->saldo_pendiente_usd,
                'forma_pago' => 'EFECTIVO',
                'banco_id' => '',     
                'cuenta_id' => '',    
                'banco_cliente' => '', 
                'referencia' => '',
                'notas' => '',
            ],
        ];

        $this->abierto = true;
    }

    public function agregarLinea(): void
    {
        $saldoRestante = $this->calcularSaldoRestante();
        if ($saldoRestante <= 0.01) {
            $this->addError('lineas', 'El pago ya cubre el total de la cita.');
            return;
        }

        $tasaPredeterminada = Tasa::predeterminada();
        $cambioActual = $tasaPredeterminada?->cambioActualDe('dolar');

        $this->lineas[] = [
            'moneda' => 'dolar',
            'tasa_id' => $tasaPredeterminada?->id,
            'cambio_id' => $cambioActual?->id,
            'tasa_momento' => $cambioActual?->monto,
            'monto' => $saldoRestante,
            'forma_pago' => 'EFECTIVO',
            'banco' => '',
            'referencia' => '',
            'notas' => '',
        ];
    }

    public function quitarLinea(int $index): void
    {
        if (count($this->lineas) <= 1) {
            $this->addError('lineas', 'Debe haber al menos una línea de pago.');
            return;
        }
        unset($this->lineas[$index]);
        $this->lineas = array_values($this->lineas);
    }

    public function updatedLineas($value, $key): void
    {
        $parts = explode('.', $key);
        if (count($parts) !== 2) return;

        $index = (int) $parts[0];
        $campo = $parts[1];

        if (in_array($campo, ['moneda', 'tasa_id'])) {
            $this->cargarCambioLinea($index);
        }

        // Si cambia la moneda o el banco, limpiamos la cuenta
        if (in_array($campo, ['moneda', 'banco'])) {
            $this->lineas[$index]['cuenta_id'] = '';
        }
    }

    private function cargarCambioLinea(int $index): void
    {
        $linea = $this->lineas[$index] ?? null;
        if (!$linea || !$linea['tasa_id']) return;

        $tasa = Tasa::find($linea['tasa_id']);
        if (!$tasa) return;

        // Si la moneda es Bs, usar la tasa dólar para el snapshot
        $monedaCambio = $linea['moneda'] === 'bs' ? 'dolar' : $linea['moneda'];

        $cambio = $tasa->cambioActualDe($monedaCambio);

        if ($cambio) {
            $this->lineas[$index]['cambio_id'] = $cambio->id;
            $this->lineas[$index]['tasa_momento'] = (float) $cambio->monto;
        } else {
            $this->lineas[$index]['cambio_id'] = null;
            $this->lineas[$index]['tasa_momento'] = null;
        }
    }

    public function calcularSaldoRestante(): float
    {
        if (!$this->cita) return 0;

        $totalPagadoUsd = 0;

        foreach ($this->lineas as $linea) {
            $totalPagadoUsd += $this->calcularLineaUsd($linea);
        }
        return max(0, round($this->cita->saldo_pendiente_usd - $totalPagadoUsd, 2));
    }

    /**
     * Convierte una línea de pago a USD usando la tasa elegida en esa línea.
     */
    public function calcularLineaUsd(array $linea): float
    {
        $monto = (float) ($linea['monto'] ?? 0);
        $tasa = (float) ($linea['tasa_momento'] ?? 0);

        if ($tasa <= 0) return 0;

        return match ($linea['moneda'] ?? 'dolar') {
            'dolar' => round($monto, 2),
            'bs'    => round($monto / $tasa, 2),
            'euro'  => round(($monto * $tasa) / $this->tasaDolarDeLinea($linea), 2),
            default => 0,
        };
    }

    /**
     * Tasa dólar vigente para una línea (para conversiones de euro).
     */
    private function tasaDolarDeLinea(array $linea): float
    {
        $tasaId = $linea['tasa_id'] ?? null;
        if (!$tasaId) return 0;

        $tasa = \App\Models\Tasa::find($tasaId);
        return (float) ($tasa?->cambioActualDe('dolar')?->monto ?? 0);
    }

    public function calcularLineaBs(array $linea): float
    {
        $monto = (float) ($linea['monto'] ?? 0);
        $tasa = (float) ($linea['tasa_momento'] ?? 0);

        return match ($linea['moneda'] ?? 'dolar') {
            'bs' => $monto,
            'dolar', 'euro' => round($monto * $tasa, 2),
            default => 0,
        };
    }

    public function calcularTotalBs(): float
    {
        $total = 0;
        foreach ($this->lineas as $linea) {
            $total += $this->calcularLineaBs($linea);
        }
        return round($total, 2);
    }

    public function requiereBanco(int $index): bool
    {
        return in_array($this->lineas[$index]['forma_pago'] ?? '', ['TRANSFERENCIA', 'PAGO_MOVIL']);
    }

    public function requiereReferencia(int $index): bool
    {
        return in_array($this->lineas[$index]['forma_pago'] ?? '', ['TRANSFERENCIA', 'PAGO_MOVIL', 'ZELLE', 'BINANCE']);
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        if (!$this->cita || !$this->caja) return;
        // Validar
        $rules = [
            'lineas' => 'required|array|min:1',
            'lineas.*.moneda' => 'required|in:dolar,euro,bs',
            'lineas.*.tasa_id' => 'required|exists:tasas,id',
            'lineas.*.monto' => 'required|numeric|min:0.01',
            'lineas.*.forma_pago' => 'required|in:EFECTIVO,TRANSFERENCIA,PAGO_MOVIL,TARJETA_DEBITO,TARJETA_CREDITO,ZELLE,BINANCE,OTRO',
            'lineas.*.banco_cliente' => 'nullable|string',
        ];

        // Validación condicional por cada línea
        foreach ($this->lineas as $i => $linea) {
            if ($linea['forma_pago'] !== 'EFECTIVO') {
                $rules["lineas.{$i}.cuenta_id"] = 'required|exists:cuentas,id';
            }

            // Si es transferencia o pago móvil, exigimos banco
            if (in_array($linea['forma_pago'], ['TRANSFERENCIA', 'PAGO_MOVIL'])) {
                $rules["lineas.{$i}.banco"] = 'required|string';
            }

            // Si es transferencia, pago móvil, zelle o binance, exigimos referencia
            if (in_array($linea['forma_pago'], ['TRANSFERENCIA', 'PAGO_MOVIL', 'ZELLE', 'BINANCE'])) {
                $rules["lineas.{$i}.referencia"] = 'required|string';
            }
        }

        $this->validate($rules);

        // Validar monto total no exceda saldo
        $saldoRestante = $this->calcularSaldoRestante();

        if ($saldoRestante < -0.01) {
            $this->addError('lineas', 'El total de los pagos excede el saldo pendiente de la cita.');
            return;
        }

        // Validar banco/referencia condicionales
        foreach ($this->lineas as $i => $linea) {
            if ($this->requiereBanco($i) && empty($linea['banco'])) {
                $this->addError("lineas.{$i}.banco", 'El banco es obligatorio para esta forma de pago.');
                return;
            }
            if ($this->requiereReferencia($i) && empty($linea['referencia'])) {
                $this->addError("lineas.{$i}.referencia", 'La referencia es obligatoria para esta forma de pago.');
                return;
            }
            if (!$linea['cambio_id'] || !$linea['tasa_momento']) {
                $this->addError("lineas.{$i}.tasa_id", 'La tasa no tiene cotización válida.');
                return;
            }
        }


        \DB::transaction(function () {
            $totalBs = 0;

            foreach ($this->lineas as $linea) {
                
                $cuentaIdFinal = $linea['cuenta_id'] ?? null;

                if ($linea['forma_pago'] === 'EFECTIVO') {
                    $cuentaCajaChica = \App\Models\Cuenta::where('tipo', 'EFECTIVO')
                        ->where('moneda', $linea['moneda'])
                        ->where('activa', true)
                        ->first();
                    $cuentaIdFinal = $cuentaCajaChica ? $cuentaCajaChica->id : null;
                }

                $pago = Pago::create([
                    'caja_id' => $this->caja->id,
                    'cuenta_id' => $cuentaIdFinal,
                    'cita_id' => $this->cita->id,
                    'paciente_id' => $this->cita->paciente_id,
                    'monto' => $linea['monto'],
                    'tasa_id' => $linea['tasa_id'],
                    'cambio_id' => $linea['cambio_id'],
                    'tasa_momento' => $linea['tasa_momento'],
                    'moneda' => $linea['moneda'],
                    'forma_pago' => $linea['forma_pago'],
                    'banco' => $linea['banco'] ?? null,
                    'banco_cliente' => $linea['banco_cliente'] ?? null,
                    'referencia' => $linea['referencia'] ?: null,
                    'notas' => $linea['notas'] ?: null,
                    'registrado_por' => auth()->id(),
                    'estado' => 'ACTIVO',
                ]);

                

                $totalBs += (float) $pago->monto_bs;
            }

            

            // Actualizar cita
            $this->cita->total_pagado = round((float) $this->cita->total_pagado + $totalBs, 2);
            $this->cita->save();

            $this->cita->refresh();
            $this->cita->load('pagos');
            $this->cita->recalcularEstadoPago();
        });

        session()->flash('success', 'Pagos registrados correctamente.');
        $this->abierto = false;

        $this->dispatch('pago-registrado');
        $this->redirect(route('facturacion.index'), navigate: false);
    }
    /**
     * Obtiene el monto de la tasa dólar predeterminada actual.
     */
    private function tasaDolarActual(): float
    {
        return (float) (\App\Models\Tasa::montoPredeterminada('dolar') ?? 0);
    }

    public function getCuentasDeMoneda($moneda)
    {
        // Traemos las cuentas activas filtradas por la moneda de la línea
        return \App\Models\Cuenta::with('banco')
            ->where('activa', true)
            ->where('moneda', $moneda)
            ->get();
    }

    public function getBancosConCuentas($moneda)
    {
        // Retorna solo los bancos que tienen al menos una cuenta activa en esa moneda
        return \App\Models\Banco::whereHas('cuentas', function($q) use ($moneda) {
            $q->where('moneda', $moneda)->where('activa', true);
        })->orderBy('nombre')->get();
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
        <div class="fixed inset-0 bg-black/50 z-40"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-4xl max-h-[95vh] overflow-y-auto">

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
                            <p class="text-xs text-gray-500">
                                Cita #{{ $cita->id }} · {{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                <div class="px-6 py-5 space-y-4">

                    {{-- Info de la cita --}}
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg grid grid-cols-3 gap-4 text-sm">
                        <div>
                            <span class="text-gray-600 text-xs">Total cita:</span>
                            <p class="font-bold text-gray-800">${{ number_format($cita->total, 2) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 text-xs">Pagado:</span>
                            <p class="font-semibold text-gray-700">${{ number_format($cita->total_pagado_usd, 2) }}</p>
                        </div>
                        <div>
                            <span class="text-gray-600 text-xs">Saldo pendiente:</span>
                            <p class="font-bold text-amber-600">${{ number_format($cita->saldo_pendiente_usd, 2) }}</p>
                        </div>
                    </div>

                    {{-- Líneas de pago --}}
                    <div class="space-y-3">
                        @foreach ($lineas as $i => $linea)
                            <div class="border border-gray-200 rounded-lg p-4 bg-gray-50/50" wire:key="linea-{{ $i }}">
                                <div class="flex items-center justify-between mb-3">
                                    <span class="text-xs font-semibold text-gray-500 uppercase">
                                        Pago {{ $i + 1 }}
                                    </span>
                                    @if (count($lineas) > 1)
                                        <button type="button" wire:click="quitarLinea({{ $i }})" class="text-red-500 hover:text-red-700 text-xs font-medium">
                                            Quitar
                                        </button>
                                    @endif
                                </div>

                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 mb-3">
                                    {{-- Moneda --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Moneda</label>
                                        <select wire:model.live="lineas.{{ $i }}.moneda" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            <option value="dolar">USD</option>
                                            <option value="euro">EUR</option>
                                            <option value="bs">Bs</option>
                                        </select>
                                    </div>

                                    {{-- Tasa --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Tasa</label>
                                        <select wire:model.live="lineas.{{ $i }}.tasa_id" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            @foreach ($tasas as $t)
                                                <option value="{{ $t->id }}">{{ $t->etiqueta }}</option>
                                            @endforeach
                                        </select>
                                        @if (!empty($linea['tasa_momento']))
                                            <p class="text-[10px] text-teal-700 font-semibold mt-0.5">Bs {{ number_format($linea['tasa_momento'], 2) }}</p>
                                        @endif
                                    </div>

                                    {{-- Monto --}}
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Monto ({{ strtoupper($linea['moneda']) }})</label>
                                        <input type="number" step="0.01" min="0.01" wire:model.live="lineas.{{ $i }}.monto" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs font-semibold">
                                    </div>

                                    {{-- Forma de pago --}}
                                    <div class="col-span-2 sm:col-span-3">
                                        <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Forma de Pago</label>
                                        <select wire:model.live="lineas.{{ $i }}.forma_pago" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                            <option value="EFECTIVO">Efectivo</option>
                                            <option value="TRANSFERENCIA">Transferencia</option>
                                            <option value="PAGO_MOVIL">Pago Móvil</option>
                                            <option value="TARJETA_DEBITO">Tarjeta de Débito</option>
                                            <option value="TARJETA_CREDITO">Tarjeta de Crédito</option>
                                            <option value="ZELLE">Zelle</option>
                                            <option value="BINANCE">Binance</option>
                                            <option value="OTRO">Otro</option>
                                        </select>
                                    </div>
                                </div>

                                    @if ($linea['forma_pago'] === 'EFECTIVO')
                                            {{-- Si es efectivo, no mostramos nada de banco. El sistema asigna la caja chica automáticamente. --}}
                                            <p class="text-[10px] text-teal-600 italic mt-2">
                                                * El dinero ingresará automáticamente a la Caja Chica ({{ strtoupper($linea['moneda']) }}).
                                            </p>
                                        @elseif (in_array($linea['forma_pago'], ['ZELLE', 'BINANCE']))
                                            {{-- Formas de pago digitales --}}
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-gray-200">
                                                {{-- Cuenta Plataforma --}}
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Plataforma Digital</label>
                                                    <select wire:model.live="lineas.{{ $i }}.cuenta_id" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                        <option value="">Seleccione plataforma...</option>
                                                        @foreach (\App\Models\Cuenta::where('tipo', 'PLATAFORMA DIGITAL')->where('moneda', $linea['moneda'])->where('activa', true)->get() as $c)
                                                            <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                {{-- Referencia --}}
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Referencia de transacción *</label>
                                                    <input type="text" wire:model="lineas.{{ $i }}.referencia" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="ID de operación">
                                                </div>
                                            </div>
                                        @else
                                            {{-- Formas bancarias tradicionales --}}
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 pt-3 border-t border-gray-200">
                                                {{-- Banco Empresa --}}
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Banco Empresa</label>
                                                    <select wire:model.live="lineas.{{ $i }}.banco" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                        <option value="">Seleccione banco...</option>
                                                        @foreach ($this->getBancosConCuentas($linea['moneda']) as $b)
                                                            <option value="{{ $b->nombre }}">{{ $b->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                {{-- Cuenta Empresa --}}
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Cuenta Empresa</label>
                                                    <select wire:model="lineas.{{ $i }}.cuenta_id" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                        <option value="">Seleccione cuenta...</option>
                                                        @if(!empty($linea['banco']))
                                                            {{-- Consulta directa usando whereHas para relacionar con el nombre del banco --}}
                                                            @foreach (\App\Models\Cuenta::whereHas('banco', fn($q) => $q->where('nombre', $linea['banco']))->where('moneda', $linea['moneda'])->where('activa', true)->get() as $c)
                                                                <option value="{{ $c->id }}">{{ $c->nombre }} ({{ $c->numero_cuenta ?? 'S/N' }})</option>
                                                            @endforeach
                                                        @endif
                                                    </select>
                                                </div>

                                                {{-- Banco Cliente --}}
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Banco Cliente</label>
                                                    <select wire:model="lineas.{{ $i }}.banco_cliente" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs">
                                                        <option value="">Seleccione banco del cliente...</option>
                                                        @foreach (\App\Models\Banco::where('activo', true)->orderBy('nombre')->get() as $b)
                                                            <option value="{{ $b->nombre }}">{{ $b->nombre }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                {{-- Referencia --}}
                                                <div>
                                                    <label class="block text-[10px] font-semibold text-gray-600 uppercase mb-1">Referencia</label>
                                                    <input type="text" wire:model="lineas.{{ $i }}.referencia" class="w-full px-2 py-1.5 border border-gray-300 rounded text-xs" placeholder="Nro de comprobante">
                                                </div>
                                            </div>
                                        @endif
                                
                                {{-- Equivalencia --}}
                                <div class="mt-3 pt-2 border-t border-gray-200 text-xs">
                                    <span class="text-gray-500">Equivale a:</span>
                                    <span class="font-bold text-teal-700 ml-1">
                                        Bs {{ number_format($this->calcularLineaBs($linea), 2) }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    {{-- Botón agregar línea --}}
                    <button type="button" wire:click="agregarLinea"
                            class="w-full py-2 border-2 border-dashed border-teal-300 text-teal-600 rounded-lg text-sm font-medium hover:bg-teal-50 transition">
                        + Agregar otro método de pago
                    </button>

                    @error('lineas')
                        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-800">
                            {{ $message }}
                        </div>
                    @enderror

                    {{-- Resumen --}}
                    <div class="p-4 bg-teal-50 border border-teal-200 rounded-lg space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-teal-800">Total a pagar (calculado):</span>
                            <span class="font-bold text-teal-900 text-lg">
                                Bs {{ number_format($this->calcularTotalBs(), 2) }}
                            </span>
                        </div>

                        @php
                            $saldoRestanteUsd = $this->calcularSaldoRestante();

                            // Última línea para saber en qué moneda mostrar el saldo
                            $ultimaLinea = end($this->lineas);
                            $monedaUltima = $ultimaLinea['moneda'] ?? 'dolar';
                            $tasaUltima = (float) ($ultimaLinea['tasa_momento'] ?? 0);

                            // Calcular el saldo restante en la moneda de la última línea
                            $saldoRestanteMoneda = match ($monedaUltima) {
                                'dolar' => $saldoRestanteUsd,
                                'bs'    => $tasaUltima > 0 ? $saldoRestanteUsd * $tasaUltima : 0,
                                'euro'  => $tasaUltima > 0 ? ($saldoRestanteUsd * $this->tasaDolarActual()) / $tasaUltima : 0,
                                default => $saldoRestanteUsd,
                            };
                        @endphp

                        <div class="flex items-center justify-between text-xs text-teal-700">
                            <span>Saldo restante (USD):</span>
                            <span class="font-semibold">${{ number_format($saldoRestanteUsd, 2) }}</span>
                        </div>

                        @if ($saldoRestanteUsd > 0 && $monedaUltima !== 'dolar' && $tasaUltima > 0)
                            <div class="flex items-center justify-between text-xs text-teal-700">
                                <span>
                                    Saldo restante ({{ strtoupper($monedaUltima) }}):
                                </span>
                                <span class="font-semibold">
                                    {{ $monedaUltima === 'euro' ? '€' : 'Bs ' }}
                                    {{ number_format($saldoRestanteMoneda, 2) }}
                                </span>
                            </div>
                        @endif
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
                            <span wire:loading.remove>Registrar {{ count($lineas) }} Pago(s)</span>
                            <span wire:loading>Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>