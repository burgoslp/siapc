<?php

use Livewire\Component;
use App\Models\Paciente;

new class extends Component
{
    public string $search = '';
    public ?int $seleccionadoId = null;
    public ?string $seleccionadoTexto = null;
    public bool $abierto = false;

    public function mount(?int $value = null, ?string $texto = null): void
    {
        if ($value) {
            $p = Paciente::find($value);
            if ($p) {
                $this->seleccionadoId = $p->id;
                $this->seleccionadoTexto = "{$p->nombre} {$p->apellido} ({$p->lci}-{$p->ci})";
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
        $this->dispatch('paciente-seleccionado', id: null);
    }

    public function seleccionar(int $id): void
    {
        $p = Paciente::find($id);
        if (!$p) return;

        $this->seleccionadoId = $p->id;
        $this->seleccionadoTexto = "{$p->nombre} {$p->apellido} ({$p->lci}-{$p->ci})";
        $this->search = $this->seleccionadoTexto;
        $this->abierto = false;

        $this->dispatch('paciente-seleccionado', id: $p->id);
    }

    public function with(): array
    {
        $resultados = collect();

        if ($this->abierto && strlen($this->search) >= 2 && $this->search !== $this->seleccionadoTexto) {
            $term = $this->search;

            $resultados = Paciente::where('estado', 'activo')
                ->where(function ($q) use ($term) {
                    $q->where('nombre', 'like', "%{$term}%")
                      ->orWhere('apellido', 'like', "%{$term}%")
                      ->orWhere('ci', 'like', "%{$term}%")
                      ->orWhereRaw("CONCAT(nombre, ' ', apellido) LIKE ?", ["%{$term}%"]);
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
            placeholder="Buscar paciente por nombre, apellido o cédula..."
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
                    No se encontraron pacientes.
                </div>
            @else
                @foreach ($resultados as $p)
                    <button type="button"
                            wire:click="seleccionar({{ $p->id }})"
                            class="w-full text-left px-4 py-2.5 hover:bg-teal-50 border-b border-gray-50 last:border-0 transition">
                        <div class="font-medium text-gray-800 text-sm">
                            {{ $p->nombre }} {{ $p->apellido }}
                        </div>
                        <div class="text-xs text-gray-500 mt-0.5">
                            {{ $p->lci }}-{{ $p->ci }}
                            @if ($p->telefono) · {{ $p->telefono }} @endif
                        </div>
                    </button>
                @endforeach
            @endif
        </div>
    @endif
</div>