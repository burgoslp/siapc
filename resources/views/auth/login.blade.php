<x-guest-layout>
    <div class="mb-8">
        <h2 class="text-2xl font-bold text-slate-900 tracking-tight">Iniciar sesión</h2>
        <p class="text-sm text-slate-500 mt-1">Ingresa tus credenciales para continuar</p>
    </div>

    {{-- Session Status --}}
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email / Username --}}
        <div>
            <x-input-label for="email" :value="__('Usuario o Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="text" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="Usuario o Email" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        {{-- Password --}}
        <div>
            <x-input-label for="password" :value="__('Contraseña')" />
            <x-text-input id="password" class="block mt-1 w-full" type="password" name="password" required autocomplete="current-password" placeholder="••••••••" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        {{-- Remember Me --}}
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center cursor-pointer">
                <input id="remember_me" type="checkbox"
                       class="rounded border-slate-300 text-teal-600 shadow-sm focus:ring-teal-500"
                       name="remember">
                <span class="ms-2 text-sm text-slate-600">Recordarme</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-teal-600 hover:text-teal-700 transition-colors"
                   href="{{ route('password.request') }}">
                    ¿Olvidaste tu contraseña?
                </a>
            @endif
        </div>

        <x-primary-button class="w-full">
            Iniciar sesión
        </x-primary-button>
    </form>
</x-guest-layout>