<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    /**
     * Uso: ->middleware('role:admin') o ->middleware('role:admin,medico')
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        // Sin login → al login
        if (!$user) {
            return redirect()->route('login');
        }

        // Inactivo → logout
        if (!$user->activo) {
            auth()->logout();
            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está inactiva. Contacta al administrador.',
            ]);
        }

        // Verificar roles
        if (!empty($roles) && !$user->tieneAlgunRol($roles)) {
            abort(403, 'No tienes permiso para acceder a esta sección.');
        }

        return $next($request);
    }
}