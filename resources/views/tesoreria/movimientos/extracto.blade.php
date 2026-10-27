@extends('layouts.base')
@section('title', config('app.name', 'SIAPC') . ' - Movimientos interno de las cuentas')
@section('contenido')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Extracto de Cuentas</h1>
        <p class="text-sm text-gray-500 mt-1">Historial detallado de ingresos (cobros) y egresos (gastos) por cuenta</p>
    </div>   

    <livewire:extracto-cuenta-table />
</div>
@endsection