<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Caja;
use App\Models\User;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $estado = '';

    #[Url(history: true)]
    public string $abiertaPor = '';

    #[Url(history: true)]
    public string $fechaDesde = '';

    #[Url(history: true)]
    public string $fechaHasta = '';

    #[Url(history: true)]
    public string $rango = 'todas'; // hoy | semana | mes | todas

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingEstado(): void { $this->resetPage(); }
    public function updatingAbiertaPor(): void { $this->resetPage(); }
    public function updatingFechaDesde(): void { $this->resetPage(); }
    public function updatingFechaHasta(): void { $this->resetPage(); }
    public function updatingRango(): void { $this->resetPage(); }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'estado', 'abiertaPor', 'fechaDesde', 'fechaHasta']);
        $this->rango = 'todas';
        $this->resetPage();
    }

    #[On('caja-abierta')]
    #[On('caja-cerrada')]
    public function refrescar(): void {}

    public function with(): array
    {
        $query = Caja::with(['abiertaPor', 'cerradaPor'])
            ->withCount('pagos');

        // Rango rápido
        switch ($this->rango) {
            case 'hoy':
                $query->whereDate('abierta_en', today());
                break;
            case 'semana':
                $query->whereBetween('abierta_en', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mes':
                $query->whereBetween('abierta_en', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'todas':
            default:
                // sin filtro
                break;
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where('codigo', 'like', "%{$term}%");
        }

        if ($this->estado !== '') {
            $query->where('estado', $this->estado);
        }

        if ($this->abiertaPor !== '') {
            $query->where('abierta_por', $this->abiertaPor);
        }

        if ($this->fechaDesde !== '') {
            $query->whereDate('abierta_en', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta !== '') {
            $query->whereDate('abierta_en', '<=', $this->fechaHasta);
        }

        return [
            'cajas' => $query->orderByDesc('abierta_en')->paginate(20),
            'usuarios' => User::whereHas('roles', fn($q) => $q->whereIn('nombre', ['admin', 'recepcion']))
                ->orderBy('username')
                ->get(),
        ];
    }
};
?>

<div>
    {{-- Filtros rápidos --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach (['hoy' => 'Hoy', 'semana' => 'Esta semana', 'mes' => 'Este mes', 'todas' => 'Todas'] as $key => $label)
            <button wire:click="$set('rango', '{{ $key }}')"
                    class="px-3 py-1.5 text-xs font-medium rounded-lg transition
                        {{ $rango === $key ? 'bg-teal-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach

        <button wire:click="limpiarFiltros"
                class="ml-auto px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700">
            Limpiar filtros
        </button>
    </div>

    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Buscar por código..."
                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <select wire:model.live="estado" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos los estados</option>
            <option value="ABIERTA">Abiertas</option>
            <option value="CERRADA">Cerradas</option>
        </select>

        <select wire:model.live="abiertaPor" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Abierta por...</option>
            @foreach ($usuarios as $u)
                <option value="{{ $u->id }}">{{ $u->nombre_mostrar }}</option>
            @endforeach
        </select>

        <div class="flex gap-2">
            <input type="date" wire:model.live="fechaDesde"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <input type="date" wire:model.live="fechaHasta"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-4 py-3">Código</th>
                        <th class="px-4 py-3">Apertura</th>
                        <th class="px-4 py-3">Cierre</th>
                        <th class="px-4 py-3 text-center">Pagos</th>
                        <th class="px-4 py-3 text-right">Total General</th>
                        <th class="px-4 py-3 text-center">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($cajas as $caja)
                        <tr wire:key="caja-{{ $caja->id }}" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-mono font-semibold text-gray-800">
                                    {{ $caja->codigo }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="text-gray-800">
                                    {{ $caja->abierta_en->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $caja->abierta_en->format('h:i A') }}
                                    @if ($caja->abiertaPor)
                                        · {{ $caja->abiertaPor->nombre_mostrar }}
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($caja->cerrada_en)
                                    <div class="text-gray-800">
                                        {{ $caja->cerrada_en->format('d/m/Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $caja->cerrada_en->format('h:i A') }}
                                        @if ($caja->cerradaPor)
                                            · {{ $caja->cerradaPor->nombre_mostrar }}
                                        @endif
                                    </div>
                                @else
                                    <span class="text-xs text-gray-400 italic">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center min-w-[28px] px-2 py-0.5 rounded-full text-xs font-medium bg-teal-100 text-teal-800">
                                    {{ $caja->pagos_count }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($caja->estado === 'CERRADA')
                                    <div class="font-bold text-gray-800">
                                        Bs {{ number_format($caja->total_general, 2) }}
                                    </div>
                                @else
                                    <div class="font-bold text-teal-700">
                                        Bs {{ number_format($caja->totalGeneralBs(), 2) }}
                                    </div>
                                    <div class="text-[10px] text-teal-600">En vivo</div>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if ($caja->estado === 'ABIERTA')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        Abierta
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                                        Cerrada
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <a href="{{ route('facturacion.cajas.detalle', $caja) }}"
                                   class="px-3 py-1.5 bg-slate-100 text-slate-700 rounded-md text-xs font-medium hover:bg-slate-200 inline-flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-12 text-center text-gray-500 text-sm">
                                No hay portadas de caja con los filtros actuales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($cajas->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">
                {{ $cajas->links() }}
            </div>
        @endif
    </div>
</div>