<?php

use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;
use Livewire\Attributes\On;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Medico;

new class extends Component
{
    use WithPagination;

    #[Url(history: true)]
    public string $tab = 'pendientes'; // pendientes | atendidas

    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(history: true)]
    public string $medicoId = '';

    public function updatingSearch(): void { $this->resetPage(); }
    public function updatingMedicoId(): void { $this->resetPage(); }
    public function updatingTab(): void { $this->resetPage(); }

    // Al guardar una consulta desde el modal, refresca la tabla
    #[On('consulta-guardada')]
    public function refrescar(): void {}

    public function with(): array
    {
        $medicos = Medico::where('estado', 'ACTIVO')->orderBy('apellido')->get();

        $contadorPendientesQuery = Cita::where('estado', 'CONFIRMADA')
            ->whereDoesntHave('consulta');

        $contadorAtendidasQuery = Consulta::query();

        if (auth()->user()?->esMedico() && !auth()->user()->puedeVerTodo()) {
            $contadorPendientesQuery->where('medico_id', auth()->user()->medico->id);
            $contadorAtendidasQuery->where('medico_id', auth()->user()->medico->id);
        }

        $contadorPendientes = $contadorPendientesQuery->count();
        $contadorAtendidas = $contadorAtendidasQuery->count();

        if ($this->tab === 'pendientes') {
            // Citas CONFIRMADAS que aún no tienen consulta registrada
            $query = Cita::with(['paciente', 'medico.especialidades'])
                ->where('estado', 'CONFIRMADA')
                ->whereDoesntHave('consulta');

            if (auth()->user()?->esMedico() && !auth()->user()->puedeVerTodo()) {
                $query->where('medico_id', auth()->user()->medico->id);
            }

            if ($this->search !== '') {
                $term = $this->search;
                $query->whereHas('paciente', function ($q) use ($term) {
                    $q->where('nombre', 'like', "%{$term}%")
                      ->orWhere('apellido', 'like', "%{$term}%")
                      ->orWhere('ci', 'like', "%{$term}%");
                });
            }

            if ($this->medicoId !== '') {
                $query->where('medico_id', $this->medicoId);
            }

            return [
                'pendientes' => $query->orderBy('fecha', 'asc')->paginate(15),
                'atendidas' => collect(),
                'medicos' => $medicos,
                'contadorPendientes' => $contadorPendientes,
                'contadorAtendidas' => $contadorAtendidas,
            ];
        }

        // Tab atendidas: consultas ya creadas
        $query = Consulta::with(['paciente', 'medico', 'cita']);
        if (auth()->user()?->esMedico() && !auth()->user()->puedeVerTodo()) {
            $query->where('medico_id', auth()->user()->medico->id);
        }

        if ($this->search !== '') {
            $term = $this->search;
            $query->whereHas('paciente', function ($q) use ($term) {
                $q->where('nombre', 'like', "%{$term}%")
                  ->orWhere('apellido', 'like', "%{$term}%")
                  ->orWhere('ci', 'like', "%{$term}%");
            });
        }

        if ($this->medicoId !== '') {
            $query->where('medico_id', $this->medicoId);
        }

        return [
            'pendientes' => collect(),
            'atendidas' => $query->orderBy('created_at', 'desc')->paginate(15),
            'medicos' => $medicos,
            'contadorPendientes' => $contadorPendientes,
            'contadorAtendidas' => $contadorAtendidas,
        ];
    }
};
?>

<div>
    {{-- Tabs --}}
    <div class="border-b border-gray-100 mb-6">
        <div class="flex gap-4">
            <button wire:click="$set('tab', 'pendientes')"
                    class="px-4 py-3 text-sm font-semibold border-b-2 transition flex items-center gap-2
                        {{ $tab === 'pendientes' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Pendientes de Atender
                <span class="px-2 py-0.5 text-xs rounded-full {{ $tab === 'pendientes' ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $contadorPendientes }}
                </span>
            </button>
            <button wire:click="$set('tab', 'atendidas')"
                    class="px-4 py-3 text-sm font-semibold border-b-2 transition flex items-center gap-2
                        {{ $tab === 'atendidas' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                Atendidas
                <span class="px-2 py-0.5 text-xs rounded-full {{ $tab === 'atendidas' ? 'bg-teal-100 text-teal-700' : 'bg-gray-100 text-gray-600' }}">
                    {{ $contadorAtendidas }}
                </span>
            </button>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-6">
        <div class="relative">
            <input type="text"
                   wire:model.live.debounce.400ms="search"
                   placeholder="Buscar paciente..."
                   class="w-full pl-9 pr-3 py-2 border border-gray-300 rounded-lg text-sm">
            <svg class="w-4 h-4 text-gray-400 absolute left-3 top-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
        </div>

        @if (auth()->user()?->puedeVerTodo())
            <select wire:model.live="medicoId"
                    class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
                <option value="">Todos los médicos</option>
                @foreach ($medicos as $m)
                    <option value="{{ $m->id }}">{{ $m->nombre }} {{ $m->apellido }}</option>
                @endforeach
            </select>
        @endif
    </div>

    {{-- Mensajes --}}
    @if (session('success'))
        <div class="mb-4 p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    {{-- Loading --}}
    <div wire:loading class="text-xs text-blue-600 font-medium mb-3">
        Cargando...
    </div>

    {{-- ============================================================ --}}
    {{-- TAB: PENDIENTES DE ATENDER --}}
    {{-- ============================================================ --}}
    @if ($tab === 'pendientes')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="px-4 py-3">Fecha / Hora</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Médico</th>
                            <th class="px-4 py-3">Motivo</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse ($pendientes as $cita)
                            <tr wire:key="pend-{{ $cita->id }}" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-800">{{ $cita->fecha->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $cita->fecha->format('h:i A') }}</div>
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
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button wire:click="$dispatch('abrir-consulta-form', { citaId: {{ $cita->id }} })"
                                            class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium rounded-md inline-flex items-center transition">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                        Atender
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-gray-500 text-sm">
                                    No hay consultas pendientes. Todas las citas confirmadas han sido atendidas. 🎉
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($pendientes->hasPages())
                <div class="px-4 py-3 border-t bg-gray-50">
                    {{ $pendientes->links() }}
                </div>
            @endif
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- TAB: ATENDIDAS --}}
    {{-- ============================================================ --}}
    @if ($tab === 'atendidas')
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="px-4 py-3">Estado</th>
                            <th class="px-4 py-3">Fecha Registro</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Médico</th>
                            <th class="px-4 py-3">Diagnóstico</th>
                            <th class="px-4 py-3">Signos</th>
                            <th class="px-4 py-3 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @forelse ($atendidas as $consulta)
                            <tr wire:key="aten-{{ $consulta->id }}" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="flex items-center gap-1">
                                        <span class="inline-flex items-center text-green-600" title="Completada">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        </span>
                                        @if ($consulta->fue_editada)
                                            <span class="px-2 py-0.5 text-[10px] font-semibold rounded-full bg-amber-100 text-amber-700"
                                                title="Editada el {{ $consulta->editada_en->format('d/m/Y h:i A') }} por {{ $consulta->editor?->username ?? '—' }}">
                                                Editada
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-800">{{ $consulta->created_at->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $consulta->created_at->format('h:i A') }}</div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">
                                        {{ $consulta->paciente?->nombre }} {{ $consulta->paciente?->apellido }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $consulta->paciente?->lci }}-{{ $consulta->paciente?->ci }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-800">
                                    {{ $consulta->medico?->nombre }} {{ $consulta->medico?->apellido }}
                                </td>
                                <td class="px-4 py-3 text-gray-600 max-w-xs truncate" title="{{ $consulta->diagnostico }}">
                                    {{ $consulta->diagnostico ? \Str::limit($consulta->diagnostico, 60) : '—' }}
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-xs text-gray-600">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($consulta->tension_formateada)
                                            <span class="px-2 py-0.5 rounded-full bg-blue-50 text-blue-700 text-[10px] font-medium">
                                                TA: {{ $consulta->tension_formateada }}
                                            </span>
                                        @endif
                                        @if ($consulta->temperatura)
                                            <span class="px-2 py-0.5 rounded-full bg-orange-50 text-orange-700 text-[10px] font-medium">
                                                T: {{ $consulta->temperatura }}°C
                                            </span>
                                        @endif
                                        @if ($consulta->imc)
                                            <span class="px-2 py-0.5 rounded-full bg-purple-50 text-purple-700 text-[10px] font-medium">
                                                IMC: {{ $consulta->imc }}
                                            </span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <div class="inline-flex items-center space-x-1">
                                        <a href="{{ route('pacientes.show', $consulta->paciente_id) }}"
                                        title="Ver paciente"
                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </a>

                                        @if ($consulta->cita_id)
                                            <button wire:click="$dispatch('abrir-consulta-form', { citaId: {{ $consulta->cita_id }} })"
                                                    title="Ver / Editar consulta"
                                                    class="p-1.5 text-amber-600 hover:bg-amber-50 rounded-md">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                        @endif
                                        {{-- Imprimir PDF --}}
                                        <a href="{{ url('/consultas/' . $consulta->id . '/pdf') }}"
                                        target="_blank"
                                        title="Imprimir Receta"
                                        class="p-1.5 text-slate-600 hover:bg-slate-100 rounded-md">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-gray-500 text-sm">
                                    No hay consultas atendidas registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($atendidas->hasPages())
                <div class="px-4 py-3 border-t bg-gray-50">
                    {{ $atendidas->links() }}
                </div>
            @endif
        </div>
    @endif
</div>