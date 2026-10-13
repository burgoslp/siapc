@extends('layouts.base')
@section('contenido')
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Panel de Control Clínico</h1>
                        <p class="text-sm text-slate-500 mt-1">Resumen de operaciones de hoy en el Centro Médico San Rafael.</p>
                    </div>
                    <div class="flex items-center space-x-3">
                        <button class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 rounded-xl text-sm font-semibold text-slate-700 shadow-sm hover:bg-slate-50 transition-all">
                            <svg class="w-4 h-4 mr-2 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                            </svg>
                            Exportar Reporte
                        </button>
                        <button class="inline-flex items-center px-4 py-2 bg-teal-600 border border-transparent rounded-xl text-sm font-semibold text-white shadow-md shadow-teal-600/20 hover:bg-teal-700 transition-all">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nueva Cita
                        </button>
                    </div>
                </div>

                <!-- KPI Metric Cards Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    
                    <x-stat-card title="Citas Hoy" value="28" change="+12%" :isPositive="true" iconBg="bg-teal-50 text-teal-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </x-stat-card>

                    <x-stat-card title="Pacientes Activos" value="1,420" change="+4%" :isPositive="true" iconBg="bg-blue-50 text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </x-stat-card>

                    <x-stat-card title="Urgencias Atendidas" value="8" change="-2%" :isPositive="false" iconBg="bg-amber-50 text-amber-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                        </svg>
                    </x-stat-card>

                    <x-stat-card title="Ingresos del Mes" value="$12,450" change="+8%" :isPositive="true" iconBg="bg-emerald-50 text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </x-stat-card>
                </div>

                <!-- Main Grid: Citas del Día & Actividad -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left 2-Columns: Próximas Citas -->
                    <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                        <div class="flex items-center justify-between mb-5">
                            <div>
                                <h2 class="text-base font-bold text-slate-800">Próximas Consultas de Hoy</h2>
                                <p class="text-xs text-slate-500">Pacientes programados en el bloque actual</p>
                            </div>
                            <a href="#" class="text-xs font-semibold text-teal-600 hover:text-teal-700">Ver Todas &rarr;</a>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b border-slate-100 text-[11px] font-semibold text-slate-400 uppercase tracking-wider">
                                        <th class="pb-3 px-2">Paciente</th>
                                        <th class="pb-3 px-2">Especialidad / Doctor</th>
                                        <th class="pb-3 px-2">Hora</th>
                                        <th class="pb-3 px-2">Estado</th>
                                        <th class="pb-3 px-2 text-right">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-50 text-sm">
                                    <!-- Row 1 -->
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-3 px-2">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-full bg-teal-100 text-teal-700 flex items-center justify-center font-bold text-xs">
                                                    AL
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-800">Ana Lucía Gómez</p>
                                                    <p class="text-xs text-slate-400">ID: #P-8821</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-2">
                                            <p class="text-slate-700 font-medium">Cardiología</p>
                                            <p class="text-xs text-slate-400">Dr. Martínez</p>
                                        </td>
                                        <td class="py-3 px-2 font-medium text-slate-600">10:00 AM</td>
                                        <td class="py-3 px-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                                En Espera
                                            </span>
                                        </td>
                                        <td class="py-3 px-2 text-right">
                                            <button class="text-slate-400 hover:text-teal-600 p-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Row 2 -->
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-3 px-2">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center font-bold text-xs">
                                                    JR
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-800">Jorge Ramírez</p>
                                                    <p class="text-xs text-slate-400">ID: #P-8822</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-2">
                                            <p class="text-slate-700 font-medium">Pediatría</p>
                                            <p class="text-xs text-slate-400">Dra. Herrera</p>
                                        </td>
                                        <td class="py-3 px-2 font-medium text-slate-600">10:30 AM</td>
                                        <td class="py-3 px-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                                Confirmada
                                            </span>
                                        </td>
                                        <td class="py-3 px-2 text-right">
                                            <button class="text-slate-400 hover:text-teal-600 p-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Row 3 -->
                                    <tr class="hover:bg-slate-50/80 transition-colors">
                                        <td class="py-3 px-2">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-700 flex items-center justify-center font-bold text-xs">
                                                    MS
                                                </div>
                                                <div>
                                                    <p class="font-semibold text-slate-800">Marta Sánchez</p>
                                                    <p class="text-xs text-slate-400">ID: #P-8825</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="py-3 px-2">
                                            <p class="text-slate-700 font-medium">Dermatología</p>
                                            <p class="text-xs text-slate-400">Dr. Castro</p>
                                        </td>
                                        <td class="py-3 px-2 font-medium text-slate-600">11:15 AM</td>
                                        <td class="py-3 px-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                                                Pendiente
                                            </span>
                                        </td>
                                        <td class="py-3 px-2 text-right">
                                            <button class="text-slate-400 hover:text-teal-600 p-1">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/>
                                                </svg>
                                            </button>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Right 1-Column: Quick Actions & Activity Stream -->
                    <div class="space-y-6">
                        
                        <!-- Quick Actions Grid -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                            <h2 class="text-base font-bold text-slate-800 mb-4">Acciones Rápidas</h2>
                            <div class="grid grid-cols-2 gap-3">
                                <a href="#" class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                                    <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
                                        </svg>
                                    </div>
                                    <span>Alta Paciente</span>
                                </a>

                                <a href="#" class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                                    <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                        </svg>
                                    </div>
                                    <span>Emitir Receta</span>
                                </a>

                                <a href="#" class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                                    <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                    </div>
                                    <span>Cobrar Consulta</span>
                                </a>

                                <a href="#" class="flex flex-col items-center justify-center p-3 bg-slate-50 hover:bg-teal-50 hover:text-teal-700 rounded-xl border border-slate-100 transition-all text-slate-700 text-xs font-medium text-center space-y-2 group">
                                    <div class="p-2 bg-white rounded-lg shadow-sm text-teal-600 group-hover:bg-teal-600 group-hover:text-white transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z"/>
                                        </svg>
                                    </div>
                                    <span>Ver Laboratorio</span>
                                </a>
                            </div>
                        </div>

                        <!-- Activity Timeline Stream -->
                        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                            <h2 class="text-base font-bold text-slate-800 mb-4">Actividad Reciente</h2>
                            <div class="relative pl-6 space-y-4 before:absolute before:left-2 before:top-2 before:bottom-2 before:w-0.5 before:bg-slate-100">
                                
                                <div class="relative">
                                    <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-teal-500 ring-4 ring-white"></span>
                                    <p class="text-xs font-semibold text-slate-800">Cita completada</p>
                                    <p class="text-xs text-slate-500">Dr. Martínez finalizó consulta con Pedro V.</p>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">Hace 15 min</span>
                                </div>

                                <div class="relative">
                                    <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-blue-500 ring-4 ring-white"></span>
                                    <p class="text-xs font-semibold text-slate-800">Inventario actualizado</p>
                                    <p class="text-xs text-slate-500">Reabastecimiento de Paracetamol 500mg.</p>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">Hace 45 min</span>
                                </div>

                                <div class="relative">
                                    <span class="absolute -left-6 top-1 w-2.5 h-2.5 rounded-full bg-emerald-500 ring-4 ring-white"></span>
                                    <p class="text-xs font-semibold text-slate-800">Factura #F-302 pagos</p>
                                    <p class="text-xs text-slate-500">Pago completado vía tarjeta débito.</p>
                                    <span class="text-[10px] text-slate-400 mt-0.5 block">Hace 1 hora</span>
                                </div>

                            </div>
                        </div>

                    </div>

                </div>
                
@endsection