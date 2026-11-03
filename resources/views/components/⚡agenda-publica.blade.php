<?php

use Livewire\Component;
use App\Models\AgendaCompartida;
use App\Models\Cita;
use App\Models\Paciente;
use Carbon\Carbon;

new class extends Component
{
    public $token;
    public $agenda;
    public $medico;

    // Estado del flujo
    public $paso = '1'; // 1: Servicio, 2: Fecha/Hora, 3: Datos, 4: Éxito

    // Selecciones del paciente
    public $tarifaSeleccionada = null;
    public $fechaSeleccionada = null;
    public $horaSeleccionada = null;

    // Datos del paciente
    public $ci = '';
    public $email = '';
    public $nombre = '';
    public $apellido = '';
    public $telefono = '';
    public $fecha_nacimiento = '';
    public $pacienteExistente = null;

    public function mount($token)
    {
        $this->token = $token;
        $this->agenda = AgendaCompartida::with(['medico', 'tarifas', 'disponibilidades'])
            ->where('token', $token)
            ->where('activo', true)
            ->first();

        if (!$this->agenda || $this->agenda->fecha_expiracion->isPast()) {
            abort(404, 'El enlace de agendamiento ha expirado o no es válido.');
        }

        $this->medico = $this->agenda->medico;
    }

    // Validar paciente y avanzar al paso de fecha/hora
    public function seleccionarServicio($tarifaId)
    {
        $this->tarifaSeleccionada = $this->agenda->tarifas->find($tarifaId);
        $this->paso = '2';
    }

    public function seleccionarFecha($fecha)
    {
        $this->fechaSeleccionada = $fecha;
        $this->horaSeleccionada = null;
    }

    public function seleccionarHora($hora)
    {
        $this->horaSeleccionada = $hora;
    }

    public function continuarADatos()
    {
        if (!$this->horaSeleccionada) {
            $this->addError('hora', 'Por favor selecciona una hora.');
            return;
        }
        $this->paso = '3';
    }

    public function validarPaciente()
    {
        $this->validate([
            'ci' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        // Buscamos si la cédula ya existe en el sistema
        $paciente = Paciente::where('ci', $this->ci)->first();

        if ($paciente) {
            // Si el paciente existe, validamos que el correo coincida
            if ($paciente->email !== $this->email) {
                $this->addError('email', 'El correo no coincide con el registrado para esta cédula. Verifica o usa otro.');
                return;
            }
            // Si todo coincide, le cargamos los datos y confirmamos la cita
            $this->pacienteExistente = $paciente;
            $this->nombre = $paciente->nombre;
            $this->apellido = $paciente->apellido;
            $this->confirmarCita();
        } else {
            // Si NO existe, no confirmamos nada. El HTML se encargará de mostrar el formulario completo.
            $this->pacienteExistente = null; 
            
            //Lanzamos un mensaje para avisar que es nuevo
            session()->flash('info_nuevo_paciente', 'No encontramos tu cédula. Por favor, completa tus datos para registrarte.');
        }
    }

    public function confirmarCita()
    {
        // Validamos los campos extra si es un paciente nuevo
        if (!$this->pacienteExistente) {
            $this->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'telefono' => 'required|string|max:30',
                'fecha_nacimiento' => 'required|date',
            ]);
        }

        \DB::transaction(function () {
            if ($this->pacienteExistente) {
                $paciente = $this->pacienteExistente;
            } else {
                // Creamos el paciente con todos los datos
                $paciente = Paciente::create([
                    'nombre' => $this->nombre,
                    'apellido' => $this->apellido,
                    'ci' => $this->ci,
                    'email' => $this->email,
                    'telefono' => $this->telefono,
                    'fecha_nacimiento' => $this->fecha_nacimiento, 
                    'estado' => 'activo',
                ]);
            }

            // 2. Calcular la fecha y hora final
            $fechaHora = Carbon::parse($this->fechaSeleccionada . ' ' . $this->horaSeleccionada);

            // 3. Crear la cita
            Cita::create([
                'paciente_id' => $paciente->id,
                'medico_id' => $this->medico->id,
                'fecha' => $fechaHora,
                'motivo' => $this->tarifaSeleccionada->nombre,
                'estado' => 'PENDIENTE', // Entra como pendiente para que recepción confirme
                'tarifa_id' => $this->tarifaSeleccionada->id,
                'precio' => $this->tarifaSeleccionada->precio_usd,
                'total' => $this->tarifaSeleccionada->precio_usd,
                'estado_pago' => 'PENDIENTE',
            ]);
        });

        $this->paso = '4';
    }

    public function getFechasDisponiblesProperty()
    {
        return $this->agenda->disponibilidades;
    }

    public function getHorasDisponiblesProperty()
    {
        if (!$this->fechaSeleccionada) return [];

        // 👇 CORRECCIÓN: Usamos filter para comparar la fecha formateada como string
        $disponibilidad = $this->agenda->disponibilidades
            ->filter(fn($d) => $d->fecha->format('Y-m-d') === $this->fechaSeleccionada)
            ->first();

        if (!$disponibilidad) return [];

        $inicio = Carbon::parse($this->fechaSeleccionada . ' ' . $disponibilidad->hora_inicio);
        $fin = Carbon::parse($this->fechaSeleccionada . ' ' . $disponibilidad->hora_fin);
        
        $duracion = $this->tarifaSeleccionada->duracion_minutos ?? 30;

        // Obtener citas ya agendadas para ese médico en esa fecha
        $citasOcupadas = Cita::where('medico_id', $this->medico->id)
            ->whereDate('fecha', $this->fechaSeleccionada)
            ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
            ->pluck('fecha')
            ->map(fn($f) => $f->format('H:i'))
            ->toArray();

        $horas = [];
        $actual = $inicio->copy();

        while ($actual->copy()->addMinutes($duracion)->lte($fin)) {
            $horaStr = $actual->format('H:i');
            if (!in_array($horaStr, $citasOcupadas)) {
                $horas[] = $horaStr;
            }
            $actual->addMinutes($duracion);
        }

        return $horas;
    }
};
?>

<div class="max-w-4xl mx-auto bg-white rounded-2xl shadow-xl overflow-hidden mt-10 mb-10 flex flex-col md:flex-row">
    
    {{-- Sidebar: Info del Doctor --}}
    <div class="bg-slate-50 p-8 md:w-1/3 border-r border-slate-100 flex flex-col items-center text-center">
        <img src="{{ asset('images/logo/logo2.png') }}" alt="Logo" class="w-16 h-16 rounded-2xl shadow-sm mb-4">
        <h2 class="text-lg font-bold text-slate-800">Cita con</h2>
        <h3 class="text-xl font-bold text-siapc-blue mt-1">Dr(a). {{ $medico->nombre }} {{ $medico->apellido }}</h3>
        <p class="text-xs text-slate-500 mt-1">{{ $medico->especialidades->first()?->nombre }}</p>
        
        <div class="mt-6 text-left w-full space-y-3">
            <div class="flex items-start text-xs text-slate-600">
                <svg class="w-4 h-4 mr-2 text-siapc-blue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Consultorio Dental (Dirección de la Clínica)
            </div>
            <div class="flex items-start text-xs text-slate-600">
                <svg class="w-4 h-4 mr-2 text-siapc-blue shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Hora del proveedor: America/Caracas
            </div>
        </div>
    </div>

    {{-- Contenido Principal --}}
    <div class="p-8 md:w-2/3 flex-1 flex flex-col">
        
        {{-- PASO 1: SELECCIONAR SERVICIO --}}
        @if($paso === '1')
            <div class="flex-1">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Reserva una cita</p>
                <h2 class="text-2xl font-bold text-slate-800 mb-6">Elige la opción que mejor te convenga</h2>
                
                <p class="text-xs text-slate-500 mb-3">1. Selecciona tus servicios</p>
                <div class="space-y-2 max-h-80 overflow-y-auto pr-2">
                    @foreach($agenda->tarifas as $tarifa)
                        <button wire:click="seleccionarServicio({{ $tarifa->id }})" class="w-full text-left p-3 border border-slate-200 rounded-lg hover:border-siapc-blue hover:bg-siapc-blue/5 transition flex justify-between items-center group">
                            <div>
                                <p class="text-sm font-semibold text-slate-800 group-hover:text-siapc-blue">{{ $tarifa->nombre }}</p>
                                <p class="text-[10px] text-slate-500">{{ $tarifa->duracion_minutos ?? 30 }} min de atención</p>
                            </div>
                            <svg class="w-4 h-4 text-slate-300 group-hover:text-siapc-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- PASO 2: SELECCIONAR FECHA Y HORA --}}
        @if($paso === '2')
            <div class="flex-1">
                <button wire:click="$set('paso', '1')" class="text-xs text-slate-500 hover:text-siapc-blue mb-4 inline-flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver a servicios
                </button>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">2. Elige una fecha y hora</h2>
                <p class="text-sm text-slate-500 mb-6">Servicio: <span class="font-semibold text-siapc-blue">{{ $tarifaSeleccionada->nombre }}</span></p>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Fechas --}}
                    <div>
                        <p class="text-xs font-semibold text-slate-600 uppercase mb-2">Fechas disponibles</p>
                        <div class="space-y-2 max-h-60 overflow-y-auto">
                            @foreach($this->fechasDisponibles as $disp)
                                <button wire:click="seleccionarFecha('{{ $disp->fecha->format('Y-m-d') }}')" 
                                        class="w-full text-left px-3 py-2 border rounded-lg text-sm transition
                                        {{ $fechaSeleccionada === $disp->fecha->format('Y-m-d') ? 'bg-siapc-blue text-white border-siapc-blue font-semibold' : 'bg-white text-slate-700 border-slate-200 hover:border-siapc-blue' }}">
                                    {{ $disp->fecha->translatedFormat('l, d \d\e F') }}
                                </button>
                            @endforeach
                        </div>
                    </div>

                    {{-- Horas --}}
                    <div>
                        <p class="text-xs font-semibold text-slate-600 uppercase mb-2">Horas disponibles</p>
                        @if($fechaSeleccionada)
                            <div class="grid grid-cols-2 gap-2 max-h-60 overflow-y-auto">
                                @forelse($this->horasDisponibles as $hora)
                                    <button wire:click="seleccionarHora('{{ $hora }}')"
                                            class="px-3 py-2 border rounded-lg text-sm transition text-center
                                            {{ $horaSeleccionada === $hora ? 'bg-siapc-blue text-white border-siapc-blue font-semibold' : 'bg-white text-slate-700 border-slate-200 hover:border-siapc-blue' }}">
                                        {{ $hora }}
                                    </button>
                                @empty
                                    <p class="col-span-2 text-xs text-amber-600 bg-amber-50 p-2 rounded">No hay horas disponibles para este día.</p>
                                @endforelse
                            </div>
                        @else
                            <p class="text-xs text-slate-400 italic">Selecciona una fecha primero.</p>
                        @endif
                    </div>
                </div>
                @error('hora') <span class="text-xs text-red-500 block mt-4">{{ $message }}</span> @enderror
            </div>
            
            <div class="mt-8 pt-4 border-t border-slate-100 flex justify-end">
                <button wire:click="continuarADatos" class="px-6 py-2 bg-siapc-blue text-white rounded-lg text-sm font-semibold hover:bg-siapc-indigo transition">
                    Continuar con estos datos
                </button>
            </div>
        @endif

        {{-- PASO 3: DATOS DEL PACIENTE --}}
        @if($paso === '3')
            <div class="flex-1">
                <button wire:click="$set('paso', '2')" class="text-xs text-slate-500 hover:text-siapc-blue mb-4 inline-flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver a fecha y hora
                </button>
                <h2 class="text-2xl font-bold text-slate-800 mb-6">3. Añade tus datos</h2>

                <div class="space-y-4 max-w-md">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Cédula de Identidad *</label>
                            <input type="text" wire:model="ci" placeholder="Ej: 12345678" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-siapc-blue/20 focus:border-siapc-blue">
                            @error('ci') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Correo Electrónico *</label>
                            <input type="email" wire:model="email" placeholder="tu@correo.com" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm focus:ring-2 focus:ring-siapc-blue/20 focus:border-siapc-blue">
                            @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    {{-- 👇 Si no es paciente existente, mostramos el formulario completo --}}
                    @if(!$pacienteExistente && (session('info_nuevo_paciente') || $errors->has('nombre') || $errors->has('fecha_nacimiento')))
                        
                        <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg text-xs text-blue-800">
                            No encontramos tu cédula en el sistema. Completa tus datos para registrarte.
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Nombre *</label>
                                <input type="text" wire:model="nombre" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                                @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Apellido *</label>
                                <input type="text" wire:model="apellido" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                                @error('apellido') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Fecha de Nacimiento *</label>
                            <input type="date" wire:model="fecha_nacimiento" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            @error('fecha_nacimiento') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase mb-1">Teléfono *</label>
                            <input type="text" wire:model="telefono" placeholder="0414-1234567" class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                            @error('telefono') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    @endif
                </div>
            </div>

            <div class="mt-8 pt-4 border-t border-slate-100 flex justify-end">
                {{-- Cambiamos el texto del botón según el estado --}}
                @if(!$pacienteExistente && (session('info_nuevo_paciente') || $errors->has('nombre') || $errors->has('fecha_nacimiento')))
                    <button wire:click="confirmarCita" class="px-6 py-2 bg-siapc-blue text-white rounded-lg text-sm font-semibold hover:bg-siapc-indigo transition flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Registrar y Confirmar Cita
                    </button>
                @else
                    <button wire:click="validarPaciente" class="px-6 py-2 bg-siapc-blue text-white rounded-lg text-sm font-semibold hover:bg-siapc-indigo transition flex items-center">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        Buscar y Confirmar Cita
                    </button>
                @endif
            </div>
        @endif

        {{-- PASO 4: ÉXITO --}}
        @if($paso === '4')
            <div class="flex-1 flex flex-col items-center justify-center text-center py-10">
                <div class="w-20 h-20 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mb-6">
                    <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h2 class="text-2xl font-bold text-slate-800 mb-2">¡Cita Agendada!</h2>
                <p class="text-sm text-slate-500 mb-6 max-w-md">
                    Hemos registrado tu solicitud. La recepción se pondrá en contacto contigo para confirmar los detalles.
                </p>
                <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 text-left w-full max-w-sm text-sm">
                    <p><span class="font-semibold text-slate-600">Paciente:</span> {{ $nombre }} {{ $apellido }}</p>
                    <p class="mt-1"><span class="font-semibold text-slate-600">Servicio:</span> {{ $tarifaSeleccionada->nombre }}</p>
                    <p class="mt-1"><span class="font-semibold text-slate-600">Fecha:</span> {{ \Carbon\Carbon::parse($fechaSeleccionada)->translatedFormat('l, d \d\e F') }}</p>
                    <p class="mt-1"><span class="font-semibold text-slate-600">Hora:</span> {{ $horaSeleccionada }}</p>
                </div>
            </div>
        @endif
    </div>
</div>