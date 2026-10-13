<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use Livewire\Attributes\Computed;
use App\Models\Paciente;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    public string $estado = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingEstado(): void
    {
        $this->resetPage();
    }

    #[On('paciente-guardado')]
    public function refrescar(): void
    {
        //
    }

    #[Computed]
    public function pacientes()
    {
        $query = Paciente::query();

        if ($this->search !== '') {
            $query->where(function ($q) {
                $q->where('nombre', 'like', '%' . $this->search . '%')
                  ->orWhere('apellido', 'like', '%' . $this->search . '%')
                  ->orWhere('ci', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->estado !== '') {
            $query->where('estado', $this->estado);
        }

        return $query->orderBy('apellido')->orderBy('nombre')->paginate(10);
    }
};
?>

<div>
    {{-- Buscador y filtro --}}
    <div class="flex flex-col sm:flex-row gap-4 mb-6">
        <div class="relative flex-1">
            <input type="text"
                   wire:model.live.debounce.300ms="search"
                   placeholder="Buscar por cédula, nombre o apellido..."
                   class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg text-sm">
        </div>

        <select wire:model.live="estado"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos</option>
            <option value="activo">Activos</option>
            <option value="inactivo">Inactivos</option>
        </select>
    </div>

    
    <div wire:loading class="text-xs text-blue-600 font-medium mb-3">
        Buscando registros...
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-gray-50 border-b text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-6 py-3">Documento</th>
                        <th class="px-6 py-3">Paciente</th>
                        <th class="px-6 py-3">Nacimiento / Edad</th>
                        <th class="px-6 py-3">Teléfono</th>
                        <th class="px-6 py-3">Email</th>
                        <th class="px-6 py-3">Estado</th>
                        <th class="px-6 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($this->pacientes as $paciente)
                        <tr wire:key="paciente-{{ $paciente->id }}" class="hover:bg-gray-50">
                            <td class="px-6 py-4 font-semibold text-gray-900">
                                {{ $paciente->lci }}-{{ $paciente->ci }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-gray-900">{{ $paciente->nombre }} {{ $paciente->apellido }}</div>
                                <div class="text-xs text-gray-500 capitalize">{{ $paciente->genero ?? 'No especificado' }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-gray-800">{{ $paciente->fecha_nacimiento?->format('d/m/Y') ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ $paciente->fecha_nacimiento?->age ?? '—' }} años</div>
                            </td>
                            <td class="px-6 py-4 text-gray-600">{{ $paciente->telefono ?? 'N/A' }}</td>
                            <td class="px-6 py-4 text-gray-600">{{ $paciente->email ?? 'N/A' }}</td>
                            <td class="px-6 py-4">
                                @if ($paciente->estado === 'activo')
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                                @else
                                    <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactivo</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="inline-flex items-center space-x-1">
                                    {{-- Ver --}}
                                    <a href="{{ route('pacientes.show', $paciente) }}"
                                    title="Ver historial"
                                    class="p-2 text-blue-600 hover:bg-blue-50 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>

                                    {{-- Editar --}}
                                    <button type="button"
                                            wire:click="$dispatch('editar-paciente', { id: {{ $paciente->id }} })"
                                            title="Editar paciente"
                                            class="p-2 text-amber-600 hover:bg-amber-50 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    {{-- Agregar Condición --}}
                                    <button type="button"
                                            wire:click="$dispatch('abrir-condicion-form', { pacienteId: {{ $paciente->id }} })"
                                            title="Agregar condición médica"
                                            class="p-2 text-teal-600 hover:bg-teal-50 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4m0 0v4m0-4H8m4 0h4"/>
                                        </svg>
                                    </button>

                                    {{-- Agregar Alergia --}}
                                    <button type="button"
                                            wire:click="$dispatch('abrir-alergia-form', { pacienteId: {{ $paciente->id }} })"
                                            title="Agregar alergia"
                                            class="p-2 text-purple-600 hover:bg-purple-50 rounded-md transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                        </svg>
                                    </button>

                                    {{-- Eliminar --}}
                                    <form action="{{ route('pacientes.destroy', $paciente) }}" method="POST"
                                        class="inline-block"
                                        onsubmit="return confirm('¿Eliminar este paciente?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                title="Eliminar paciente"
                                                class="p-2 text-red-600 hover:bg-red-50 rounded-md transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                                No se encontraron pacientes.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($this->pacientes->hasPages())
            <div class="px-6 py-4 border-t bg-gray-50">
                {{ $this->pacientes->links() }}
            </div>
        @endif
    </div>
</div>