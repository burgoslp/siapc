@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Listado de Pacientes')
@section('contenido')
<div class="container mx-auto px-4 py-6">

    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <h1 class="text-2xl font-bold text-gray-800">Listado de Pacientes</h1>
        
        <!-- Botón para abrir el modal -->
       <button type="button"
                onclick="Livewire.dispatch('nuevo-paciente')"
                class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition shadow-sm">
            Nuevo Paciente
        </button>
    </div>

    <!-- Alerta de éxito -->
    @if (session('success'))
        <div x-data="{ show: true }" 
            x-init="setTimeout(() => show = false, 1000)" 
            x-show="show" 
            x-transition.opacity.duration.500ms
            class="flex items-center p-4 mb-6 text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg relative" 
            role="alert">
            <svg class="w-5 h-5 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
            </svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Tabla de Pacientes con su buscador -->
    <livewire:pacientes-table />
    <livewire:paciente-form />
    <livewire:condicion-form />
    <livewire:alergia-form />
    
</div>


@endsection
