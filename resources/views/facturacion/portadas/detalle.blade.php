@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Detalle de las portadas')
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

    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('facturacion.cajas') }}"
                   class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">
                    <span class="font-mono">{{ $caja->codigo }}</span>
                </h1>
                @if ($caja->estado === 'ABIERTA')
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                        Abierta
                    </span>
                @else
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-700">
                        Cerrada
                    </span>
                @endif
            </div>
            <p class="text-sm text-gray-500 mt-1">
                Abierta el {{ $caja->abierta_en->format('d/m/Y \a \l\a\s H:i') }}
                @if ($caja->abiertaPor)
                    · por {{ $caja->abiertaPor->nombre_mostrar }}
                @endif
            </p>
        </div>
    </div>

    {{-- Info general --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        {{-- Monto inicial --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Monto Inicial</p>
            <p class="text-2xl font-bold text-gray-800 mt-1">
                {{ $caja->moneda_inicial === 'bs' ? 'Bs ' : ($caja->moneda_inicial === 'dolar' ? '$' : '€') }}
                {{ number_format($caja->monto_inicial, 2) }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                {{ $caja->moneda_inicial === 'bs' ? 'Bolívares' : ($caja->moneda_inicial === 'dolar' ? 'Dólares' : 'Euros') }}
            </p>
        </div>

        {{-- Total general --}}
        <div class="bg-white rounded-xl shadow-sm border border-teal-200 ring-1 ring-teal-100 p-5">
            <p class="text-xs font-semibold text-teal-600 uppercase tracking-wider">Total General</p>
            <p class="text-2xl font-bold text-teal-700 mt-1">
                @if ($caja->estado === 'CERRADA')
                    Bs {{ number_format($caja->total_general, 2) }}
                @else
                    Bs {{ number_format($caja->totalGeneralBs(), 2) }}
                @endif
            </p>
            <p class="text-xs text-teal-600 mt-1">
                {{ $caja->estado === 'CERRADA' ? 'Total al cierre' : 'En vivo' }}
            </p>
        </div>

        {{-- Cierre --}}
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Cierre</p>
            @if ($caja->cerrada_en)
                <p class="text-base font-bold text-gray-800 mt-1">
                    {{ $caja->cerrada_en->format('d/m/Y H:i') }}
                </p>
                <p class="text-xs text-gray-500 mt-1">
                    @if ($caja->cerradaPor)
                        Por {{ $caja->cerradaPor->nombre_mostrar }}
                    @endif
                </p>
            @else
                <p class="text-base text-gray-400 italic mt-1">Sin cerrar</p>
            @endif
        </div>
    </div>

    {{-- Totales por forma de pago --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <h2 class="text-base font-bold text-gray-800">Totales por Forma de Pago</h2>
            <p class="text-xs text-gray-500 mt-0.5">
                {{ $caja->estado === 'CERRADA' ? 'Snapshot al momento del cierre' : 'Calculado en tiempo real' }}
            </p>
        </div>

        <div class="p-6">
            @php
                if ($caja->estado === 'CERRADA') {
                    $totales = [
                        'TRANSFERENCIA'   => $caja->total_transferencia,
                        'PAGO_MOVIL'      => $caja->total_pago_movil,
                        'TARJETA_DEBITO'  => $caja->total_tarjeta_debito,
                        'TARJETA_CREDITO' => $caja->total_tarjeta_credito,
                        'ZELLE'           => $caja->total_zelle,
                        'BINANCE'         => $caja->total_binance,
                        'OTRO'            => $caja->total_otros,
                    ];

                    // 👇 Consultamos el desglose del efectivo en la BD
                    $efectivoUsdDetalle = $caja->pagos()
                        ->where('estado', 'ACTIVO')
                        ->where('forma_pago', 'EFECTIVO')
                        ->where('moneda', 'dolar')
                        ->sum('monto');
                        
                    $efectivoBsDetalle = $caja->pagos()
                        ->where('estado', 'ACTIVO')
                        ->where('forma_pago', 'EFECTIVO')
                        ->where('moneda', 'bs')
                        ->sum('monto');

                } else {
                    // Para cajas abiertas usamos la misma lógica en vivo
                    $totales = $caja->totalesPorFormaPago();
                    
                    $efectivoUsdDetalle = $caja->pagos()
                        ->where('estado', 'ACTIVO')
                        ->where('forma_pago', 'EFECTIVO')
                        ->where('moneda', 'dolar')
                        ->sum('monto');
                        
                    $efectivoBsDetalle = $caja->pagos()
                        ->where('estado', 'ACTIVO')
                        ->where('forma_pago', 'EFECTIVO')
                        ->where('moneda', 'bs')
                        ->sum('monto');
                }
            @endphp

            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                {{-- Efectivo USD --}}
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Efectivo USD</p>
                    <p class="text-sm font-bold text-gray-800 mt-1">$ {{ number_format($efectivoUsdDetalle, 2) }}</p>
                </div>

                {{-- Efectivo Bs --}}
                <div class="bg-gray-50 rounded-lg p-3 border border-gray-100">
                    <p class="text-[10px] uppercase tracking-wider font-semibold text-gray-500">Efectivo Bs</p>
                    <p class="text-sm font-bold text-gray-800 mt-1">Bs {{ number_format($efectivoBsDetalle, 2) }}</p>
                </div>

                {{-- Otros métodos de pago --}}
                @foreach ($formas as $key => $info)
                    @if ($key !== 'EFECTIVO' && ($totales[$key] ?? 0) > 0)
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

            <div class="mt-4 pt-4 border-t-2 border-gray-200">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-semibold text-gray-700 uppercase tracking-wide">
                        Total General
                    </span>
                    <span class="text-2xl font-bold text-teal-700">
                        @if ($caja->estado === 'CERRADA')
                            Bs {{ number_format($caja->total_general, 2) }}
                        @else
                            Bs {{ number_format($caja->totalGeneralBs(), 2) }}
                        @endif
                    </span>
                </div>
            </div>

            @if ($caja->observaciones_cierre)
                <div class="mt-4 p-3 bg-slate-50 border border-slate-200 rounded-lg">
                    <p class="text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">
                        Observaciones del cierre
                    </p>
                    <p class="text-sm text-slate-700">{{ $caja->observaciones_cierre }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Pagos --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-base font-bold text-gray-800">Pagos Registrados</h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        {{ $caja->pagos->count() }} pagos en esta caja
                    </p>
                </div>
            </div>
        </div>

        @if ($caja->pagos->isEmpty())
            <div class="py-12 text-center text-sm text-gray-400">
                No hay pagos registrados en esta caja.
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-100 text-xs uppercase tracking-wider text-gray-500 font-semibold">
                            <th class="px-4 py-3">Hora</th>
                            <th class="px-4 py-3">Paciente</th>
                            <th class="px-4 py-3">Cita</th>
                            <th class="px-4 py-3">Forma</th>
                            <th class="px-4 py-3">Moneda</th>
                            <th class="px-4 py-3 text-right">Tasa</th>
                            <th class="px-4 py-3 text-right">Monto</th>
                            <th class="px-4 py-3 text-right">En Bs</th>
                            <th class="px-4 py-3">Referencia</th>
                            <th class="px-4 py-3">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-sm">
                        @foreach ($caja->pagos->sortBy('created_at') as $pago)
                            <tr class="hover:bg-gray-50 transition {{ $pago->esta_anulado ? 'opacity-60 bg-red-50/30' : '' }}">
                                <td class="px-4 py-3 whitespace-nowrap text-gray-600 text-xs">
                                    {{ $pago->created_at->format('h:i A') }}
                                </td>
                                <td class="px-4 py-3">
                                    <div class="font-medium text-gray-800 text-sm">
                                        {{ $pago->paciente?->nombre }} {{ $pago->paciente?->apellido }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $pago->paciente?->lci }}-{{ $pago->paciente?->ci }}
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs">
                                    @if ($pago->cita)
                                        <div class="font-mono font-semibold text-teal-700">
                                            #{{ $pago->cita->numero_cita }}
                                        </div>
                                        <div class="text-gray-500">
                                            {{ $pago->cita->fecha->format('d/m/Y') }}
                                        </div>
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 whitespace-nowrap">
                                        {{ $pago->forma_pago_etiqueta }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs font-mono text-gray-600">
                                    {{ $pago->moneda_etiqueta }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs">
                                    @if ($pago->tasa_momento)
                                        <div class="font-semibold text-gray-700">
                                            {{ number_format($pago->tasa_momento, 2) }}
                                        </div>
                                        @if ($pago->tasa)
                                            <div class="text-[10px] text-gray-500">
                                                {{ $pago->tasa->etiqueta }}
                                            </div>
                                        @endif
                                    @else
                                        <span class="text-gray-400">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold {{ $pago->esta_anulado ? 'text-gray-400 line-through' : 'text-gray-800' }}">
                                    {{ $pago->monto_formateado }}
                                </td>
                                <td class="px-4 py-3 text-right font-bold {{ $pago->esta_anulado ? 'text-gray-400 line-through' : 'text-teal-700' }}">
                                    Bs {{ number_format($pago->monto_bs, 2) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-gray-500">
                                    @if ($pago->banco)
                                        <div>{{ $pago->banco }}</div>
                                    @endif
                                    @if ($pago->referencia)
                                        <div class="font-mono">{{ $pago->referencia }}</div>
                                    @endif
                                    @if (!$pago->banco && !$pago->referencia)
                                        <span class="italic">—</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3">
                                    @if ($pago->esta_anulado)
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-red-100 text-red-800">
                                            Anulado
                                        </span>
                                    @else
                                        <span class="px-2 py-0.5 rounded-full text-[10px] font-medium bg-green-100 text-green-800">
                                            Activo
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    

</div>
@endsection