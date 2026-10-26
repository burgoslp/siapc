<?php

use Livewire\Component;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Paciente;
use Carbon\Carbon;

new class extends Component
{
    public $fecha_desde = '';
    public $fecha_hasta = '';

    public function mount(): void
    {
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function updatedFechaDesde(): void {}
    public function updatedFechaHasta(): void {}

    public function setRango(string $rango): void
    {
        match ($rango) {
            'hoy' => [$this->fecha_desde = now()->format('Y-m-d'), $this->fecha_hasta = now()->format('Y-m-d')],
            'semana' => [$this->fecha_desde = now()->startOfWeek()->format('Y-m-d'), $this->fecha_hasta = now()->endOfWeek()->format('Y-m-d')],
            'mes' => [$this->fecha_desde = now()->startOfMonth()->format('Y-m-d'), $this->fecha_hasta = now()->endOfMonth()->format('Y-m-d')],
            'mes_anterior' => [$this->fecha_desde = now()->subMonth()->startOfMonth()->format('Y-m-d'), $this->fecha_hasta = now()->subMonth()->endOfMonth()->format('Y-m-d')],
            default => null,
        };
    }

    public function with(): array
    {
        $desde = Carbon::parse($this->fecha_desde)->startOfDay();
        $hasta = Carbon::parse($this->fecha_hasta)->endOfDay();

        // 1. KPIs Generales de Citas
        $citasQuery = Cita::whereBetween('fecha', [$desde, $hasta]);
        
        $totalCitas = (clone $citasQuery)->count();
        $citasCompletadas = (clone $citasQuery)->where('estado', 'COMPLETADA')->count();
        $citasCanceladas = (clone $citasQuery)->whereIn('estado', ['CANCELADA', 'NO_ASISTIO'])->count();
        
        // Tasa de asistencia (Completadas / (Total - Canceladas))
        $citasValidas = $totalCitas - $citasCanceladas;
        $tasaAsistencia = $citasValidas > 0 ? round(($citasCompletadas / $citasValidas) * 100, 1) : 0;

        // 2. Pacientes Nuevos vs Recurrentes
        // Paciente nuevo: Su fecha de created_at está en el rango
        $pacientesNuevos = Paciente::whereBetween('created_at', [$desde, $hasta])->count();
        
        // Pacientes atendidos en el rango (únicos)
        $pacientesAtendidos = (clone $citasQuery)->where('estado', 'COMPLETADA')->distinct('paciente_id')->count('paciente_id');
        
        // Recurrentes = Atendidos - Nuevos (si es negativo, se ajusta a 0)
        $pacientesRecurrentes = max(0, $pacientesAtendidos - $pacientesNuevos);

        // 3. Rendimiento por Odontólogo
        $rendimientoMedicos = Cita::with('medico')
            ->whereBetween('fecha', [$desde, $hasta])
            ->where('estado', 'COMPLETADA')
            ->get()
            ->groupBy('medico_id')
            ->map(function ($citas, $medicoId) {
                $medico = $citas->first()->medico;
                return [
                    'nombre' => $medico ? "Dr(a). {$medico->apellido}, {$medico->nombre}" : 'Desconocido',
                    'citas' => $citas->count(),
                    'consultas' => Consulta::whereIn('cita_id', $citas->pluck('id'))->count(),
                ];
            })
            ->sortByDesc('citas')
            ->values()
            ->toArray();

        // 4. Top Tratamientos (Basado en las tarifas de las citas)
        $topTratamientos = Cita::with('tarifa')
            ->whereBetween('fecha', [$desde, $hasta])
            ->where('estado', 'COMPLETADA')
            ->whereNotNull('tarifa_id')
            ->get()
            ->groupBy('tarifa_id')
            ->map(function ($citas) {
                return [
                    'nombre' => $citas->first()->tarifa->nombre ?? 'Desconocido',
                    'cantidad' => $citas->count(),
                ];
            })
            ->sortByDesc('cantidad')
            ->take(5) // Top 5
            ->values()
            ->toArray();

        return [
            'totalCitas' => $totalCitas,
            'citasCompletadas' => $citasCompletadas,
            'citasCanceladas' => $citasCanceladas,
            'tasaAsistencia' => $tasaAsistencia,
            'pacientesNuevos' => $pacientesNuevos,
            'pacientesRecurrentes' => $pacientesRecurrentes,
            'rendimientoMedicos' => $rendimientoMedicos,
            'topTratamientos' => $topTratamientos,
        ];
    }
};
?>

<div class="space-y-6">
    
    {{-- FILTROS --}}
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex flex-wrap gap-2">
            <button wire:click="setRango('hoy')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">Hoy</button>
            <button wire:click="setRango('semana')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">Esta Semana</button>
            <button wire:click="setRango('mes')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">Este Mes</button>
            <button wire:click="setRango('mes_anterior')" class="px-3 py-1.5 text-xs font-medium rounded-lg border border-gray-200 text-gray-600 hover:bg-gray-50 transition">Mes Anterior</button>
        </div>
        <div class="flex items-center gap-2">
            <input type="date" wire:model.live="fecha_desde" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
            <span class="text-gray-400">al</span>
            <input type="date" wire:model.live="fecha_hasta" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
        </div>
    </div>

    {{-- TARJETAS KPI --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        {{-- Total Citas --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Citas Agendadas</p>
            <p class="text-3xl font-bold text-gray-800 mt-2">{{ $totalCitas }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $citasCompletadas }} atendidas | {{ $citasCanceladas }} perdidas</p>
        </div>

        {{-- Tasa de Asistencia --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Tasa de Asistencia</p>
            <p class="text-3xl font-bold {{ $tasaAsistencia >= 80 ? 'text-teal-600' : 'text-amber-500' }} mt-2">{{ $tasaAsistencia }}%</p>
            <p class="text-xs text-gray-500 mt-1">Pacientes que sí asistieron</p>
        </div>

        {{-- Pacientes Nuevos --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Pacientes Nuevos</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $pacientesNuevos }}</p>
            <p class="text-xs text-gray-500 mt-1">Registrados en este período</p>
        </div>

        {{-- Pacientes Recurrentes --}}
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Pacientes Recurrentes</p>
            <p class="text-3xl font-bold text-purple-600 mt-2">{{ $pacientesRecurrentes }}</p>
            <p class="text-xs text-gray-500 mt-1">Pacientes que volvieron</p>
        </div>
    </div>

    {{-- SECCIÓN DE TABLAS --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        {{-- Rendimiento por Odontólogo --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-800">Rendimiento por Odontólogo</h3>
            </div>
            <div class="p-5">
                @forelse ($rendimientoMedicos as $medico)
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $medico['nombre'] }}</span>
                            <span class="font-bold text-gray-800">{{ $medico['citas'] }} citas</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php
                                // Calculamos el porcentaje respecto al que más citas tuvo
                                $maxCitas = count($rendimientoMedicos) > 0 ? max(array_column($rendimientoMedicos, 'citas')) : 1;
                                $porcentaje = ($medico['citas'] / $maxCitas) * 100;
                            @endphp
                            <div class="bg-teal-500 h-2 rounded-full" style="width: {{ $porcentaje }}%"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">{{ $medico['consultas'] }} consultas clínicas registradas</p>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">No hay citas completadas en este período.</p>
                @endforelse
            </div>
        </div>

        {{-- Top 5 Tratamientos --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-800">Top 5 Tratamientos Realizados</h3>
            </div>
            <div class="p-5">
                @forelse ($topTratamientos as $tratamiento)
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $tratamiento['nombre'] }}</span>
                            <span class="font-bold text-gray-800">{{ $tratamiento['cantidad'] }} veces</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php
                                $maxCantidad = count($topTratamientos) > 0 ? max(array_column($topTratamientos, 'cantidad')) : 1;
                                $porcentaje = ($tratamiento['cantidad'] / $maxCantidad) * 100;
                            @endphp
                            <div class="bg-blue-500 h-2 rounded-full" style="width: {{ $porcentaje }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">No hay tratamientos registrados en este período.</p>
                @endforelse
            </div>
        </div>

    </div>

</div>