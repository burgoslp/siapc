@extends('layouts.base')

@section('contenido')
<div class="container mx-auto px-4 py-6 max-w-5xl">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Configuración de la Empresa</h1>
        <p class="text-sm text-gray-500 mt-1">Administra los datos que aparecerán en los PDFs (Recetas, Presupuestos, etc.)</p>
    </div>

    <livewire:empresa-form />
</div>
@endsection