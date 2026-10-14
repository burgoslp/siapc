<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cambio;
use App\Models\Tasa;

new class extends Component
{
    public bool $abierto = false;
    public ?int $tasaId = null;
    public ?Tasa $tasa = null;

    public $moneda = 'dolar';
    public $monto = '';

    #[On('nuevo-cambio')]
    public function abrir(int $tasaId): void
    {
        $this->reset();
        $this->tasaId = $tasaId;
        $this->tasa = Tasa::find($tasaId);

        if (!$this->tasa) return;

        // Prellenar con el último monto en dólar
        $ultimo = $this->tasa->cambioActualDe('dolar');
        $this->monto = $ultimo?->monto ?? '';

        $this->abierto = true;
    }

    public function updatedMoneda(): void
    {
        // Al cambiar moneda, prellenar con el último monto de esa moneda
        if (!$this->tasa) return;

        $ultimo = $this->tasa->cambioActualDe($this->moneda);
        $this->monto = $ultimo?->monto ?? '';
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'moneda' => 'required|in:dolar,euro',
            'monto' => 'required|numeric|min:0.0001|max:99999999.9999',
        ]);

        // Advertir si el monto es idéntico al anterior (mismo monto en la misma moneda)
        $ultimo = $this->tasa->cambioActualDe($this->moneda);
        if ($ultimo && (float) $ultimo->monto === (float) $datos['monto']) {
            $this->addError('monto', 'El monto es idéntico al anterior. Si no cambió, no es necesario registrar un nuevo cambio.');
            return;
        }

        Cambio::create([
            'tasa_id' => $this->tasaId,
            'moneda' => $datos['moneda'],
            'monto' => $datos['monto'],
        ]);

        $this->dispatch('cambio-guardado');
        session()->flash('success', 'Nueva cotización registrada.');
        $this->abierto = false;
    }
};
?>

<div>
    @if ($abierto && $tasa)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Registrar Nuevo Cambio</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Tasa: <span class="font-semibold text-teal-700">{{ $tasa->etiqueta }}</span>
                        </p>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5 space-y-4">

                    {{-- Selector de moneda --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Moneda *</label>
                        <div class="grid grid-cols-2 gap-2">
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="moneda" value="dolar" class="peer sr-only">
                                <div class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-center font-medium text-gray-600
                                            peer-checked:bg-blue-50 peer-checked:border-blue-400 peer-checked:text-blue-700
                                            hover:bg-gray-50 transition">
                                    Dólar (USD)
                                </div>
                            </label>
                            <label class="cursor-pointer">
                                <input type="radio" wire:model.live="moneda" value="euro" class="peer sr-only">
                                <div class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-center font-medium text-gray-600
                                            peer-checked:bg-purple-50 peer-checked:border-purple-400 peer-checked:text-purple-700
                                            hover:bg-gray-50 transition">
                                    Euro (EUR)
                                </div>
                            </label>
                        </div>
                        @error('moneda') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    @php
                        $ultimo = $tasa->cambioActualDe($moneda);
                    @endphp

                    @if ($ultimo)
                        <div class="p-3 bg-gray-50 border border-gray-200 rounded-lg">
                            <p class="text-xs text-gray-500 uppercase tracking-wide font-semibold">
                                Cotización anterior en {{ $moneda === 'dolar' ? 'USD' : 'EUR' }}
                            </p>
                            <p class="text-lg font-bold text-gray-700 mt-1">
                                Bs {{ number_format($ultimo->monto, 4) }}
                            </p>
                            <p class="text-xs text-gray-400 mt-0.5">
                                Registrada el {{ $ultimo->created_at->format('d/m/Y \a \l\a\s H:i') }}
                            </p>
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Nuevo monto (Bs) *
                        </label>
                        <input type="number" step="0.0001" min="0" wire:model="monto"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm"
                               placeholder="Ej: 36.5000">
                        <p class="text-xs text-gray-500 mt-1">
                            ¿Cuántos bolívares vale 1 unidad de la moneda?
                        </p>
                        @error('monto') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                            Registrar Cambio
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>