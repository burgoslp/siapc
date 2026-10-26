@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Gestión de citas')
@section('contenido')
<div class="container mx-auto px-4 py-6"
     x-data="{ vista: localStorage.getItem('citas-vista') || 'lista' }"
     x-init="$watch('vista', v => localStorage.setItem('citas-vista', v))">

    {{-- Encabezado (igual que antes) --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Gestión de Citas</h1>
            <p class="text-sm text-gray-500 mt-1">Administra las citas del consultorio</p>
        </div>

        <div class="flex items-center gap-3">
            {{-- Toggle Lista/Calendario --}}
            <div class="bg-gray-100 p-1 rounded-lg flex">
                <button @click="vista = 'lista'"
                        :class="vista === 'lista' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    Lista
                </button>
                <button @click="vista = 'calendario'"
                        :class="vista === 'calendario' ? 'bg-white text-gray-800 shadow-sm' : 'text-gray-500'"
                        class="px-3 py-1.5 text-xs font-medium rounded-md transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Calendario
                </button>
            </div>

            <button type="button"
                    onclick="Livewire.dispatch('nueva-cita')"
                    class="px-4 py-2 bg-teal-600 hover:bg-teal-700 text-white rounded-lg text-sm font-medium transition shadow-sm inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nueva Cita
            </button>
        </div>
    </div>

    {{-- Contenido --}}
    <div x-show="vista === 'lista'" x-cloak>
        <livewire:citas-table />
    </div>

    <div x-show="vista === 'calendario'" x-cloak>
        <livewire:citas-calendar />
    </div>

    <livewire:cita-form />
</div>


@endsection
@push('scripts')
    @vite('resources/js/citas-calendar.js')
@endpush