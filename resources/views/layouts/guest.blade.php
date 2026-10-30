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
        <div class="hidden lg:flex lg:w-2/5 xl:w-1/3 bg-siapc-navy text-white flex-col justify-between p-12 relative overflow-hidden">

            {{-- Decoración: Círculos con los nuevos colores --}}
            <div class="absolute inset-0 pointer-events-none overflow-hidden">
                <div class="absolute -top-32 -left-32 w-[30rem] h-[30rem] rounded-full bg-siapc-blue opacity-40 mix-blend-screen"></div>
                <div class="absolute -bottom-32 -right-32 w-[25rem] h-[25rem] rounded-full bg-siapc-indigo opacity-40 mix-blend-screen"></div>
                <div class="absolute top-1/3 left-1/4 w-64 h-64 rounded-full bg-siapc-blue opacity-20 blur-2xl"></div>
            </div>

            {{-- Logo --}}
            <div class="relative z-10">
                <a href="/" class="flex items-center space-x-4">
                    <img src="{{ asset('images/logo/logo4.png') }}" 
                         alt="SIAPC Logo" 
                         class="h-12 w-12 object-cover rounded-2xl shadow-lg shadow-siapc-blue/50 ring-2 ring-white/20">
                    <div>
                        <span class="text-2xl font-bold tracking-wide">SIAPC</span>
                        <span class="block text-xs text-blue-300 font-medium tracking-wider uppercase">Salud & Bienestar</span>
                    </div>
                </a>
            </div>

            {{-- Mensaje central --}}
            <div class="relative z-10 space-y-4">
                <h1 class="text-4xl font-bold leading-tight">
                    Bienvenido a SIAPC
                </h1>
                <p class="text-blue-200 text-sm leading-relaxed max-w-sm">
                    Sistema integral para la gestión clínica de tu consultorio. Pacientes, citas, consultas e historial médico en un solo lugar.
                </p>

                <div class="pt-6 space-y-4">
                    <div class="flex items-center text-sm text-blue-100">
                        <div class="w-6 h-6 rounded-full bg-siapc-blue flex items-center justify-center mr-3 flex-shrink-0 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        Historial clínico centralizado
                    </div>
                    <div class="flex items-center text-sm text-blue-100">
                        <div class="w-6 h-6 rounded-full bg-siapc-blue flex items-center justify-center mr-3 flex-shrink-0 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        Agenda inteligente de citas
                    </div>
                    <div class="flex items-center text-sm text-blue-100">
                        <div class="w-6 h-6 rounded-full bg-siapc-blue flex items-center justify-center mr-3 flex-shrink-0 shadow-sm">
                            <svg class="w-3.5 h-3.5 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        Consultas y diagnósticos seguros
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="relative z-10 text-xs text-blue-300/60">
                © {{ date('Y') }} SIAPC · Centro Médico
            </div>
        </div>

        {{-- Panel Derecho: contenido del formulario --}}
        <div class="flex-1 flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">

                {{-- Logo mobile --}}
                <div class="lg:hidden mb-8 flex items-center justify-center space-x-3">
                    <img src="{{ asset('images/logo/logo2.png') }}" 
                         alt="SIAPC Logo" 
                         class="h-12 w-12 object-cover rounded-2xl shadow-md">
                    <div>
                        <span class="text-xl font-bold text-slate-900 tracking-wide">SIAPC</span>
                        <span class="block text-xs text-siapc-blue font-medium tracking-wider uppercase">Salud & Bienestar</span>
                    </div>
                </div>

                {{-- Formulario --}}
                <div class="bg-white p-8 rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100">
                    {{ $slot }}
                </div>

                <p class="mt-8 text-center text-xs text-slate-400">
                    ¿Problemas para acceder? Contacta al administrador del sistema.
                </p>
            </div>
        </div>
    </div>

    @livewireScripts
</body>
</html>