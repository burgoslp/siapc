@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Panel de control')
@section('contenido')
<div class="space-y-6" x-data>
    
    {{-- ENCABEZADO --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Panel de Control</h1>
            <p class="text-sm text-slate-500 mt-1">Resumen de operaciones de {{ now()->translatedFormat('F Y') }}</p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('citas.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all">
                Ver Agenda
            </a>
            <button type="button" onclick="Livewire.dispatch('nueva-cita')" class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-xl text-sm font-semibold text-white shadow-md shadow-teal-600/20 hover:bg-teal-700 transition-all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Cita
            </button>
        </div>
    </div>

    {{-- ALERTAS --}}
    @if(count($alertas) > 0)
        <div class="space-y-2">
            @foreach($alertas as $alerta)
                <div class="p-3 bg-red-50 border border-red-200 rounded-lg flex items-center justify-between">
                    <div class="flex items-center text-red-800 text-sm font-medium">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        {{ $alerta['mensaje'] }}
                    </div>
                    <a href="{{ $alerta['ruta'] }}" class="text-xs font-bold text-red-700 hover:underline">Resolver &rarr;</a>
                </div>
            @endforeach
        </div>
    @endif

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">

        {{-- Citas Hoy --}}
        <div class="relative bg-white p-5 rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-teal-400 to-emerald-600"></div>
            
            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider pl-2">Citas Hoy</p>
            <p class="text-3xl font-bold text-slate-800 mt-2 pl-2">{{ $citasHoy }}</p>
            <p class="text-xs text-slate-500 mt-1 pl-2">{{ $consultasPendientes }} pendientes por atender</p>
        </div>

        {{-- Ingresos Hoy --}}
        <div class="relative bg-white p-5 rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-blue-400 to-indigo-600"></div>
            
            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider pl-2">Ingresos Hoy</p>
            <div class="mt-2 pl-2 space-y-0.5">
                <p class="text-xl font-bold {{ $ingresosHoyUsd > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                    $ {{ number_format($ingresosHoyUsd, 2) }}
                </p>
                <p class="text-xl font-bold {{ $ingresosHoyBs > 0 ? 'text-emerald-600' : 'text-slate-400' }}">
                    Bs {{ number_format($ingresosHoyBs, 2) }}
                </p>
            </div>
            <p class="text-xs text-slate-500 mt-1 pl-2">Cobros registrados</p>
        </div>

        {{-- Ingresos del Mes --}}
        <div class="relative bg-white p-5 rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-cyan-400 to-blue-600"></div>
            
            <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider pl-2">Ingresos del Mes</p>
            <div class="mt-2 pl-2 space-y-0.5">
                <p class="text-xl font-bold {{ $ingresosMesUsd > 0 ? 'text-slate-800' : 'text-slate-400' }}">
                    $ {{ number_format($ingresosMesUsd, 2) }}
                </p>
                <p class="text-xl font-bold {{ $ingresosMesBs > 0 ? 'text-slate-800' : 'text-slate-400' }}">
                    Bs {{ number_format($ingresosMesBs, 2) }}
                </p>
            </div>
            <p class="text-xs text-slate-500 mt-1 pl-2">Acumulado mensual</p>
        </div>

        {{-- Gastos del Mes --}}
        @if(!$esMedicoPuro)
            <div class="relative bg-white p-5 rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-rose-400 to-red-600"></div>
                
                <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider pl-2">Gastos del Mes</p>
                <div class="mt-2 pl-2 space-y-0.5">
                    <p class="text-xl font-bold {{ $gastosMesUsd > 0 ? 'text-red-600' : 'text-slate-400' }}">
                        $ {{ number_format($gastosMesUsd, 2) }}
                    </p>
                    <p class="text-xl font-bold {{ $gastosMesBs > 0 ? 'text-red-600' : 'text-slate-400' }}">
                        Bs {{ number_format($gastosMesBs, 2) }}
                    </p>
                </div>
                <p class="text-xs text-slate-500 mt-1 pl-2">Egresos registrados</p>
            </div>
        @else
            <div class="relative bg-white p-5 rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="absolute left-0 top-0 bottom-0 w-1.5 bg-gradient-to-b from-amber-400 to-orange-600"></div>
                
                <p class="text-sm font-semibold text-slate-500 uppercase tracking-wider pl-2">Consultas</p>
                <p class="text-3xl font-bold text-blue-600 mt-2 pl-2">{{ $consultasPendientes }}</p>
                <p class="text-xs text-slate-500 mt-1 pl-2">Pendientes por atender</p>
            </div>
        @endif
    </div>
    {{-- ALERTAS DE GASTOS FIJOS --}}
    @if(isset($alertasGastosFijos) && $alertasGastosFijos->count() > 0)
        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-5 shadow-sm">
            <div class="flex items-center gap-3 mb-3">
                <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div>
                    <h3 class="text-sm font-bold text-amber-800">Gastos Fijos por Vencer</h3>
                    <p class="text-xs text-amber-600">Tienes {{ $alertasGastosFijos->count() }} compromiso(s) programado(s) para los próximos 7 días.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
                @foreach($alertasGastosFijos as $alerta)
                    <div class="bg-white p-3 rounded-lg border border-amber-100 flex justify-between items-center">
                        <div>
                            <p class="text-sm font-semibold text-gray-800">{{ $alerta->concepto }}</p>
                            <p class="text-xs text-gray-500">
                                Vence: {{ $alerta->fecha_proximo->format('d/m/Y') }} 
                                <span class="font-bold {{ $alerta->dias_restantes == 0 ? 'text-red-500' : 'text-amber-600' }}">
                                    ({{ $alerta->dias_restantes == 0 ? 'Hoy' : 'En ' . $alerta->dias_restantes . ' días' }})
                                </span>
                            </p>
                        </div>
                        <a href="{{ route('gastos.fijos.index') }}" class="text-xs font-semibold text-siapc-blue hover:text-siapc-indigo">Pagar &rarr;</a>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- GRID PRINCIPAL --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- COLUMNA IZQUIERDA (Gráfico y Citas) --}}
        <div class="lg:col-span-2 space-y-6">
            
            {{-- Gráfico de Ingresos --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h2 class="text-base font-bold text-slate-800 mb-4">Ingresos Últimos 7 Días</h2>
                <div id="chart-dashboard" wire:ignore data-chart="{{ json_encode(['labels' => $chartLabels, 'data' => $chartData]) }}"></div>
            </div>

            {{-- Próximas Citas --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-slate-800">Próximas Citas</h2>
                    <a href="{{ route('citas.index') }}" class="text-xs font-semibold text-teal-600 hover:text-teal-700">Ver Todas &rarr;</a>
                </div>

                @if ($proximasCitas->isEmpty())
                    <div class="py-8 text-center text-sm text-slate-400">No hay citas próximas.</div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <tbody class="divide-y divide-slate-50 text-sm">
                                @foreach ($proximasCitas as $cita)
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-3 px-2">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xs">
                                                    {{ strtoupper(substr($cita->paciente?->nombre ?? '?', 0, 1)) }}
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-800">{{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}</p>
                                                    <p class="text-xs text-slate-400">Dr(a). {{ $cita->medico?->nombre }} {{ $cita->medico?->apellido }}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-2 text-right">
                                            <p class="font-medium text-slate-700">{{ $cita->fecha->format('d/m/Y') }}</p>
                                            <p class="text-xs text-slate-500">{{ $cita->fecha->format('h:i A') }}</p>
                                        </td>
                                        <td class="py-3 px-2 text-right">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $cita->estado === 'CONFIRMADA' ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-slate-100 text-slate-600 border border-slate-200' }}">
                                                {{ ucfirst(strtolower($cita->estado)) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- COLUMNA DERECHA (Acciones y Top Médicos) --}}
        <div class="space-y-6">
            
            {{-- Acciones Rápidas --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h2 class="text-base font-bold text-slate-800 mb-4">Acciones Rápidas</h2>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button" onclick="Livewire.dispatch('nuevo-paciente')" class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                        <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                        </div>
                        <span>Paciente</span>
                    </button>
                    <a href="{{ route('consultas.index') }}" class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                        <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <span>Consultas</span>
                    </a>
                </div>
            </div>

            {{-- Top Médicos (Solo Admin) --}}
            @if(!$esMedicoPuro && count($topMedicos) > 0)
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                    <h2 class="text-base font-bold text-slate-800 mb-4">Top Doctores del Mes</h2>
                    <div class="space-y-4">
                        @foreach($topMedicos as $index => $medico)
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold {{ $index === 0 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ $index + 1 }}
                                    </span>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800">Dr(a). {{ $medico->apellido }}</p>
                                        <p class="text-xs text-slate-500">{{ $medico->nombre }}</p>
                                    </div>
                                </div>
                                <span class="text-sm font-bold text-teal-600">{{ $medico->citas_count }} citas</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>

{{-- Script del Gráfico --}}
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const el = document.querySelector("#chart-dashboard");
        if (!el) return;
        
        const chartData = JSON.parse(el.dataset.chart);
        
        const options = {
            series: [{ name: 'Ingresos', data: chartData.data }],
            chart: { type: 'area', height: 250, toolbar: { show: false }, fontFamily: 'Inter, sans-serif' },
            colors: ['#0d9488'],
            dataLabels: { enabled: false },
            stroke: { curve: 'smooth', width: 2 },
            xaxis: { categories: chartData.labels },
            yaxis: { labels: { formatter: (val) => 'Bs ' + val.toLocaleString() } },
            tooltip: { y: { formatter: (val) => 'Bs ' + val.toLocaleString() } },
            fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05 } }
        };
        const chart = new ApexCharts(el, options);
        chart.render();
    });
</script>
@endpush

<livewire:paciente-form />
<livewire:cita-form />
@endsection