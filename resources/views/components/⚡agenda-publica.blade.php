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
    public $empresa = [];

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

        $empresaDb = \App\Models\Empresa::all();
        foreach ($empresaDb as $e) {
            $this->empresa[$e->clave] = $e->valor;
        }
    }

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

        // 🕵️‍♂️ PRE-CHECK: Validar que el slot no haya sido tomado mientras el paciente elegía
        $fechaHora = Carbon::parse($this->fechaSeleccionada . ' ' . $this->horaSeleccionada);
        $duracion = $this->tarifaSeleccionada->duracion_minutos ?? 30;

        $slotTomado = Cita::where('medico_id', $this->medico->id)
            ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
            ->where('fecha', '<', $fechaHora->copy()->addMinutes($duracion))
            ->whereRaw('DATE_ADD(fecha, INTERVAL ' . (int) $duracion . ' MINUTE) > ?', [$fechaHora])
            ->exists();

        if ($slotTomado) {
            $this->horaSeleccionada = null; // Limpiamos la hora seleccionada
            session()->flash('slot_error', '¡Ups! Alguien te ha ganado, intenta en otro horario.');
            return; // Nos quedamos en el paso 2
        }

        // Si todo está bien, avanzamos al paso 3
        $this->paso = '3';
    }

    public function validarPaciente()
    {
        $this->validate([
            'ci' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ]);

        $paciente = Paciente::where('ci', $this->ci)->first();

        if ($paciente) {
            if ($paciente->email !== $this->email) {
                $this->addError('email', 'El correo no coincide con el registrado para esta cédula. Verifica o usa otro.');
                return;
            }
            $this->pacienteExistente = $paciente;
            $this->nombre = $paciente->nombre;
            $this->apellido = $paciente->apellido;
            $this->confirmarCita();
        } else {
            $this->pacienteExistente = null;
            session()->flash('info_nuevo_paciente', 'No encontramos tu cédula. Por favor, completa tus datos para registrarte.');
        }
    }

    public function confirmarCita()
    {
        // 1. Validación previa (fuera de transacción) de datos del paciente nuevo
        if (!$this->pacienteExistente) {
            $this->validate([
                'nombre' => 'required|string|max:255',
                'apellido' => 'required|string|max:255',
                'telefono' => 'required|string|max:30',
                'fecha_nacimiento' => 'required|date',
            ]);
        }

        // 2. Verificación rápida antes de la transacción (para UX)
        if (!$this->agenda->activo || $this->agenda->fecha_expiracion->isPast()) {
            $this->addError('general', 'Este enlace de agendamiento ya no está disponible.');
            return;
        }

        $slotTomado = false;
        $agendaExpirada = false;

        try {
            \DB::transaction(function () use (&$slotTomado, &$agendaExpirada) {

                // 🔒 Bloqueamos la agenda para evitar race conditions
                $agendaFresh = AgendaCompartida::where('id', $this->agenda->id)
                    ->lockForUpdate()
                    ->first();

                // 3. Re-validación de activo / expiración dentro de la transacción
                if (!$agendaFresh || !$agendaFresh->activo || $agendaFresh->fecha_expiracion->isPast()) {
                    $agendaExpirada = true;
                    throw new \RuntimeException('agenda_expirada');
                }

                // 4. Re-validación de que el slot siga libre (bloqueo de la cita)
                $fechaHora = Carbon::parse($this->fechaSeleccionada . ' ' . $this->horaSeleccionada);
                $duracion = $this->tarifaSeleccionada->duracion_minutos ?? 30;

                $conflicto = Cita::where('medico_id', $this->medico->id)
                    ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
                    ->where('fecha', '<', $fechaHora->copy()->addMinutes($duracion))
                    ->whereRaw('DATE_ADD(fecha, INTERVAL ' . (int) $duracion . ' MINUTE) > ?', [$fechaHora])
                    ->lockForUpdate()
                    ->exists();

                if ($conflicto) {
                    $slotTomado = true;
                    throw new \RuntimeException('slot_tomado');
                }

                // 5. Crear / obtener paciente
                if ($this->pacienteExistente) {
                    $paciente = $this->pacienteExistente;
                } else {
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

                // 6. Crear la cita
                Cita::create([
                    'paciente_id' => $paciente->id,
                    'medico_id' => $this->medico->id,
                    'agenda_compartida_id' => $this->agenda->id,
                    'fecha' => $fechaHora,
                    'motivo' => $this->tarifaSeleccionada->nombre,
                    'estado' => 'PENDIENTE',
                    'tarifa_id' => $this->tarifaSeleccionada->id,
                    'precio' => $this->tarifaSeleccionada->precio_usd,
                    'total' => $this->tarifaSeleccionada->precio_usd,
                    'estado_pago' => 'PENDIENTE',
                ]);
            });
        } catch (\RuntimeException $e) {

            if ($e->getMessage() === 'slot_tomado') {
                // 🔁 Volver al Paso 2, limpiar hora y flash
                $this->horaSeleccionada = null;
                $this->paso = '2';
                session()->flash('slot_error', '¡Ups! Alguien te ha ganado, intenta en otro horario.');
                return;
            }

            if ($e->getMessage() === 'agenda_expirada') {
                $this->addError('general', 'Este enlace de agendamiento ya no está disponible.');
                return;
            }

            throw $e;
        }

        $this->paso = '4';
    }

    public function getFechasDisponiblesProperty()
    {
        return $this->agenda->disponibilidades;
    }

    public function getHorasDisponiblesProperty()
    {
        if (!$this->fechaSeleccionada) return [];

        $disponibilidad = $this->agenda->disponibilidades
            ->filter(fn($d) => $d->fecha->format('Y-m-d') === $this->fechaSeleccionada)
            ->first();

        if (!$disponibilidad) return [];

        $inicio = Carbon::parse($this->fechaSeleccionada . ' ' . $disponibilidad->hora_inicio);
        $fin = Carbon::parse($this->fechaSeleccionada . ' ' . $disponibilidad->hora_fin);

        $duracion = $this->tarifaSeleccionada->duracion_minutos ?? 30;

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
    
    {{-- Sidebar: Info del Doctor y Clínica --}}
    <div class="bg-slate-50 p-8 md:w-1/3 border-r border-slate-100 flex flex-col items-center text-center">
        
        {{-- Logo de la Clínica (si lo tiene) --}}
        @if(!empty($this->empresa['logo']) && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->empresa['logo']))
            <img src="{{ \Illuminate\Support\Facades\Storage::url($this->empresa['logo']) }}" alt="Logo Clínica" class="w-20 h-20 object-contain rounded-2xl shadow-sm mb-4 bg-white p-2">
        @else
            <img src="{{ asset('images/logo/logo2.png') }}" alt="Logo" class="w-16 h-16 rounded-2xl shadow-sm mb-4">
        @endif

        <h2 class="text-xs font-bold text-slate-500 uppercase tracking-wider">Cita con</h2>
        <h3 class="text-xl font-bold text-siapc-blue mt-1">Dr(a). {{ $this->medico->nombre }} {{ $this->medico->apellido }}</h3>
        <p class="text-xs text-slate-500 mt-1">{{ $this->medico->especialidades->first()?->nombre }}</p>
        
        <div class="mt-6 text-left w-full space-y-4 border-t border-slate-200 pt-4">
            
            {{-- Nombre de la Clínica --}}
            <div class="flex items-start text-xs text-slate-600">
                <svg class="w-4 h-4 mr-2 text-siapc-blue shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <span class="font-semibold">{{ $this->empresa['nombre_clinica'] ?? 'Consultorio Dental' }}</span>
            </div>

            {{-- Dirección --}}
            @if(!empty($this->empresa['direccion']))
            <div class="flex items-start text-xs text-slate-600">
                <svg class="w-4 h-4 mr-2 text-siapc-blue shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span>{{ $this->empresa['direccion'] }}</span>
            </div>
            @endif

            {{-- Zona Horaria --}}
            <div class="flex items-start text-xs text-slate-600">
                <svg class="w-4 h-4 mr-2 text-siapc-blue shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Hora del proveedor: America/Caracas</span>
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

                {{-- 👇 ALERTA DE SLOT TOMADO (NUEVO) 👇 --}}
                @if(session('slot_error'))
                    <div class="mb-6 p-3 bg-amber-50 border border-amber-200 rounded-lg flex items-start gap-2 animate-pulse">
                        <svg class="w-5 h-5 text-amber-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-sm text-amber-800 font-medium">{{ session('slot_error') }}</p>
                    </div>
                @endif

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
                {{-- 👇 BOTÓN DINÁMICO CON MENSAJE DE ERROR 👇 --}}
                @if(session('slot_error'))
                    <button wire:click="$refresh" class="px-6 py-2 bg-siapc-blue text-white rounded-lg text-sm font-semibold hover:bg-siapc-indigo transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Ver otros horarios
                    </button>
                @else
                    <button wire:click="continuarADatos" class="px-6 py-2 bg-siapc-blue text-white rounded-lg text-sm font-semibold hover:bg-siapc-indigo transition">
                        Continuar con estos datos
                    </button>
                @endif
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