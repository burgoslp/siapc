@extends('layouts.base')

@section('contenido')
<div class="space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">
                Panel de Control Clínico
            </h1>
            <p class="text-sm text-slate-500 mt-1">
                Resumen de operaciones de hoy en ClíniCare
            </p>
        </div>
        <div class="flex items-center space-x-3">
            <a href="{{ route('citas.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all">
                <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                Ver Citas
            </a>
            <button type="button"
                    onclick="Livewire.dispatch('nueva-cita')"
                    class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-xl text-sm font-semibold text-white shadow-md shadow-teal-600/20 hover:bg-teal-700 transition-all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Cita
            </button>
        </div>
    </div>

    {{-- KPIs --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">

        <x-stat-card
            title="Citas Hoy"
            :value="$citasHoy"
            :change="'Programadas'"
            :isPositive="true"
            iconBg="bg-teal-50 text-teal-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </x-stat-card>

        <x-stat-card
            title="Pacientes Activos"
            :value="number_format($pacientesActivos)"
            change="Total en sistema"
            :isPositive="true"
            iconBg="bg-blue-50 text-blue-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </x-stat-card>

        <x-stat-card
            title="Consultas Pendientes"
            :value="$consultasPendientes"
            change="Por atender"
            :isPositive="$consultasPendientes === 0"
            iconBg="bg-amber-50 text-amber-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>
            </svg>
        </x-stat-card>

        <x-stat-card
            title="Ingresos del Mes"
            :value="'$' . number_format($ingresosMes, 2)"
            change="{{ now()->translatedFormat('F Y') }}"
            :isPositive="true"
            iconBg="bg-emerald-50 text-emerald-600">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </x-stat-card>
    </div>

    {{-- Grid principal --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Próximas citas --}}
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-5">
                <div>
                    <h2 class="text-base font-bold text-slate-800">Próximas Consultas de Hoy</h2>
                    <p class="text-xs text-slate-500">Pacientes programados en el bloque actual</p>
                </div>
                <a href="{{ route('citas.index') }}"
                   class="text-xs font-semibold text-teal-600 hover:text-teal-700">
                    Ver Todas &rarr;
                </a>
            </div>

            @if ($proximasCitas->isEmpty())
                <div class="py-10 text-center text-sm text-slate-400">
                    No hay citas programadas para hoy.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                <th class="pb-3 px-2">Paciente</th>
                                <th class="pb-3 px-2">Especialidad / Doctor</th>
                                <th class="pb-3 px-2">Hora</th>
                                <th class="pb-3 px-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50 text-sm">
                            @foreach ($proximasCitas as $cita)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-3 px-2">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xs">
                                                {{ strtoupper(substr($cita->paciente?->nombre ?? '?', 0, 1) . substr($cita->paciente?->apellido ?? '?', 0, 1)) }}
                                            </div>
                                            <div>
                                                <p class="font-semibold text-slate-800">
                                                    {{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}
                                                </p>
                                                <p class="text-xs text-slate-400">
                                                    {{ $cita->paciente?->lci }}-{{ $cita->paciente?->ci }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="py-3 px-2">
                                        <p class="text-slate-700 font-medium">
                                            {{ $cita->medico?->especialidades->first()?->nombre ?? 'Sin especialidad' }}
                                        </p>
                                        <p class="text-xs text-slate-400">
                                            Dr(a). {{ $cita->medico?->nombre }} {{ $cita->medico?->apellido }}
                                        </p>
                                    </td>
                                    <td class="py-3 px-2 font-medium text-slate-600 whitespace-nowrap">
                                        {{ $cita->fecha->format('h:i A') }}
                                    </td>
                                    <td class="py-3 px-2">
                                        @php
                                            $colores = [
                                                'PENDIENTE'  => 'bg-slate-100 text-slate-600 border-slate-200',
                                                'CONFIRMADA' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                'COMPLETADA' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            ];
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold border {{ $colores[$cita->estado] ?? 'bg-gray-100 text-gray-600 border-gray-200' }}">
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

        {{-- Columna derecha --}}
        <div class="space-y-6">

            {{-- Acciones rápidas --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h2 class="text-base font-bold text-slate-800 mb-4">Acciones Rápidas</h2>
                <div class="grid grid-cols-2 gap-3">
                    <button type="button"
                            onclick="Livewire.dispatch('nuevo-paciente')"
                            class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                        <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                            </svg>
                        </div>
                        <span>Alta Paciente</span>
                    </button>

                    <a href="{{ route('consultas.index') }}"
                       class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                        <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                        </div>
                        <span>Atender Consultas</span>
                    </a>

                    <a href="{{ route('citas.index') }}"
                       class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                        <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                        <span>Ver Agenda</span>
                    </a>

                    <a href="{{ route('pacientes.index') }}"
                       class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                        <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                        </div>
                        <span>Pacientes</span>
                    </a>
                </div>
            </div>

            {{-- Últimas consultas --}}
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h2 class="text-base font-bold text-slate-800 mb-4">Últimas Consultas</h2>

                @if ($ultimasConsultas->isEmpty())
                    <p class="text-sm text-slate-400 text-center py-4">
                        Sin consultas registradas aún.
                    </p>
                @else
                    <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
                        @foreach ($ultimasConsultas as $consulta)
                            <div class="relative">
                                <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-teal-500 ring-4 ring-white"></span>
                                <p class="text-xs font-semibold text-slate-800">
                                    {{ $consulta->paciente?->nombre }} {{ $consulta->paciente?->apellido }}
                                </p>
                                <p class="text-xs text-slate-500">
                                    Dr(a). {{ $consulta->medico?->nombre }} {{ $consulta->medico?->apellido }}
                                    @if ($consulta->diagnostico)
                                        · {{ \Str::limit($consulta->diagnostico, 40) }}
                                    @endif
                                </p>
                                <span class="text-[10px] text-slate-400 mt-0.5 block">
                                    {{ $consulta->created_at->diffForHumans() }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

    </div>
    
</div>
<livewire:paciente-form />
<livewire:cita-form />
@endsection