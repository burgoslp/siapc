<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\AgendaCompartida;
use App\Models\Disponibilidad;
use App\Models\Cita;
use Carbon\Carbon;

new class extends Component
{
    public bool $abierto = false;
    public ?AgendaCompartida $agenda = null;

    // Datos editables
    public array $horarios = []; // [ '2026-11-09' => ['activo' => true, 'inicio' => '08:00', 'fin' => '12:00', 'bloqueado' => false] ]
    public $fecha_expiracion = '';

    #[On('editar-agenda')]
    public function abrir(int $agendaId): void
    {
        $this->agenda = AgendaCompartida::with('disponibilidades')->find($agendaId);
        
        if (!$this->agenda) return;

        $this->fecha_expiracion = $this->agenda->fecha_expiracion->format('Y-m-d');
        $this->horarios = [];

        // 1. Obtener los días que tienen citas agendadas con este enlace
        $diasConCitas = Cita::where('agenda_compartida_id', $this->agenda->id)
            ->whereNotIn('estado', ['CANCELADA', 'NO_ASISTIO'])
            ->pluck('fecha')
            ->map(fn($f) => $f->format('Y-m-d'))
            ->unique()
            ->toArray();

        // 2. Cargar los horarios actuales de la agenda
        foreach ($this->agenda->disponibilidades as $disp) {
            $fecha = $disp->fecha->format('Y-m-d');
            $this->horarios[$fecha] = [
                'activo' => true,
                'inicio' => Carbon::parse($disp->hora_inicio)->format('H:i'),
                'fin' => Carbon::parse($disp->hora_fin)->format('H:i'),
                'bloqueado' => in_array($fecha, $diasConCitas), // 👈 Si tiene citas, se bloquea
            ];
        }

        // 3. Rellenar los días faltantes del rango (por si el doctor quiere activar uno nuevo)
        $inicio = Carbon::parse($this->agenda->created_at)->startOfDay();
        $fin = Carbon::parse($this->agenda->fecha_expiracion)->startOfDay();
        
        for ($date = $inicio; $date->lte($fin); $date->addDay()) {
            $fechaStr = $date->format('Y-m-d');
            if (!isset($this->horarios[$fechaStr])) {
                $this->horarios[$fechaStr] = [
                    'activo' => false,
                    'inicio' => '08:00',
                    'fin' => '12:00',
                    'bloqueado' => in_array($fechaStr, $diasConCitas),
                ];
            }
        }
        
        // Ordenar por fecha
        ksort($this->horarios);

        $this->abierto = true;
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->resetErrorBag();
    }

    public function guardar(): void
    {
        if (!$this->agenda) return;

        \DB::transaction(function () {
            // 1. Borrar los horarios anteriores (solo los que no estén bloqueados)
            foreach ($this->horarios as $fecha => $data) {
                if (!$data['bloqueado']) {
                    Disponibilidad::where('agenda_compartida_id', $this->agenda->id)
                        ->whereDate('fecha', $fecha)
                        ->delete();
                }
            }

            // 2. Crear los nuevos horarios
            foreach ($this->horarios as $fecha => $data) {
                if ($data['activo'] && $data['inicio'] < $data['fin']) {
                    Disponibilidad::create([
                        'agenda_compartida_id' => $this->agenda->id,
                        'fecha' => $fecha,
                        'hora_inicio' => $data['inicio'],
                        'hora_fin' => $data['fin'],
                    ]);
                }
            }

            // 3. Actualizar la fecha de expiración
            $this->agenda->update([
                'fecha_expiracion' => Carbon::parse($this->fecha_expiracion)->endOfDay(),
            ]);
        });

        session()->flash('success', 'Agenda actualizada correctamente.');
        $this->abierto = false;
        $this->dispatch('agenda-actualizada');
    }
};
?>

<div>
    @if ($abierto && $agenda)
        <div class="fixed inset-0 bg-black/50 z-40" wire:click="cerrar"></div>

        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="cerrar">
            <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl max-h-[90vh] flex flex-col">
                {{-- Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                    <div>
                        <h3 class="text-lg font-bold text-gray-800">Editar Agenda</h3>
                        <p class="text-xs text-gray-500 mt-0.5">Los días con citas agendadas están bloqueados.</p>
                    </div>
                    <button type="button" wire:click="cerrar" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="p-6 overflow-y-auto flex-1 space-y-4">
                    {{-- Fecha de expiración --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-1">Fecha de Expiración del Enlace</label>
                        <input type="date" wire:model="fecha_expiracion" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm">
                    </div>

                    {{-- Horarios --}}
                    <div>
                        <p class="text-xs font-semibold text-gray-600 uppercase mb-2">Días y Horarios</p>
                        <div class="space-y-2 max-h-64 overflow-y-auto pr-2">
                            @foreach($horarios as $fecha => $data)
                                <div class="flex items-center gap-3 p-2 rounded-lg border {{ $data['bloqueado'] ? 'border-rose-200 bg-rose-50' : ($data['activo'] ? 'border-siapc-blue/30 bg-siapc-blue/5' : 'border-gray-200 bg-gray-50') }}">
                                    
                                    {{-- Checkbox (Deshabilitado si está bloqueado) --}}
                                    <input type="checkbox" 
                                           wire:model.live="horarios.{{ $fecha }}.activo" 
                                           @if($data['bloqueado']) disabled @endif
                                           class="rounded text-siapc-blue focus:ring-siapc-blue disabled:opacity-50">
                                    
                                    <span class="w-32 text-sm font-medium {{ $data['bloqueado'] ? 'text-rose-700' : ($data['activo'] ? 'text-siapc-navy' : 'text-gray-400') }}">
                                        {{ \Carbon\Carbon::parse($fecha)->translatedFormat('D, d M') }}
                                    </span>
                                    
                                    @if($data['bloqueado'])
                                        <span class="text-[10px] font-bold text-rose-600 uppercase flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            Bloqueado (Tiene Citas)
                                        </span>
                                    @elseif($data['activo'])
                                        <input type="time" wire:model="horarios.{{ $fecha }}.inicio" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                        <span class="text-gray-400 text-xs">a</span>
                                        <input type="time" wire:model="horarios.{{ $fecha }}.fin" class="px-2 py-1 border border-gray-300 rounded text-xs">
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3 bg-gray-50 rounded-b-2xl">
                    <button wire:click="cerrar" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 rounded-lg">
                        Cancelar
                    </button>
                    <button wire:click="guardar" type="button" class="px-5 py-2 bg-siapc-blue text-white rounded-lg text-sm font-semibold hover:bg-siapc-indigo">
                        Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>