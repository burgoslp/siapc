<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Caja;
use App\Models\Pago;

new class extends Component
{
    public bool $abierto = false;
    public ?Pago $pago = null;

    public $motivo = '';

    #[On('anular-pago')]
    public function abrir(int $pagoId): void
    {
        $this->pago = Pago::with(['cita', 'paciente', 'caja'])->find($pagoId);

        if (!$this->pago) {
            return;
        }

        // Verificar caja abierta
        if (!$this->pago->caja || $this->pago->caja->estado !== 'ABIERTA') {
            session()->flash('error', 'No se puede anular un pago de una caja cerrada.');
            return;
        }

        // Verificar que no esté ya anulado
        if ($this->pago->estado === 'ANULADO') {
            session()->flash('error', 'Este pago ya está anulado.');
            return;
        }

        $this->motivo = '';
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function confirmar(): void
    {
        if (!$this->pago) return;

        $this->validate([
            'motivo' => 'required|string|min:5|max:500',
        ], [
            'motivo.required' => 'Debes indicar el motivo de la anulación.',
            'motivo.min' => 'El motivo debe tener al menos 5 caracteres.',
        ]);

        \DB::transaction(function () {
            // 1. Marcar el pago como ANULADO
            $this->pago->update([
                'estado' => 'ANULADO',
                'motivo_anulacion' => $this->motivo,
                'anulado_por' => auth()->id(),
                'anulado_en' => now(),
            ]);

            // 2. Restar del acumulador de la cita
            $cita = $this->pago->cita;
            if ($cita) {
                $cita->total_pagado = max(0, round(
                    (float) $cita->total_pagado - $this->pago->monto_bs,
                    2
                ));
                $cita->save();

                // 3. Recalcular estado de pago
                $cita->refresh();
                $cita->load('pagos');
                $cita->recalcularEstadoPago();
            }
        });

        session()->flash('success', 'Pago anulado correctamente.');
        $this->abierto = false;

        $this->dispatch('pago-anulado');

        // Redirigir para recargar los totales
        $this->redirect(route('facturacion.pagos'), navigate: false);
    }
};
?>

<div>
    @if ($abierto && $pago)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Anular Pago</h3>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-4">

                    {{-- Aviso --}}
                    <div class="p-3 bg-amber-50 border border-amber-200 rounded-lg text-xs text-amber-800">
                        <div class="flex items-start gap-2">
                            <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                            <div>
                                <p class="font-semibold">Esta acción no se puede deshacer.</p>
                                <p class="mt-0.5">El pago quedará registrado como anulado y se descontará del cuadre de caja.</p>
                            </div>
                        </div>
                    </div>

                    {{-- Datos del pago --}}
                    <div class="p-4 bg-slate-50 border border-slate-200 rounded-lg space-y-2 text-sm">
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Paciente:</span>
                            <span class="font-semibold text-gray-800">
                                {{ $pago->paciente?->nombre }} {{ $pago->paciente?->apellido }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Forma de pago:</span>
                            <span class="text-gray-800">{{ $pago->forma_pago_etiqueta }}</span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">Monto original:</span>
                            <span class="font-semibold text-gray-800">
                                {{ $pago->monto_formateado }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between">
                            <span class="text-gray-600">En Bs:</span>
                            <span class="text-gray-800">
                                Bs {{ number_format($pago->monto_bs, 2) }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between pt-2 border-t border-slate-200">
                            <span class="text-gray-600">Fecha del pago:</span>
                            <span class="text-gray-800">
                                {{ $pago->created_at->format('d/m/Y H:i A') }}
                            </span>
                        </div>
                    </div>

                    {{-- Motivo --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Motivo de la anulación *
                        </label>
                        <textarea wire:model="motivo" rows="3"
                                  placeholder="Ej: Cobro duplicado, error de monto, paciente canceló..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                        @error('motivo') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="button" wire:click="confirmar"
                                wire:loading.attr="disabled"
                                class="px-5 py-2 bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white rounded-lg text-sm font-medium inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/>
                            </svg>
                            <span wire:loading.remove>Anular Pago</span>
                            <span wire:loading>Procesando...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>