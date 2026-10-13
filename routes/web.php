<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ConsultaController;



Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', fn() => view('auth.dashboard'))->name('dashboard');

    // Pacientes
    Route::resource('pacientes', PacienteController::class)->except(['create', 'store', 'edit', 'update']);

    // Citas
    Route::get('citas', [CitaController::class, 'index'])->name('citas.index');

    // Consultas
    Route::get('consultas', [ConsultaController::class, 'index'])->name('consultas.index');
});

/*
|--------------------------------------------------------------------------
| Rutas de autenticación (Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';