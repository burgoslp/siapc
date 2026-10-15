@extends('layouts.base')

@section('contenido')
@php
    $formas = [
        'EFECTIVO'        => ['label' => 'Efectivo',       'color' => 'emerald'],
        'TRANSFERENCIA'   => ['label' => 'Transferencia',  'color' => 'blue'],
        'PAGO_MOVIL'      => ['label' => 'Pago Móvil',     'color' => 'purple'],
        'TARJETA_DEBITO'  => ['label' => 'T. Débito',      'color' => 'indigo'],
        'TARJETA_CREDITO' => ['label' => 'T. Crédito',     'color' => 'pink'],
        'ZELLE'           => ['label' => 'Zelle',          'color' => 'violet'],
        'BINANCE'         => ['label' => 'Binance',        'color' => 'amber'],
        'OTRO'            => ['label' => 'Otro',           'color' => 'gray'],
    ];
@endphp
<div class="container mx-auto px-4 py-6 space-y-6">

    {{-- ============================================================ --}}
    {{-- ENCABEZADO --}}
    {{-- ============================================================ --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Facturación y Pagos</h1>
            <p class="text-sm text-gray-500 mt-1">
                Gestión de caja y cobros del consultorio
            </p>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- ESTADO DE LA CAJA --}}
    {{-- ============================================================ --}}
    @if ($cajaAbierta)
        {{-- Caja abierta --}}
        <div class="bg-white rounded-2xl shadow-sm border border-teal-200 ring-1 ring-teal-100 overflow-hidden">
            <div class="bg-teal-50 border-b border-teal-100 px-6 py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-teal-500 text-white flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-gray-800">Caja Abierta</h2>
                                <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-teal-500 text-white">
                                    En curso
                                </span>
                            </div>
                            <p class="text-xs text-gray-600 mt-0.5">
                                <span class="font-mono font-semibold">{{ $cajaAbierta->codigo }}</span>
                                · Abierta {{ $cajaAbierta->abierta_en->diffForHumans() }}
                                · Por {{ $cajaAbierta->abiertaPor?->nombre_mostrar ?? 'N/A' }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-2">
                        <a href="{{ route('facturacion.pagos') }}"
                           class="px-3 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Ver Pagos
                        </a>
                        <button type="button"
                                onclick="Livewire.dispatch('cerrar-caja')"
                                class="px-3 py-2 bg-red-600 text-white rounded-lg text-sm font-medium hover:bg-red-700 inline-flex items-center">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                            </svg>
                            Cerrar Caja
                        </button>
                    </div>
                </div>
            </div>

            {{-- Totales en vivo --}}
            <div class="p-6">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                    Totales por forma de pago (en tiempo real)
                </p>

                @php
                    $totales = $cajaAbierta->totalesPorFormaPago();
                @endphp

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @foreach ($formas as $key => $info)
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">
                                {{ $info['label'] }}
                            </p>
                            <p class="text-sm font-bold text-gray-800 mt-1">
                                Bs {{ number_format($totales[$key] ?? 0, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">
                            Total General del Día
                        </p>
                        <p class="text-2xl font-bold text-teal-700">
                            Bs {{ number_format($cajaAbierta->totalGeneralBs(), 2) }}
                        </p>
                    </div>
                    @if ($cajaAbierta->monto_inicial > 0)
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">
                                Monto Inicial
                            </p>
                            <p class="text-sm font-semibold text-gray-700">
                                {{ $cajaAbierta->moneda_inicial === 'bs' ? 'Bs ' : ($cajaAbierta->moneda_inicial === 'dolar' ? '$' : '€') }}
                                {{ number_format($cajaAbierta->monto_inicial, 2) }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @else
        {{-- Sin caja abierta --}}
        <div class="bg-white rounded-2xl shadow-sm border border-amber-200 overflow-hidden">
            <div class="bg-amber-50 border-b border-amber-100 px-6 py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-amber-500 text-white flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-800">No hay caja abierta</h2>
                            <p class="text-xs text-gray-600 mt-0.5">
                                Abre una caja para comenzar a registrar pagos
                            </p>
                        </div>
                    </div>

                    <button type="button"
                            onclick="Livewire.dispatch('abrir-caja')"
                            class="px-4 py-2 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700 inline-flex items-center shadow-md shadow-teal-600/20">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        Abrir Caja
                    </button>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- ÚLTIMO CIERRE --}}
    {{-- ============================================================ --}}
    @if ($ultimaCajaCerrada && !$cajaAbierta)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-100">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-gray-800">Último Cierre de Caja</h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            <span class="font-mono">{{ $ultimaCajaCerrada->codigo }}</span>
                            · Cerrada {{ $ultimaCajaCerrada->cerrada_en?->diffForHumans() }}
                        </p>
                    </div>
                    <a href="{{ route('facturacion.pagos') }}"
                       class="text-xs font-semibold text-teal-600 hover:text-teal-700">
                        Ver detalles &rarr;
                    </a>
                </div>
            </div>

            <div class="p-6">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    @php
                        $totalesCerrada = [
                            'EFECTIVO'        => $ultimaCajaCerrada->total_efectivo,
                            'TRANSFERENCIA'   => $ultimaCajaCerrada->total_transferencia,
                            'PAGO_MOVIL'      => $ultimaCajaCerrada->total_pago_movil,
                            'TARJETA_DEBITO'  => $ultimaCajaCerrada->total_tarjeta_debito,
                            'TARJETA_CREDITO' => $ultimaCajaCerrada->total_tarjeta_credito,
                            'ZELLE'           => $ultimaCajaCerrada->total_zelle,
                            'BINANCE'         => $ultimaCajaCerrada->total_binance,
                            'OTRO'            => $ultimaCajaCerrada->total_otros,
                        ];
                    @endphp

                    @foreach ($formas as $key => $info)
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">
                                {{ $info['label'] }}
                            </p>
                            <p class="text-sm font-bold text-gray-800 mt-1">
                                Bs {{ number_format($totalesCerrada[$key] ?? 0, 2) }}
                            </p>
                        </div>
                    @endforeach
                </div>

                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">
                            Total General del Cierre
                        </p>
                        <p class="text-2xl font-bold text-gray-800">
                            Bs {{ number_format($ultimaCajaCerrada->total_general, 2) }}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- CITAS PENDIENTES POR COBRAR --}}
    {{-- ============================================================ --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Citas Pendientes por Cobrar</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Pacientes con citas confirmadas o completadas con saldo pendiente
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">
                        Total Pendiente
                    </p>
                    <p class="text-lg font-bold text-amber-600">
                        ${{ number_format($totalPendienteUsd, 2) }}
                    </p>
                </div>
            </div>
        </div>

        @if ($citasPorCobrar->isEmpty())
            <div class="py-12 text-center text-sm text-gray-400">
                No hay citas pendientes por cobrar.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="px-4 py-3">Fecha</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Médico</th>
                            <th class="px-4 py-3 text-right">Total</th>
                            <th class="px-4 py-3 text-right">Pagado</th>
                            <th class="px-4 py-3 text-right">Saldo</th>
                            <th class="px-4 py-3 text-center">Estado</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @foreach ($citasPorCobrar as $cita)
                            <tr wire:key="cita-{{ $cita->id }}" class="hover:bg-gray-50 transition">
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div class="font-medium text-gray-800">
                                        {{ $cita->fecha->format('d/m/Y') }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $cita->fecha->format('h:i A') }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800">
                                        {{ $cita->paciente?->nombre }} {{ $cita->paciente?->apellido }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $cita->paciente?->lci }}-{{ $cita->paciente?->ci }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    Dr(a). {{ $cita->medico?->nombre }} {{ $cita->medico?->apellido }}
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                    ${{ number_format((float) $cita->total, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right text-gray-600">
                                    ${{ number_format($cita->total_pagado_usd, 2) }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold text-amber-600">
                                    ${{ number_format($cita->saldo_pendiente_usd, 2) }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if ($cita->estado_pago === 'PARCIAL')
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                            Parcial
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                            Pendiente
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <button type="button"
                                            onclick="Livewire.dispatch('cobrar-cita', { citaId: {{ $cita->id }} })"
                                            class="px-3 py-1.5 bg-teal-600 text-white rounded-md text-xs font-medium hover:bg-teal-700 inline-flex items-center">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                                        </svg>
                                        Cobrar
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    <livewire:caja-apertura />
    <livewire:caja-cierre />
    <livewire:pago-form />

</div>
@endsection