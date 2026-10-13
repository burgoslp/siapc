<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cita;
use App\Models\Medico;

new class extends Component
{
    public string $vista = 'dayGridMonth'; // dayGridMonth | timeGridWeek | timeGridDay | listWeek
    public string $medicoId = '';
    public string $estado = '';

    #[On('cita-guardada')]
    public function refrescar(): void
    {
        // Dispara el evento al JS para que recargue los eventos
        $this->dispatch('calendario-refrescar');
    }

    /**
     * Endpoint que FullCalendar llama vía fetch para obtener eventos.
     * Devuelve JSON.
     */
    #[On('cargar-eventos')]
    public function cargarEventos(string $start, string $end): void
    {
        $citas = Cita::with(['paciente', 'medico'])
            ->whereBetween('fecha', [$start, $end])
            ->when($this->medicoId, fn($q) => $q->where('medico_id', $this->medicoId))
            ->when($this->estado, fn($q) => $q->where('estado', $this->estado))
            ->get();

        $eventos = $citas->map(function ($cita) {
            $colores = [
                'PENDIENTE'    => '#eab308', // amarillo
                'CONFIRMADA'   => '#3b82f6', // azul
                'COMPLETADA'   => '#22c55e', // verde
                'CANCELADA'    => '#6b7280', // gris
                'NO_ASISTIO'   => '#ef4444', // rojo
                'REPROGRAMADA' => '#a855f7', // morado
            ];

            return [
                'id' => $cita->id,
                'title' => $cita->paciente?->nombre . ' ' . $cita->paciente?->apellido,
                'start' => $cita->fecha->format('Y-m-d\TH:i:s'),
                'backgroundColor' => $colores[$cita->estado] ?? '#6b7280',
                'borderColor' => $colores[$cita->estado] ?? '#6b7280',
                'extendedProps' => [
                    'medico' => $cita->medico?->nombre . ' ' . $cita->medico?->apellido,
                    'estado' => $cita->estado,
                    'motivo' => $cita->motivo,
                ],
            ];
        });

        $this->dispatch('eventos-cargados', eventos: $eventos);
    }

    public function with(): array
    {
        return [
            'medicos' => Medico::where('estado', 'ACTIVO')->orderBy('apellido')->get(),
        ];
    }
};
?>

<div>
    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
        <select wire:model.live="medicoId"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos los médicos</option>
            @foreach ($medicos as $m)
                <option value="{{ $m->id }}">{{ $m->nombre }} {{ $m->apellido }}</option>
            @endforeach
        </select>

        <select wire:model.live="estado"
                class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos los estados</option>
            <option value="PENDIENTE">Pendiente</option>
            <option value="CONFIRMADA">Confirmada</option>
            <option value="COMPLETADA">Completada</option>
            <option value="CANCELADA">Cancelada</option>
            <option value="NO_ASISTIO">No asistió</option>
        </select>
    </div>

    {{-- Contenedor del calendario --}}
    <div
        x-data="calendarioCitas"
        x-init="init()"
        @calendario-refrescar.window="recargarEventos()"
        @eventos-cargados.window="actualizarEventos($event.detail.eventos)"
        class="bg-white rounded-xl shadow-sm border border-gray-100 p-4"
        wire:ignore
    >
        <div id="fullcalendar-citas"></div>
    </div>
</div>
