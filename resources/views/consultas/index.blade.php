@extends('layouts.base')

@section('contenido')
<div class="container mx-auto px-4 py-6">

    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6 gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Consultas Médicas</h1>
            <p class="text-sm text-gray-500 mt-1">Historial clínico de todos los pacientes</p>
        </div>
    </div>

    {{-- Tabla Livewire --}}
    <livewire:consultas-table />
    <livewire:consultas-form />

</div>
@endsection
