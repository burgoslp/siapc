@extends('layouts.base')

@section('contenido')
<div class="container mx-auto px-4 py-6 space-y-6">

    {{-- Encabezado --}}
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('facturacion.index') }}"
                   class="text-gray-400 hover:text-gray-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                </a>
                <h1 class="text-2xl font-bold text-gray-800">Pagos Registrados</h1>
            </div>
            <p class="text-sm text-gray-500 mt-1">
                @if ($caja)
                    Caja: <span class="font-mono font-semibold">{{ $caja->codigo }}</span>
                    · {{ $caja->estado === 'ABIERTA' ? 'En curso' : 'Cerrada' }}
                @else
                    Sin caja seleccionada
                @endif
            </p>
        </div>
    </div>

    @if (session('success'))
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            {{ session('success') }}
        </div>
    @endif

    @if ($caja)
        <livewire:pagos-table :cajaId="$caja->id" />
    @else
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-10 text-center text-gray-500">
            No hay caja seleccionada para mostrar pagos.
        </div>
    @endif

</div>
@endsection