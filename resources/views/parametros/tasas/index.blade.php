@extends('layouts.base')

@section('contenido')
<div class="container mx-auto px-4 py-6">

    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Tasas de Cambio</h1>
            <p class="text-sm text-gray-500 mt-1">Gestión de fuentes y cotizaciones de moneda extranjera</p>
        </div>

        <button type="button"
                onclick="Livewire.dispatch('nueva-tasa')"
                class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium transition shadow-sm inline-flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Tasa
        </button>
    </div>

    <livewire:tasas-table />
    <livewire:tasa-form />
    <livewire:cambio-form />

</div>
@endsection