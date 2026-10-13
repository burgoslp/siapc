<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Paciente;
use App\Models\Condicion;
use App\Models\CondicionPaciente;

new class extends Component
{
    public bool $abierto = false;
    public ?int $pacienteId = null;
    public ?Paciente $paciente = null;

    // Campos del formulario
    public $id_condicion = '';
    public $fecha_diagnostico = '';
    public $severidad = 'LEVE';
    public $estado = 'ACTIVA';
    public $diagnostico_por = '';
    public $especialidad = '';
    public $notas = '';
    public $fecha_ultimo_control = '';
    public $proximo_control_sugerido = '';

    #[On('abrir-condicion-form')]
    public function abrir(int $pacienteId): void
    {
        $this->reset();
        $this->pacienteId = $pacienteId;
        $this->paciente = Paciente::find($pacienteId);

        if (!$this->paciente) {
            return;
        }

        $this->fecha_diagnostico = now()->format('Y-m-d');
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'id_condicion' => 'required|exists:condiciones,id',
            'fecha_diagnostico' => 'nullable|date',
            'severidad' => 'required|in:LEVE,MODERADA,GRAVE,SEVERA',
            'estado' => 'required|in:ACTIVA,CONTROLADA,EN_REMISION,RESUELTA,SOSPECHA',
            'diagnostico_por' => 'nullable|string|max:100',
            'especialidad' => 'nullable|string|max:100',
            'notas' => 'nullable|string',
            'fecha_ultimo_control' => 'nullable|date',
            'proximo_control_sugerido' => 'nullable|date',
        ]);

        // convertir fechas vacías a null en caso de que el usuario no las haya llenado
        $datos['fecha_diagnostico'] = $datos['fecha_diagnostico'] ?: null;
        $datos['fecha_ultimo_control'] = $datos['fecha_ultimo_control'] ?: null;
        $datos['proximo_control_sugerido'] = $datos['proximo_control_sugerido'] ?: null;

        // Evitar duplicado: mismo paciente + misma condición
        $existe = CondicionPaciente::where('id_paciente', $this->pacienteId)
            ->where('id_condicion', $this->id_condicion)
            ->exists();

        if ($existe) {
            $this->addError('id_condicion', 'Este paciente ya tiene registrada esa condición.');
            return;
        }

        CondicionPaciente::create($datos + ['id_paciente' => $this->pacienteId]);

        $this->dispatch('condicion-guardada', pacienteId: $this->pacienteId);
        $this->abierto = false;

        session()->flash('success', 'Condición registrada correctamente.');
    }
};
?>

<div>
    @if ($abierto && $paciente)
        {{-- Fondo --}}
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        {{-- Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Agregar Condición Médica</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Paciente: {{ $paciente->nombre }} {{ $paciente->apellido }}
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
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Condición --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Condición *
                            </label>
                            <select wire:model="id_condicion"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Seleccione una condición...</option>
                                @foreach (Condicion::where('activo', true)->orderBy('nombre')->get() as $cond)
                                    <option value="{{ $cond->id }}">
                                        {{ $cond->nombre }}
                                        @if ($cond->codigo_cie10) ({{ $cond->codigo_cie10 }}) @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('id_condicion') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Fecha diagnóstico --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fecha de Diagnóstico</label>
                            <input type="date" wire:model="fecha_diagnostico"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('fecha_diagnostico') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Severidad --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Severidad *</label>
                            <select wire:model="severidad"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="LEVE">Leve</option>
                                <option value="MODERADA">Moderada</option>
                                <option value="GRAVE">Grave</option>
                                <option value="SEVERA">Severa</option>
                            </select>
                            @error('severidad') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Estado --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Estado *</label>
                            <select wire:model="estado"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="ACTIVA">Activa</option>
                                <option value="CONTROLADA">Controlada</option>
                                <option value="EN_REMISION">En Remisión</option>
                                <option value="RESUELTA">Resuelta</option>
                                <option value="SOSPECHA">Sospecha</option>
                            </select>
                            @error('estado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Diagnóstico por --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Diagnóstico por</label>
                            <input type="text" wire:model="diagnostico_por"
                                   placeholder="Nombre del médico"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>

                        {{-- Especialidad --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Especialidad</label>
                            <input type="text" wire:model="especialidad"
                                   placeholder="Ej: Cardiología"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>

                        {{-- Fecha último control --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Último Control</label>
                            <input type="date" wire:model="fecha_ultimo_control"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>

                        {{-- Próximo control --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Próximo Control Sugerido</label>
                            <input type="date" wire:model="proximo_control_sugerido"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>

                        {{-- Notas --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Notas</label>
                            <textarea wire:model="notas" rows="3"
                                      placeholder="Observaciones adicionales..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
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
                            Guardar Condición
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>