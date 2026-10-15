<?php
use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Caja;

new class extends Component
{
    public bool $abierto = false;

    public $monto_inicial = 0;
    public $moneda_inicial = 'bs';

    #[On('abrir-caja')]
    public function abrir(): void
    {
        // No permitir abrir si ya hay una caja abierta
        if (Caja::hayAbierta()) {
            session()->flash('error', 'Ya hay una caja abierta. Debes cerrarla antes de abrir una nueva.');
            return;
        }

        $this->reset();
        $this->monto_inicial = 0;
        $this->moneda_inicial = 'bs';
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'monto_inicial' => 'required|numeric|min:0|max:999999999.99',
            'moneda_inicial' => 'required|in:dolar,euro,bs',
        ]);

        // Verificar de nuevo por si alguien abrió otra caja mientras
        if (Caja::hayAbierta()) {
            $this->addError('monto_inicial', 'Ya hay una caja abierta.');
            return;
        }

        $caja = Caja::create([
            'codigo' => Caja::generarCodigo(),
            'abierta_por' => auth()->id(),
            'abierta_en' => now(),
            'monto_inicial' => $datos['monto_inicial'],
            'moneda_inicial' => $datos['moneda_inicial'],
            'estado' => 'ABIERTA',
        ]);

        $this->dispatch('caja-abierta');
        session()->flash('success', "Caja {$caja->codigo} abierta correctamente.");
        $this->abierto = false;

        $this->redirect(route('facturacion.index'), navigate: false);
    }
};
?>

<div>
    @if ($abierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-full bg-teal-100 text-teal-600 flex items-center justify-center">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Abrir Caja</h3>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5 space-y-4">

                    <div class="p-3 bg-slate-50 border border-slate-200 rounded-lg text-xs text-slate-600">
                        Abre la caja al inicio del día. Puedes dejar el monto inicial en 0 si no tienes efectivo en caja.
                    </div>

                    {{-- Monto inicial --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Monto Inicial
                        </label>
                        <input type="number" step="0.01" min="0" wire:model="monto_inicial"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <p class="text-xs text-gray-500 mt-1">
                            Efectivo con el que abre la caja (opcional, puede ser 0).
                        </p>
                        @error('monto_inicial') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Moneda --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Moneda del Monto Inicial
                        </label>
                        <div class="grid grid-cols-3 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="moneda_inicial" value="bs" class="peer sr-only">
                                <div class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-center font-medium text-gray-600
                                            peer-checked:bg-teal-50 peer-checked:border-teal-400 peer-checked:text-teal-700
                                            hover:bg-gray-50 transition">
                                    Bs
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="moneda_inicial" value="dolar" class="peer sr-only">
                                <div class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-center font-medium text-gray-600
                                            peer-checked:bg-blue-50 peer-checked:border-blue-400 peer-checked:text-blue-700
                                            hover:bg-gray-50 transition">
                                    USD
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model="moneda_inicial" value="euro" class="peer sr-only">
                                <div class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-center font-medium text-gray-600
                                            peer-checked:bg-purple-50 peer-checked:border-purple-400 peer-checked:text-purple-700
                                            hover:bg-gray-50 transition">
                                    EUR
                                </div>
                            </label>
                        </div>
                        @error('moneda_inicial') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Abrir Caja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>