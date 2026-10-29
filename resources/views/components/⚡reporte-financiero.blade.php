<?php

use Livewire\Component;
use App\Models\Pago;
use App\Models\Gasto;
use Carbon\Carbon;

new class extends Component
{
    public $fecha_desde = '';
    public $fecha_hasta = '';
    public $moneda_visual = 'bs'; // 'bs' o 'usd'

    public function mount(): void
    {
        $this->fecha_desde = now()->startOfMonth()->format('Y-m-d');
        $this->fecha_hasta = now()->format('Y-m-d');
    }

    public function updatedFechaDesde(): void { $this->dispatch('redibujar-graficos'); }
    public function updatedFechaHasta(): void { $this->dispatch('redibujar-graficos'); }

    public function setRango(string $rango): void
    {
        match ($rango) {
            'hoy' => [$this->fecha_desde = now()->format('Y-m-d'), $this->fecha_hasta = now()->format('Y-m-d')],
            'semana' => [$this->fecha_desde = now()->startOfWeek()->format('Y-m-d'), $this->fecha_hasta = now()->endOfWeek()->format('Y-m-d')],
            'mes' => [$this->fecha_desde = now()->startOfMonth()->format('Y-m-d'), $this->fecha_hasta = now()->endOfMonth()->format('Y-m-d')],
            'mes_anterior' => [$this->fecha_desde = now()->subMonth()->startOfMonth()->format('Y-m-d'), $this->fecha_hasta = now()->subMonth()->endOfMonth()->format('Y-m-d')],
            default => null,
        };
        $this->dispatch('redibujar-graficos');
    }

    private function getEvolucionDiaria($desde, $hasta): array
    {
        $periodo = \Carbon\CarbonPeriod::create($desde, $hasta);
        $labels = [];
        $ingresosPorDia = [];
        $egresosPorDia = [];

        $pagos = Pago::where('estado', 'ACTIVO')
            ->whereBetween('created_at', [$desde, $hasta])
            ->get();
        $gastos = Gasto::whereBetween('fecha_gasto', [$desde, $hasta])->get();

        foreach ($periodo as $fecha) {
            $dia = $fecha->format('Y-m-d');
            $labels[] = $fecha->format('d/m');

            $ingresosDia = 0;
            foreach ($pagos as $p) {
                if ($p->created_at->format('Y-m-d') === $dia) {
                    // Regla de conversión para PAGOS en la gráfica
                    $tasa = (float) ($p->tasa_momento ?: 1);
                    if ($this->moneda_visual === 'bs') {
                        $ingresosDia += $p->moneda === 'bs' ? (float) $p->monto : (float) $p->monto * $tasa;
                    } else {
                        $ingresosDia += $p->moneda === 'dolar' ? (float) $p->monto : (float) $p->monto / $tasa;
                    }
                }
            }

            $egresosDia = 0;
            foreach ($gastos as $g) {
                if ($g->fecha_gasto->format('Y-m-d') === $dia) {
                    // Regla de conversión para GASTOS en la gráfica
                    $tasa = (float) ($g->tasa_cambio ?: 1);
                    if ($this->moneda_visual === 'bs') {
                        $egresosDia += $g->moneda === 'bs' ? (float) $g->monto : (float) $g->monto * $tasa;
                    } else {
                        $egresosDia += $g->moneda === 'dolar' ? (float) $g->monto : (float) $g->monto / $tasa;
                    }
                }
            }

            $ingresosPorDia[] = round($ingresosDia, 2);
            $egresosPorDia[] = round($egresosDia, 2);
        }

        return [
            'labels' => $labels,
            'ingresos' => $ingresosPorDia,
            'egresos' => $egresosPorDia,
        ];
    }

    public function with(): array
    {
        $desde = Carbon::parse($this->fecha_desde)->startOfDay();
        $hasta = Carbon::parse($this->fecha_hasta)->endOfDay();

        $pagos = Pago::with(['cita.medico'])->where('estado', 'ACTIVO')
            ->whereBetween('created_at', [$desde, $hasta])
            ->get();

        $gastos = Gasto::whereBetween('fecha_gasto', [$desde, $hasta])->get();

        // 1. Calcular Totales
        $ingresosTotales = 0;
        $egresosTotales = 0;
        $desgloseFormas = [
            'EFECTIVO_BS'     => 0,
            'EFECTIVO_USD'    => 0,
            'TRANSFERENCIA'   => 0,
            'PAGO_MOVIL'      => 0,
            'TARJETA_DEBITO'  => 0,
            'TARJETA_CREDITO' => 0,
            'ZELLE'           => 0,
            'BINANCE'         => 0,
            'OTRO'            => 0,
        ];
        $desgloseMedicos = [];

        foreach ($pagos as $p) {
            // Regla de conversión para PAGOS en los KPIs
            $tasa = (float) ($p->tasa_momento ?: 1);
            if ($this->moneda_visual === 'bs') {
                $montoFinal = $p->moneda === 'bs' ? (float) $p->monto : (float) $p->monto * $tasa;
            } else {
                $montoFinal = $p->moneda === 'dolar' ? (float) $p->monto : (float) $p->monto / $tasa;
            }
            
            $ingresosTotales += $montoFinal;

            // Desglose por forma de pago
            $forma = $p->forma_pago;
            if ($forma === 'EFECTIVO') {
                $forma = $p->moneda === 'bs' ? 'EFECTIVO_BS' : 'EFECTIVO_USD';
            }
            if (isset($desgloseFormas[$forma])) {
                $desgloseFormas[$forma] += $montoFinal;
            } else {
                $desgloseFormas['OTRO'] += $montoFinal;
            }

            // Desglose por médico
            $medicoNombre = $p->cita?->medico ? "Dr(a). " . $p->cita->medico->apellido . ", " . $p->cita->medico->nombre : 'Sin médico asignado';
            if (!isset($desgloseMedicos[$medicoNombre])) {
                $desgloseMedicos[$medicoNombre] = 0;
            }
            $desgloseMedicos[$medicoNombre] += $montoFinal;
        }

        foreach ($gastos as $g) {
            // Regla de conversión para GASTOS en los KPIs
            $tasa = (float) ($g->tasa_cambio ?: 1);
            if ($this->moneda_visual === 'bs') {
                $montoFinal = $g->moneda === 'bs' ? (float) $g->monto : (float) $g->monto * $tasa;
            } else {
                $montoFinal = $g->moneda === 'dolar' ? (float) $g->monto : (float) $g->monto / $tasa;
            }
            $egresosTotales += $montoFinal;
        }

        $utilidad = $ingresosTotales - $egresosTotales;

        // Formatear desgloses
        $formasFormateadas = [];
        foreach ($desgloseFormas as $key => $monto) {
            if ($monto > 0) {
                $label = match($key) {
                    'EFECTIVO_BS' => 'Efectivo (Bs)',
                    'EFECTIVO_USD' => 'Efectivo (USD)',
                    'TRANSFERENCIA' => 'Transferencia',
                    'PAGO_MOVIL' => 'Pago Móvil',
                    'TARJETA_DEBITO' => 'Tarjeta Débito',
                    'TARJETA_CREDITO' => 'Tarjeta Crédito',
                    'ZELLE' => 'Zelle',
                    'BINANCE' => 'Binance',
                    default => 'Otro'
                };
                $formasFormateadas[$label] = $monto;
            }
        }
        arsort($formasFormateadas);

        $medicosFormateados = [];
        foreach ($desgloseMedicos as $medico => $monto) {
            $medicosFormateados[$medico] = $monto;
        }
        arsort($medicosFormateados);

        // Datos para los gráficos
        $evolucion = $this->getEvolucionDiaria($desde, $hasta);

        return [
            'ingresos' => $ingresosTotales,
            'egresos' => $egresosTotales,
            'utilidad' => $utilidad,
            'simbolo' => $this->moneda_visual === 'usd' ? '$' : 'Bs ',
            'cantidad_pagos' => $pagos->count(),
            'cantidad_gastos' => $gastos->count(),
            'desgloseFormas' => $formasFormateadas,
            'desgloseMedicos' => $medicosFormateados,
            'totalIngresosFormas' => $ingresosTotales > 0 ? $ingresosTotales : 1,
            
            // Datos para gráficos
            'chartLineLabels' => $evolucion['labels'],
            'chartLineIngresos' => $evolucion['ingresos'],
            'chartLineEgresos' => $evolucion['egresos'],
            'chartPieLabels' => array_keys($formasFormateadas),
            'chartPieSeries' => array_values($formasFormateadas),
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
        <div class="flex flex-col sm:flex-row items-center gap-3">
            <div class="flex items-center gap-2">
                <input type="date" wire:model.live="fecha_desde" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
                <span class="text-gray-400">al</span>
                <input type="date" wire:model.live="fecha_hasta" class="px-2 py-1.5 border border-gray-300 rounded-lg text-sm">
            </div>
            <div class="flex bg-gray-100 p-1 rounded-lg">
                <button wire:click="$set('moneda_visual', 'bs'); $dispatch('redibujar-graficos')" class="px-3 py-1 text-xs font-semibold rounded-md {{ $moneda_visual === 'bs' ? 'bg-white shadow text-teal-700' : 'text-gray-500' }}">Bs</button>
                <button wire:click="$set('moneda_visual', 'usd'); $dispatch('redibujar-graficos')" class="px-3 py-1 text-xs font-semibold rounded-md {{ $moneda_visual === 'usd' ? 'bg-white shadow text-teal-700' : 'text-gray-500' }}">USD</button>
            </div>
        </div>
    </div>

    {{-- TARJETAS KPI --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Ingresos Totales</p>
            <p class="text-3xl font-bold text-teal-700 mt-2">{{ $simbolo }}{{ number_format($ingresos, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $cantidad_pagos }} pagos registrados</p>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Egresos Totales</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $simbolo }}{{ number_format($egresos, 2) }}</p>
            <p class="text-xs text-gray-500 mt-1">{{ $cantidad_gastos }} gastos registrados</p>
        </div>
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 relative overflow-hidden">
            <p class="text-sm font-semibold text-gray-500 uppercase tracking-wider">Utilidad Neta</p>
            <p class="text-3xl font-bold mt-2 {{ $utilidad >= 0 ? 'text-gray-800' : 'text-red-600' }}">
                {{ $simbolo }}{{ number_format($utilidad, 2) }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Ingresos menos egresos</p>
        </div>
    </div>

    {{-- TABLAS DE DESGLOSE --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-800">Ingresos por Método de Pago</h3>
            </div>
            <div class="p-5">
                @forelse ($desgloseFormas as $label => $monto)
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $label }}</span>
                            <span class="font-bold text-gray-800">{{ $simbolo }}{{ number_format($monto, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php
                                $porcentaje = ($monto / $totalIngresosFormas) * 100;
                                $color = str_contains($label, 'Efectivo') ? 'bg-emerald-500' : (str_contains($label, 'Zelle') || str_contains($label, 'Binance') ? 'bg-purple-500' : 'bg-blue-500');
                            @endphp
                            <div class="{{ $color }} h-2 rounded-full" style="width: {{ $porcentaje }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">No hay ingresos registrados en este período.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 bg-gray-50/50">
                <h3 class="text-sm font-bold text-gray-800">Ingresos por Odontólogo</h3>
            </div>
            <div class="p-5">
                @forelse ($desgloseMedicos as $medico => $monto)
                    <div class="mb-4">
                        <div class="flex justify-between text-sm mb-1">
                            <span class="font-medium text-gray-700">{{ $medico }}</span>
                            <span class="font-bold text-gray-800">{{ $simbolo }}{{ number_format($monto, 2) }}</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            @php
                                $porcentaje = ($monto / $totalIngresosFormas) * 100;
                            @endphp
                            <div class="bg-teal-500 h-2 rounded-full" style="width: {{ $porcentaje }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 text-center py-4">No hay ingresos registrados en este período.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- GRÁFICOS --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100 lg:col-span-2">
            <h3 class="text-sm font-bold text-gray-800 mb-4">Evolución Diaria</h3>
            <div id="chart-line" wire:ignore
                 data-chart="{{ json_encode([
                     'labels' => $chartLineLabels,
                     'ingresos' => $chartLineIngresos,
                     'egresos' => $chartLineEgresos,
                     'simbolo' => $simbolo
                 ]) }}">
            </div>
        </div>

        <div class="bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
            <h3 class="text-sm font-bold text-gray-800 mb-4">Distribución por Método</h3>
            <div id="chart-pie" wire:ignore
                 data-chart="{{ json_encode([
                     'labels' => $chartPieLabels,
                     'series' => $chartPieSeries,
                     'simbolo' => $simbolo
                 ]) }}">
            </div>
        </div>
    </div>
</div>