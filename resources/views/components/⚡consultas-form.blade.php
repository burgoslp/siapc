<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cita;
use App\Models\Consulta;

new class extends Component
{
    public bool $abierto = false;
    public ?int $citaId = null;
    public ?Cita $cita = null;

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

    #[On('abrir-consulta-form')]
    public function abrir(int $citaId): void
    {
        $this->reset();
        $this->citaId = $citaId;
        $this->cita = Cita::with(['paciente', 'medico'])->find($citaId);

        if (!$this->cita) {
            return;
        }

        $existente = Consulta::where('cita_id', $citaId)->first();

        if ($existente) {
            $this->cargarDesdeConsulta($existente);
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

    public function guardar(): void
    {
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

        $consulta = Consulta::where('cita_id', $this->citaId)->first();

        if ($consulta) {
            $consulta->update($datos);
            session()->flash('success', 'Consulta actualizada correctamente.');
        } else {
            Consulta::create($datos + [
                'cita_id' => $this->citaId,
                'paciente_id' => $this->cita->paciente_id,
                'medico_id' => $this->cita->medico_id,
            ]);
            session()->flash('success', 'Consulta registrada correctamente.');
        }

        // 👇 Cierra el ciclo de la cita: pasa a COMPLETADA
        if ($this->cita && $this->cita->estado !== 'COMPLETADA') {
            $this->cita->update(['estado' => 'COMPLETADA']);
        }

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
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[95vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Registrar Consulta</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            {{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}
                            · {{ $cita->fecha->format('d/m/Y h:i A') }}
                            · Dr(a). {{ $cita->medico?->nombre }} {{ $cita->medico?->apellido }}
                        </p>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Formulario --}}
                <form wire:submit="guardar" class="px-6 py-5">

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
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('peso') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Altura (m)</label>
                                <input type="number" step="0.01" wire:model="altura" placeholder="1.75"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('altura') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Frec. Cardíaca</label>
                                <input type="number" wire:model="frecuencia_cardiaca" placeholder="72"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('frecuencia_cardiaca') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">T. Sistólica</label>
                                <input type="number" wire:model="tension_sistolica" placeholder="120"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('tension_sistolica') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">T. Diastólica</label>
                                <input type="number" wire:model="tension_diastolica" placeholder="80"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('tension_diastolica') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Temperatura (°C)</label>
                                <input type="number" step="0.1" wire:model="temperatura" placeholder="36.5"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('temperatura') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- Evaluación Clínica --}}
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
                                <input type="text" wire:model="motivo" placeholder="Motivo principal de la consulta"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Síntomas</label>
                                <textarea wire:model="sintomas" rows="2" placeholder="Síntomas referidos por el paciente..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Diagnóstico</label>
                                <textarea wire:model="diagnostico" rows="2" placeholder="Diagnóstico médico..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tratamiento</label>
                                <textarea wire:model="tratamiento" rows="3" placeholder="Indicaciones médicas, medicamentos..."
                                          class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                            </div>
                        </div>
                    </div>

                    {{-- Reposo --}}
                    <div class="mb-6">
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
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="NO">No</option>
                                    <option value="SI">Sí</option>
                                </select>
                            </div>

                            @if ($reposo === 'SI')
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Días</label>
                                    <input type="number" wire:model.live="reposo_dias" placeholder="3"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    @error('reposo_dias') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Desde</label>
                                    <input type="date" wire:model.live="reposo_fecha_inicio"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
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

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                            Guardar Consulta
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>