<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Alergeno;

new class extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $categoria = '';

    #[On('alergeno-guardado')]
    public function refrescar(): void {}

    public function with(): array
    {
        $query = Alergeno::query()->withCount('alergiasPacientes');

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('descripcion', 'like', "%{$term}%");
            });
        }

        if ($this->categoria !== '') {
            $query->where('categoria', $this->categoria);
        }

        // Categorías únicas para el filtro
        $categorias = Alergeno::whereNotNull('categoria')
            ->distinct()
            ->orderBy('categoria')
            ->pluck('categoria');

        return [
            'alergenos' => $query->orderBy('categoria')->orderBy('nombre')->get(),
            'categorias' => $categorias,
        ];
    }

    public function eliminar(int $id): void
    {
        $al = Alergeno::find($id);
        if (!$al) return;

        $al->delete();
        session()->flash('success', 'Alérgeno eliminado correctamente.');
    }
};
?>

<div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Buscar alérgeno..."
                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <select wire:model.live="categoria" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todas las categorías</option>
            @foreach ($categorias as $cat)
                <option value="{{ $cat }}">{{ $cat }}</option>
            @endforeach
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
                        <th class="px-4 py-3">Categoría</th>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3 text-center">Pacientes</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($alergenos as $al)
                        <tr wire:key="al-{{ $al->id }}" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-medium text-gray-800">
                                {{ $al->nombre }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($al->categoria)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                        {{ $al->categoria }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-md truncate">
                                {{ $al->descripcion ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-medium
                                    {{ $al->alergias_pacientes_count > 0 ? 'bg-teal-100 text-teal-800' : 'bg-gray-100 text-gray-600' }}">
                                    {{ $al->alergias_pacientes_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1">
                                    <button wire:click="$dispatch('editar-alergeno', { id: {{ $al->id }} })"
                                            title="Editar"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button wire:click="eliminar({{ $al->id }})"
                                            wire:confirm="¿Eliminar el alérgeno {{ $al->nombre }}?"
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
                            <td colspan="5" class="px-4 py-10 text-center text-gray-500 text-sm">
                                No hay alérgenos que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>