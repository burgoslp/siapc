@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Cuentas Bancarias')
@section('contenido')
<div class="container mx-auto px-4 py-6 space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Cuentas Bancarias</h1>
            <p class="text-sm text-gray-500 mt-1">
                Gestión de cuentas del consultorio y sus comisiones
            </p>
        </div>

        <button type="button"
                onclick="Livewire.dispatch('nueva-cuenta')"
                class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium transition shadow-sm inline-flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Cuenta
        </button>
    </div>

    {{-- Tabla de cuentas --}}
    <livewire:cuentas-table />

    {{-- Modal de cuenta (crear/editar) --}}
    <livewire:cuenta-form />
    <livewire:movimiento-entre-cuenta-form />

</div>
@endsection