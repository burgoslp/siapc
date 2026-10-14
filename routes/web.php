<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PacienteController;
use App\Http\Controllers\CitaController;
use App\Http\Controllers\ConsultaController;
use App\Http\Controllers\EspecialidadController;
use App\Http\Controllers\CondicionController;
use App\Http\Controllers\AlergenoController;
use App\Http\Controllers\MedicoController;
use App\Http\Controllers\TarifaController;
use App\Http\Controllers\TasaController;

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', fn() => view('auth.dashboard'))->name('dashboard');

    // Pacientes
    Route::resource('pacientes', PacienteController::class)->except(['create', 'store', 'edit', 'update']);

    // Citas
    Route::get('citas', [CitaController::class, 'index'])->name('citas.index');

    // Consultas
    Route::get('consultas', [ConsultaController::class, 'index'])->name('consultas.index');

    //parametros 
    Route::get('especialidades', [EspecialidadController::class, 'index'])->name('especialidades.index');
    Route::get('condiciones', [CondicionController::class, 'index'])->name('condiciones.index');
    Route::get('alergenos', [AlergenoController::class, 'index'])->name('alergenos.index');

    //medicos
    Route::get('parametros/medicos', [MedicoController::class, 'index'])->name('medicos.index');

    //tarifas
    Route::get('parametros/tarifas', [TarifaController::class, 'index'])->name('tarifas.index');

    //tasas
    Route::get('parametros/tasas', [TasaController::class, 'index'])->name('tasas.index');
});

/*
|--------------------------------------------------------------------------
| Rutas de autenticación (Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';