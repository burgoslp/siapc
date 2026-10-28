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

    #[Url(history: true)]
    public string $estadoFiltro = 'activos';

    public function mount(?int $cajaId = null): void
    {
        $this->cajaId = $cajaId;
    }

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingFormaPago(): void { $this->resetPage(); }
    public function updatingMoneda(): void { $this->resetPage(); }
    public function updatingEstadoFiltro(): void { $this->resetPage(); }

    #[On('pago-anulado')]
    public function refrescar(): void {}

    public function with(): array
    {
        $query = Pago::with([
            'cita.paciente',
            'cita.medico',
            'paciente',
            'registradoPor',
            'anuladoPor',
            'tasa',
            'cambio',
        ]);

        if ($this->cajaId) {
            $query->where('caja_id', $this->cajaId);
        }

        if ($this->estadoFiltro === 'activos') {
            $query->where('estado', 'ACTIVO');
        } elseif ($this->estadoFiltro === 'anulados') {
            $query->where('estado', 'ANULADO');
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->whereHas('cita', function ($citaQuery) use ($term) {
                    $citaQuery->where('numero_cita', 'like', "%{$term}%");
                })
                // 2. O buscar por datos del paciente (como ya lo tenías)
                ->orWhereHas('paciente', function ($pac) use ($term) {
                    $pac->where('nombre', 'like', "%{$term}%")
                        ->orWhere('apellido', 'like', "%{$term}%")
                        ->orWhere('ci', 'like', "%{$term}%")
                        ->orWhereRaw("CONCAT(nombre, ' ', apellido) LIKE ?", ["%{$term}%"]);
                });
            });
        }

        if ($this->formaPago !== '') {
            $query->where('forma_pago', $this->formaPago);
        }

        if ($this->moneda !== '') {
            $query->where('moneda', $this->moneda);
        }

        $pagos = $query->orderByDesc('created_at')->paginate(50);

        $totalBs = collect($pagos->items())
            ->where('estado', 'ACTIVO')
            ->sum(fn($p) => $p->monto_bs);

        return [
            'pagos' => $pagos,
            'totalBs' => $totalBs,
        ];
    }
};
?>

<div>
    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Buscar por cita, paciente, cedula..."
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

        <select wire:model.live="estadoFiltro" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="activos">Solo activos</option>
            <option value="todos">Todos</option>
            <option value="anulados">Solo anulados</option>
        </select>
    </div>

    {{-- Total --}}
    <div class="mb-4 flex items-center justify-between p-3 bg-teal-50 border border-teal-200 rounded-lg">
        <span class="text-xs font-semibold text-teal-800 uppercase tracking-wider">
            Total activo mostrado
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
                        <th class="px-3 py-3">Hora</th>
                        <th class="px-3 py-3">Paciente</th>
                        <th class="px-3 py-3">Cita</th>
                        <th class="px-3 py-3">Forma</th>
                        <th class="px-3 py-3">Moneda</th>
                        <th class="px-3 py-3 text-right">Tasa</th>
                        <th class="px-3 py-3 text-right">Monto</th>
                        <th class="px-3 py-3 text-right">En Bs</th>
                        <th class="px-3 py-3">Referencia</th>
                        <th class="px-3 py-3">Registrado por</th>
                        <th class="px-3 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($pagos as $pago)
                        <tr wire:key="pago-{{ $pago->id }}"
                            class="hover:bg-gray-50 transition {{ $pago->esta_anulado ? 'opacity-60 bg-red-50/30' : '' }}">

                            {{-- Hora --}}
                            <td class="px-3 py-3 whitespace-nowrap text-gray-600 text-xs">
                                {{ $pago->created_at->format('h:i A') }}
                            </td>

                            {{-- Paciente --}}
                            <td class="px-3 py-3">
                                <div class="font-medium text-gray-800 text-sm">
                                    {{ $pago->paciente?->nombre }} {{ $pago->paciente?->apellido }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $pago->paciente?->lci }}-{{ $pago->paciente?->ci }}
                                </div>
                            </td>

                            {{-- Cita --}}
                            <td class="px-3 py-3 text-xs">
                                @if ($pago->cita)
                                    <div class="font-mono font-semibold text-teal-700">
                                        #{{ $pago->cita->numero_cita }}
                                    </div>
                                    <div class="text-gray-500 mt-0.5">
                                        {{ $pago->cita->fecha->format('d/m/Y') }}
                                        · {{ $pago->cita->fecha->format('h:i A') }}
                                    </div>
                                    <div class="text-[10px] mt-0.5">
                                        @if ($pago->cita->estado_pago === 'PAGADA')
                                            <span class="text-green-600 font-medium">Pagada</span>
                                        @elseif ($pago->cita->estado_pago === 'PARCIAL')
                                            <span class="text-amber-600 font-medium">Parcial</span>
                                        @else
                                            <span class="text-orange-600 font-medium">Pendiente</span>
                                        @endif
                                        <span class="text-gray-400">
                                            · ${{ number_format($pago->cita->total, 2) }}
                                        </span>
                                    </div>
                                @else
                                    <span class="text-gray-400 italic">—</span>
                                @endif
                            </td>

                            {{-- Forma de pago --}}
                            <td class="px-3 py-3">
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 whitespace-nowrap">
                                    {{ $pago->forma_pago_etiqueta }}
                                </span>
                            </td>

                            {{-- Moneda --}}
                            <td class="px-3 py-3 text-xs font-mono text-gray-600">
                                {{ $pago->moneda_etiqueta }}
                            </td>

                            {{-- Tasa --}}
                            <td class="px-3 py-3 text-right text-xs">
                                @if ($pago->tasa_momento)
                                    <div class="font-semibold text-gray-700">
                                        {{ number_format($pago->tasa_momento, 2) }}
                                    </div>
                                    @if ($pago->tasa)
                                        <div class="text-[10px] text-gray-500">
                                            {{ $pago->tasa->etiqueta }}
                                        </div>
                                    @endif
                                @else
                                    <span class="text-gray-400">—</span>
                                @endif
                            </td>

                            {{-- Monto --}}
                            <td class="px-3 py-3 text-right font-semibold {{ $pago->esta_anulado ? 'text-gray-400 line-through' : 'text-gray-800' }}">
                                {{ $pago->monto_formateado }}
                            </td>

                            {{-- En Bs --}}
                            <td class="px-3 py-3 text-right font-bold {{ $pago->esta_anulado ? 'text-gray-400 line-through' : 'text-teal-700' }}">
                                Bs {{ number_format($pago->monto_bs, 2) }}
                            </td>

                            {{-- Referencia --}}
                            <td class="px-3 py-3 text-xs text-gray-500">
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

                            {{-- Registrado por --}}
                            <td class="px-3 py-3 text-xs text-gray-600">
                                {{ $pago->registradoPor?->nombre_mostrar ?? 'N/A' }}
                            </td>

                            {{-- Acciones --}}
                            <td class="px-3 py-3 text-right whitespace-nowrap">
                                @if ($pago->esta_anulado)
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800">
                                            Anulado
                                        </span>
                                        @if ($pago->anuladoPor)
                                            <span class="text-[10px] text-gray-500">
                                                por {{ $pago->anuladoPor->nombre_mostrar }}
                                            </span>
                                        @endif
                                        @if ($pago->anulado_en)
                                            <span class="text-[10px] text-gray-400">
                                                {{ $pago->anulado_en->format('d/m H:i') }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    @php
                                        $caja = $pago->caja;
                                        $puedeGestionar = auth()->user()->puedeVerTodo() && $caja && $caja->estado === 'ABIERTA';
                                    @endphp
                                    @if ($puedeGestionar)
                                        <div class="inline-flex items-center space-x-1">
                                            {{-- Editar --}}
                                            <button wire:click="$dispatch('editar-pago', { pagoId: {{ $pago->id }} })"
                                                    title="Editar pago"
                                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>

                                            {{-- Anular --}}
                                            <button wire:click="$dispatch('anular-pago', { pagoId: {{ $pago->id }} })"
                                                    title="Anular pago"
                                                    class="p-1.5 text-red-600 hover:bg-red-50 rounded-md">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/>
                                                </svg>
                                            </button>
                                        </div>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-4 py-12 text-center text-gray-500 text-sm">
                                No hay pagos con los filtros actuales.
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