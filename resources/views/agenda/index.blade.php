@extends('layouts.base')

@section('title', 'Agendas Compartidas')

@section('contenido')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Agendas de Auto-Agendamiento</h1>
        <p class="text-sm text-gray-500 mt-1">Gestiona los enlaces que envías a tus pacientes.</p>
    </div>

    <livewire:agendas-table />
</div>
@endsection