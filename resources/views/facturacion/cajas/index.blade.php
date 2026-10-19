@extends('layouts.base')

@section('contenido')
<div class="container mx-auto px-4 py-6 space-y-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Portadas de Caja</h1>
            <p class="text-sm text-gray-500 mt-1">
                Historial completo de aperturas y cierres de caja
            </p>
        </div>

        <a href="{{ route('facturacion.index') }}"
           class="px-4 py-2 bg-white border border-gray-300 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-50 inline-flex items-center">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Ir a Facturación
        </a>
    </div>

    <livewire:cajas-table />

</div>
@endsection