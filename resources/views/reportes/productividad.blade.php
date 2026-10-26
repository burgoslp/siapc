@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Resumen de Productividad')

@section('contenido')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Reporte de Productividad</h1>
        <p class="text-sm text-gray-500 mt-1">Rendimiento clínico y operativo</p>
    </div>

    <livewire:reporte-productividad />
</div>
@endsection