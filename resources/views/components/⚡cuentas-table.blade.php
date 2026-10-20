<?php

use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Cuenta;
use App\Models\Banco;

new class extends Component
{
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $bancoId = '';

    #[Url(history: true)]
    public string $tipo = '';

    #[Url(history: true)]
    public string $estado = 'activas'; // activas | inactivas | todas

    public function updatingSearch(): void {}
    public function updatingBancoId(): void {}
    public function updatingTipo(): void {}
    public function updatingEstado(): void {}

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'bancoId', 'tipo']);
        $this->estado = 'activas';
    }

    #[On('cuenta-guardada')]
    #[On('transferencia-registrada')]
    #[On('movimiento-registrado')]
    public function refrescar(): void {}

    public function with(): array
    {
        $query = Cuenta::with(['banco', 'comisiones']);

        // Filtros
        if ($this->search !== '') {
            $term = $this->search;
            $query->where(function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('numero_cuenta', 'like', "%{$term}%")
                  ->orWhere('titular', 'like', "%{$term}%")
                  ->orWhere('ci', 'like', "%{$term}%");
            });
        }

        if ($this->bancoId !== '') {
            $query->where('banco_id', $this->bancoId);
        }

        if ($this->tipo !== '') {
            $query->where('tipo', $this->tipo);
        }

        if ($this->estado === 'activas') {
            $query->where('activa', true);
        } elseif ($this->estado === 'inactivas') {
            $query->where('activa', false);
        }

        return [
            'cuentas' => $query->orderByDesc('es_predeterminada')->orderBy('nombre')->get(),
            'bancos' => Banco::where('activo', true)->orderBy('nombre')->get(),
        ];
    }

    public function toggleActiva(int $id): void
    {
        $cuenta = Cuenta::find($id);
        if (!$cuenta) return;

        $cuenta->update(['activa' => !$cuenta->activa]);
        session()->flash('success', 'Estado actualizado.');
    }

    public function marcarPredeterminada(int $id): void
    {
        $cuenta = Cuenta::find($id);
        if (!$cuenta) return;

        Cuenta::where('id', '!=', $id)->update(['es_predeterminada' => false]);
        $cuenta->update(['es_predeterminada' => true, 'activa' => true]);

        session()->flash('success', "Cuenta '{$cuenta->nombre}' marcada como predeterminada.");
    }
};
?>

<div>
    {{-- Barra de búsqueda y filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-4 mb-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3">
            {{-- Búsqueda --}}
            <div class="relative">
                <input type="text"
                       wire:model.live.debounce.400ms="search"
                       placeholder="Buscar nombre, número o RIF..."
                       class="w-full pl-9 pr-9 py-2 border border-gray-300 rounded-lg text-sm">
                <svg class="w-4 h-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                @if ($search !== '')
                    <button wire:click="$set('search', '')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                @endif
            </div>

            {{-- Banco --}}
            <select wire:model.live="bancoId"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Todos los bancos</option>
                @foreach ($bancos as $b)
                    <option value="{{ $b->id }}">{{ $b->nombre }}</option>
                @endforeach
            </select>

            {{-- Tipo --}}
            <select wire:model.live="tipo"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Todos los tipos</option>
                <option value="EFECTIVO">Efectivo</option>
                <option value="BANCO">Banco</option>
                <option value="DIVISA">Divisa</option>
                <option value="OTRO">Otro</option>
            </select>

            {{-- Estado --}}
            <select wire:model.live="estado"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="activas">Solo activas</option>
                <option value="inactivas">Solo inactivas</option>
                <option value="todas">Todas</option>
            </select>
        </div>

        <div class="flex justify-between items-center mt-3 pt-3 border-t border-gray-100">
            <div class="text-xs text-gray-500">
                {{ $cuentas->count() }} cuenta(s)
                @if ($search !== '' || $bancoId !== '' || $tipo !== '')
                    · <button wire:click="limpiarFiltros" class="text-teal-600 hover:underline">Limpiar filtros</button>
                @endif
            </div>

            <button type="button"
                    onclick="Livewire.dispatch('nuevo-movimiento')"
                    class="px-4 py-2 bg-cyan-600 hover:bg-cyan-700 text-white rounded-lg text-sm font-medium inline-flex items-center">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                Nuevo Movimiento
            </button>
        </div>
    </div>

    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Grid de cuentas --}}
    @if ($cuentas->isEmpty())
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center text-gray-500">
            No hay cuentas que coincidan con los filtros.
        </div>
    @else
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
            @foreach ($cuentas as $cuenta)
                <div wire:key="cuenta-{{ $cuenta->id }}"
                     class="bg-white rounded-2xl shadow-sm border overflow-hidden
                            {{ $cuenta->es_predeterminada ? 'border-teal-300 ring-1 ring-teal-100' : 'border-gray-100' }}">

                    {{-- Encabezado --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b
                                {{ $cuenta->es_predeterminada ? 'bg-teal-50/50 border-teal-100' : 'bg-gray-50/50 border-gray-100' }}">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-sm
                                        {{ $cuenta->activa ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-500' }}">
                                {{ strtoupper(substr($cuenta->nombre, 0, 2)) }}
                            </div>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h3 class="text-base font-bold text-gray-800">{{ $cuenta->nombre }}</h3>
                                    @if ($cuenta->es_predeterminada)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-teal-100 text-teal-800">
                                            Predeterminada
                                        </span>
                                    @endif
                                    @if (!$cuenta->activa)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">
                                            Inactiva
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-gray-500 mt-0.5">
                                    {{ $cuenta->tipo_etiqueta }}
                                    @if ($cuenta->banco)
                                        · {{ $cuenta->banco->nombre }}
                                    @endif
                                    · {{ $cuenta->moneda_etiqueta }}
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-1">
                            @if (!$cuenta->es_predeterminada && $cuenta->activa)
                                <button wire:click="marcarPredeterminada({{ $cuenta->id }})"
                                        title="Marcar como predeterminada"
                                        class="p-1.5 text-slate-500 hover:bg-slate-100 rounded-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </button>
                            @endif
                            <button wire:click="$dispatch('editar-cuenta', { id: {{ $cuenta->id }} })"
                                    title="Editar"
                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Saldo grande --}}
                    <div class="px-5 py-4 border-b border-gray-100">
                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Saldo Actual</p>
                        <p class="text-2xl font-bold text-gray-800 mt-1">{{ $cuenta->saldo_formateado }}</p>
                    </div>

                    {{-- Datos --}}
                    <div class="px-5 py-4 space-y-2">
                        @if ($cuenta->numero_cuenta)
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500 text-xs">Número:</span>
                                <span class="font-mono text-gray-800 text-xs">{{ $cuenta->numero_cuenta }}</span>
                            </div>
                        @endif
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 text-xs">Titular:</span>
                            <span class="text-gray-800 text-right text-xs">{{ $cuenta->titular ?? '—' }}</span>
                        </div>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500 text-xs">RIF:</span>
                            <span class="text-gray-800 font-mono text-xs">{{ $cuenta->lci }}-{{ $cuenta->ci }}</span>
                        </div>
                    </div>

                    {{-- Comisiones --}}
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                        <p class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider mb-2">
                            Comisiones ({{ $cuenta->comisiones->count() }})
                        </p>

                        @if ($cuenta->comisiones->isEmpty())
                            <p class="text-xs text-gray-400 italic">Sin comisiones configuradas</p>
                        @else
                            <div class="space-y-1">
                                @foreach ($cuenta->comisiones->sortBy('forma_pago') as $comision)
                                    <div class="flex items-center justify-between text-xs py-1">
                                        <span class="text-gray-700">{{ $comision->forma_pago_etiqueta }}</span>
                                        <div class="flex items-center gap-2">
                                            @if ($comision->porcentaje_ingreso > 0 || $comision->monto_fijo_ingreso > 0)
                                                <span class="px-1.5 py-0.5 rounded bg-green-100 text-green-800 text-[10px]">
                                                    ↓ {{ $comision->descripcion_ingreso }}
                                                </span>
                                            @endif
                                            @if ($comision->porcentaje_egreso > 0 || $comision->monto_fijo_egreso > 0)
                                                <span class="px-1.5 py-0.5 rounded bg-red-100 text-red-800 text-[10px]">
                                                    ↑ {{ $comision->descripcion_egreso }}
                                                </span>
                                            @endif
                                            @if ($comision->porcentaje_ingreso == 0 && $comision->monto_fijo_ingreso == 0
                                                && $comision->porcentaje_egreso == 0 && $comision->monto_fijo_egreso == 0)
                                                <span class="text-gray-400 text-[10px]">Sin comisión</span>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Footer --}}
                    <div class="px-5 py-3 border-t border-gray-100 bg-gray-50/30 flex justify-between items-center">
                        <button wire:click="toggleActiva({{ $cuenta->id }})"
                                class="text-xs font-medium {{ $cuenta->activa ? 'text-gray-500 hover:text-red-600' : 'text-teal-600 hover:text-teal-700' }}">
                            {{ $cuenta->activa ? 'Desactivar' : 'Activar' }}
                        </button>
                        <div class="flex items-center gap-3">
                            <a href="{{ route('tesoreria.movimientos.index', ['cuentaId' => $cuenta->id]) }}"
                            class="text-xs font-medium text-slate-600 hover:text-slate-800 inline-flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                Ver movimientos
                            </a>

                            <button type="button"
                                    onclick="Livewire.dispatch('nuevo-movimiento', { cuentaOrigenId: {{ $cuenta->id }} })"
                                    class="text-xs font-medium text-cyan-600 hover:text-cyan-700 inline-flex items-center">
                                <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                Nuevo Movimiento
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>