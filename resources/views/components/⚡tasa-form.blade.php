<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Tasa;

new class extends Component
{
    public ?int $tasaId = null;
    public bool $abierto = false;

    public $nombre = '';
    public $etiqueta = '';
    public $descripcion = '';
    public $predeterminado = false;
    public $activa = true;

    #[On('nueva-tasa')]
    public function abrirNueva(): void
    {
        $this->reset();
        $this->activa = true;
        $this->predeterminado = false;
        $this->tasaId = null;
        $this->abierto = true;
    }

    #[On('editar-tasa')]
    public function abrirEdicion(int $id): void
    {
        $tasa = Tasa::find($id);
        if (!$tasa) return;

        $this->tasaId = $tasa->id;
        $this->nombre = $tasa->nombre;
        $this->etiqueta = $tasa->etiqueta;
        $this->descripcion = $tasa->descripcion ?? '';
        $this->predeterminado = (bool) $tasa->predeterminado;
        $this->activa = (bool) $tasa->activa;
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
            'nombre' => 'required|string|max:50|alpha_dash|unique:tasas,nombre,' . ($this->tasaId ?? 'NULL'),
            'etiqueta' => 'required|string|max:100',
            'descripcion' => 'nullable|string',
            'predeterminado' => 'boolean',
            'activa' => 'boolean',
        ]);

        // Si se marca como predeterminada, quitar el flag a las demás
        if ($datos['predeterminado']) {
            Tasa::where('id', '!=', $this->tasaId)->update(['predeterminado' => false]);
        }

        // No se puede desactivar una tasa predeterminada sin asignar otra
        if ($this->tasaId && !$datos['activa'] && $datos['predeterminado']) {
            $this->addError('activa', 'No puedes desactivar la tasa predeterminada.');
            return;
        }

        // Si se va a desactivar y es la predeterminada, verificar que exista otra
        if ($this->tasaId && !$datos['activa']) {
            $tasaActual = Tasa::find($this->tasaId);
            if ($tasaActual && $tasaActual->predeterminado) {
                $otraPredeterminada = Tasa::where('id', '!=', $this->tasaId)
                    ->where('predeterminado', true)
                    ->exists();
                if (!$otraPredeterminada) {
                    $this->addError('activa', 'Antes de desactivar esta tasa, marca otra como predeterminada.');
                    return;
                }
            }
        }

        if ($this->tasaId) {
            Tasa::find($this->tasaId)?->update($datos);
            session()->flash('success', 'Tasa actualizada correctamente.');
        } else {
            Tasa::create($datos);
            session()->flash('success', 'Tasa creada correctamente.');
        }

        $this->dispatch('tasa-guardada');
        $this->abierto = false;
    }
};
?>

<div>
    @if ($abierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-md">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ $tasaId ? 'Editar Tasa' : 'Nueva Tasa' }}
                    </h3>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5 space-y-4">

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Nombre interno *
                        </label>
                        <input type="text" wire:model="nombre"
                               placeholder="bcv, paralelo, binance..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <p class="text-xs text-gray-500 mt-1">
                            Identificador único. Solo minúsculas, números y guiones.
                        </p>
                        @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                            Etiqueta visible *
                        </label>
                        <input type="text" wire:model="etiqueta"
                               placeholder="BCV, Paralelo, Binance..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @error('etiqueta') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción</label>
                        <textarea wire:model="descripcion" rows="2"
                                  placeholder="Tasa oficial del Banco Central..."
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                        @error('descripcion') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="space-y-2 pt-2">
                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" wire:model.live="predeterminado"
                                   class="w-4 h-4 mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">
                                Marcar como predeterminada
                                <span class="block text-xs text-gray-500">
                                    Se usará por defecto al calcular los montos en Bs.
                                    Se desmarcará la actual predeterminada.
                                </span>
                            </span>
                        </label>

                        <label class="flex items-start cursor-pointer">
                            <input type="checkbox" wire:model="activa"
                                   class="w-4 h-4 mt-0.5 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                            <span class="ml-2 text-sm text-gray-700">
                                Tasa activa
                                <span class="block text-xs text-gray-500">
                                    Solo las tasas activas aparecen en los formularios.
                                </span>
                            </span>
                        </label>
                        @error('activa') <span class="text-xs text-red-500 block ml-6">{{ $message }}</span> @enderror
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                            {{ $tasaId ? 'Guardar Cambios' : 'Crear Tasa' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>