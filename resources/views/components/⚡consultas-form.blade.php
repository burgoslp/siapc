<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Odontograma;

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

    // Odontograma (espejo del hijo)
    public array $datosOdontograma = [];
    public bool $odontogramaModificado = false;
    public ?string $tipoOdontograma = 'permanente'; 


    #[On('abrir-consulta-form')]
    public function abrir(int $citaId): void
    {
        $this->reset();
        $this->citaId = $citaId;
        $this->cita = Cita::with(['paciente', 'medico', 'consulta'])->find($citaId);

        if (!$this->cita) {
            return;
        }

        $existente = $this->cita->consulta;

        if ($existente) {
            $this->consultaExistente = $existente;
            $this->cargarDesdeConsulta($existente);

            $this->dentroDeVentana = $existente->dentro_de_ventana_edicion;
            $this->soloLectura = !$existente->puedeSerEditadaPor(auth()->user());
            
        } else {
            $this->motivo = $this->cita->motivo ?? '';
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
        // Bloqueo duro: si está en solo lectura, no hace nada
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

        // 1) Guardar / actualizar la consulta
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

        // 2) Odontograma: solo si fue modificado
        if ($this->odontogramaModificado && !empty($this->datosOdontograma)) {
            if ($esEdicion) {
                // Edición: actualizar el registro existente de esta consulta
                $odontoExistente = Odontograma::where('consulta_id', $consulta->id)->first();

                if ($odontoExistente) {
                    $odontoExistente->update(['datos' => $this->datosOdontograma]);
                } else {
                    Odontograma::create([
                        'paciente_id' => $this->cita->paciente_id,
                        'consulta_id' => $consulta->id,
                        'datos'       => $this->datosOdontograma,
                    ]);
                }
            } else {
                // Nueva consulta: crear registro nuevo
                Odontograma::create([
                    'paciente_id' => $this->cita->paciente_id,
                    'consulta_id' => $consulta->id,
                    'tipo' => $this->tipoOdontograma,
                    'datos' => $this->datosOdontograma,
                ]);
            }
        }

        // 3) Cerrar ciclo de la cita
        if ($this->cita && $this->cita->estado !== 'COMPLETADA') {
            $this->cita->update(['estado' => 'COMPLETADA']);
        }

        session()->flash(
            'success',
            $esEdicion ? 'Consulta actualizada correctamente.' : 'Consulta registrada correctamente.'
        );

        $this->dispatch('consulta-guardada');
        $this->abierto = false;
    }
};
?>

<div>
    @if ($abierto && $cita)
        {{-- Fondo --}}
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        {{-- Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[95vh] flex flex-col">

                {{-- Header sticky --}}
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
                        <button type="button"
                                wire:click="cerrar"
                                class="px-3 py-2 text-sm text-gray-600 hover:bg-gray-100 rounded-lg transition">
                            {{ $soloLectura ? 'Cerrar' : 'Cancelar' }}
                        </button>

                        @unless ($soloLectura)
                            <button type="button"
                                    wire:click="guardar"
                                    wire:loading.attr="disabled"
                                    wire:target="guardar"
                                    class="px-4 py-2 bg-teal-600 hover:bg-teal-700 disabled:bg-teal-400 disabled:cursor-not-allowed text-white text-sm font-semibold rounded-lg transition inline-flex items-center">
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

                        <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600 ml-1">
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
                        <button type="button"
                                wire:click="$set('tab', 'clinica')"
                                class="py-3 text-sm font-semibold border-b-2 transition
                                    {{ $tab === 'clinica' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Clínica
                        </button>
                        <button type="button"
                                wire:click="$set('tab', 'odontograma')"
                                class="py-3 text-sm font-semibold border-b-2 transition
                                    {{ $tab === 'odontograma' ? 'border-teal-600 text-teal-600' : 'border-transparent text-gray-500 hover:text-gray-700' }}">
                            Odontograma
                            @if ($odontogramaModificado && !$soloLectura)
                                <span class="ml-1 inline-block w-2 h-2 rounded-full bg-amber-500" title="Con cambios sin guardar"></span>
                            @endif
                        </button>
                    </div>
                </div>

                {{-- Contenido --}}
                <div class="overflow-y-auto flex-1">

                    {{-- TAB: CLÍNICA --}}
                    <div class="px-6 py-5 {{ $tab === 'clinica' ? '' : 'hidden' }}">
                        {{-- Signos Vitales --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                                </svg>
                                Signos Vitales
                            </h4>

                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Peso (kg)</label>
                                    <input type="number" step="0.01" wire:model="peso" placeholder="70.5"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                    @error('peso') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Altura (m)</label>
                                    <input type="number" step="0.01" wire:model="altura" placeholder="1.75"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                    @error('altura') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Frec. Cardíaca</label>
                                    <input type="number" wire:model="frecuencia_cardiaca" placeholder="72"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                    @error('frecuencia_cardiaca') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">T. Sistólica</label>
                                    <input type="number" wire:model="tension_sistolica" placeholder="120"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                    @error('tension_sistolica') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">T. Diastólica</label>
                                    <input type="number" wire:model="tension_diastolica" placeholder="80"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                    @error('tension_diastolica') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Temperatura (°C)</label>
                                    <input type="number" step="0.1" wire:model="temperatura" placeholder="36.5"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                    @error('temperatura') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        {{-- Evaluación --}}
                        <div class="mb-6">
                            <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Evaluación Clínica
                            </h4>

                            <div class="space-y-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Motivo</label>
                                    <input type="text" wire:model="motivo"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Síntomas</label>
                                    <textarea wire:model="sintomas" rows="2"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none disabled:bg-gray-50 disabled:text-gray-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Diagnóstico</label>
                                    <textarea wire:model="diagnostico" rows="2"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none disabled:bg-gray-50 disabled:text-gray-500"></textarea>
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tratamiento</label>
                                    <textarea wire:model="tratamiento" rows="3"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none disabled:bg-gray-50 disabled:text-gray-500"></textarea>
                                </div>
                            </div>
                        </div>

                        {{-- Reposo --}}
                        <div class="mb-2">
                            <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                                Reposo Médico
                            </h4>

                            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">¿Requiere Reposo?</label>
                                    <select wire:model.live="reposo"
                                        @if($soloLectura) disabled @endif
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                        <option value="NO">No</option>
                                        <option value="SI">Sí</option>
                                    </select>
                                </div>

                                @if ($reposo === 'SI')
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Días</label>
                                        <input type="number" wire:model.live="reposo_dias" placeholder="3"
                                            @if($soloLectura) disabled @endif
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                        @error('reposo_dias') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Desde</label>
                                        <input type="date" wire:model.live="reposo_fecha_inicio"
                                            @if($soloLectura) disabled @endif
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm disabled:bg-gray-50 disabled:text-gray-500">
                                        @error('reposo_fecha_inicio') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Hasta</label>
                                        <input type="date" wire:model="reposo_fecha_fin" readonly
                                            class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-sm">
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
            </div>
        </div>
    @endif
</div>