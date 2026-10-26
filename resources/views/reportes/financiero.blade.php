@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Resumen financiero')
@section('contenido')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Reporte Financiero</h1>
        <p class="text-sm text-gray-500 mt-1">Ingresos, gastos y utilidad de la clínica</p>
    </div>

    <livewire:reporte-financiero />
    
</div>
@endsection

@push('scripts')
    @vite('resources/js/reportes.js')
@endpush