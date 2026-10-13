<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Paciente;
use App\Models\Alergeno;
use App\Models\AlergiaPaciente;

new class extends Component
{
    public bool $abierto = false;
    public ?int $pacienteId = null;
    public ?Paciente $paciente = null;

    public $id_alergeno = '';
    public $tipo_reaccion = 'CUTANEA';
    public $severidad = 'LEVE';
    public $estado = 'ACTIVA';
    public ?string $fecha_diagnostico = null;
    public $observaciones = '';

    #[On('abrir-alergia-form')]
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

    public function updated($property): void
    {
        if ($property === 'fecha_diagnostico' && $this->fecha_diagnostico === '') {
            $this->fecha_diagnostico = null;
        }
    }

    public function guardar(): void
    {
        $datos = $this->validate([
            'id_alergeno' => 'required|exists:alergenos,id',
            'tipo_reaccion' => 'required|in:CUTANEA,RESPIRATORIA,DIGESTIVA,ANAFILACTICA,OCULAR,OTRA',
            'severidad' => 'required|in:LEVE,MODERADA,GRAVE',
            'estado' => 'required|in:ACTIVA,INACTIVA,SOSPECHA,CONFIRMADA',
            'fecha_diagnostico' => 'nullable|date',
            'observaciones' => 'nullable|string',
        ]);

        // Normalizar vacíos
        $datos['fecha_diagnostico'] = $datos['fecha_diagnostico'] ?: null;
        $datos['observaciones'] = $datos['observaciones'] ?: null;

        // Anti-duplicado: mismo paciente + mismo alérgeno
        $existe = AlergiaPaciente::where('id_paciente', $this->pacienteId)
            ->where('id_alergeno', $this->id_alergeno)
            ->exists();

        if ($existe) {
            $this->addError('id_alergeno', 'Este paciente ya tiene registrada esa alergia.');
            return;
        }

        AlergiaPaciente::create($datos + [
            'id_paciente' => $this->pacienteId,
        ]);

        $this->dispatch('alergia-guardada', pacienteId: $this->pacienteId);
        $this->abierto = false;

        session()->flash('success', 'Alergia registrada correctamente.');
    }
};
?>

<div>
    @if ($abierto && $paciente)
        {{-- Fondo --}}
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        {{-- Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4"
             wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-xl max-h-[90vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Agregar Alergia</h3>
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

                        {{-- Alérgeno --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">
                                Alérgeno *
                            </label>
                            <select wire:model="id_alergeno"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Seleccione un alérgeno...</option>
                                @foreach (Alergeno::orderBy('categoria')->orderBy('nombre')->get() as $al)
                                    <option value="{{ $al->id }}">
                                        {{ $al->nombre }}
                                        @if ($al->categoria) — {{ $al->categoria }} @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('id_alergeno') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Tipo de reacción --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo de Reacción *</label>
                            <select wire:model="tipo_reaccion"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="CUTANEA">Cutánea</option>
                                <option value="RESPIRATORIA">Respiratoria</option>
                                <option value="DIGESTIVA">Digestiva</option>
                                <option value="ANAFILACTICA">Anafiláctica</option>
                                <option value="OCULAR">Ocular</option>
                                <option value="OTRA">Otra</option>
                            </select>
                            @error('tipo_reaccion') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Severidad --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Severidad *</label>
                            <select wire:model="severidad"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="LEVE">Leve</option>
                                <option value="MODERADA">Moderada</option>
                                <option value="GRAVE">Grave</option>
                            </select>
                            @error('severidad') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Estado --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Estado *</label>
                            <select wire:model="estado"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="ACTIVA">Activa</option>
                                <option value="INACTIVA">Inactiva</option>
                                <option value="SOSPECHA">Sospecha</option>
                                <option value="CONFIRMADA">Confirmada</option>
                            </select>
                            @error('estado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Fecha --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fecha Diagnóstico</label>
                            <input type="date" wire:model="fecha_diagnostico"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('fecha_diagnostico') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Observaciones --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Observaciones</label>
                            <textarea wire:model="observaciones" rows="3"
                                      placeholder="Detalles sobre la reacción..."
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
                                class="px-5 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg text-sm font-medium">
                            Guardar Alergia
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>