<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full bg-slate-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', config('app.name', 'SIAPC'))</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon/favicon.png') }}">
    

    {{-- Google Fonts: Inter --}}
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body { font-family: 'Inter', sans-serif; }
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: #f1f5f9; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        [x-cloak] { display: none !important; }
    </style>
    @vite([
    'resources/css/app.css',
    'resources/js/app.js',
    ])
    @livewireStyles
    @stack('styles')
</head>
<body class="h-full antialiased text-slate-800 bg-slate-50" x-data="{ sidebarOpen: false }">

    <div class="min-h-screen flex flex-col md:flex-row">

        {{-- Mobile Sidebar Backdrop --}}
        <div x-show="sidebarOpen"
             x-cloak
             x-transition:enter="transition-opacity ease-linear duration-300"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-linear duration-300"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="sidebarOpen = false"
             class="fixed inset-0 z-40 bg-slate-900/60 backdrop-blur-sm md:hidden"></div>

        {{-- Sidebar Navigation --}}
        <aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
               class="fixed inset-y-0 left-0 z-50 w-64 bg-slate-900 text-slate-300 transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:inset-auto md:flex md:flex-col shrink-0 shadow-2xl">

            {{-- Logo & Brand --}}
            <div class="flex items-center justify-between h-16 px-6 bg-slate-950/50 border-b border-slate-800">
                <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                    <div class="p-2 bg-teal-500 text-white rounded-xl shadow-lg shadow-teal-500/30">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-lg font-bold text-white tracking-wide">SIAPC</span>
                        <span class="block text-xs text-teal-400 font-medium">Salud & Bienestar</span>
                    </div>
                </a>
                <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Navigation Links --}}
            <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto">
                {{-- Dashboard --}}
                <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-2">Inicio</p>

                <a href="{{ route('dashboard') }}"
                class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all {{ request()->routeIs('dashboard') ? 'text-white bg-teal-600 shadow-md shadow-teal-600/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                    <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-teal-100' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    Dashboard
                </a>

                {{-- ============================================================ --}}
                {{-- MÓDULO: MÉDICOS --}}
                {{-- ============================================================ --}}
                <div x-data="{ open: true }" class="space-y-1 pt-4">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Módulos Médicos</p>

                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-teal-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                            </svg>
                            Gestión Clínica
                        </span>
                        <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                        <a href="{{ route('pacientes.index') }}"
                        class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('pacientes.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                            Pacientes
                        </a>
                        <a href="{{ route('citas.index') }}"
                        class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('citas.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                            <span>Citas</span>
                            <span class="px-2 py-0.5 text-xs font-semibold bg-teal-500/10 text-teal-400 rounded-full">
                                {{ \App\Models\Cita::hoy()->count() }}
                            </span>
                        </a>
                        <a href="{{ route('consultas.index') }}"
                        class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('consultas.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                            Consultas
                        </a>
                    </div>
                </div>

                {{-- ============================================================ --}}
                {{-- MÓDULO: FACTURACIÓN --}}
                {{-- ============================================================ --}}
                <div x-data="{ open: true }" class="space-y-1 pt-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Facturación y Pagos</p>

                    <button @click="open = !open"
                            class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                        <span class="flex items-center">
                            <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            Caja y Cobros
                        </span>
                        <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </button>

                    <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                        <a href="{{ route('facturacion.index') }}"
                        class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('facturacion.index') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                            <span>Facturación</span>
                            @if (\App\Models\Caja::hayAbierta())
                                <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-500/20 text-emerald-400 rounded-full">
                                    Abierta
                                </span>
                            @endif
                        </a>
                        <a href="{{ route('facturacion.cajas') }}"
                        class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('facturacion.cajas*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                            Portadas
                        </a>
                    </div>
                </div>
                {{-- ============================================================ --}}
                {{-- MÓDULO: GASTOS --}}
                {{-- ============================================================ --}}
                <div class="pt-2">
                    <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Gastos y Servicios</p>
                    
                    <a href="{{ route('gastos.index') }}"
                       class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all {{ request()->routeIs('gastos.*') ? 'text-white bg-teal-600 shadow-md shadow-teal-600/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                        <svg class="w-5 h-5 mr-3 {{ request()->routeIs('gastos.*') ? 'text-teal-100' : 'text-slate-400' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                        Gastos
                    </a>
                </div>

                {{-- TESORERÍA --}}
                @if (auth()->user()?->esAdmin())
                    <div x-data="{ open: {{ request()->routeIs('tesoreria.*') ? 'true' : 'false' }} }" class="space-y-1 pt-2">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Configuración Financiera</p>

                        <button @click="open = !open"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                </svg>
                                Tesorería
                            </span>
                            <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                            <a href="{{ route('tesoreria.cuentas.index') }}"
                            class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('tesoreria.cuentas.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Cuentas Bancarias
                            </a>
                            <a href="{{ route('tesoreria.movimientos.index') }}"
                            class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all {{ request()->routeIs('tesoreria.movimientos.*') ? 'text-white bg-teal-600 shadow-md shadow-teal-600/20' : 'text-slate-400 hover:text-slate-100 hover:bg-slate-800/60' }}">
                                <svg class="w-5 h-5 mr-3 ..." fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                </svg>
                                Movimientos
                            </a>
                        </div>
                    </div>
                @endif

                {{-- ============================================================ --}}
                {{-- MÓDULO: REPORTES --}}
                {{-- ============================================================ --}}
                @if (auth()->user()?->esAdmin() || auth()->user()?->puedeVerTodo())
                    <div x-data="{ open: {{ request()->routeIs('reportes.*') ? 'true' : 'false' }} }" class="space-y-1 pt-2">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Análisis</p>

                        <button @click="open = !open"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-orange-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                                </svg>
                                Reportes
                            </span>
                            <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                            <a href="{{ route('reportes.financiero') }}"
                               class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('reportes.financiero') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Financiero
                            </a>
                            <a href="{{ route('reportes.productividad') }}"
                               class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('reportes.productividad') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Productividad
                            </a>
                        </div>
                    </div>
                @endif

                {{-- ============================================================ --}}
                {{-- MÓDULO: PARÁMETROS (solo admin) --}}
                {{-- ============================================================ --}}
                @if (auth()->user()?->esAdmin())
                    <div x-data="{ open: {{ request()->routeIs('especialidades.*', 'condiciones.*', 'alergenos.*', 'medicos.*', 'tarifas.*', 'tasas.*', 'empresa.*') ? 'true' : 'false' }} }" class="space-y-1 pt-2">
                        <p class="px-3 text-xs font-semibold uppercase tracking-wider text-slate-500 mb-1">Parámetros</p>

                        <button @click="open = !open"
                                class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-slate-800/60 transition-colors">
                            <span class="flex items-center">
                                <svg class="w-5 h-5 mr-3 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                                Configuración
                            </span>
                            <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>

                        <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                            <a href="{{ route('especialidades.index') }}"
                            class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('especialidades.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Especialidades
                            </a>
                            <a href="{{ route('condiciones.index') }}"
                            class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('condiciones.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Condiciones
                            </a>
                            <a href="{{ route('alergenos.index') }}"
                            class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('alergenos.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Alérgenos
                            </a>
                            <a href="{{ route('medicos.index') }}"
                            class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('medicos.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Médicos
                            </a>
                            <a href="{{ route('tarifas.index') }}"
                            class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('tarifas.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Tarifas
                            </a>
                            <a href="{{ route('tasas.index') }}"
                            class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('tasas.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Tasas de Cambio
                            </a>
                            <a href="{{ route('empresa.index') }}"
                            class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('empresa.*') ? 'text-teal-400 bg-slate-800/40' : 'text-slate-400 hover:text-teal-400 hover:bg-slate-800/40' }}">
                                Datos de Empresa
                            </a>
                        </div>
                    </div>
                @endif

            </nav>

            {{-- User Brief / Footer --}}
            <div class="p-4 bg-slate-950/40 border-t border-slate-800 flex items-center space-x-3">
                <div class="w-9 h-9 rounded-full bg-teal-500 text-white flex items-center justify-center font-bold text-sm ring-2 ring-teal-500/50">
                    {{ strtoupper(substr(auth()->user()->nombre_mostrar ?? 'U', 0, 2)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-white truncate">{{ auth()->user()->nombre_mostrar ?? 'Invitado' }}</p>
                    <p class="text-xs text-slate-400 truncate">{{ auth()->user()->rol_mostrar ?? '' }}</p>
                </div>
            </div>
        </aside>

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">

            {{-- Header Horizontal --}}
            <header class="bg-white border-b border-slate-200 sticky top-0 z-30 shadow-sm">
                <div class="flex items-center justify-between h-16 px-4 sm:px-6 lg:px-8">

                    {{-- Search & Toggle Mobile --}}
                    <div class="flex items-center flex-1 space-x-4">
                        <button @click="sidebarOpen = true"
                                class="md:hidden text-slate-500 hover:text-slate-700 focus:outline-none p-2 rounded-lg hover:bg-slate-100">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>

                        <div class="relative w-full max-w-md hidden sm:block">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                                </svg>
                            </div>
                            <input type="text"
                                   placeholder="Buscar paciente, cita o médico..."
                                   class="w-full pl-9 pr-4 py-2 text-sm bg-slate-100 border border-transparent rounded-xl focus:bg-white focus:border-teal-500 focus:ring-2 focus:ring-teal-200 transition-all outline-none">
                        </div>
                    </div>

                    {{-- Right Widgets --}}
                    <div class="flex items-center space-x-3 sm:space-x-4">
                        {{-- Widget: Tasas de cambio --}}
                        @php
                            $tasaActual = \App\Models\Tasa::predeterminada();
                            $montos = $tasaActual?->montosVigentes() ?? [];
                        @endphp

                        @if ($tasaActual && !empty($montos))
                            <div class="hidden lg:flex items-center space-x-2 text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg">
                                <svg class="w-4 h-4 text-teal-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>

                                <span class="font-semibold text-slate-700">{{ $tasaActual->etiqueta }}</span>

                                <span class="text-slate-300">·</span>

                                <div class="flex items-center gap-2">
                                    @foreach ($montos as $moneda => $monto)
                                        <div class="flex items-center gap-1">
                                            <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                                                {{ $moneda === 'dolar' ? 'USD' : 'EUR' }}
                                            </span>
                                            <span class="font-semibold text-teal-700">
                                                Bs {{ number_format($monto, 2) }}
                                            </span>

                                            @if (!$loop->last)
                                                <span class="text-slate-300 ml-1">|</span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Date Widget --}}
                        <div class="hidden lg:flex items-center space-x-2 text-xs font-medium text-slate-500 bg-slate-100 px-3 py-1.5 rounded-lg">
                            <svg class="w-4 h-4 text-teal-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            <span>{{ now()->isoFormat('D MMM, YYYY') }}</span>
                        </div>

                        {{-- User Profile Dropdown --}}
                        <div class="relative" x-data="{ open: false }">
                           
                            <button @click.stop="open = !open"
                                    class="flex items-center space-x-2 p-1 rounded-xl hover:bg-slate-100 transition-colors">
                                <div class="w-8 h-8 rounded-full bg-teal-500 text-white flex items-center justify-center font-bold text-xs ring-2 ring-teal-500/30">
                                    {{ strtoupper(substr(auth()->user()->nombre_mostrar ?? 'U', 0, 2)) }}
                                </div>
                                <span class="hidden sm:inline-block text-sm font-medium text-slate-700">
                                    {{ auth()->user()->nombre_mostrar ?? 'Invitado' }}
                                </span>
                                <svg :class="open ? 'rotate-180' : ''"
                                    class="w-4 h-4 text-slate-400 transition-transform"
                                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>

                            <div x-show="open"
                                x-cloak
                                @click.outside="open = false"
                                x-transition
                                class="absolute right-0 mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-1.5 z-50">
                                <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                    Mi Perfil
                                </a>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit"
                                            class="w-full text-left px-4 py-2 text-xs font-medium text-red-600 hover:bg-red-50">
                                        Cerrar Sesión
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </header>

            {{-- Main Yield Content --}}
            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 space-y-6">
                @yield('contenido')
            </main>
        </div>
    </div>
    @livewireScripts
    @stack('scripts')
</body>
</html>