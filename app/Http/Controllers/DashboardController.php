<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Paciente;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $esMedicoPuro = $user->esMedico() && !$user->puedeVerTodo();

        // ============================
        // KPIs
        // ============================

        // Citas de hoy
        $queryCitasHoy = Cita::hoy();
        if ($esMedicoPuro) {
            $queryCitasHoy->where('medico_id', $user->medico->id);
        }
        $citasHoy = $queryCitasHoy->count();

        // Pacientes activos
        $pacientesActivos = Paciente::where('estado', 'activo')->count();

        // Consultas pendientes de atender (citas CONFIRMADAS sin consulta)
        $queryPendientes = Cita::where('estado', 'CONFIRMADA')
            ->whereDoesntHave('consulta');
        if ($esMedicoPuro) {
            $queryPendientes->where('medico_id', $user->medico->id);
        }
        $consultasPendientes = $queryPendientes->count();

        // Ingresos del mes (citas pagadas)
        $queryIngresos = Cita::where('estado_pago', 'PAGADA')
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year);
        if ($esMedicoPuro) {
            $queryIngresos->where('medico_id', $user->medico->id);
        }
        $ingresosMes = $queryIngresos->sum('total');

        // ============================
        // Próximas citas de hoy
        // ============================
        $queryProximas = Cita::with(['paciente', 'medico.especialidades'])
            ->hoy()
            ->whereIn('estado', ['PENDIENTE', 'CONFIRMADA'])
            ->orderBy('fecha', 'asc')
            ->limit(10);
        if ($esMedicoPuro) {
            $queryProximas->where('medico_id', $user->medico->id);
        }
        $proximasCitas = $queryProximas->get();

        // ============================
        // Últimas consultas registradas
        // ============================
        $queryUltimasConsultas = Consulta::with(['paciente', 'medico'])
            ->orderByDesc('created_at')
            ->limit(5);
        if ($esMedicoPuro) {
            $queryUltimasConsultas->where('medico_id', $user->medico->id);
        }
        $ultimasConsultas = $queryUltimasConsultas->get();

        return view('auth.dashboard', compact(
            'citasHoy',
            'pacientesActivos',
            'consultasPendientes',
            'ingresosMes',
            'proximasCitas',
            'ultimasConsultas',
        ));
    }
}