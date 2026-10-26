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

    $totales = $cajaDisponible->totalesPorFormaPago();
    
    $efectivoUsd = $cajaDisponible->pagos()
    ->where('estado', 'ACTIVO')
    ->where('forma_pago', 'EFECTIVO')
    ->where('moneda', 'dolar')
    ->sum('monto');
    
    $efectivoBs = $cajaDisponible->pagos()
    ->where('estado', 'ACTIVO')
    ->where('forma_pago', 'EFECTIVO')
    ->where('moneda', 'bs')
    ->sum('monto');
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

    @if (session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
            {{ session('error') }}
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- ALERTA: CAJA VENCIDA --}}
    {{-- ============================================================ --}}
    @if ($cajaVencida)
        <div class="bg-white rounded-2xl shadow-sm border border-red-300 ring-1 ring-red-100 overflow-hidden">
            <div class="bg-red-50 border-b border-red-200 px-6 py-4">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-full bg-red-500 text-white flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                            </svg>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-lg font-bold text-red-800">Caja sin cerrar del día anterior</h2>
                            </div>
                            <p class="text-xs text-red-700 mt-0.5">
                                La caja <span class="font-mono font-semibold">{{ $cajaVencida->codigo }}</span>
                                fue abierta el {{ $cajaVencida->abierta_en->format('d/m/Y \a \l\a\s H:i') }}
                                y aún no ha sido cerrada.
                            </p>
                        </div>
                    </div>

                    <button type="button"
                            onclick="Livewire.dispatch('cerrar-caja')"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm font-semibold hover:bg-red-700 inline-flex items-center shadow-md shadow-red-600/20">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Cerrar la caja ahora
                    </button>
                </div>
            </div>

            <div class="p-6">
                <div class="p-4 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800 space-y-2">
                    <p class="font-semibold">¿Por qué no puedo registrar pagos?</p>
                    <p class="text-xs leading-relaxed">
                        Los pagos que registres hoy se guardarían con la fecha de apertura de la caja de ayer,
                        lo que causaría un descuadre en los reportes y en el cierre de caja.
                    </p>
                    <p class="text-xs leading-relaxed font-semibold">
                        Para continuar, cierra la caja del día anterior. Después podrás abrir una nueva caja para hoy.
                    </p>
                </div>

                {{-- Resumen de la caja vencida --}}
                <div class="mt-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">
                        Totales de la caja sin cerrar
                    </p>

                    @php
                        $totalesVencida = $cajaVencida->totalesPorFormaPago();

                        $efectivoUsdVencida = $cajaVencida->pagos()
                            ->where('estado', 'ACTIVO')
                            ->where('forma_pago', 'EFECTIVO')
                            ->where('moneda', 'dolar')
                            ->sum('monto');
                            
                        $efectivoBsVencida = $cajaVencida->pagos()
                            ->where('estado', 'ACTIVO')
                            ->where('forma_pago', 'EFECTIVO')
                            ->where('moneda', 'bs')
                            ->sum('monto');
                    @endphp

                     <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                        {{-- Efectivo USD --}}
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Efectivo USD</p>
                            <p class="text-sm font-bold text-gray-800 mt-1">$ {{ number_format($efectivoUsdVencida, 2) }}</p>
                        </div>

                        {{-- Efectivo Bs --}}
                        <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                            <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Efectivo Bs</p>
                            <p class="text-sm font-bold text-gray-800 mt-1">Bs {{ number_format($efectivoBsVencida, 2) }}</p>
                        </div>

                        {{-- Otros métodos --}}
                        @foreach ($formas as $key => $info)
                            @if ($key !== 'EFECTIVO' && ($totalesVencida[$key] ?? 0) > 0)
                                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                    <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">
                                        {{ $info['label'] }}
                                    </p>
                                    <p class="text-sm font-bold text-gray-800 mt-1">
                                        Bs {{ number_format($totalesVencida[$key], 2) }}
                                    </p>
                                </div>
                            @endif
                        @endforeach
                    </div>

                    <div class="mt-4 pt-4 border-t border-gray-100">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                                Total General
                            </span>
                            <span class="text-2xl font-bold text-red-700">
                                Bs {{ number_format($cajaVencida->totalGeneralBs(), 2) }}
                            </span>
                        </div>
                    </div>
                </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============================================================ --}}
    {{-- CAJA ABIERTA DE HOY --}}
    {{-- ============================================================ --}}
    @if ($cajaDisponible && !$cajaVencida)
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
                                <span class="font-mono font-semibold">{{ $cajaDisponible->codigo }}</span>
                                · Abierta {{ $cajaDisponible->abierta_en->diffForHumans() }}
                                · Por {{ $cajaDisponible->abiertaPor?->nombre_mostrar ?? 'N/A' }}
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
                    $totales = $cajaDisponible->totalesPorFormaPago();
                @endphp

                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {{-- 👇 EFECTIVO USD --}}
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">
                            Efectivo USD
                        </p>
                        <p class="text-sm font-bold text-gray-800 mt-1">
                            $ {{ number_format($efectivoUsd, 2) }}
                        </p>
                    </div>

                    {{-- 👇 EFECTIVO BS --}}
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">
                            Efectivo Bs
                        </p>
                        <p class="text-sm font-bold text-gray-800 mt-1">
                            Bs {{ number_format($efectivoBs, 2) }}
                        </p>
                    </div>

                    {{-- OTROS MÉTODOS DE PAGO --}}
                    @foreach ($formas as $key => $info)
                        {{-- Saltamos EFECTIVO porque ya lo mostramos desglosado --}}
                        @if ($key !== 'EFECTIVO')
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">
                                    {{ $info['label'] }}
                                </p>
                                <p class="text-sm font-bold text-gray-800 mt-1">
                                    Bs {{ number_format($totales[$key] ?? 0, 2) }}
                                </p>
                            </div>
                        @endif
                    @endforeach
            </div>

                <div class="mt-4 pt-4 border-t border-gray-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">
                            Total General del Día
                        </p>
                        <p class="text-2xl font-bold text-teal-700">
                            Bs {{ number_format($cajaDisponible->totalGeneralBs(), 2) }}
                        </p>
                    </div>
                    @if ($cajaDisponible->monto_inicial > 0)
                        <div class="text-right">
                            <p class="text-xs text-gray-500 uppercase tracking-wider font-semibold">
                                Monto Inicial
                            </p>
                            <p class="text-sm font-semibold text-gray-700">
                                {{ $cajaDisponible->moneda_inicial === 'bs' ? 'Bs ' : ($cajaDisponible->moneda_inicial === 'dolar' ? '$' : '€') }}
                                {{ number_format($cajaDisponible->monto_inicial, 2) }}
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @elseif (!$cajaVencida)
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
    @if ($ultimaCajaCerrada && !$cajaDisponible && !$cajaVencida)
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

                    $efectivoUsdCerrada = $ultimaCajaCerrada->pagos()
                        ->where('estado', 'ACTIVO')
                        ->where('forma_pago', 'EFECTIVO')
                        ->where('moneda', 'dolar')
                        ->sum('monto');
                        
                    $efectivoBsCerrada = $ultimaCajaCerrada->pagos()
                        ->where('estado', 'ACTIVO')
                        ->where('forma_pago', 'EFECTIVO')
                        ->where('moneda', 'bs')
                        ->sum('monto');
                @endphp

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                    {{-- Efectivo USD --}}
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Efectivo USD</p>
                        <p class="text-sm font-bold text-gray-800 mt-1">$ {{ number_format($efectivoUsdCerrada, 2) }}</p>
                    </div>

                    {{-- Efectivo Bs --}}
                    <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                        <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Efectivo Bs</p>
                        <p class="text-sm font-bold text-gray-800 mt-1">Bs {{ number_format($efectivoBsCerrada, 2) }}</p>
                    </div>

                    {{-- Otros métodos --}}
                    @foreach ($formas as $key => $info)
                        @if ($key !== 'EFECTIVO')
                            <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                                <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">
                                    {{ $info['label'] }}
                                </p>
                                <p class="text-sm font-bold text-gray-800 mt-1">
                                    Bs {{ number_format($totalesCerrada[$key] ?? 0, 2) }}
                                </p>
                            </div>
                        @endif
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
    <livewire:citas-por-cobrar-table :cajaVencida="$cajaVencida ? true : false" :cajaDisponible="$cajaDisponible ? true : false" />
    <livewire:caja-apertura />
    <livewire:caja-cierre />
    <livewire:pago-form />

</div>
@endsection