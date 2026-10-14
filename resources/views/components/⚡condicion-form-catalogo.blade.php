<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Condicion;

new class extends Component
{
    public ?int $condicionId = null;
    public bool $abierto = false;

    public $nombre = '';
    public $codigo_cie10 = '';
    public $categoria = 'METABOLICA';
    public $descripcion = '';
    public $requiere_seguimiento = false;
    public $frecuencia_seguimiento_dias = '';
    public $activo = true;

    #[On('nueva-condicion')]
    public function abrirNueva(): void
    {
        $this->reset();
        $this->categoria = 'METABOLICA';
        $this->activo = true;
        $this->abierto = true;
    }

    #[On('editar-condicion')]
    public function abrirEdicion(int $id): void
    {
        $c = Condicion::find($id);
        if (!$c) return;

        $this->condicionId = $c->id;
        $this->nombre = $c->nombre;
        $this->codigo_cie10 = $c->codigo_cie10 ?? '';
        $this->categoria = $c->categoria;
        $this->descripcion = $c->descripcion ?? '';
        $this->requiere_seguimiento = (bool) $c->requiere_seguimiento;
        $this->frecuencia_seguimiento_dias = $c->frecuencia_seguimiento_dias ?? '';
        $this->activo = (bool) $c->activo;
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
            'nombre' => 'required|string|max:200|unique:condiciones,nombre,' . ($this->condicionId ?? 'NULL'),
            'codigo_cie10' => 'nullable|string|max:10',
            'categoria' => 'required|in:METABOLICA,CARDIOVASCULAR,RESPIRATORIA,DIGESTIVA,NEUROLOGICA,MUSCULOESQUELETICA,RENAL,AUTOINMUNE,ONCOLOGICA,INFECCIOSA,PSIQUIATRICA,OTRA',
            'descripcion' => 'nullable|string',
            'requiere_seguimiento' => 'boolean',
            'frecuencia_seguimiento_dias' => 'nullable|integer|min:1|max:3650',
            'activo' => 'boolean',
        ]);

        // Si no requiere seguimiento, limpiar la frecuencia
        if (!$datos['requiere_seguimiento']) {
            $datos['frecuencia_seguimiento_dias'] = null;
        }

        if ($this->condicionId) {
            Condicion::find($this->condicionId)?->update($datos);
            session()->flash('success', 'Condición actualizada correctamente.');
        } else {
            Condicion::create($datos);
            session()->flash('success', 'Condición creada correctamente.');
        }

        $this->dispatch('condicion-guardada');
        $this->abierto = false;
    }
};
?>

<div>
    @if ($abierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ $condicionId ? 'Editar Condición' : 'Nueva Condición' }}
                    </h3>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre *</label>
                            <input type="text" wire:model="nombre"
                                   placeholder="Ej: Diabetes Mellitus Tipo 2"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Código CIE-10</label>
                            <input type="text" wire:model="codigo_cie10"
                                   placeholder="Ej: E11"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('codigo_cie10') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Categoría *</label>
                            <select wire:model="categoria" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="METABOLICA">Metabólica</option>
                                <option value="CARDIOVASCULAR">Cardiovascular</option>
                                <option value="RESPIRATORIA">Respiratoria</option>
                                <option value="DIGESTIVA">Digestiva</option>
                                <option value="NEUROLOGICA">Neurológica</option>
                                <option value="MUSCULOESQUELETICA">Musculoesquelética</option>
                                <option value="RENAL">Renal</option>
                                <option value="AUTOINMUNE">Autoinmune</option>
                                <option value="ONCOLOGICA">Oncológica</option>
                                <option value="INFECCIOSA">Infecciosa</option>
                                <option value="PSIQUIATRICA">Psiquiátrica</option>
                                <option value="OTRA">Otra</option>
                            </select>
                            @error('categoria') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descripción</label>
                            <textarea wire:model="descripcion" rows="2"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                        </div>

                        <div class="sm:col-span-2 space-y-2">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="requiere_seguimiento"
                                       class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-2 text-sm text-gray-700">Requiere seguimiento periódico</span>
                            </label>

                            @if ($requiere_seguimiento)
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                        Frecuencia (días)
                                    </label>
                                    <input type="number" wire:model="frecuencia_seguimiento_dias"
                                           placeholder="Ej: 90"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    @error('frecuencia_seguimiento_dias') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="activo"
                                       class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-2 text-sm text-gray-700">Activa</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                            {{ $condicionId ? 'Guardar Cambios' : 'Crear Condición' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>