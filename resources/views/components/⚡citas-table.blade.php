<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Cita;
use App\Models\Medico;

new class extends Component
{
    use WithPagination;

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $estado = '';

    #[Url(history: true)]
    public string $medicoId = '';

    #[Url(history: true)]
    public string $fechaDesde = '';

    #[Url(history: true)]
    public string $fechaHasta = '';

    #[Url(history: true)]
    public string $rango = 'proximas'; // proximas | hoy | semana | todas

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingEstado(): void { $this->resetPage(); }
    public function updatingMedicoId(): void { $this->resetPage(); }
    public function updatingFechaDesde(): void { $this->resetPage(); }
    public function updatingFechaHasta(): void { $this->resetPage(); }
    public function updatingRango(): void { $this->resetPage(); }

    public function limpiarFiltros(): void
    {
        $this->reset(['search', 'estado', 'medicoId', 'fechaDesde', 'fechaHasta']);
        $this->rango = 'proximas';
        $this->resetPage();
    }

    #[On('cita-guardada')]
    public function refrescar(): void {}

    public function cambiarEstado(int $citaId, string $nuevoEstado): void
    {
        $cita = Cita::find($citaId);

        if (!$cita) return;

        // Validaciones de transición
        $permitidos = [
            'PENDIENTE'    => ['CONFIRMADA', 'CANCELADA', 'NO_ASISTIO'],
            'CONFIRMADA'   => ['CANCELADA', 'NO_ASISTIO'],
            'CANCELADA'    => [],
            'NO_ASISTIO'   => [],
        ];

        if (!in_array($nuevoEstado, $permitidos[$cita->estado] ?? [])) {
            session()->flash('error', 'Transición de estado no permitida.');
            return;
        }

        $cita->update(['estado' => $nuevoEstado]);
        session()->flash('success', "Cita marcada como {$nuevoEstado}.");
    }

    public function with(): array
    {
        $query = Cita::with(['paciente', 'medico.especialidades','tarifa']);
        // Filtrar por médico si es un médico puro (sin admin/recepcion)
        if (auth()->user()?->esMedico() && !auth()->user()->puedeVerTodo()) {
            $query->where('medico_id', auth()->user()->medico->id);
        }

        // Rango rápido
        switch ($this->rango) {
            case 'hoy':
                $query->hoy();
                break;
            case 'semana':
                $query->whereBetween('fecha', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'proximas':
                $query->where('fecha', '>=', now()->startOfDay());
                break;
            case 'todas':
            default:
                // sin filtro
                break;
        }

        // Búsqueda por paciente
        if ($this->search !== '') {
            $term = $this->search;
            $query->whereHas('paciente', function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('apellido', 'like', "%{$term}%")
                  ->orWhere('ci', 'like', "%{$term}%");
            });
        }

        if ($this->estado !== '') {
            $query->where('estado', $this->estado);
        }

        if ($this->medicoId !== '') {
            $query->where('medico_id', $this->medicoId);
        }

        if ($this->fechaDesde !== '') {
            $query->whereDate('fecha', '>=', $this->fechaDesde);
        }

        if ($this->fechaHasta !== '') {
            $query->whereDate('fecha', '<=', $this->fechaHasta);
        }

        // Orden ascendente en próximas, descendente en todas
        $orden = $this->rango === 'todas' ? 'desc' : 'asc';

        return [
            'citas' => $query->orderBy('fecha', $orden)->paginate(15),
            'medicos' => Medico::where('estado', 'ACTIVO')->orderBy('apellido')->get(),
        ];
    }
};
?>

<div>
    {{-- Filtros rápidos --}}
    <div class="flex flex-wrap gap-2 mb-4">
        @foreach (['proximas' => 'Próximas', 'hoy' => 'Hoy', 'semana' => 'Esta semana', 'todas' => 'Todas'] as $key => $label)
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
                   placeholder="Buscar paciente..."
                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        <select wire:model.live="estado"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos los estados</option>
            <option value="PENDIENTE">Pendiente</option>
            <option value="CONFIRMADA">Confirmada</option>
            <option value="COMPLETADA">Completada</option>
            <option value="CANCELADA">Cancelada</option>
            <option value="NO_ASISTIO">No asistió</option>
            <option value="REPROGRAMADA">Reprogramada</option>
        </select>

        @if (auth()->user()?->puedeVerTodo())
            <select wire:model.live="medicoId"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Todos los médicos</option>
                @foreach ($medicos as $m)
                    <option value="{{ $m->id }}">{{ $m->nombre }} {{ $m->apellido }}</option>
                @endforeach
            </select>
        @endif

        <div class="flex gap-2">
            <input type="date" wire:model.live="fechaDesde"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <input type="date" wire:model.live="fechaHasta"
                   class="flex-1 px-3 py-2 border border-gray-300 rounded-lg text-sm">
        </div>
    </div>

    {{-- Mensajes --}}
    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- Loading --}}
    <div wire:loading class="text-xs text-blue-600 font-medium mb-3">
        Cargando...
    </div>

    {{-- Tabla --}}
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                        <th class="px-4 py-3">Nro. Cita</th>
                        <th class="px-4 py-3">Fecha / Hora</th>
                        <th class="px-4 py-3">Paciente</th>
                        <th class="px-4 py-3">Médico</th>
                        <th class="px-4 py-3">Motivo</th>
                        <th class="px-4 py-3">Monto</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-sm">
                    @forelse ($citas as $cita)
                        <tr wire:key="cita-{{ $cita->id }}" class="hover:bg-gray-50 transition">
                            <td class="px-4 py-3 font-mono text-xs font-semibold text-teal-700">
                                {{ $cita->numero_cita ?? 'N/A' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="font-medium text-gray-800">
                                    {{ $cita->fecha->format('d/m/Y') }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $cita->fecha->format('h:i A') }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium text-gray-800">
                                    {{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $cita->paciente?->lci }}-{{ $cita->paciente?->ci }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-gray-800">
                                    {{ $cita->medico?->nombre }} {{ $cita->medico?->apellido }}
                                </div>
                                <div class="text-xs text-gray-500">
                                    {{ $cita->medico?->especialidades->pluck('nombre')->join(', ') }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-gray-600 max-w-xs truncate">
                                {{ $cita->motivo ?? '—' }}
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @if ($cita->total)
                                    <div class="font-semibold text-gray-800">
                                        ${{ number_format($cita->total, 2) }}
                                    </div>
                                    @if ($cita->total_bs)
                                        <div class="text-xs text-gray-500">
                                            Bs {{ number_format($cita->total_bs, 2) }}
                                        </div>
                                    @endif
                                    @if ($cita->estado_pago === 'PAGADA')
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-800">
                                            Pagada
                                        </span>
                                    @elseif ($cita->estado_pago === 'PARCIAL')
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-yellow-100 text-yellow-800">
                                            Parcial
                                        </span>
                                    @elseif ($cita->estado_pago === 'ANULADA')
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                            Anulada
                                        </span>
                                    @else
                                        <span class="inline-block mt-1 px-1.5 py-0.5 rounded text-[10px] font-medium bg-orange-100 text-orange-800">
                                            Pendiente
                                        </span>
                                    @endif
                                @else
                                    <span class="text-xs text-gray-400 italic">Sin tarifa</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                @php
                                    $colores = [
                                        'PENDIENTE'    => 'bg-yellow-100 text-yellow-800',
                                        'CONFIRMADA'   => 'bg-blue-100 text-blue-800',
                                        'COMPLETADA'   => 'bg-green-100 text-green-800',
                                        'CANCELADA'    => 'bg-gray-100 text-gray-800',
                                        'NO_ASISTIO'   => 'bg-red-100 text-red-800',
                                        'REPROGRAMADA' => 'bg-purple-100 text-purple-800',
                                    ];
                                @endphp
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium {{ $colores[$cita->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst(strtolower(str_replace('_', ' ', $cita->estado))) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap">
                                <div class="inline-flex items-center space-x-1">
                                    {{-- Confirmar --}}
                                    @if (in_array($cita->estado, ['PENDIENTE', 'REPROGRAMADA']))
                                        <button wire:click="cambiarEstado({{ $cita->id }}, 'CONFIRMADA')"
                                                title="Confirmar"
                                                class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    @endif

                                    

                                    {{-- No asistió --}}
                                    @if (in_array($cita->estado, ['PENDIENTE', 'CONFIRMADA']))
                                        <button wire:click="cambiarEstado({{ $cita->id }}, 'NO_ASISTIO')"
                                                title="No asistió"
                                                class="p-1.5 text-red-600 hover:bg-red-50 rounded-md">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Cancelar --}}
                                    @if (in_array($cita->estado, ['PENDIENTE', 'CONFIRMADA', 'REPROGRAMADA']))
                                        <button wire:click="cambiarEstado({{ $cita->id }}, 'CANCELADA')"
                                                title="Cancelar"
                                                class="p-1.5 text-gray-500 hover:bg-gray-100 rounded-md">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        </button>
                                    @endif

                                    {{-- Editar --}}
                                    <button wire:click="$dispatch('editar-cita', { id: {{ $cita->id }} })"
                                            title="Editar"
                                            class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-gray-500 text-sm">
                                No hay citas que coincidan con los filtros.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($citas->hasPages())
            <div class="px-4 py-3 border-t bg-gray-50">
                {{ $citas->links() }}
            </div>
        @endif
    </div>
</div>