<?php

use Livewire\Component;
use App\Models\AgendaCompartida;
use App\Models\Disponibilidad;
use App\Models\Medico;
use App\Models\Tarifa;
use Carbon\Carbon;
use Livewire\Attributes\On;

new class extends Component
{
    public bool $abierto = false;
    public string $paso = '1';

    // Paso 1
    public $medico_id = '';
    public $fecha_inicio = '';
    public $fecha_fin = '';

    // Paso 2
    public array $horarios = []; // [ '2026-11-09' => ['activo' => true, 'inicio' => '08:00', 'fin' => '12:00'] ]

    // Paso 3
    public array $tarifasSeleccionadas = [];
    public $enlaceGenerado = null;

    #[On('abrir-agenda-form')]
    public function abrirModal(): void
    {
        $this->reset();
        $this->fecha_inicio = now()->format('Y-m-d');
        $this->fecha_fin = now()->addDays(5)->format('Y-m-d');
        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function siguientePaso(): void
    {
        if ($this->paso === '1') {
            $this->validate([
                'medico_id' => 'required|exists:medicos,id',
                'fecha_inicio' => 'required|date',
                'fecha_fin' => 'required|date|after_or_equal:fecha_inicio',
            ]);

            // Generar los días del rango
            $inicio = Carbon::parse($this->fecha_inicio);
            $fin = Carbon::parse($this->fecha_fin);
            
            $this->horarios = [];
            for ($date = $inicio; $date->lte($fin); $date->addDay()) {
                if ($date->isWeekday()) { // Por defecto solo activamos Lunes a Viernes
                    $this->horarios[$date->format('Y-m-d')] = [
                        'activo' => true,
                        'inicio' => '08:00',
                        'fin' => '12:00',
                    ];
                } else {
                    $this->horarios[$date->format('Y-m-d')] = [
                        'activo' => false,
                        'inicio' => '08:00',
                        'fin' => '12:00',
                    ];
                }
            }
            $this->paso = '2';
        } 
        elseif ($this->paso === '2') {
            // Validar que al menos un día esté activo
            $hayDiaActivo = collect($this->horarios)->contains('activo', true);
            if (!$hayDiaActivo) {
                $this->addError('horarios', 'Debes activar al menos un día con horario.');
                return;
            }
            $this->paso = '3';
        }
    }

    public function pasoAnterior(): void
    {
        if ($this->paso === '2') $this->paso = '1';
        if ($this->paso === '3') $this->paso = '2';
    }

    // Utilidad para copiar el horario del primer día a todos
    public function copiarHorarioATodos(): void
    {
        $primerDiaActivo = collect($this->horarios)->firstWhere('activo', true);
        if ($primerDiaActivo) {
            foreach ($this->horarios as $fecha => $horario) {
                $this->horarios[$fecha]['inicio'] = $primerDiaActivo['inicio'];
                $this->horarios[$fecha]['fin'] = $primerDiaActivo['fin'];
            }
        }
    }

    public function generarEnlace(): void
    {
        $this->validate([
            'tarifasSeleccionadas' => 'required|array|min:1',
            'tarifasSeleccionadas.*' => 'exists:tarifas,id',
        ], [
            'tarifasSeleccionadas.required' => 'Debes seleccionar al menos un servicio.'
        ]);

        // Crear la Agenda Compartida
        $agenda = AgendaCompartida::create([
            'medico_id' => $this->medico_id,
            'creado_por' => auth()->id(),
            'token' => \Str::random(40),
            'fecha_expiracion' => Carbon::parse($this->fecha_fin)->endOfDay(), // Expira al final del último día
            'activo' => true,
        ]);

        // Guardar los horarios activos
        foreach ($this->horarios as $fecha => $horario) {
            if ($horario['activo'] && $horario['inicio'] < $horario['fin']) {
                Disponibilidad::create([
                    'agenda_compartida_id' => $agenda->id,
                    'fecha' => $fecha,
                    'hora_inicio' => $horario['inicio'],
                    'hora_fin' => $horario['fin'],
                ]);
            }
        }

        // Guardar las tarifas
        $agenda->tarifas()->sync($this->tarifasSeleccionadas);

        // Generar URL
        $this->enlaceGenerado = route('public.agenda', ['token' => $agenda->token]);
    }

    public function with(): array
    {
        return [
            'medicos' => Medico::where('estado', 'ACTIVO')->orderBy('apellido')->get(),
            'tarifas' => Tarifa::where('activo', true)->orderBy('nombre')->get(),
        ];
    }
};
?>

<div>
    @if ($abierto)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">

                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Crear Enlace de Auto-Agendamiento</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Los pacientes podrán agendar citas en los horarios que definas.</p>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                {{-- Indicador de Pasos --}}
                @if(!$enlaceGenerado)
                    <div class="px-6 py-3 bg-slate-50 border-b border-gray-100 flex items-center justify-center gap-6 text-xs font-semibold">
                        <span class="{{ $paso === '1' ? 'text-siapc-blue' : 'text-gray-400' }}">1. Fechas y Médico</span>
                        <span class="text-gray-300">→</span>
                        <span class="{{ $paso === '2' ? 'text-siapc-blue' : 'text-gray-400' }}">2. Horarios</span>
                        <span class="text-gray-300">→</span>
                        <span class="{{ $paso === '3' ? 'text-siapc-blue' : 'text-gray-400' }}">3. Servicios y Enlace</span>
                    </div>
                @endif

                <div class="p-6 overflow-y-auto flex-1">
                    {{-- CONTENIDO: ENLACE GENERADO --}}
                    @if($enlaceGenerado)
                        <div class="text-center py-8">
                            <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">¡Enlace Creado!</h3>
                            <p class="text-sm text-gray-500 mb-6">Copia este enlace y envíalo a tus pacientes por WhatsApp.</p>
                            
                            <div class="flex items-center bg-slate-50 border border-slate-200 rounded-lg p-2 max-w-lg mx-auto">
                                <input type="text" value="{{ $enlaceGenerado }}" readonly class="flex-1 bg-transparent border-none text-sm text-slate-700 focus:ring-0 px-2">
                                <button onclick="navigator.clipboard.writeText('{{ $enlaceGenerado }}'); alert('Enlace copiado');" type="button" class="px-4 py-2 bg-siapc-blue text-white rounded-md text-xs font-bold hover:bg-siapc-indigo transition">
                                    Copiar
                                </button>
                            </div>
                        </div>
                    @else
                        {{-- PASO 1 --}}
                        @if($paso === '1')
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-1">Seleccionar Odontólogo *</label>
                                    <select wire:model="medico_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        <option value="">Seleccione un médico...</option>
                                        @foreach($medicos as $m)
                                            <option value="{{ $m->id }}">Dr(a). {{ $m->apellido }}, {{ $m->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @error('medico_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Fecha de Inicio *</label>
                                        <input type="date" wire:model="fecha_inicio" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        @error('fecha_inicio') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                    <div>
                                        <label class="block text-xs font-semibold text-gray-700 mb-1">Fecha de Fin *</label>
                                        <input type="date" wire:model="fecha_fin" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                                        @error('fecha_fin') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                                    </div>
                                </div>
                            </div>
                        @endif

                        {{-- PASO 2 --}}
                        @if($paso === '2')
                            <div class="space-y-4">
                                <div class="flex justify-between items-center mb-2">
                                    <p class="text-xs text-gray-500">Activa los días que deseas abrir y define sus horarios.</p>
                                    <button wire:click="copiarHorarioATodos" type="button" class="text-xs font-semibold text-siapc-blue hover:text-siapc-indigo">
                                        Copiar horario del primer día
                                    </button>
                                </div>
                                
                                <div class="space-y-2 max-h-64 overflow-y-auto pr-2">
                                    @foreach($horarios as $fecha => $data)
                                        <div class="flex items-center gap-3 p-2 rounded-lg border {{ $data['activo'] ? 'border-siapc-blue/30 bg-siapc-blue/5' : 'border-gray-200 bg-gray-50' }}">
                                            <input type="checkbox" wire:model.live="horarios.{{ $fecha }}.activo" class="rounded text-siapc-blue focus:ring-siapc-blue">
                                            
                                            <span class="w-32 text-sm font-medium {{ $data['activo'] ? 'text-siapc-navy' : 'text-gray-400' }}">
                                                {{ \Carbon\Carbon::parse($fecha)->translatedFormat('D, d M') }}
                                            </span>
                                            
                                            @if($data['activo'])
                                                <input type="time" wire:model="horarios.{{ $fecha }}.inicio" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                                <span class="text-gray-400 text-xs">a</span>
                                                <input type="time" wire:model="horarios.{{ $fecha }}.fin" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                                @error('horarios') <span class="text-xs text-red-500 block mt-2">{{ $message }}</span> @enderror
                            </div>
                        @endif

                        {{-- PASO 3 --}}
                        @if($paso === '3')
                            <div class="space-y-4">
                                <p class="text-xs text-gray-500 mb-2">Selecciona los servicios que el paciente podrá elegir en el enlace.</p>
                                
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto p-1">
                                    @foreach($tarifas as $t)
                                        <label class="flex items-start p-3 border border-gray-200 rounded-lg cursor-pointer hover:bg-gray-50 transition {{ in_array($t->id, $tarifasSeleccionadas) ? 'border-siapc-blue/50 bg-siapc-blue/5' : '' }}">
                                            <input type="checkbox" wire:model.live="tarifasSeleccionadas" value="{{ $t->id }}" class="mt-0.5 rounded text-siapc-blue focus:ring-siapc-blue">
                                            <div class="ml-3">
                                                <p class="text-sm font-semibold text-gray-800">{{ $t->nombre }}</p>
                                                <p class="text-[10px] text-gray-500">{{ $t->duracion_minutos ?? 30 }} mins · ${{ number_format($t->precio_usd, 2) }}</p>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                @error('tarifasSeleccionadas') <span class="text-xs text-red-500 block mt-2">{{ $message }}</span> @enderror
                            </div>
                        @endif
                    @endif
                </div>

                {{-- Footer (Botones) --}}
                @if(!$enlaceGenerado)
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-between bg-gray-50 rounded-b-2xl">
                        @if($paso !== '1')
                            <button wire:click="pasoAnterior" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg">
                                Atrás
                            </button>
                        @else
                            <div></div> {{-- Espaciador --}}
                        @endif

                        @if($paso !== '3')
                            <button wire:click="siguientePaso" type="button" class="px-5 py-2 bg-siapc-blue text-white rounded-lg text-sm font-semibold hover:bg-siapc-indigo inline-flex items-center">
                                Continuar
                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        @else
                            <button wire:click="generarEnlace" type="button" class="px-5 py-2 bg-siapc-blue text-white rounded-lg text-sm font-semibold hover:bg-siapc-indigo inline-flex items-center">
                                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                                Generar Enlace
                            </button>
                        @endif
                    </div>
                @else
                    <div class="px-6 py-4 border-t border-gray-100 flex justify-end bg-gray-50 rounded-b-2xl">
                        <button wire:click="cerrar" type="button" class="px-5 py-2 bg-slate-800 text-white rounded-lg text-sm font-semibold hover:bg-slate-900">
                            Finalizar
                        </button>
                    </div>
                @endif

            </div>
        </div>
    @endif
</div>