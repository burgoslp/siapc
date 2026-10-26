<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Medico;
use App\Models\Especialidad;

new class extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $especialidadId = '';

    #[Url(history: true)]
    public string $estado = '';

    #[On('medico-guardado')]
    public function refrescar(): void {}

    public function with(): array
    {
        $query = Medico::with(['especialidades', 'user']);

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('apellido', 'like', "%{$term}%")
                  ->orWhere('ci', 'like', "%{$term}%")
                  ->orWhere('licencia_medica', 'like', "%{$term}%")
                  ->orWhereHas('user', fn($u) => $u->where('username', 'like', "%{$term}%"));
            });
        }

        if ($this->especialidadId !== '') {
            $query->whereHas('especialidades', fn($q) => $q->where('especialidades.id', $this->especialidadId));
        }

        if ($this->estado !== '') {
            $query->where('estado', $this->estado);
        }

        return [
            'medicos' => $query->orderBy('apellido')->orderBy('nombre')->get(),
            'especialidades' => Especialidad::orderBy('nombre')->get(),
        ];
    }

    public function eliminar(int $id): void
    {
        $medico = Medico::find($id);
        if (!$medico) return;

        // Desactivar el user asociado
        if ($medico->user) {
            $medico->user->update(['activo' => false]);
        }

        $medico->delete();
        session()->flash('success', 'Médico eliminado. El usuario quedó inactivo.');
    }
};
?>

<div>
    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Buscar por nombre, cédula o licencia..."
                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <select wire:model.live="especialidadId" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todas las especialidades</option>
            @foreach ($especialidades as $esp)
                <option value="{{ $esp->id }}">{{ $esp->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="estado" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos los estados</option>
            <option value="ACTIVO">Activos</option>
            <option value="INACTIVO">Inactivos</option>
            <option value="LICENCIA">En licencia</option>
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
                        <th class="px-4 py-3">Médico</th>
                        <th class="px-4 py-3">Cédula</th>
                        <th class="px-4 py-3">Licencia</th>
                        <th class="px-4 py-3">Especialidades</th>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Firma</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($medicos as $medico)
                        <tr wire:key="med-{{ $medico->id }}" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">
                                    {{ $medico->nombre }} {{ $medico->apellido }}
                                </div>
                                @if ($medico->telefono)
                                    <div class="text-xs text-gray-500">{{ $medico->telefono }}</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 whitespace-nowrap">
                                {{ $medico->lci }}-{{ $medico->ci }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 text-xs font-mono">
                                    {{ $medico->licencia_medica }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                @if ($medico->especialidades->count())
                                    <div class="flex flex-wrap gap-1">
                                        @foreach ($medico->especialidades->take(2) as $esp)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                                {{ $esp->nombre }}
                                            </span>
                                        @endforeach
                                        @if ($medico->especialidades->count() > 2)
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                +{{ $medico->especialidades->count() - 2 }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">Sin especialidad</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($medico->user)
                                    <div class="text-xs">
                                        <div class="text-gray-700 font-medium">{{ $medico->user->username }}</div>
                                        <div class="text-gray-500">{{ $medico->user->email }}</div>
                                    </div>
                                @else
                                    <span class="text-xs text-red-500 italic">Sin usuario</span>
                                @endif
                            </td>
                            <td>
                                @if ($medico->firma_url)
                                    <img src="{{ $medico->firma_url }}" alt="Firma" class="h-10 max-w-[100px] object-contain mx-auto">
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($medico->estado === 'ACTIVO')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                                @elseif ($medico->estado === 'LICENCIA')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">Licencia</span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1">
                                    <button wire:click="$dispatch('editar-medico', { id: {{ $medico->id }} })"
                                            title="Editar"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="eliminar({{ $medico->id }})"
                                            wire:confirm="¿Eliminar al médico {{ $medico->nombre }} {{ $medico->apellido }}? El usuario asociado quedará inactivo."
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
                                No hay médicos que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>