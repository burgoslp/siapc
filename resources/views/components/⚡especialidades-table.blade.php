<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Especialidad;

new class extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    public function updatingSearch(): void {}

    #[On('especialidad-guardada')]
    public function refrescar(): void {}

    public function eliminar(int $id): void
    {
        $esp = Especialidad::find($id);
        if (!$esp) return;

        $esp->delete();
        session()->flash('success', 'Especialidad eliminada correctamente.');
    }

    public function with(): array
    {
        $query = Especialidad::query()->withCount('medicos');

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('descripcion', 'like', "%{$term}%");
            });
        }

        return [
            'especialidades' => $query->orderBy('nombre')->get(),
        ];
    }
};
?>

<div>
    {{-- Buscador --}}
    <div class="relative mb-4">
        <input type="text"
               wire:model.live.debounce.400ms="search"
               placeholder="Buscar especialidad..."
               class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>
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
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3 text-center">Médicos</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($especialidades as $esp)
                        <tr wire:key="esp-{{ $esp->id }}" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $esp->nombre }}
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-md">
                                {{ $esp->descripcion ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $esp->medicos_count > 0 ? 'bg-teal-100 text-teal-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $esp->medicos_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1">
                                    <button wire:click="$dispatch('editar-especialidad', { id: {{ $esp->id }} })"
                                            title="Editar"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>

                                    <button wire:click="eliminar({{ $esp->id }})"
                                            wire:confirm="¿Eliminar la especialidad {{ $esp->nombre }}? Los médicos asociados perderán esta especialidad."
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
                            <td colspan="4" class="px-4 py-10 text-center text-gray-500 text-sm">
                                No hay especialidades registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>