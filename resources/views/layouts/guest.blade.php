<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'SIAPC') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon/favicon.png') }}">

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        [x-cloak] { display: none !important; }
    </style>

    @livewireStyles
</head>
<body class="h-full antialiased text-slate-800">

    <div class="min-h-screen flex">

        {{-- Panel Izquierdo: Branding (solo desktop) --}}
        <div class="hidden lg:flex lg:w-2/5 xl:w-1/3 bg-slate-900 text-white flex-col justify-between p-12 relative overflow-hidden">

            {{-- Decoración --}}
            <div class="absolute inset-0 opacity-10 pointer-events-none">
                <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-teal-500"></div>
                <div class="absolute bottom-0 -right-24 w-80 h-80 rounded-full bg-teal-400"></div>
            </div>

            {{-- Logo --}}
            <div class="relative z-10">
                <a href="/" class="flex items-center space-x-3">
                    <div class="p-2.5 bg-teal-500 text-white rounded-xl shadow-lg shadow-teal-500/30">
                        <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-xl font-bold tracking-wide">SIAPC</span>
                        <span class="block text-xs text-teal-400 font-medium">Salud & Bienestar</span>
                    </div>
                </a>
            </div>

            {{-- Mensaje central --}}
            <div class="relative z-10 space-y-4">
                <h1 class="text-3xl font-bold leading-tight">
                    Bienvenido a SIAPC
                </h1>
                <p class="text-slate-400 text-sm leading-relaxed max-w-sm">
                    Sistema integral para la gestión clínica de tu consultorio. Pacientes, citas, consultas e historial médico en un solo lugar.
                </p>

                <div class="pt-6 space-y-3">
                    <div class="flex items-center text-sm text-slate-300">
                        <svg class="w-4 h-4 mr-3 text-teal-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Historial clínico centralizado
                    </div>
                    <div class="flex items-center text-sm text-slate-300">
                        <svg class="w-4 h-4 mr-3 text-teal-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Agenda inteligente de citas
                    </div>
                    <div class="flex items-center text-sm text-slate-300">
                        <svg class="w-4 h-4 mr-3 text-teal-400 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        Consultas y diagnósticos seguros
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="relative z-10 text-xs text-slate-500">
                © {{ date('Y') }} SIAPC · Centro Médico
            </div>
        </div>

        {{-- Panel Derecho: contenido --}}
        <div class="flex-1 flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">

                {{-- Logo mobile --}}
                <div class="lg:hidden mb-8 flex items-center justify-center space-x-3">
                    <div class="p-2.5 bg-teal-500 text-white rounded-xl shadow-lg shadow-teal-500/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-slate-900 tracking-wide">ClíniCare</span>
                        <span class="block text-xs text-teal-600 font-medium">Salud & Bienestar</span>
                    </div>
                </div>

                {{ $slot }}

                {{-- Nota inferior --}}
                <p class="mt-8 text-center text-xs text-slate-400">
                    ¿Problemas para acceder? Contacta al administrador del sistema.
                </p>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>