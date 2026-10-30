<?php

use Livewire\Component;
use Livewire\Attributes\On;
use App\Models\Cita;
use Carbon\Carbon;

new class extends Component
{
    public string $start = '';
    public string $end = '';
    public string $medicoId = '';
    public string $estado = '';

    public function mount(): void
    {
        $this->start = now()->startOfMonth()->format('Y-m-d');
        $this->end = now()->endOfMonth()->format('Y-m-d');
    }

    #[On('cargar-eventos')]
    public function cargarEventos(string $start, string $end, string $medicoId = '', string $estado = ''): void
    {
        $this->start = $start;
        $this->end = $end;
        $this->medicoId = $medicoId;
        $this->estado = $estado;

        // las fechas para incluir el día completo
        $desde = Carbon::parse($start)->startOfDay();
        $hasta = Carbon::parse($end)->endOfDay();

        $query = Cita::with(['paciente', 'medico', 'tarifa'])
            ->whereBetween('fecha', [$desde, $hasta]);

        if ($medicoId !== '') {
            $query->where('medico_id', $medicoId);
        }

        if ($estado !== '') {
            $query->where('estado', $estado);
        }

        $eventos = $query->get()->map(function ($cita) {
            $color = match ($cita->estado) {
                'PENDIENTE' => '#f59e0b', // amber-500
                'CONFIRMADA' => '#0ea5e9', // sky-500
                'COMPLETADA' => '#10b981', // emerald-500
                'CANCELADA' => '#ef4444', // red-500
                'NO_ASISTIO' => '#64748b', // slate-500
                default => '#3b82f6',
            };

            return [
                'id' => $cita->id,
                'title' => $cita->paciente?->nombre . ' ' . $cita->paciente?->apellido,
                'start' => $cita->fecha->toIso8601String(),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'extendedProps' => [
                    'medico' => 'Dr(a). ' . $cita->medico?->nombre . ' ' . $cita->medico?->apellido,
                    'estado' => $cita->estado,
                    'motivo' => $cita->motivo,
                ]
            ];
        });

        $this->dispatch('eventos-cargados', eventos: $eventos->toArray());
    }

    public function updatedMedicoId(): void
    {
        $this->dispatch('filtros-actualizados', medicoId: $this->medicoId, estado: $this->estado);
    }

    public function updatedEstado(): void
    {
        $this->dispatch('filtros-actualizados', medicoId: $this->medicoId, estado: $this->estado);
    }

    public function with(): array
    {
        return [
            'medicos' => \App\Models\Medico::where('estado', 'ACTIVO')->orderBy('apellido')->get(),
            'estados' => ['PENDIENTE', 'CONFIRMADA', 'COMPLETADA', 'CANCELADA', 'NO_ASISTIO'],
        ];
    }
};
?>

<div class="space-y-4">
    {{-- Filtros --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <select wire:model.live="medicoId" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos los médicos</option>
            @foreach ($medicos as $m)
                <option value="{{ $m->id }}">Dr(a). {{ $m->apellido }}, {{ $m->nombre }}</option>
            @endforeach
        </select>

        <select wire:model.live="estado" class="px-3 py-2 border border-gray-300 rounded-lg text-sm">
            <option value="">Todos los estados</option>
            @foreach ($estados as $e)
                <option value="{{ $e }}">{{ ucfirst(strtolower($e)) }}</option>
            @endforeach
        </select>
    </div>

    {{-- Contenedor del calendario --}}
    <div wire:ignore>
        <div id="fullcalendar-citas" x-data="calendarioCitas" x-init="init()"></div>
    </div>
</div>

@push('scripts')
    @vite('resources/js/citas-calendar.js')
@endpush