@extends('layouts.base')

@section('title', config('app.name', 'SIAPC') . ' - ' . $paciente->nombre . ' ' . $paciente->apellido)

@section('contenido')
<div class="container mx-auto px-4 py-6">

    {{-- Alerta de éxito --}}
    @if (session('success'))
        <div x-data="{ show: true }"
             x-init="setTimeout(() => show = false, 3500)"
             x-show="show"
             x-transition.opacity.duration.500ms
             class="flex items-center p-4 mb-6 text-sm text-green-800 bg-green-50 border border-green-200 rounded-lg">
            <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            {{ session('success') }}
        </div>
    @endif

    {{-- Componente Livewire con toda la lógica --}}
    <livewire:paciente-detalle :pacienteId="$paciente->id" />

    {{-- Componentes de formularios --}}
    <livewire:condicion-form />
    <livewire:alergia-form />
    <livewire:paciente-form />
    <livewire:consultas-form />


</div>
@endsection