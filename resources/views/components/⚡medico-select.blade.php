<?php

use Livewire\Component;
use App\Models\Medico;

new class extends Component
{
    public string $search = '';
    public ?int $seleccionadoId = null;
    public ?string $seleccionadoTexto = null;
    public bool $abierto = false;

    public function mount(?int $value = null): void
    {
        if ($value) {
            $m = Medico::with('especialidades')->find($value);
            if ($m) {
                $this->seleccionadoId = $m->id;
                $this->seleccionadoTexto = "{$m->apellido}, {$m->nombre} — " . $m->especialidades->pluck('nombre')->join(', ');
                $this->search = $this->seleccionadoTexto;
            }
        }
    }

    public function updatingSearch(): void
    {
        $this->abierto = true;
    }

    public function abrir(): void
    {
        $this->abierto = true;
    }

    public function limpiar(): void
    {
        $this->seleccionadoId = null;
        $this->seleccionadoTexto = null;
        $this->search = '';
        $this->abierto = false;
        $this->dispatch('medico-seleccionado', id: null);
    }

    public function seleccionar(int $id): void
    {
        $m = Medico::with('especialidades')->find($id);
        if (!$m) return;

        $this->seleccionadoId = $m->id;
        $this->seleccionadoTexto = "{$m->apellido}, {$m->nombre} — " . $m->especialidades->pluck('nombre')->join(', ');
        $this->search = $this->seleccionadoTexto;
        $this->abierto = false;

        $this->dispatch('medico-seleccionado', id: $m->id);
    }

    public function with(): array
    {
        $resultados = collect();

        if ($this->abierto && strlen($this->search) >= 2 && $this->search !== $this->seleccionadoTexto) {
            $term = $this->search;

            $resultados = Medico::with('especialidades')
                ->activos()
                ->where(function ($query) use ($term) {
                    // Busca por nombre, apellido o cédula
                    $query->where('nombre', 'like', "%{$term}%")
                          ->orWhere('apellido', 'like', "%{$term}%")
                          ->orWhere('ci', 'like', "%{$term}%")
                          ->orWhere('licencia_medica', 'like', "%{$term}%")
                          // 👇 AQUÍ ESTÁ LA MAGIA: Busca dentro de las especialidades
                          ->orWhereHas('especialidades', function ($q) use ($term) {
                              $q->where('nombre', 'like', "%{$term}%");
                          });
                })
                ->orderBy('apellido')
                ->limit(15)
                ->get();
        }

        return [
            'resultados' => $resultados,
        ];
    }
};
?>

<div x-data="{ open: @entangle('abierto') }"
     @click.outside="open = false; $wire.set('abierto', false)"
     class="relative">

    <div class="relative">
        <input
            type="text"
            wire:model.live.debounce.300ms="search"
            wire:focus="abrir"
            placeholder="Buscar médico por nombre, apellido o cédula..."
            class="w-full pl-10 pr-10 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500 outline-none">

        {{-- Icono de búsqueda --}}
        <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
             fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
        </svg>

        {{-- Botón limpiar --}}
        @if ($seleccionadoId)
            <button type="button"
                    wire:click="limpiar"
                    class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        @endif
    </div>

    {{-- Dropdown de resultados --}}
    @if ($abierto && strlen($search) >= 2 && $search !== $seleccionadoTexto)
        <div class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded-lg shadow-lg max-h-72 overflow-y-auto">
            @if ($resultados->isEmpty())
                <div class="px-4 py-3 text-sm text-gray-500 text-center">
                    No se encontraron médicos.
                </div>
            @else
                @foreach ($resultados as $m)
                    <button type="button"
                            wire:click="seleccionar({{ $m->id }})"
                            class="w-full text-left px-4 py-2.5 hover:bg-teal-50 border-b border-gray-50 last:border-0 transition">
                        <div class="font-medium text-gray-800 text-sm">
                            Dr(a). {{ $m->apellido }}, {{ $m->nombre }}
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            {{ $m->especialidades->pluck('nombre')->join(', ') }}
                            @if ($m->licencia_medica) · MPPS: {{ $m->licencia_medica }} @endif
                        </div>
                    </button>
                @endforeach
            @endif
        </div>
    @endif
</div>