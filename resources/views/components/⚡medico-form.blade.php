<?php

use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\On;
use App\Models\Medico;
use App\Models\User;
use App\Models\Role;
use App\Models\Especialidad;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

new class extends Component
{
    use WithFileUploads; // 👈 IMPORTANTE

    public ?int $medicoId = null;
    public bool $abierto = false;

    // Datos personales
    public $nombre = '';
    public $apellido = '';
    public $lci = 'V';
    public $ci = '';
    public $telefono = '';
    public $estado = 'ACTIVO';

    // Datos profesionales
    public $licencia_medica = '';
    public $especialidadesSeleccionadas = [];
    
    // Firma Digital
    public $firmaFile;      // Archivo temporal
    public $firmaActual;    // Ruta guardada en BD

    // Datos de acceso (opcional)
    public bool $tieneAcceso = false;
    public $username = '';
    public $email = '';
    public $password = '';
    public $password_confirmation = '';

    #[On('nuevo-medico')]
    public function abrirNueva(): void
    {
        $this->reset();
        $this->lci = 'V';
        $this->estado = 'ACTIVO';
        $this->especialidadesSeleccionadas = [];
        $this->tieneAcceso = false;
        $this->medicoId = null;
        $this->firmaFile = null;
        $this->firmaActual = null;
        $this->abierto = true;
    }

    #[On('editar-medico')]
    public function abrirEdicion(int $id): void
    {
        $medico = Medico::with(['especialidades', 'user'])->find($id);
        if (!$medico) return;

        $this->medicoId = $medico->id;
        $this->nombre = $medico->nombre;
        $this->apellido = $medico->apellido;
        $this->lci = $medico->lci;
        $this->ci = $medico->ci;
        $this->telefono = $medico->telefono ?? '';
        $this->estado = $medico->estado;
        $this->licencia_medica = $medico->licencia_medica;
        $this->especialidadesSeleccionadas = $medico->especialidades->pluck('id')->toArray();
        
        // Cargar firma
        $this->firmaActual = $medico->firma_path;
        $this->firmaFile = null;

        // Si tiene user, marcar el checkbox y cargar los datos
        if ($medico->user) {
            $this->tieneAcceso = true;
            $this->username = $medico->user->username;
            $this->email = $medico->user->email;
        } else {
            $this->tieneAcceso = false;
            $this->username = '';
            $this->email = '';
        }

        $this->password = '';
        $this->password_confirmation = '';
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        $esCreando = $this->medicoId === null;

        // Reglas base
        $rules = [
            'nombre' => 'required|string|max:255',
            'apellido' => 'required|string|max:255',
            'lci' => 'required|in:V,E,J,P',
            'ci' => 'required|string|max:20|unique:medicos,ci,' . ($this->medicoId ?? 'NULL'),
            'telefono' => 'nullable|string|max:30',
            'estado' => 'required|in:ACTIVO,INACTIVO,LICENCIA',
            'licencia_medica' => 'required|string|max:50|unique:medicos,licencia_medica,' . ($this->medicoId ?? 'NULL'),
            'especialidadesSeleccionadas' => 'array',
            'especialidadesSeleccionadas.*' => 'exists:especialidades,id',
            'firmaFile' => 'nullable|image|max:2048', // Máximo 2MB
        ];

        // Reglas de acceso
        if ($this->tieneAcceso) {
            $userId = $this->medicoId
                ? Medico::find($this->medicoId)?->user_id
                : null;

            $rules['username'] = 'required|string|max:50|alpha_dash|unique:users,username,' . ($userId ?? 'NULL');
            $rules['email'] = 'required|email|max:255|unique:users,email,' . ($userId ?? 'NULL');

            $userExiste = $userId !== null;
            if (!$userExiste) {
                $rules['password'] = 'required|string|min:6|confirmed';
            } else {
                $rules['password'] = 'nullable|string|min:6|confirmed';
            }
        }

        $datos = $this->validate($rules);

        \DB::transaction(function () use ($datos, $esCreando) {

            // Manejar la subida de la firma
            $firmaPath = $this->firmaActual;
            if ($this->firmaFile) {
                // Borrar la firma anterior si existe
                if ($this->firmaActual && Storage::disk('public')->exists($this->firmaActual)) {
                    Storage::disk('public')->delete($this->firmaActual);
                }
                // Guardar la nueva firma
                $firmaPath = $this->firmaFile->store('firmas', 'public');
            }

            if ($esCreando) {
                $medico = Medico::create([
                    'nombre' => $datos['nombre'],
                    'apellido' => $datos['apellido'],
                    'lci' => $datos['lci'],
                    'ci' => $datos['ci'],
                    'telefono' => $datos['telefono'] ?: null,
                    'estado' => $datos['estado'],
                    'licencia_medica' => $datos['licencia_medica'],
                    'firma_path' => $firmaPath, // 👈 Guardar firma
                ]);

                if ($this->tieneAcceso) {
                    $user = User::create([
                        'username' => $datos['username'],
                        'email' => $datos['email'],
                        'password' => Hash::make($datos['password']),
                        'activo' => true,
                    ]);

                    $rolMedico = Role::where('nombre', 'medico')->first();
                    if ($rolMedico) {
                        $user->roles()->sync([$rolMedico->id]);
                    }

                    $medico->update(['user_id' => $user->id]);
                }

                $medico->especialidades()->sync($this->especialidadesSeleccionadas);
                session()->flash('success', 'Médico creado correctamente.');

            } else {
                $medico = Medico::with('user')->find($this->medicoId);
                if (!$medico) return;

                $medico->update([
                    'nombre' => $datos['nombre'],
                    'apellido' => $datos['apellido'],
                    'lci' => $datos['lci'],
                    'ci' => $datos['ci'],
                    'telefono' => $datos['telefono'] ?: null,
                    'estado' => $datos['estado'],
                    'licencia_medica' => $datos['licencia_medica'],
                    'firma_path' => $firmaPath, // 👈 Actualizar firma
                ]);

                if ($medico->user && $this->tieneAcceso) {
                    $userData = [
                        'username' => $datos['username'],
                        'email' => $datos['email'],
                        'activo' => true,
                    ];
                    if (!empty($datos['password'])) {
                        $userData['password'] = Hash::make($datos['password']);
                    }
                    $medico->user->update($userData);
                } elseif (!$medico->user && $this->tieneAcceso) {
                    $user = User::create([
                        'username' => $datos['username'],
                        'email' => $datos['email'],
                        'password' => Hash::make($datos['password']),
                        'activo' => true,
                    ]);

                    $rolMedico = Role::where('nombre', 'medico')->first();
                    if ($rolMedico) {
                        $user->roles()->sync([$rolMedico->id]);
                    }

                    $medico->update(['user_id' => $user->id]);
                } elseif ($medico->user && !$this->tieneAcceso) {
                    $medico->user->update(['activo' => false]);
                    $medico->update(['user_id' => null]);
                }

                $medico->especialidades()->sync($this->especialidadesSeleccionadas);
                session()->flash('success', 'Médico actualizado correctamente.');
            }
        });

        $this->dispatch('medico-guardado');
        $this->abierto = false;
    }

    public function with(): array
    {
        return [
            'todasLasEspecialidades' => Especialidad::orderBy('nombre')->get(),
        ];
    }
};
?>

<div>
    @if ($abierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-3xl max-h-[95vh] overflow-y-auto">

                {{-- Encabezado --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 sticky top-0 bg-white z-10">
                    <h3 class="text-lg font-bold text-gray-800">
                        {{ $medicoId ? 'Editar Médico' : 'Nuevo Médico' }}
                    </h3>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <form wire:submit="guardar" class="px-6 py-5">

                    {{-- SECCIÓN: Datos personales --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3">
                            Datos Personales
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre *</label>
                                <input type="text" wire:model="nombre" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Apellido *</label>
                                <input type="text" wire:model="apellido" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('apellido') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Cédula *</label>
                                <div class="flex gap-2">
                                    <select wire:model="lci" class="w-1/3 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="V">V</option>
                                        <option value="E">E</option>
                                        <option value="J">J</option>
                                        <option value="P">P</option>
                                    </select>
                                    <input type="text" wire:model="ci" placeholder="12345678" class="w-2/3 px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                                @error('ci') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Teléfono</label>
                                <input type="text" wire:model="telefono" placeholder="0414-1234567" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('telefono') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN: Datos profesionales --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3">
                            Datos Profesionales
                        </h4>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Licencia Médica *</label>
                                <input type="text" wire:model="licencia_medica" placeholder="MPPS-12345" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                @error('licencia_medica') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Estado *</label>
                                <select wire:model="estado" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    <option value="ACTIVO">Activo</option>
                                    <option value="INACTIVO">Inactivo</option>
                                    <option value="LICENCIA">En licencia</option>
                                </select>
                                @error('estado') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Especialidades</label>
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mt-1 p-3 border border-gray-200 rounded-lg max-h-48 overflow-y-auto">
                                    @foreach ($todasLasEspecialidades as $esp)
                                        <label class="flex items-center cursor-pointer text-sm">
                                            <input type="checkbox" wire:model="especialidadesSeleccionadas" value="{{ $esp->id }}" class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                            <span class="ml-2 text-gray-700">{{ $esp->nombre }}</span>
                                        </label>
                                    @endforeach
                                </div>
                                @error('especialidadesSeleccionadas') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN: Firma Digital --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-3">
                            Firma Digital
                        </h4>
                        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50/50">
                            <div class="flex flex-col sm:flex-row items-center gap-6">
                                {{-- Previsualización de la firma --}}
                                <div class="w-48 h-24 bg-white border border-gray-300 rounded-lg flex items-center justify-center overflow-hidden shrink-0">
                                    @if ($firmaFile)
                                        <img src="{{ $firmaFile->temporaryUrl() }}" class="max-h-full max-w-full object-contain" alt="Previsualización">
                                    @elseif ($firmaActual)
                                        <img src="{{ \Illuminate\Support\Facades\Storage::url($firmaActual) }}" class="max-h-full max-w-full object-contain" alt="Firma actual">
                                    @else
                                        <span class="text-xs text-gray-400 text-center px-2">Sin firma<br>(Sube una imagen PNG)</span>
                                    @endif
                                </div>

                                {{-- Input para subir --}}
                                <div class="flex-1 w-full">
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Subir Firma (PNG con fondo transparente)</label>
                                    <input type="file" wire:model="firmaFile" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 transition">
                                    <div wire:loading wire:target="firmaFile" class="text-xs text-teal-600 mt-2">Subiendo firma...</div>
                                    @error('firmaFile') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                                    <p class="text-[10px] text-gray-500 mt-2">La firma se usará automáticamente en los PDFs de recetas e informes.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- SECCIÓN: Acceso al sistema (opcional) --}}
                    <div class="mb-6">
                        <div class="p-4 border border-gray-200 rounded-lg bg-gray-50/50">
                            <label class="flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="tieneAcceso" class="w-4 h-4 rounded border-gray-300 text-teal-600 focus:ring-teal-500">
                                <span class="ml-2 text-sm font-semibold text-gray-800">¿Suministrar datos de acceso al sistema?</span>
                            </label>
                            <p class="text-xs text-gray-500 mt-1 ml-6">Activa esta opción si el médico debe poder iniciar sesión en el sistema.</p>
                        </div>

                        @if ($tieneAcceso)
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-4 pl-6 border-l-2 border-teal-200">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Usuario *</label>
                                    <input type="text" wire:model="username" placeholder="cmendoza" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    @error('username') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Email *</label>
                                    <input type="email" wire:model="email" placeholder="cmendoza@clinica.test" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Contraseña @if (!$medicoId || !Medico::find($medicoId)?->user_id) * @endif</label>
                                    <input type="password" wire:model="password" placeholder="{{ $medicoId && Medico::find($medicoId)?->user_id ? 'Dejar en blanco para no cambiar' : 'Mínimo 6 caracteres' }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                    @error('password') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Confirmar Contraseña</label>
                                    <input type="password" wire:model="password_confirmation" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Botones --}}
                    <div class="flex justify-end gap-3 mt-6 pt-4 border-t border-gray-100">
                        <button type="button" wire:click="cerrar" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 hover:bg-gray-50">Cancelar</button>
                        <button type="submit" class="px-5 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium">
                            {{ $medicoId ? 'Guardar Cambios' : 'Crear Médico' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif
</div>