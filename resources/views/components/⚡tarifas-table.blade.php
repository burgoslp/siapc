<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Tarifa;
use App\Models\Especialidad;

new class extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $tipo = '';

    #[Url(history: true)]
    public string $especialidadId = '';

    #[Url(history: true)]
    public string $estado = 'activos'; // activos | inactivos | todas

    #[On('tarifa-guardada')]
    public function refrescar(): void {}

    public function with(): array
    {
        $query = Tarifa::query()
            ->with(['especialidad'])
            ->withCount('citas');

        if ($this->search !== '') {
            $term = $this->search;
            $query->where('nombre', 'like', "%{$term}%");
        }

        if ($this->tipo !== '') {
            $query->where('tipo', $this->tipo);
        }

        if ($this->especialidadId === 'genericas') {
            $query->whereNull('especialidad_id');
        } elseif ($this->especialidadId !== '') {
            $query->where('especialidad_id', $this->especialidadId);
        }

        if ($this->estado === 'activos') {
            $query->where('activo', true);
        } elseif ($this->estado === 'inactivos') {
            $query->where('activo', false);
        }

        return [
            'tarifas' => $query
                ->orderByRaw('CASE WHEN especialidad_id IS NULL THEN 1 ELSE 0 END')
                ->orderBy('nombre')
                ->get(),
            'especialidades' => Especialidad::orderBy('nombre')->get(),
        ];
    }

    public function eliminar(int $id): void
    {
        $tarifa = Tarifa::find($id);
        if (!$tarifa) return;

        // Si tiene citas asociadas, solo desactivar
        if ($tarifa->citas()->exists()) {
            $tarifa->update(['activo' => false]);
            session()->flash('success', 'La tarifa tiene citas asociadas. Se desactivó en lugar de eliminar.');
            return;
        }

        $tarifa->delete();
        session()->flash('success', 'Tarifa eliminada correctamente.');
    }

    public function toggleActivo(int $id): void
    {
        $tarifa = Tarifa::find($id);
        if (!$tarifa) return;

        $tarifa->update(['activo' => !$tarifa->activo]);
        session()->flash('success', 'Estado actualizado.');
    }
};
?>

<div>
    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Buscar por nombre..."
                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <select wire:model.live="tipo" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos los tipos</option>
            <option value="CONSULTA">Consulta</option>
            <option value="PROCEDIMIENTO">Procedimiento</option>
            <option value="EXAMEN">Examen</option>
            <option value="URGENCIA">Urgencia</option>
            <option value="SEGUIMIENTO">Seguimiento</option>
        </select>

        <select wire:model.live="especialidadId" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todas las especialidades</option>
            <option value="genericas">— Genéricas (sin especialidad) —</option>
            @foreach ($especialidades as $esp)
                <option value="{{ $esp->id }}">{{ $esp->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="estado" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="activos">Solo activas</option>
            <option value="inactivos">Solo inactivas</option>
            <option value="todas">Todas</option>
        </select>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-4 py-3">Nombre</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Especialidad</th>
                        <th class="px-4 py-3 text-right">Precio (USD)</th>
                        <th class="px-4 py-3 text-center">Citas</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($tarifas as $t)
                        <tr wire:key="tar-{{ $t->id }}" class="hover:bg-gray-50 transition {{ !$t->activo ? 'opacity-60' : '' }}">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $t->nombre }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                    @switch($t->tipo)
                                        @case('CONSULTA') bg-blue-100 text-blue-800 @break
                                        @case('PROCEDIMIENTO') bg-purple-100 text-purple-800 @break
                                        @case('EXAMEN') bg-amber-100 text-amber-800 @break
                                        @case('URGENCIA') bg-red-100 text-red-800 @break
                                        @case('SEGUIMIENTO') bg-teal-100 text-teal-800 @break
                                        @default bg-gray-100 text-gray-800
                                    @endswitch">
                                    {{ $t->tipo_etiqueta }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">
                                @if ($t->especialidad)
                                    {{ $t->especialidad->nombre }}
                                @else
                                    <span class="text-xs italic text-gray-400">Genérica</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-bold text-gray-800">
                                    {{ $t->precio_formateado }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $t->citas_count > 0 ? 'bg-teal-100 text-teal-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $t->citas_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <button wire:click="toggleActivo({{ $t->id }})"
                                        title="{{ $t->activo ? 'Desactivar' : 'Activar' }}"
                                        class="focus:outline-none">
                                    @if ($t->activo)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 hover:bg-green-200">
                                            Activa
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600 hover:bg-gray-200">
                                            Inactiva
                                        </span>
                                    @endif
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1">
                                    <button wire:click="$dispatch('editar-tarifa', { id: {{ $t->id }} })"
                                            title="Editar"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="eliminar({{ $t->id }})"
                                            wire:confirm="¿Eliminar la tarifa {{ $t->nombre }}?"
                                            title="Eliminar"
                                            class="p-1.5 text-red-600 hover:bg-red-50 rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-gray-500 text-sm">
                                No hay tarifas que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>