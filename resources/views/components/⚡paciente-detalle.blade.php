<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Paciente;

new class extends Component
{
    public Paciente $paciente;
    public ?string $mensajeExito = null;

    public function mount(int $pacienteId): void
    {
        $this->paciente = Paciente::with([
            'alergias.alergeno',
            'condiciones.condicion',
            'consultas.medico',
        ])->findOrFail($pacienteId);
    }

    #[On('paciente-guardado')]
    public function refrescarPaciente(): void
    {
        $this->recargar();
        $this->mensajeExito = 'Paciente actualizado correctamente.';
    }

    #[On('condicion-guardada')]
    public function refrescarCondiciones(): void
    {
        $this->recargar();
        $this->mensajeExito = 'Condición registrada correctamente.';
    }

    #[On('consulta-guardada')]
    public function refrescarConsultas(): void
    {
        $this->recargar();
        $this->mensajeExito = 'Consulta registrada correctamente.';
    }

    #[On('alergia-guardada')]
    public function refrescarAlergias(): void
    {
        $this->recargar();
        $this->mensajeExito = 'Alergia registrada correctamente.';
    }

    private function recargar(): void
    {
        $this->paciente->refresh();
        $this->paciente->load([
            'alergias.alergeno',
            'condiciones.condicion',
            'consultas.medico',
        ]);
    }
};
?>

<div x-data="{ tab: 'datos' }">

    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div class="flex items-center space-x-4">
            <div class="w-14 h-14 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-lg">
                {{ strtoupper(substr($paciente->nombre, 0, 1) . substr($paciente->apellido, 0, 1)) }}
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-800">
                    {{ $paciente->nombre }} {{ $paciente->apellido }}
                </h1>
                <p class="text-sm text-gray-500">
                    {{ $paciente->lci }}-{{ $paciente->ci }}
                    · {{ $paciente->fecha_nacimiento?->age ?? '—' }} años
                    · {{ ucfirst($paciente->genero ?? 'N/A') }}
                </p>
            </div>
        </div>

        <div class="flex gap-2">
            <a href="{{ route('pacientes.index') }}"
               class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                ← Volver
            </a>
            <button type="button"
                    onclick="Livewire.dispatch('editar-paciente', { id: {{ $paciente->id }} })"
                    class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-lg text-sm font-medium">
                Editar Paciente
            </button>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        {{-- Barra de tabs --}}
        <div class="border-b border-gray-100 flex overflow-x-auto">
            <button @click="tab = 'datos'"
                    :class="tab === 'datos' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-6 py-4 text-sm font-semibold border-b-2 transition whitespace-nowrap">
                Datos Generales
            </button>
            <button @click="tab = 'condiciones'"
                    :class="tab === 'condiciones' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-6 py-4 text-sm font-semibold border-b-2 transition whitespace-nowrap">
                Condiciones
                <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full bg-teal-100 text-teal-700">
                    {{ $paciente->condiciones->count() }}
                </span>
            </button>
            <button @click="tab = 'alergias'"
                    :class="tab === 'alergias' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-6 py-4 text-sm font-semibold border-b-2 transition whitespace-nowrap">
                Alergias
                <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full bg-purple-100 text-purple-700">
                    {{ $paciente->alergias->count() }}
                </span>
            </button>
            <button @click="tab = 'consultas'"
                    :class="tab === 'consultas' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700'"
                    class="px-6 py-4 text-sm font-semibold border-b-2 transition whitespace-nowrap">
                Consultas
                <span class="ml-1.5 px-2 py-0.5 text-xs rounded-full bg-blue-100 text-blue-700">
                    {{ $paciente->consultas->count() }}
                </span>
            </button>
        </div>

        {{-- Contenido --}}
        <div class="p-6">

            {{-- TAB: DATOS GENERALES --}}
            <div x-show="tab === 'datos'" x-cloak>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-5">
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Teléfono</p>
                        <p class="text-sm text-gray-800 mt-0.5">{{ $paciente->telefono ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Email</p>
                        <p class="text-sm text-gray-800 mt-0.5">{{ $paciente->email ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-400 uppercase">Estado</p>
                        <p class="text-sm mt-0.5">
                            @if ($paciente->estado === 'activo')
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">Activo</span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">Inactivo</span>
                            @endif
                        </p>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-xs font-semibold text-gray-400 uppercase">Dirección</p>
                        <p class="text-sm text-gray-800 mt-0.5">{{ $paciente->direccion ?? '—' }}</p>
                    </div>
                    <div class="sm:col-span-2 lg:col-span-3">
                        <p class="text-xs font-semibold text-gray-400 uppercase">Antecedentes Familiares</p>
                        <p class="text-sm text-gray-800 mt-0.5 whitespace-pre-wrap">
                            {{ $paciente->antecedentes_familiares ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            {{-- TAB: CONDICIONES --}}
            <div x-show="tab === 'condiciones'" x-cloak>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-700">Condiciones Registradas</h3>
                    <button type="button"
                            onclick="Livewire.dispatch('abrir-condicion-form', { pacienteId: {{ $paciente->id }} })"
                            class="px-3 py-1.5 bg-teal-600 hover:bg-teal-700 text-white text-xs font-medium rounded-lg">
                        + Agregar Condición
                    </button>
                </div>

                @if ($paciente->condiciones->isEmpty())
                    <div class="py-10 text-center text-sm text-gray-500">
                        No hay condiciones registradas para este paciente.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-gray-500 border-b border-gray-100">
                                    <th class="pb-2 px-2">Condición</th>
                                    <th class="pb-2 px-2">Severidad</th>
                                    <th class="pb-2 px-2">Estado</th>
                                    <th class="pb-2 px-2">Diagnóstico</th>
                                    <th class="pb-2 px-2">Próximo Control</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                @foreach ($paciente->condiciones as $cp)
                                    <tr wire:key="cond-{{ $cp->id }}">
                                        <td class="py-3 px-2">
                                            <div class="font-medium text-gray-800">{{ $cp->condicion?->nombre ?? '—' }}</div>
                                            <div class="text-xs text-gray-500">{{ $cp->condicion?->codigo_cie10 ?? '' }}</div>
                                        </td>
                                        <td class="py-3 px-2">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                @if($cp->severidad === 'LEVE') bg-green-100 text-green-800
                                                @elseif($cp->severidad === 'MODERADA') bg-yellow-100 text-yellow-800
                                                @elseif($cp->severidad === 'GRAVE') bg-orange-100 text-orange-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst(strtolower($cp->severidad)) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-2 text-gray-700">{{ str_replace('_', ' ', $cp->estado) }}</td>
                                        <td class="py-3 px-2 text-gray-600">
                                            {{ $cp->fecha_diagnostico?->format('d/m/Y') ?? '—' }}
                                        </td>
                                        <td class="py-3 px-2 text-gray-600">
                                            {{ $cp->proximo_control_sugerido?->format('d/m/Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            {{-- TAB: ALERGIAS --}}
            <div x-show="tab === 'alergias'" x-cloak>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-700">Alergias Registradas</h3>
                    <button type="button"
                            onclick="Livewire.dispatch('abrir-alergia-form', { pacienteId: {{ $paciente->id }} })"
                            class="px-3 py-1.5 bg-purple-600 hover:bg-purple-700 text-white text-xs font-medium rounded-lg">
                        + Agregar Alergia
                    </button>
                </div>

                @if ($paciente->alergias->isEmpty())
                    <div class="py-10 text-center text-sm text-gray-500">
                        No hay alergias registradas para este paciente.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-gray-500 border-b border-gray-100">
                                    <th class="pb-2 px-2">Alérgeno</th>
                                    <th class="pb-2 px-2">Tipo de Reacción</th>
                                    <th class="pb-2 px-2">Severidad</th>
                                    <th class="pb-2 px-2">Estado</th>
                                    <th class="pb-2 px-2">Diagnóstico</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                @foreach ($paciente->alergias as $ap)
                                    <tr wire:key="alerg-{{ $ap->id }}">
                                        <td class="py-3 px-2">
                                            <div class="font-medium text-gray-800">{{ $ap->alergeno?->nombre ?? '—' }}</div>
                                            <div class="text-xs text-gray-500">{{ $ap->alergeno?->categoria ?? '' }}</div>
                                        </td>
                                        <td class="py-3 px-2 text-gray-700">
                                            {{ ucfirst(strtolower($ap->tipo_reaccion)) }}
                                        </td>
                                        <td class="py-3 px-2">
                                            <span class="px-2 py-0.5 rounded-full text-xs font-medium
                                                @if($ap->severidad === 'LEVE') bg-green-100 text-green-800
                                                @elseif($ap->severidad === 'MODERADA') bg-yellow-100 text-yellow-800
                                                @else bg-red-100 text-red-800 @endif">
                                                {{ ucfirst(strtolower($ap->severidad)) }}
                                            </span>
                                        </td>
                                        <td class="py-3 px-2 text-gray-700">{{ str_replace('_', ' ', $ap->estado) }}</td>
                                        <td class="py-3 px-2 text-gray-600">
                                            {{ $ap->fecha_diagnostico?->format('d/m/Y') ?? '—' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
            {{-- TAB: CONSULTAS --}}
            <div x-show="tab === 'consultas'" x-cloak>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-sm font-bold text-gray-700">Historial de Consultas</h3>
                </div>

                @if ($paciente->consultas->isEmpty())
                    <div class="py-10 text-center text-sm text-gray-500">
                        Este paciente no tiene consultas registradas.
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="text-xs uppercase tracking-wider text-gray-500 border-b border-gray-100">
                                    <th class="pb-2 px-2">Fecha</th>
                                    <th class="pb-2 px-2">Médico</th>
                                    <th class="pb-2 px-2">Motivo</th>
                                    <th class="pb-2 px-2">Diagnóstico</th>
                                    <th class="pb-2 px-2">Signos</th>
                                    <th class="pb-2 px-2 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-50 text-sm">
                                @foreach ($paciente->consultas->sortByDesc('created_at') as $consulta)
                                    <tr wire:key="consulta-{{ $consulta->id }}" class="hover:bg-gray-50">
                                        <td class="py-3 px-2 whitespace-nowrap">
                                            <div class="font-medium text-gray-800">
                                                {{ $consulta->created_at->format('d/m/Y') }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ $consulta->created_at->format('h:i A') }}
                                            </div>
                                        </td>
                                        <td class="py-3 px-2">
                                            <div class="text-gray-800">
                                                {{ $consulta->medico?->nombre }} {{ $consulta->medico?->apellido }}
                                            </div>
                                        </td>
                                        <td class="py-3 px-2 text-gray-600 max-w-xs truncate" title="{{ $consulta->motivo }}">
                                            {{ $consulta->motivo ?? '—' }}
                                        </td>
                                        <td class="py-3 px-2 text-gray-600 max-w-xs truncate" title="{{ $consulta->diagnostico }}">
                                            {{ $consulta->diagnostico ? \Str::limit($consulta->diagnostico, 50) : '—' }}
                                        </td>
                                        <td class="py-3 px-2 whitespace-nowrap">
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
                                        <td class="py-3 px-2 text-right whitespace-nowrap">
                                            @if ($consulta->cita_id)
                                                <button type="button"
                                                        wire:click="$dispatch('abrir-consulta-form', { citaId: {{ $consulta->cita_id }} })"
                                                        title="Ver detalle de la consulta"
                                                        class="p-1.5 text-blue-600 hover:bg-blue-50 rounded-md">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>