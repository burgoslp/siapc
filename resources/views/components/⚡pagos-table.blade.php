<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Pago;

new class extends Component
{
    use WithPagination;

    public ?int $cajaId = null;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $formaPago = '';

    #[Url(history: true)]
    public string $moneda = '';

    public function mount(?int $cajaId = null): void
    {
        $this->cajaId = $cajaId;
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFormaPago(): void { $this->resetPage(); }
    public function updatingMoneda(): void { $this->resetPage(); }

    #[On('pago-registrado')]
    public function refrescar(): void {}

    public function with(): array
    {
        $query = Pago::with(['cita', 'paciente', 'registradoPor']);

        if ($this->cajaId) {
            $query->where('caja_id', $this->cajaId);
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->whereHas('paciente', function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('apellido', 'like', "%{$term}%")
                  ->orWhere('ci', 'like', "%{$term}%");
            });
        }

        if ($this->formaPago !== '') {
            $query->where('forma_pago', $this->formaPago);
        }

        if ($this->moneda !== '') {
            $query->where('moneda', $this->moneda);
        }

        $pagos = $query->orderByDesc('created_at')->paginate(50);

        // Calcular total en Bs de los resultados actuales
        $totalBs = collect($pagos->items())->sum(fn($p) => $p->monto_bs);

        return [
            'pagos' => $pagos,
            'totalBs' => $totalBs,
        ];
    }
};
?>

<div>
    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Buscar por paciente..."
                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <select wire:model.live="formaPago" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todas las formas</option>
            <option value="EFECTIVO">Efectivo</option>
            <option value="TRANSFERENCIA">Transferencia</option>
            <option value="PAGO_MOVIL">Pago Móvil</option>
            <option value="TARJETA_DEBITO">Tarjeta Débito</option>
            <option value="TARJETA_CREDITO">Tarjeta Crédito</option>
            <option value="ZELLE">Zelle</option>
            <option value="BINANCE">Binance</option>
            <option value="OTRO">Otro</option>
        </select>

        <select wire:model.live="moneda" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todas las monedas</option>
            <option value="dolar">USD</option>
            <option value="euro">EUR</option>
            <option value="bs">Bs</option>
        </select>
    </div>

    {{-- Total --}}
    <div class="mb-4 flex items-center justify-between p-3 bg-teal-50 border border-teal-200 rounded-lg">
        <span class="text-xs font-semibold text-teal-800 uppercase tracking-wider">
            Total mostrado
        </span>
        <span class="text-lg font-bold text-teal-900">
            Bs {{ number_format($totalBs, 2) }}
        </span>
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-4 py-3">Hora</th>
                        <th class="px-4 py-3">Paciente</th>
                        <th class="px-4 py-3">Forma de pago</th>
                        <th class="px-4 py-3">Moneda</th>
                        <th class="px-4 py-3 text-right">Monto</th>
                        <th class="px-4 py-3 text-right">En Bs</th>
                        <th class="px-4 py-3">Referencia</th>
                        <th class="px-4 py-3">Registrado por</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($pagos as $pago)
                        <tr wire:key="pago-{{ $pago->id }}" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">
                                {{ $pago->created_at->format('h:i A') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">
                                    {{ $pago->paciente?->nombre }} {{ $pago->paciente?->apellido }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700">
                                    {{ $pago->forma_pago_etiqueta }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-600">
                                {{ $pago->moneda_etiqueta }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                {{ $pago->monto_formateado }}
                            </td>
                            <td class="px-4 py-3 text-right font-bold text-teal-700">
                                Bs {{ number_format($pago->monto_bs, 2) }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">
                                @if ($pago->banco)
                                    <div>{{ $pago->banco }}</div>
                                @endif
                                @if ($pago->referencia)
                                    <div class="font-mono">{{ $pago->referencia }}</div>
                                @endif
                                @if (!$pago->banco && !$pago->referencia)
                                    <span class="italic">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                {{ $pago->registradoPor?->nombre_mostrar ?? 'N/A' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center text-gray-500 text-sm">
                                No hay pagos registrados con los filtros actuales.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($pagos->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">
                {{ $pagos->links() }}
            </div>
        @endif
    </div>
</div>