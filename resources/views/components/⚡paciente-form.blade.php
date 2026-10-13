<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Paciente;

new class extends Component
{
    public ?Paciente $paciente = null;
    public bool $abierto = false;

    public $lci = 'V';
    public $ci = '';
    public $nombre = '';
    public $apellido = '';
    public $fecha_nacimiento = '';
    public $genero = '';
    public $telefono = '';
    public $email = '';
    public $direccion = '';
    public $antecedentes_familiares = '';
    public $estado = 'activo';

    #[On('editar-paciente')]
    public function cargarPaciente(int $id): void
    {
        $p = Paciente::find($id);
        if (!$p) return;

        $this->paciente = $p;
        $this->lci = $p->lci;
        $this->ci = $p->ci;
        $this->nombre = $p->nombre;
        $this->apellido = $p->apellido;
        $this->fecha_nacimiento = $p->fecha_nacimiento?->format('Y-m-d');
        $this->genero = $p->genero ?? '';
        $this->telefono = $p->telefono ?? '';
        $this->email = $p->email ?? '';
        $this->direccion = $p->direccion ?? '';
        $this->antecedentes_familiares = $p->antecedentes_familiares ?? '';
        $this->estado = $p->estado;

        $this->abierto = true;
    }

    #[On('nuevo-paciente')]
    public function abrirNuevo(): void
    {
        $this->reset();
        $this->paciente = null;
        $this->lci = 'V';
        $this->estado = 'activo';
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
            'lci' => 'required|in:V,E,J,P',
            'ci' => 'required|string|max:20|unique:pacientes,ci,' . ($this->paciente?->id ?? 'NULL'),
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'fecha_nacimiento' => 'required|date',
            'genero' => 'nullable|in:masculino,femenino,otro',
            'telefono' => 'nullable|string|max:30',
            'email' => 'nullable|email|max:255|unique:pacientes,email,' . ($this->paciente?->id ?? 'NULL'),
            'direccion' => 'nullable|string',
            'antecedentes_familiares' => 'nullable|string',
            'estado' => 'required|in:activo,inactivo',
        ]);

        if ($this->paciente) {
            $this->paciente->update($datos);
            $mensaje = 'Paciente actualizado correctamente.';
        } else {
            Paciente::create($datos);
            $mensaje = 'Paciente registrado correctamente.';
        }

        $this->dispatch('paciente-guardado');
        $this->abierto = false;
        session()->flash('success', $mensaje);
    }
};
?>

<div>
    @if ($abierto)
        {{-- Fondo --}}
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        {{-- Modal --}}
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ $paciente ? 'Editar Paciente' : 'Registrar Nuevo Paciente' }}
                    </h3>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                {{-- Formulario --}}
                <form wire:submit="guardar" class="px-6 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">

                        {{-- Cédula --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Cédula</label>
                            <div class="flex gap-2">
                                <select wire:model="lci" class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="V">V</option>
                                    <option value="E">E</option>
                                    <option value="J">J</option>
                                    <option value="P">P</option>
                                </select>
                                <input type="text" wire:model="ci" placeholder="12345678"
                                       class="w-2/3 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            </div>
                            @error('ci') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Nombre --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre</label>
                            <input type="text" wire:model="nombre"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Apellido --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Apellido</label>
                            <input type="text" wire:model="apellido"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('apellido') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Fecha --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Fecha de Nacimiento</label>
                            <input type="date" wire:model="fecha_nacimiento"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('fecha_nacimiento') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Género --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Género</label>
                            <select wire:model="genero" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                <option value="">Seleccione...</option>
                                <option value="masculino">Masculino</option>
                                <option value="femenino">Femenino</option>
                                <option value="otro">Otro</option>
                            </select>
                        </div>

                        {{-- Teléfono --}}
                        <div>
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Teléfono</label>
                            <input type="text" wire:model="telefono"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>

                        {{-- Email --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Email</label>
                            <input type="email" wire:model="email"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                            @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Dirección --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Dirección</label>
                            <input type="text" wire:model="direccion"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                        </div>

                        {{-- Antecedentes --}}
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Antecedentes Familiares</label>
                            <textarea wire:model="antecedentes_familiares" rows="3"
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm resize-none"></textarea>
                        </div>

                        {{-- Estado (solo editar) --}}
                        @if ($paciente)
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Estado</label>
                                <select wire:model="estado" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                        @endif
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium">
                            {{ $paciente ? 'Guardar Cambios' : 'Registrar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>