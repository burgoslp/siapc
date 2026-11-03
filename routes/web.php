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
use App\Http\Controllers\EmpresaController;
use App\Http\Controllers\GastoController;
use App\Http\Controllers\ReporteController;

/*
|--------------------------------------------------------------------------
| Rutas para Recepción, Médicos y Admin (Operatividad Diaria)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,recepcion,medico'])->group(function () {
    
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Pacientes (Ver, buscar)
    Route::resource('pacientes', PacienteController::class)
        ->except(['create', 'store', 'edit', 'update']);

    // Citas
    Route::get('citas', [CitaController::class, 'index'])->name('citas.index');

    // Facturación y Caja
    Route::get('facturacion', [FacturacionController::class, 'index'])->name('facturacion.index');
    Route::get('facturacion/pagos', [FacturacionController::class, 'pagos'])->name('facturacion.pagos');
    Route::get('facturacion/cajas', [FacturacionController::class, 'cajas'])->name('facturacion.cajas');
    Route::get('facturacion/cajas/{caja}', [FacturacionController::class, 'cajaDetalle'])->name('facturacion.cajas.detalle');

    // Agendas Compartidas
    Route::get('agendas', [\App\Http\Controllers\AgendaController::class, 'index'])->name('agendas.index');
});



/*
|--------------------------------------------------------------------------
| Rutas exclusivas para Médicos y Admin (Clínica)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin,medico'])->group(function () {
    
    // Consultas
    Route::get('consultas', [ConsultaController::class, 'index'])->name('consultas.index');
    Route::get('/consultas/{consulta}/pdf', [ConsultaController::class, 'pdf'])->name('consultas.pdf');

    // Reportes
    Route::prefix('reportes')->name('reportes.')->group(function () {
        Route::get('/financiero', [ReporteController::class, 'financiero'])->name('financiero');
        Route::get('/productividad', [ReporteController::class, 'productividad'])->name('productividad');
    });
});

/*
|--------------------------------------------------------------------------
| Rutas exclusivas para el Administrador
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'role:admin'])->group(function () {

    // ================= PARÁMETROS =================
    Route::prefix('parametros')->name('parametros.')->group(function () {
        Route::get('especialidades', [EspecialidadController::class, 'index'])->name('especialidades.index');
        Route::get('condiciones', [CondicionController::class, 'index'])->name('condiciones.index');
        Route::get('alergenos', [AlergenoController::class, 'index'])->name('alergenos.index');
        Route::get('medicos', [MedicoController::class, 'index'])->name('medicos.index');
        Route::get('tarifas', [TarifaController::class, 'index'])->name('tarifas.index');
        Route::get('tasas', [TasaController::class, 'index'])->name('tasas.index');
        Route::get('empresa', [EmpresaController::class, 'index'])->name('empresa.index');
    });

    // ================= TESORERÍA =================
    Route::prefix('tesoreria')->name('tesoreria.')->group(function () {
        Route::get('cuentas', [CuentaController::class, 'index'])->name('cuentas.index');
        Route::get('movimientos/internos', [MovimientoEntreCuentaController::class, 'index'])->name('movimientos.index');
        Route::get('movimientos/cuenta', [CuentaController::class, 'extracto'])->name('movimientos.extracto');
    });

    // ================= GASTOS =================
    Route::prefix('gastos')->name('gastos.')->group(function () {
        Route::get('variables', [GastoController::class, 'variables'])->name('variables.index');
        Route::get('fijos', [GastoController::class, 'fijos'])->name('fijos.index');
    });

});

// Ruta pública para agendar
Route::get('/agendar/{token}', function ($token) {
    return view('agenda.publica', compact('token'));
})->name('public.agenda');

/*
|--------------------------------------------------------------------------
| Rutas de autenticación (Breeze)
|--------------------------------------------------------------------------
*/
require __DIR__.'/auth.php';