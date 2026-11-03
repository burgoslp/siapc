<?php

use Livewire\Component;
use App\Models\Cita;

new class extends Component
{
    public $citasHoyContador = 0;

    public function mount(): void
    {
        $this->actualizarContadores();
    }

    public function getListeners()
    {
        return [
            'cita-guardada' => 'actualizarContadores',
        ];
    }

    public function actualizarContadores(): void
    {
        if (!auth()->check()) return;

        $user = auth()->user();
        $query = Cita::hoy();

        if ($user->esMedico() && !$user->puedeVerTodo()) {
            $query->where('medico_id', $user->medico->id);
        }

        $this->citasHoyContador = $query->count();
    }

    public function with(): array
    {
        return [
            'tieneCajaAbierta' => \App\Models\Caja::hayAbierta(),
        ];
    }
};
?>

<aside :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
       class="fixed inset-y-0 left-0 z-50 w-64 h-full bg-siapc-navy text-slate-300 transition-transform duration-300 ease-in-out md:translate-x-0 md:static md:inset-auto md:flex md:flex-col shrink-0 shadow-2xl">

    {{-- Logo & Brand --}}
    <div class="flex items-center justify-between h-20 px-6 bg-siapc-navy border-b border-white/5">
        <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
            <img src="{{ asset('images/logo/logo4.png') }}" alt="SIAPC Logo"
                class="h-10 w-10 object-cover rounded-xl shadow-lg shadow-siapc-blue/30 ring-1 ring-white/20">
            <div>
                <span class="text-lg font-bold text-white tracking-wide">SIAPC</span>
                <span class="block text-[10px] text-blue-300 font-medium tracking-wider uppercase">Salud & Bienestar</span>
            </div>
        </a>
        <button @click="sidebarOpen = false" class="md:hidden text-slate-400 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    {{-- Navigation Links --}}
    <nav class="flex-1 px-4 py-6 space-y-1.5 overflow-y-auto custom-scrollbar min-h-0">
        
        {{-- Dashboard --}}
        <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-blue-300/60 mb-2">Inicio</p>
        <a href="{{ route('dashboard') }}"
           class="flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all 
           {{ request()->routeIs('dashboard') ? 'text-white bg-siapc-blue/20 font-semibold shadow-sm' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
            <svg class="w-5 h-5 mr-3 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-siapc-blue' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            Dashboard
        </a>

        {{-- MÓDULO: MÉDICOS --}}
        <div x-data="{ open: true }" class="space-y-1 pt-4">
            <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-blue-300/60 mb-1">Módulos Médicos</p>
            <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition-colors">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    Gestión Clínica
                </span>
                <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                <a href="{{ route('pacientes.index') }}"
                   class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors 
                   {{ request()->routeIs('pacientes.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    Pacientes
                </a>
                <a href="{{ route('agendas.index') }}"
                        class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('agendas.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                            Agendas
                </a>
                <a href="{{ route('citas.index') }}"
                   class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors 
                   {{ request()->routeIs('citas.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <span>Citas</span>
                    <span class="px-2 py-0.5 text-xs font-semibold bg-emerald-500/20 text-emerald-400 rounded-full">
                        {{ $citasHoyContador }}
                    </span>
                </a>
                <a href="{{ route('consultas.index') }}"
                   class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors 
                   {{ request()->routeIs('consultas.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    Consultas
                </a>
            </div>
        </div>

        {{-- MÓDULO: FACTURACIÓN (Visible para todos) --}}
        <div x-data="{ open: true }" class="space-y-1 pt-2">
            <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-blue-300/60 mb-1">Facturación y Pagos</p>
            <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition-colors">
                <span class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    Caja y Cobros
                </span>
                <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
            <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                <a href="{{ route('facturacion.index') }}"
                   class="flex items-center justify-between px-3 py-2 text-sm font-medium rounded-lg transition-colors 
                   {{ request()->routeIs('facturacion.index') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    <span>Facturación</span>
                    @if ($tieneCajaAbierta)
                        <span class="px-1.5 py-0.5 text-[10px] font-semibold bg-emerald-500/20 text-emerald-400 rounded-full">
                            Abierta
                        </span>
                    @endif
                </a>
                <a href="{{ route('facturacion.cajas') }}"
                   class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors 
                   {{ request()->routeIs('facturacion.cajas*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">
                    Portadas
                </a>
            </div>
        </div>

        {{-- MÓDULOS EXCLUSIVOS DE ADMIN --}}
        @if(auth()->user()->esAdmin())
            
            {{-- TESORERÍA --}}
            <div x-data="{ open: {{ request()->routeIs('tesoreria.*') ? 'true' : 'false' }} }" class="space-y-1 pt-2">
                <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-blue-300/60 mb-1">Configuración Financiera</p>
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition-colors">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                        Tesorería
                    </span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                    <a href="{{ route('tesoreria.cuentas.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('tesoreria.cuentas.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Cuentas Bancarias</a>
                    <a href="{{ route('tesoreria.movimientos.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('tesoreria.movimientos.index') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Mov. Internos</a>
                    <a href="{{ route('tesoreria.movimientos.extracto') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('tesoreria.movimientos.extracto') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Mov. Cuentas</a>
                </div>
            </div>

            {{-- GASTOS --}}
            <div x-data="{ open: {{ request()->routeIs('gastos.*') ? 'true' : 'false' }} }" class="space-y-1 pt-2">
                <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-blue-300/60 mb-1">Gastos y Servicios</p>
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition-colors">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-rose-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 17h8m0 0V9m0 8l-8-8-4 4-6-6"/>
                        </svg>
                        Gastos
                    </span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                    <a href="{{ route('gastos.variables.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('gastos.variables.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Variables</a>
                    <a href="{{ route('gastos.fijos.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('gastos.fijos.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Fijos</a>
                </div>
            </div>

            {{-- REPORTES --}}
            <div x-data="{ open: {{ request()->routeIs('reportes.*') ? 'true' : 'false' }} }" class="space-y-1 pt-2">
                <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-blue-300/60 mb-1">Análisis</p>
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition-colors">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-violet-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Reportes
                    </span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                    <a href="{{ route('reportes.financiero') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('reportes.financiero') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Financiero</a>
                    <a href="{{ route('reportes.productividad') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('reportes.productividad') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Productividad</a>
                </div>
            </div>

            {{-- PARÁMETROS --}}
            <div x-data="{ open: {{ request()->routeIs('parametros.*') ? 'true' : 'false' }} }" class="space-y-1 pt-2">
                <p class="px-3 text-[10px] font-bold uppercase tracking-wider text-blue-300/60 mb-1">Parámetros</p>
                <button @click="open = !open" class="w-full flex items-center justify-between px-3 py-2 text-sm font-medium rounded-xl text-slate-300 hover:text-white hover:bg-white/5 transition-colors">
                    <span class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Configuración
                    </span>
                    <svg :class="open ? 'rotate-90' : ''" class="w-4 h-4 transform transition-transform duration-200 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
                <div x-show="open" x-cloak x-transition class="pl-8 space-y-1">
                    <a href="{{ route('parametros.especialidades.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('parametros.especialidades.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Especialidades</a>
                    <a href="{{ route('parametros.condiciones.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('parametros.condiciones.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Condiciones</a>
                    <a href="{{ route('parametros.alergenos.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('parametros.alergenos.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Alérgenos</a>
                    <a href="{{ route('parametros.medicos.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('parametros.medicos.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Médicos</a>
                    <a href="{{ route('parametros.tarifas.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('parametros.tarifas.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Tarifas</a>
                    <a href="{{ route('parametros.tasas.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('parametros.tasas.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Tasas de Cambio</a>
                    <a href="{{ route('parametros.empresa.index') }}" class="block px-3 py-2 text-sm font-medium rounded-lg transition-colors {{ request()->routeIs('parametros.empresa.*') ? 'text-white bg-siapc-blue/20 font-semibold' : 'text-slate-400 hover:text-white hover:bg-white/5' }}">Datos de Empresa</a>
                </div>
            </div>
        @endif
    </nav>

    {{-- User Brief / Footer --}}
    <div class="p-4 bg-siapc-navy border-t border-white/5 flex items-center space-x-3">
        <div class="w-9 h-9 rounded-full bg-siapc-blue text-white flex items-center justify-center font-bold text-sm ring-2 ring-siapc-blue/50">
            {{ strtoupper(substr(auth()->user()->nombre_mostrar ?? 'U', 0, 2)) }}
        </div>
        <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-white truncate">{{ auth()->user()->nombre_mostrar ?? 'Invitado' }}</p>
            <p class="text-xs text-blue-300 truncate">{{ auth()->user()->rol_mostrar ?? '' }}</p>
        </div>
    </div>
</aside>