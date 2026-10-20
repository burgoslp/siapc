<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\MovimientoEntreCuenta;
use App\Models\Cuenta;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $tipo = '';

    #[Url(history: true)]
    public string $cuentaId = '';

    #[Url(history: true)]
    public string $fechaDesde = '';

    #[Url(history: true)]
    public string $fechaHasta = '';

    #[Url(history: true)]
    public string $rango = 'todas'; // hoy | semana | mes | todas

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingTipo(): void { $this->resetPage(); }
    public function updatingCuentaId(): void { $this->resetPage(); }
    public function updatingFechaDesde(): void { $this->resetPage(); }
    public function updatingFechaHasta(): void { $this->resetPage(); }
    public function updatingRango(): void { $this->resetPage(); }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'tipo', 'cuentaId', 'fechaDesde', 'fechaHasta']);
        $this->rango = 'todas';
        $this->resetPage();
    }

    #[On('movimiento-registrado')]
    public function refrescar(): void {}

    public function with(): array
    {
        $query = MovimientoEntreCuenta::with([
            'cuentaOrigen',
            'cuentaDestino',
            'registradoPor',
        ]);

        // Rango rápido
        switch ($this->rango) {
            case 'hoy':
                $query->whereDate('created_at', today());
                break;
            case 'semana':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'mes':
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'todas':
            default:
                break;
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('referencia', 'like', "%{$term}%")
                  ->orWhere('notas', 'like', "%{$term}%")
                  ->orWhereHas('cuentaOrigen', fn($c) => $c->where('nombre', 'like', "%{$term}%"))
                  ->orWhereHas('cuentaDestino', fn($c) => $c->where('nombre', 'like', "%{$term}%"));
            });
        }

        if ($this->tipo !== '') {
            $query->where('tipo', $this->tipo);
        }

        if ($this->cuentaId !== '') {
            $query->where(function ($q) {
                $q->where('cuenta_origen_id', $this->cuentaId)
                  ->orWhere('cuenta_destino_id', $this->cuentaId);
            });
        }

        if ($this->fechaDesde !== '') {
            $query->whereDate('created_at', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta !== '') {
            $query->whereDate('created_at', '<=', $this->fechaHasta);
        }

        return [
            'movimientos' => $query->orderByDesc('created_at')->paginate(30),
            'cuentas' => Cuenta::orderBy('nombre')->get(),
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
                        {{ $rango === $key ? 'bg-cyan-600 text-white' : 'bg-white border border-gray-200 text-gray-600 hover:bg-gray-50' }}">
                {{ $label }}
            </button>
        @endforeach

        <button wire:click="limpiarFiltros"
                class="ml-auto px-3 py-1.5 text-xs font-medium text-gray-500 hover:text-gray-700">
            Limpiar filtros
        </button>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.400ms="search"
                       placeholder="Buscar referencia o cuenta..."
                       class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
            </div>

            <select wire:model.live="tipo" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Todos los tipos</option>
                <option value="RETIRO">Retiros</option>
                <option value="DEPOSITO">Depósitos</option>
                <option value="TRANSFERENCIA">Transferencias</option>
            </select>

            <select wire:model.live="cuentaId" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Todas las cuentas</option>
                @foreach ($cuentas as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }}</option>
                @endforeach
            </select>

            <div class="flex gap-2">
                <input type="date" wire:model.live="fechaDesde"
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <input type="date" wire:model.live="fechaHasta"
                       class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
            </div>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Tipo</th>
                        <th class="px-4 py-3">Origen</th>
                        <th class="px-4 py-3">Destino</th>
                        <th class="px-4 py-3 text-right">Monto Origen</th>
                        <th class="px-4 py-3 text-right">Monto Destino</th>
                        <th class="px-4 py-3 text-right">Comisión</th>
                        <th class="px-4 py-3">Referencia</th>
                        <th class="px-4 py-3">Registrado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($movimientos as $mov)
                        <tr wire:key="mov-{{ $mov->id }}" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-medium text-gray-800">{{ $mov->created_at->format('d/m/Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $mov->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if ($mov->tipo === 'RETIRO')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        Retiro
                                    </span>
                                @elseif ($mov->tipo === 'DEPOSITO')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                        Depósito
                                    </span>
                                @else
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-cyan-100 text-cyan-800">
                                        Transferencia
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $mov->cuentaOrigen?->nombre }}</div>
                                <div class="text-xs text-gray-500">{{ $mov->cuentaOrigen?->moneda_etiqueta }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">{{ $mov->cuentaDestino?->nombre }}</div>
                                <div class="text-xs text-gray-500">{{ $mov->cuentaDestino?->moneda_etiqueta }}</div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                {{ $mov->monto_origen_formateado }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-cyan-700">
                                {{ $mov->monto_destino_formateado }}
                            </td>
                            <td class="px-4 py-3 text-right text-xs">
                                @if ((float) $mov->comision_origen_monto > 0)
                                    <span class="text-red-600 font-medium">
                                        {{ $mov->cuentaOrigen?->moneda_etiqueta }} {{ number_format((float) $mov->comision_origen_monto, 2) }}
                                    </span>
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                @if ($mov->referencia)
                                    <div class="font-mono">{{ $mov->referencia }}</div>
                                @endif
                                @if ($mov->notas)
                                    <div class="text-gray-500 italic">{{ \Str::limit($mov->notas, 30) }}</div>
                                @endif
                                @if (!$mov->referencia && !$mov->notas)
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $mov->registradoPor?->nombre_mostrar ?? 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-12 text-center text-gray-500 text-sm">
                                No hay movimientos con los filtros actuales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($movimientos->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">
                {{ $movimientos->links() }}
            </div>
        @endif
    </div>
</div>