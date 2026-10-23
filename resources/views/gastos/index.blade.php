@extends('layouts.base')

@section('contenido')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gestión de Gastos</h1>
        <p class="text-sm text-gray-500 mt-1">Registra los egresos operativos de la clínica</p>
    </div>

    <livewire:gastos-table />
</div>
@endsection