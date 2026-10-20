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
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FacturacionController;
use App\Http\Controllers\CuentaController;
use App\Http\Controllers\MovimientoEntreCuentaController;
/*
|--------------------------------------------------------------------------
| Rutas autenticadas (cualquier rol)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Pacientes
    Route::resource('pacientes', PacienteController::class)
        ->except(['create', 'store', 'edit', 'update']);

    // Citas
    Route::get('citas', [CitaController::class, 'index'])->name('citas.index');

    // Consultas
    Route::get('consultas', [ConsultaController::class, 'index'])->name('consultas.index');

    //Facturación
    Route::get('facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');

    Route::get('facturacion/pagos', [FacturacionController::class, 'pagos'])->name('facturacion.pagos');
    Route::get('facturacion/cajas', [FacturacionController::class, 'cajas'])->name('facturacion.cajas');
    Route::get('facturacion/cajas/{caja}', [FacturacionController::class, 'cajaDetalle'])->name('facturacion.cajas.detalle');

});

/*
|--------------------------------------------------------------------------
| Rutas de Parámetros (solo admin)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])->prefix('parametros')->group(function () {

    Route::get('especialidades', [EspecialidadController::class, 'index'])->name('especialidades.index');
    Route::get('condiciones', [CondicionController::class, 'index'])->name('condiciones.index');
    Route::get('alergenos', [AlergenoController::class, 'index'])->name('alergenos.index');
    Route::get('medicos', [MedicoController::class, 'index'])->name('medicos.index');
    Route::get('tarifas', [TarifaController::class, 'index'])->name('tarifas.index');
    Route::get('tasas', [TasaController::class, 'index'])->name('tasas.index');

    Route::prefix('tesoreria')->group(function () {
        Route::get('cuentas', [CuentaController::class, 'index'])->name('tesoreria.cuentas.index');
        Route::get('movimientos', [MovimientoEntreCuentaController::class, 'index'])->name('tesoreria.movimientos.index');

    });
});
Route::get('odontograma', function(){
    return view('odontograma');
});



/*
|--------------------------------------------------------------------------
| Rutas de autenticación (Breeze)
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';