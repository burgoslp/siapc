<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Alergeno;

new class extends Component
{
    public ?int $alergenoId = null;
    public bool $abierto = false;

    public $nombre = '';
    public $categoria = '';
    public $descripcion = '';

    #[On('nuevo-alergeno')]
    public function abrirNueva(): void
    {
        $this->reset();
        $this->abierto = true;
    }

    #[On('editar-alergeno')]
    public function abrirEdicion(int $id): void
    {
        $al = Alergeno::find($id);
        if (!$al) return;

        $this->alergenoId = $al->id;
        $this->nombre = $al->nombre;
        $this->categoria = $al->categoria ?? '';
        $this->descripcion = $al->descripcion ?? '';
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
            'nombre' => 'required|string|max:255|unique:alergenos,nombre,' . ($this->alergenoId ?? 'NULL'),
            'categoria' => 'nullable|string|max:255',
            'descripcion' => 'nullable|string',
        ]);

        if ($this->alergenoId) {
            Alergeno::find($this->alergenoId)?->update($datos);
            session()->flash('success', 'Alérgeno actualizado correctamente.');
        } else {
            Alergeno::create($datos);
            session()->flash('success', 'Alérgeno creado correctamente.');
        }

        $this->dispatch('alergeno-guardado');
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
                        {{ $alergenoId ? 'Editar Alérgeno' : 'Nuevo Alérgeno' }}
                    </h3>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5 space-y-4">

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre *</label>
                        <input type="text" wire:model="nombre"
                               placeholder="Ej: Penicilina, Maní, Polen..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Categoría</label>
                        <input type="text" wire:model="categoria"
                               list="categorias-alergenos"
                               placeholder="Medicamento, Alimento, Ambiental..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        <datalist id="categorias-alergenos">
                            <option value="Medicamento">
                            <option value="Alimento">
                            <option value="Ambiental">
                            <option value="Contacto">
                            <option value="Insecto">
                            <option value="Otro">
                        </datalist>
                        @error('categoria') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción</label>
                        <textarea wire:model="descripcion" rows="3"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                            {{ $alergenoId ? 'Guardar Cambios' : 'Crear Alérgeno' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>