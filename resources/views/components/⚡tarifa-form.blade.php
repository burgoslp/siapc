<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Tarifa;
use App\Models\Especialidad;

new class extends Component
{
    public ?int $tarifaId = null;
    public bool $abierto = false;

    public $nombre = '';
    public $tipo = 'CONSULTA';
    public $especialidad_id = '';
    public $precio_usd = '';
    public $activo = true;

    #[On('nueva-tarifa')]
    public function abrirNueva(): void
    {
        $this->reset();
        $this->tipo = 'CONSULTA';
        $this->activo = true;
        $this->tarifaId = null;
        $this->abierto = true;
    }

    #[On('editar-tarifa')]
    public function abrirEdicion(int $id): void
    {
        $tarifa = Tarifa::find($id);
        if (!$tarifa) return;

        $this->tarifaId = $tarifa->id;
        $this->nombre = $tarifa->nombre;
        $this->tipo = $tarifa->tipo;
        $this->especialidad_id = $tarifa->especialidad_id ?? '';
        $this->precio_usd = $tarifa->precio_usd;
        $this->activo = (bool) $tarifa->activo;
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    /**
     * Autocompleta el nombre de la tarifa cuando cambia el tipo o la especialidad
     * (solo si el nombre está vacío).
     */
    public function updatedTipo(): void
    {
        if ($this->nombre === '') {
            $this->autocompletarNombre();
        }
    }

    public function updatedEspecialidadId(): void
    {
        $this->autocompletarNombre();
    }

    private function autocompletarNombre(): void
    {
        $tipoEtiqueta = match ($this->tipo) {
            'CONSULTA'      => 'Consulta',
            'PROCEDIMIENTO' => 'Procedimiento',
            'EXAMEN'        => 'Examen',
            'URGENCIA'      => 'Urgencia',
            'SEGUIMIENTO'   => 'Seguimiento',
            default         => 'Servicio',
        };

        if ($this->especialidad_id) {
            $esp = Especialidad::find($this->especialidad_id);
            if ($esp) {
                $this->nombre = "{$tipoEtiqueta} {$esp->nombre}";
                return;
            }
        }

        $this->nombre = "{$tipoEtiqueta} General";
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'nombre' => 'required|string|max:150',
            'tipo' => 'required|in:CONSULTA,PROCEDIMIENTO,EXAMEN,URGENCIA,SEGUIMIENTO',
            'especialidad_id' => 'nullable|exists:especialidades,id',
            'precio_usd' => 'required|numeric|min:0|max:999999.99',
            'activo' => 'boolean',
        ]);

        $datos['especialidad_id'] = $this->especialidad_id ?: null;

        if ($this->tarifaId) {
            Tarifa::find($this->tarifaId)?->update($datos);
            session()->flash('success', 'Tarifa actualizada correctamente.');
        } else {
            Tarifa::create($datos);
            session()->flash('success', 'Tarifa creada correctamente.');
        }

        $this->dispatch('tarifa-guardada');
        $this->abierto = false;
    }
};
?>

<div>
    @if ($abierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-lg">

                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ $tarifaId ? 'Editar Tarifa' : 'Nueva Tarifa' }}
                    </h3>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Tipo --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo *</label>
                            <select wire:model.live="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="CONSULTA">Consulta</option>
                                <option value="PROCEDIMIENTO">Procedimiento</option>
                                <option value="EXAMEN">Examen</option>
                                <option value="URGENCIA">Urgencia</option>
                                <option value="SEGUIMIENTO">Seguimiento</option>
                            </select>
                            @error('tipo') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Especialidad --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Especialidad
                                <span class="text-gray-400 normal-case font-normal">(opcional)</span>
                            </label>
                            <select wire:model.live="especialidad_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Genérica (sin especialidad)</option>
                                @foreach (Especialidad::orderBy('nombre')->get() as $esp)
                                    <option value="{{ $esp->id }}">{{ $esp->nombre }}</option>
                                @endforeach
                            </select>
                            @error('especialidad_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Nombre --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre *</label>
                            <input type="text" wire:model="nombre"
                                   placeholder="Ej: Consulta Cardiología"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            <p class="text-xs text-gray-500 mt-1">
                                Se autocompleta según el tipo y especialidad, pero puedes editarlo.
                            </p>
                            @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Precio --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Precio (USD) *</label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-500 font-semibold text-sm pointer-events-none">
                                    $
                                </span>
                                <input type="number" step="0.01" min="0" wire:model="precio_usd"
                                       placeholder="0.00"
                                       class="w-full pl-8 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>
                            @error('precio_usd') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Activa --}}
                        <div class="sm:col-span-2">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" wire:model="activo"
                                       class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-2 text-sm text-gray-700">
                                    Tarifa activa
                                    <span class="block text-xs text-gray-500">Si la desactivas, no aparecerá en los formularios de citas</span>
                                </span>
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
                            {{ $tarifaId ? 'Guardar Cambios' : 'Crear Tarifa' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>