<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Pago;

new class extends Component
{
    public bool $abierto = false;
    public ?Pago $pago = null;

    // Campos editables
    public $monto = '';
    public $forma_pago = 'EFECTIVO';
    public $banco = '';
    public $referencia = '';
    public $notas = '';

    #[On('editar-pago')]
    public function abrir(int $pagoId): void
    {
        $this->pago = Pago::with(['cita', 'paciente', 'caja'])->find($pagoId);

        if (!$this->pago) {
            return;
        }

        // Verificar caja abierta
        if (!$this->pago->caja || $this->pago->caja->estado !== 'ABIERTA') {
            session()->flash('error', 'No se puede editar un pago de una caja cerrada.');
            return;
        }

        // Verificar que no esté anulado
        if ($this->pago->estado === 'ANULADO') {
            session()->flash('error', 'No se puede editar un pago anulado.');
            return;
        }

        // Cargar datos actuales
        $this->monto = $this->pago->monto;
        $this->forma_pago = $this->pago->forma_pago;
        $this->banco = $this->pago->banco ?? '';
        $this->referencia = $this->pago->referencia ?? '';
        $this->notas = $this->pago->notas ?? '';

        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function getRequiereBancoProperty(): bool
    {
        return in_array($this->forma_pago, ['TRANSFERENCIA', 'PAGO_MOVIL']);
    }

    public function getRequiereReferenciaProperty(): bool
    {
        return in_array($this->forma_pago, ['TRANSFERENCIA', 'PAGO_MOVIL', 'ZELLE', 'BINANCE']);
    }

    public function guardar(): void
    {
        if (!$this->pago) return;

        // Reglas base
        $rules = [
            'monto' => 'required|numeric|min:0.01',
            'forma_pago' => 'required|in:EFECTIVO,TRANSFERENCIA,PAGO_MOVIL,TARJETA_DEBITO,TARJETA_CREDITO,ZELLE,BINANCE,OTRO',
            'notas' => 'nullable|string|max:500',
        ];

        if ($this->requiere_banco) {
            $rules['banco'] = 'required|string|max:100';
        } else {
            $rules['banco'] = 'nullable|string|max:100';
        }

        if ($this->requiere_referencia) {
            $rules['referencia'] = 'required|string|max:50';
        } else {
            $rules['referencia'] = 'nullable|string|max:50';
        }

        $datos = $this->validate($rules);

        // Calcular el nuevo monto en Bs
        $nuevoMontoBs = match ($this->pago->moneda) {
            'bs'    => (float) $datos['monto'],
            'dolar', 'euro' => round((float) $datos['monto'] * (float) $this->pago->tasa_momento, 2),
            default => 0,
        };

        $montoAnteriorBs = (float) $this->pago->monto_bs;

        // Validar que el nuevo monto no haga que la cita pague de más
        $cita = $this->pago->cita;
        if ($cita) {
            $totalPagadoSinEstePago = (float) $cita->total_pagado - $montoAnteriorBs;
            $nuevoTotalPagado = $totalPagadoSinEstePago + $nuevoMontoBs;

            // Convertir el total de la cita a Bs usando la tasa de la cita
            $totalCitaBs = (float) $cita->total * (float) $cita->monto_tasa_cambio;
            $tolerancia = 1.00;

            if ($nuevoTotalPagado > $totalCitaBs + $tolerancia) {
                $this->addError('monto', 'El nuevo monto hace que la cita exceda su total. Verifica el monto.');
                return;
            }
        }

        \DB::transaction(function () use ($datos, $montoAnteriorBs, $nuevoMontoBs) {
            // 1. Actualizar el pago
            $this->pago->update([
                'monto' => $datos['monto'],
                'forma_pago' => $datos['forma_pago'],
                'banco' => $datos['banco'] ?: null,
                'referencia' => $datos['referencia'] ?: null,
                'notas' => $datos['notas'] ?: null,
            ]);

            // 2. Ajustar el acumulador de la cita
            $cita = $this->pago->cita;
            if ($cita) {
                $cita->total_pagado = max(0, round(
                    (float) $cita->total_pagado - $montoAnteriorBs + $nuevoMontoBs,
                    2
                ));
                $cita->save();

                // 3. Recalcular estado de pago
                $cita->refresh();
                $cita->load('pagos');
                $cita->recalcularEstadoPago();
            }
        });

        session()->flash('success', 'Pago actualizado correctamente.');
        $this->abierto = false;

        $this->dispatch('pago-actualizado');
        $this->redirect(route('facturacion.pagos'), navigate: false);
    }
};
?>

<div>
    @if ($abierto && $pago)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[95vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800">Editar Pago</h3>
                            <p class="text-xs text-gray-500 mt-0.5">
                                Pago #{{ $pago->id }} · {{ $pago->created_at->format('d/m/Y H:i A') }}
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

                    {{-- Info no editable --}}
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg">
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            Datos fijos del pago
                        </p>
                        <div class="grid grid-cols-2 gap-3 text-sm">
                            <div>
                                <span class="text-gray-500 text-xs">Paciente:</span>
                                <div class="font-medium text-gray-800">
                                    {{ $pago->paciente?->nombre }} {{ $pago->paciente?->apellido }}
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-xs">Cita:</span>
                                <div class="font-medium text-teal-700">
                                    #{{ $pago->cita?->id }} · {{ $pago->cita?->fecha->format('d/m/Y') }}
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-xs">Moneda original:</span>
                                <div class="font-medium text-gray-800">
                                    {{ $pago->moneda_etiqueta }}
                                </div>
                            </div>
                            <div>
                                <span class="text-gray-500 text-xs">Tasa aplicada:</span>
                                <div class="font-medium text-gray-800">
                                    {{ number_format($pago->tasa_momento, 2) }}
                                    @if ($pago->tasa)
                                        ({{ $pago->tasa->etiqueta }})
                                    @endif
                                </div>
                            </div>
                        </div>
                        <p class="text-[10px] text-gray-500 mt-2 italic">
                            * La moneda y la tasa no se pueden modificar porque afectarían el histórico.
                        </p>
                    </div>

                    {{-- Campos editables --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Monto --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Monto ({{ strtoupper($pago->moneda) }}) *
                            </label>
                            <input type="number" step="0.01" min="0.01" wire:model.live="monto"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold">
                            @error('monto') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- Forma de pago --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Forma de pago *
                            </label>
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

                        {{-- Banco --}}
                        @if ($this->requiere_banco)
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                    Banco *
                                </label>
                                <input type="text" wire:model="banco"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('banco') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        {{-- Referencia --}}
                        @if ($this->requiere_referencia)
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                    Referencia *
                                </label>
                                <input type="text" wire:model="referencia"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('referencia') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        {{-- Notas --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Notas
                            </label>
                            <textarea wire:model="notas" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                        </div>
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="button" wire:click="guardar"
                                wire:loading.attr="disabled"
                                class="px-5 py-2 bg-amber-600 hover:bg-amber-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            <span wire:loading.remove>Guardar Cambios</span>
                            <span wire:loading>Guardando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>