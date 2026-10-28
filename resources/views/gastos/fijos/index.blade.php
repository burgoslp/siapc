@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Gestión de gastos y pago fijos')
@section('contenido')
<div class="container mx-auto px-4 py-6php ">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Gastos Fijos y Recurrencias</h1>
        <p class="text-sm text-gray-500 mt-1">Administra los compromisos mensuales de la clínica (alquiler, servicios, etc.)</p>
    </div>

    <livewire:recurrencias-table />
</div>
@endsection