<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Odontograma;
use Illuminate\Support\Facades\Mail;
use App\Mail\ConsultaMail;

new class extends Component
{
    public bool $abierto = false;
    public ?int $citaId = null;
    public ?Cita $cita = null;
    public ?Consulta $consultaExistente = null;

    // Estado de edición
    public bool $soloLectura = false;
    public bool $dentroDeVentana = true;

    // Signos vitales
    public $peso = '';
    public $altura = '';
    public $tension_sistolica = '';
    public $tension_diastolica = '';
    public $temperatura = '';
    public $frecuencia_cardiaca = '';

    // Clínica
    public $motivo = '';
    public $sintomas = '';
    public $diagnostico = '';
    public $tratamiento = '';

    // Reposo
    public $reposo = 'NO';
    public $reposo_dias = '';
    public $reposo_fecha_inicio = '';
    public $reposo_fecha_fin = '';

    // Tab activo
    public string $tab = 'clinica';

    // Odontograma
    public array $datosOdontograma = [];
    public bool $odontogramaModificado = false;
    public string $tipoOdontograma = 'permanente';

    // Alertas Clínicas
    public $alergiasPaciente = [];
    public $condicionesPaciente = [];

    // Correo
    public bool $modalEmailAbierto = false;
    public string $emailDestino = '';

    #[On('abrir-consulta-form')]
    public function abrir(int $citaId): void
    {
        $this->reset();
        $this->citaId = $citaId;
        $this->cita = Cita::with(['paciente', 'medico', 'consulta'])->find($citaId);

        if (!$this->cita) {
            return;
        }

        // --- LÓGICA DE ALERTAS CLÍNICAS ---
        $paciente = $this->cita->paciente;

        if ($paciente) {
            $this->alergiasPaciente = $paciente->alergias()
                ->with('alergeno')
                ->whereIn('estado', ['ACTIVA', 'SOSPECHA', 'CONFIRMADA'])
                ->get()
                ->sortByDesc(function($alergia) {
                    return match($alergia->severidad) {
                        'GRAVE' => 3, 'MODERADA' => 2, 'LEVE' => 1, default => 0,
                    };
                });

            $this->condicionesPaciente = $paciente->condiciones()
                ->with('condicion')
                ->whereIn('estado', ['ACTIVA', 'CONTROLADA', 'EN_REMISION', 'SOSPECHA'])
                ->get()
                ->sortByDesc(function($condicion) {
                    return match($condicion->severidad) {
                        'SEVERA', 'GRAVE' => 3, 'MODERADA' => 2, 'LEVE' => 1, default => 0,
                    };
                });
        }
        // --- FIN ALERTAS ---

        $existente = $this->cita->consulta;

        if ($existente) {
            $this->consultaExistente = $existente;
            $this->cargarDesdeConsulta($existente);
            $this->dentroDeVentana = $existente->dentro_de_ventana_edicion;
            $this->soloLectura = !$existente->puedeSerEditadaPor(auth()->user());
            $this->emailDestino = $paciente->email ?? '';
        } else {
            $this->motivo = $this->cita->motivo ?? '';
            $this->emailDestino = $paciente->email ?? '';
        }

        $this->abierto = true;
    }

    private function cargarDesdeConsulta(Consulta $c): void
    {
        $this->peso = $c->peso;
        $this->altura = $c->altura;
        $this->tension_sistolica = $c->tension_sistolica;
        $this->tension_diastolica = $c->tension_diastolica;
        $this->temperatura = $c->temperatura;
        $this->frecuencia_cardiaca = $c->frecuencia_cardiaca;
        $this->motivo = $c->motivo ?? '';
        $this->sintomas = $c->sintomas ?? '';
        $this->diagnostico = $c->diagnostico ?? '';
        $this->tratamiento = $c->tratamiento ?? '';
        $this->reposo = $c->reposo ?? 'NO';
        $this->reposo_dias = $c->reposo_dias;
        $this->reposo_fecha_inicio = $c->reposo_fecha_inicio?->format('Y-m-d');
        $this->reposo_fecha_fin = $c->reposo_fecha_fin?->format('Y-m-d');
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function updatedReposoDias(): void
    {
        if ($this->reposo === 'SI' && $this->reposo_dias && $this->reposo_fecha_inicio) {
            $inicio = \Carbon\Carbon::parse($this->reposo_fecha_inicio);
            $this->reposo_fecha_fin = $inicio->copy()->addDays((int) $this->reposo_dias)->format('Y-m-d');
        }
    }

    public function updatedReposoFechaInicio(): void
    {
        if ($this->reposo === 'SI' && $this->reposo_dias && $this->reposo_fecha_inicio) {
            $inicio = \Carbon\Carbon::parse($this->reposo_fecha_inicio);
            $this->reposo_fecha_fin = $inicio->copy()->addDays((int) $this->reposo_dias)->format('Y-m-d');
        }
    }

    #[On('odontograma-sincronizado')]
    public function sincronizarOdontograma(array $datos, string $tipo = 'permanente'): void
    {
        $this->datosOdontograma = $datos;
        $this->tipoOdontograma = $tipo;
        $this->odontogramaModificado = true;
    }

    public function guardar(): void
    {
        if ($this->soloLectura) {
            session()->flash('error', 'Esta consulta ya no puede editarse.');
            return;
        }

        $datos = $this->validate([
            'peso' => 'nullable|numeric|min:0|max:500',
            'altura' => 'nullable|numeric|min:0|max:3',
            'tension_sistolica' => 'nullable|integer|min:50|max:300',
            'tension_diastolica' => 'nullable|integer|min:30|max:200',
            'temperatura' => 'nullable|numeric|min:30|max:45',
            'frecuencia_cardiaca' => 'nullable|integer|min:20|max:250',
            'motivo' => 'nullable|string',
            'sintomas' => 'nullable|string',
            'diagnostico' => 'nullable|string',
            'tratamiento' => 'nullable|string',
            'reposo' => 'required|in:SI,NO',
            'reposo_dias' => 'nullable|required_if:reposo,SI|integer|min:1|max:365',
            'reposo_fecha_inicio' => 'nullable|required_if:reposo,SI|date',
            'reposo_fecha_fin' => 'nullable|required_if:reposo,SI|date|after_or_equal:reposo_fecha_inicio',
        ]);

        foreach (['peso', 'altura', 'tension_sistolica', 'tension_diastolica', 'temperatura', 'frecuencia_cardiaca', 'reposo_dias'] as $campo) {
            $datos[$campo] = $datos[$campo] ?: null;
        }

        foreach (['motivo', 'sintomas', 'diagnostico', 'tratamiento', 'reposo_fecha_inicio', 'reposo_fecha_fin'] as $campo) {
            $datos[$campo] = $datos[$campo] ?: null;
        }

        if ($datos['reposo'] === 'NO') {
            $datos['reposo_dias'] = null;
            $datos['reposo_fecha_inicio'] = null;
            $datos['reposo_fecha_fin'] = null;
        }

        $esEdicion = $this->consultaExistente !== null;

        if ($esEdicion) {
            $this->consultaExistente->update($datos + [
                'editada_por' => auth()->id(),
                'editada_en'  => now(),
            ]);
            $consulta = $this->consultaExistente;
        } else {
            $consulta = Consulta::create($datos + [
                'cita_id'     => $this->citaId,
                'paciente_id' => $this->cita->paciente_id,
                'medico_id'   => $this->cita->medico_id,
            ]);
        }

        if ($this->odontogramaModificado && !empty($this->datosOdontograma)) {
            if ($esEdicion) {
                $odontoExistente = Odontograma::where('consulta_id', $consulta->id)->first();
                if ($odontoExistente) {
                    $odontoExistente->update(['datos' => $this->datosOdontograma, 'tipo' => $this->tipoOdontograma]);
                } else {
                    Odontograma::create([
                        'paciente_id' => $this->cita->paciente_id,
                        'consulta_id' => $consulta->id,
                        'tipo' => $this->tipoOdontograma,
                        'datos' => $this->datosOdontograma,
                    ]);
                }
            } else {
                Odontograma::create([
                    'paciente_id' => $this->cita->paciente_id,
                    'consulta_id' => $consulta->id,
                    'tipo' => $this->tipoOdontograma,
                    'datos' => $this->datosOdontograma,
                ]);
            }
        }

        if ($this->cita && $this->cita->estado !== 'COMPLETADA') {
            $this->cita->update(['estado' => 'COMPLETADA']);
        }

        session()->flash('success', $esEdicion ? 'Consulta actualizada correctamente.' : 'Consulta registrada correctamente.');

        $this->dispatch('consulta-guardada');
        $this->abierto = false;
    }

    public function enviarReceta(): void
    {
        if (!$this->consultaExistente) return;

        $this->validate([
            'emailDestino' => 'required|email'
        ]);

        try {
            Mail::to($this->emailDestino)->send(new ConsultaMail($this->consultaExistente));

            session()->flash('success_email', 'Receta enviada correctamente a ' . $this->emailDestino);
            
        } catch (\Throwable $e) {
            session()->flash('error_email', 'Error al enviar: ' . $e->getMessage());
        }
    }
};
?>

<div>
    @if ($abierto && $cita)
        {{-- Fondo --}}
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        {{-- Modal Principal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[95vh] flex flex-col relative">

                {{-- HEADER --}}
                <div class="flex items-start justify-between px-6 py-4 border-b border-gray-100 bg-white rounded-t-2xl">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">
                            {{ $soloLectura ? 'Ver Consulta' : ($consultaExistente ? 'Editar Consulta' : 'Registrar Consulta') }}
                        </h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}
                            · {{ $cita->fecha->format('d/m/Y h:i A') }}
                            · Dr(a). {{ $cita->medico?->nombre }} {{ $cita->medico?->apellido }}
                        </p>
                        @if ($consultaExistente?->fue_editada)
                            <p class="text-[11px] text-amber-600 mt-1">
                                Editada el {{ $consultaExistente->editada_en->format('d/m/Y h:i A') }}
                                por {{ $consultaExistente->editor?->username ?? '—' }}
                            </p>
                        @endif
                    </div>

                    <div class="flex items-center gap-2 ml-4">
                        @if ($consultaExistente)
                            {{-- Botón PDF --}}
                            <a href="{{ url('/consultas/' . $consultaExistente->id . '/pdf') }}" 
                               target="_blank"
                               title="Descargar / Imprimir PDF"
                               class="p-2 text-slate-500 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors border border-slate-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                            </a>
                            
                            {{-- Botón Email --}}
                            <button wire:click="$set('modalEmailAbierto', true)" 
                                    title="Enviar por correo"
                                    class="p-2 text-slate-500 hover:text-teal-600 hover:bg-teal-50 rounded-lg transition-colors border border-slate-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </button>
                        @endif

                        {{-- Botón Cerrar (X) --}}
                        <button type="button" wire:click="cerrar" class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Banner solo lectura --}}
                @if ($soloLectura)
                    <div class="px-6 py-3 bg-amber-50 border-b border-amber-200 text-xs text-amber-800 flex items-start">
                        <svg class="w-4 h-4 mr-2 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>
                            @if (!$dentroDeVentana)
                                Esta consulta fue registrada el {{ $consultaExistente?->created_at->format('d/m/Y H:i') }}
                                y ya superó la ventana de edición de {{ \App\Models\Consulta::VENTANA_EDICION_HORAS }}h.
                            @else
                                No tienes permisos para editar esta consulta.
                            @endif
                            Solo puede consultarse.
                        </span>
                    </div>
                @endif

                {{-- Tabs --}}
                <div class="border-b border-gray-100 px-6 bg-gray-50/50">
                    <div class="flex gap-4">
                        <button type="button" wire:click="$set('tab', 'clinica')"
                                class="py-3 text-sm font-semibold border-b-2 transition
                                    {{ $tab === 'clinica' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Clínica
                        </button>
                        <button type="button" wire:click="$set('tab', 'odontograma')"
                                class="py-3 text-sm font-semibold border-b-2 transition
                                    {{ $tab === 'odontograma' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Odontograma
                            @if ($odontogramaModificado && !$soloLectura)
                                <span class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-500" title="Con cambios sin guardar"></span>
                            @endif
                        </button>
                    </div>
                </div>

                {{-- CONTENIDO SCROLLABLE --}}
                <div class="overflow-y-auto flex-1">

                    {{-- TAB: CLÍNICA --}}
                    <div class="px-6 py-5 {{ $tab === 'clinica' ? '' : 'hidden' }}">
                        
                        {{-- ALERTAS --}}
                        @if($alergiasPaciente->isNotEmpty() || $condicionesPaciente->isNotEmpty())
                            <div class="mb-6">
                                <h4 class="text-xs font-bold text-gray-500 uppercase tracking-wide flex items-center mb-3">
                                    <svg class="w-4 h-4 mr-1.5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                                    Alertas Médicas del Paciente
                                </h4>
                                <div class="max-h-52 overflow-y-auto pr-2 space-y-3 custom-scrollbar">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($alergiasPaciente as $alergia)
                                            @php
                                                $bgClass = match($alergia->severidad) {
                                                    'GRAVE' => 'bg-red-50 border-red-500 text-red-900',
                                                    'MODERADA' => 'bg-orange-50 border-orange-400 text-orange-900',
                                                    default => 'bg-yellow-50 border-yellow-400 text-yellow-900',
                                                };
                                                $iconColor = match($alergia->severidad) {
                                                    'GRAVE' => 'text-red-600', 'MODERADA' => 'text-orange-600', default => 'text-yellow-600',
                                                };
                                            @endphp
                                            <div class="p-3 rounded-lg border-l-4 shadow-sm {{ $bgClass }} flex items-start gap-3">
                                                <svg class="w-5 h-5 mt-0.5 {{ $iconColor }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <div>
                                                    <p class="text-sm font-bold">Alergia: {{ $alergia->alergeno->nombre }}</p>
                                                    <p class="text-xs font-medium opacity-90 mt-0.5">Severidad: <span class="uppercase">{{ $alergia->severidad }}</span> ({{ $alergia->tipo_reaccion }})</p>
                                                </div>
                                            </div>
                                        @endforeach
                                        @foreach($condicionesPaciente as $condicion)
                                            @php
                                                $bgClass = match($condicion->severidad) {
                                                    'SEVERA', 'GRAVE' => 'bg-purple-50 border-purple-500 text-purple-900',
                                                    'MODERADA' => 'bg-blue-50 border-blue-400 text-blue-900',
                                                    default => 'bg-slate-50 border-slate-400 text-slate-900',
                                                };
                                                $iconColor = match($condicion->severidad) {
                                                    'SEVERA', 'GRAVE' => 'text-purple-600', 'MODERADA' => 'text-blue-600', default => 'text-slate-600',
                                                };
                                            @endphp
                                            <div class="p-3 rounded-lg border-l-4 shadow-sm {{ $bgClass }} flex items-start gap-3">
                                                <svg class="w-5 h-5 mt-0.5 {{ $iconColor }} shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                                <div>
                                                    <p class="text-sm font-bold">{{ $condicion->condicion->nombre }}</p>
                                                    <p class="text-xs font-medium opacity-90 mt-0.5">Estado: <span class="uppercase">{{ str_replace('_', ' ', $condicion->estado) }}</span> | Severidad: <span class="uppercase">{{ $condicion->severidad }}</span></p>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- SIGNOS VITALES --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                                Signos Vitales
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Peso (kg)</label>
                                    <input type="number" step="0.01" wire:model="peso" placeholder="70.5" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Altura (m)</label>
                                    <input type="number" step="0.01" wire:model="altura" placeholder="1.75" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Frec. Cardíaca</label>
                                    <input type="number" wire:model="frecuencia_cardiaca" placeholder="72" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">T. Sistólica</label>
                                    <input type="number" wire:model="tension_sistolica" placeholder="120" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">T. Diastólica</label>
                                    <input type="number" wire:model="tension_diastolica" placeholder="80" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Temperatura (°C)</label>
                                    <input type="number" step="0.1" wire:model="temperatura" placeholder="36.5" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                </div>
                            </div>
                        </div>

                        {{-- EVALUACIÓN --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Evaluación Clínica
                            </h4>
                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Motivo</label>
                                    <input type="text" wire:model="motivo" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Síntomas</label>
                                    <textarea wire:model="sintomas" rows="2" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none disabled:bg-gray-50 disabled:text-gray-500"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Diagnóstico</label>
                                    <textarea wire:model="diagnostico" rows="2" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none disabled:bg-gray-50 disabled:text-gray-500"></textarea>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tratamiento</label>
                                    <textarea wire:model="tratamiento" rows="3" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none disabled:bg-gray-50 disabled:text-gray-500"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- REPOSO --}}
                        <div class="mb-2">
                            <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                                Reposo Médico
                            </h4>
                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">¿Requiere Reposo?</label>
                                    <select wire:model.live="reposo" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                        <option value="NO">No</option>
                                        <option value="SI">Sí</option>
                                    </select>
                                </div>
                                @if ($reposo === 'SI')
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Días</label>
                                        <input type="number" wire:model.live="reposo_dias" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Desde</label>
                                        <input type="date" wire:model.live="reposo_fecha_inicio" @if($soloLectura) disabled @endif class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Hasta</label>
                                        <input type="date" wire:model="reposo_fecha_fin" readonly class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    {{-- TAB: ODONTOGRAMA --}}
                    <div class="px-6 py-5 {{ $tab === 'odontograma' ? '' : 'hidden' }}">
                        @if ($cita)
                            <livewire:odontograma-form
                                :pacienteId="$cita->paciente_id"
                                :consultaId="$consultaExistente?->id"
                                :soloLectura="$soloLectura"
                                :key="'odonto-' . $cita->id . '-' . ($consultaExistente?->id ?? 'new')"
                            />
                        @endif
                    </div>
                </div>

                {{-- FOOTER --}}
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 rounded-b-2xl">
                    <button type="button" wire:click="cerrar" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    
                    @unless ($soloLectura)
                        <button type="button"
                                wire:click="guardar"
                                wire:loading.attr="disabled"
                                wire:target="guardar"
                                class="px-6 py-2 bg-teal-600 hover:bg-teal-700 disabled:bg-teal-400 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg shadow-sm transition inline-flex items-center">
                            <svg wire:loading wire:target="guardar" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            <svg wire:loading.remove wire:target="guardar" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            {{ $consultaExistente ? 'Actualizar Consulta' : 'Guardar Consulta' }}
                        </button>
                    @endunless
                </div>
            </div>
        </div>

        {{-- MODAL SECUNDARIO: ENVIAR CORREO --}}
        @if($modalEmailAbierto)
            <div class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-black/40 backdrop-blur-sm" wire:click.self="$set('modalEmailAbierto', false)">
                <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-5" x-data="{ init() { setTimeout(() => $refs.inputEmail.focus(), 100) } }" x-init="init()">
                    <h3 class="text-sm font-bold text-gray-800 mb-3 flex items-center">
                        <svg class="w-4 h-4 mr-2 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        Enviar Receta por Correo
                    </h3>
                    
                    <p class="text-xs text-gray-500 mb-3">El PDF se enviará adjunto al correo indicado.</p>

                    <input type="email" 
                           x-ref="inputEmail"
                           wire:model="emailDestino" 
                           placeholder="paciente@correo.com"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm mb-3 focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    
                    @error('emailDestino') <span class="text-xs text-red-500 block mb-2">{{ $message }}</span> @enderror

                    @if(session('success_email')) 
                        <div class="p-2 bg-green-50 border border-green-200 rounded text-xs text-green-700 mb-3">{{ session('success_email') }}</div>
                    @endif
                    @if(session('error_email')) 
                        <div class="p-2 bg-red-50 border border-red-200 rounded text-xs text-red-700 mb-3">{{ session('error_email') }}</div>
                    @endif

                    <div class="flex justify-end gap-2 mt-4">
                        <button wire:click="$set('modalEmailAbierto', false)" class="px-3 py-1.5 text-xs font-medium text-slate-600 hover:bg-slate-100 rounded transition">Cerrar</button>
                        <button wire:click="enviarReceta" 
                                wire:loading.attr="disabled" 
                                wire:target="enviarReceta"
                                class="px-4 py-1.5 bg-teal-600 text-white rounded text-xs font-medium hover:bg-teal-700 disabled:opacity-50 disabled:cursor-not-allowed transition flex items-center">
                            
                            {{-- Spinner animado que solo se ve cuando está cargando --}}
                            <svg wire:loading wire:target="enviarReceta" class="animate-spin -ml-1 mr-1.5 h-3 w-3 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                            </svg>
                            
                            <span wire:loading.remove wire:target="enviarReceta">Enviar</span>
                            <span wire:loading wire:target="enviarReceta">Enviando...</span>
                        </button>
                    </div>
                </div>
            </div>
        @endif

    @endif
</div>