@extends('layouts.base')

@section('title', config('app.name', 'SIAPC') . ' - Mi Perfil')

@section('contenido')
<div class="container mx-auto px-4 py-6">
    
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">Mi Perfil</h1>
        <p class="text-sm text-gray-500 mt-1">Actualiza tu información de acceso y profesional</p>
    </div>

    {{-- Mensajes de éxito --}}
    @if (session('status') === 'profile-updated')
        <div class="p-3 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
            Perfil actualizado correctamente.
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('patch')

        {{-- ============================================================ --}}
        {{-- SECCIÓN 1: DATOS DE ACCESO (Todos los usuarios) --}}
        {{-- ============================================================ --}}
        <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
            <h2 class="text-sm font-bold text-teal-700 uppercase tracking-wide mb-4">Datos de Acceso</h2>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Usuario *</label>
                    <input type="text" name="username" value="{{ old('username', $user->username) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('username') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Correo Electrónico *</label>
                    <input type="email" name="email" value="{{ old('email', $user->email) }}" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                    @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        {{-- ============================================================ --}}
        {{-- SECCIÓN 2: DATOS DEL MÉDICO (Solo si es médico) --}}
        {{-- ============================================================ --}}
        @if ($user->medico)
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-bold text-teal-700 uppercase tracking-wide">Datos Profesionales</h2>
                    <span class="px-2 py-1 bg-slate-100 text-slate-600 text-xs font-semibold rounded-md">
                        Licencia: {{ $user->medico->licencia_medica }}
                    </span>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Nombre *</label>
                        <input type="text" name="nombre" value="{{ old('nombre', $user->medico->nombre) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        @error('nombre') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Apellido *</label>
                        <input type="text" name="apellido" value="{{ old('apellido', $user->medico->apellido) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        @error('apellido') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Teléfono</label>
                        <input type="text" name="telefono" value="{{ old('telefono', $user->medico->telefono) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-teal-500 focus:border-teal-500">
                        @error('telefono') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </div>

                    {{-- Firma Digital --}}
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-2">Firma Digital</label>
                        <div class="flex items-center gap-4">
                            <div class="w-48 h-24 bg-gray-50 border border-gray-200 rounded-lg flex items-center justify-center overflow-hidden shrink-0">
                                @if ($user->medico->firma_url)
                                    <img src="{{ $user->medico->firma_url }}" class="max-h-full max-w-full object-contain" alt="Firma">
                                @else
                                    <span class="text-xs text-gray-400">Sin firma</span>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" name="firmaFile" accept="image/*" class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-teal-50 file:text-teal-700 hover:file:bg-teal-100 transition">
                                <p class="text-[10px] text-gray-500 mt-1">Sube una imagen PNG con fondo transparente.</p>
                                @error('firmaFile') <span class="text-xs text-red-500 block mt-1">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- ============================================================ --}}
        {{-- BOTÓN GUARDAR --}}
        {{-- ============================================================ --}}
        <div class="flex justify-end pt-2">
            <button type="submit" class="px-6 py-2.5 bg-teal-600 text-white rounded-lg text-sm font-semibold hover:bg-teal-700 shadow-sm transition inline-flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                Guardar Cambios
            </button>
        </div>

    </form>

</div>
@endsection