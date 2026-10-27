<?php

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\MovimientoCuenta;
use App\Models\Cuenta;
use Carbon\Carbon;

new class extends Component
{
    use WithPagination;

    public $cuenta_id = '';
    public $tipo = '';
    public $fecha_desde = '';
    public $fecha_hasta = '';

    public function mount(): void
    {
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function updatingCuentaId(): void { $this->resetPage(); }
    public function updatingTipo(): void { $this->resetPage(); }
    public function updatingFechaDesde(): void { $this->resetPage(); }
    public function updatingFechaHasta(): void { $this->resetPage(); }

    public function with(): array
    {
        $query = MovimientoCuenta::with(['cuenta.banco'])
            ->whereBetween('fecha', [
                Carbon::parse($this->fecha_desde)->startOfDay(),
                Carbon::parse($this->fecha_hasta)->endOfDay()
            ]);

        if ($this->cuenta_id !== '') {
            $query->where('cuenta_id', $this->cuenta_id);
        }

        if ($this->tipo !== '') {
            $query->where('tipo', $this->tipo);
        }

        return [
            'movimientos' => $query->orderByDesc('fecha')->paginate(20),
            'cuentas' => Cuenta::where('activa', true)->with('banco')->orderBy('nombre')->get(),
        ];
    }
};
?>

<div class="space-y-4">
    
    {{-- Filtros --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 grid grid-cols-1 sm:grid-cols-4 gap-3">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Cuenta</label>
            <select wire:model.live="cuenta_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Seleccione una cuenta...</option>
                @foreach ($cuentas as $c)
                    <option value="{{ $c->id }}">{{ $c->nombre }} ({{ strtoupper($c->moneda) }})</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
            <select wire:model.live="tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Todos</option>
                <option value="INGRESO">Solo Ingresos</option>
                <option value="EGRESO">Solo Egresos</option>
            </select>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
            <input type="date" wire:model.live="fecha_desde" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
            <input type="date" wire:model.live="fecha_hasta" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
    </div>

    @if($cuenta_id === '')
        <div class="p-6 bg-slate-50 border border-slate-200 rounded-xl text-center text-slate-500">
            Selecciona una cuenta para ver su extracto histórico.
        </div>
    @else
        {{-- Tabla --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-500 font-semibold border-b border-gray-100">
                        <tr>
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Descripción / Origen</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                            <th class="px-4 py-3 text-right">Saldo Restante</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($movimientos as $mov)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    {{ $mov->fecha->format('d/m/Y h:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">{{ $mov->descripcion }}</div>
                                    <div class="text-xs text-gray-500 uppercase">{{ $mov->origen_tipo }}</div>
                                </td>
                                <td class="px-4 py-3 text-right font-bold {{ $mov->tipo === 'INGRESO' ? 'text-emerald-600' : 'text-red-600' }}">
                                    {{ $mov->tipo === 'INGRESO' ? '+' : '-' }} 
                                    {{ $mov->cuenta->moneda === 'bs' ? 'Bs ' : '$ ' }}{{ number_format($mov->monto, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-700">
                                    {{ $mov->cuenta->moneda === 'bs' ? 'Bs ' : '$ ' }}{{ number_format($mov->saldo_despues, 2) }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-gray-500">
                                    No hay movimientos para esta cuenta en el período seleccionado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($movimientos->hasPages())
                <div class="px-4 py-3 border-t bg-gray-50">{{ $movimientos->links() }}</div>
            @endif
        </div>
    @endif
</div>