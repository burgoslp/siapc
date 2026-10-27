<?php

namespace App\Http\Controllers;

use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\Pago;
use App\Models\Gasto;
use App\Models\Caja;
use App\Models\Medico;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $esMedicoPuro = $user->esMedico() && !$user->puedeVerTodo();
        $hoy = Carbon::today();
        $inicioMes = Carbon::now()->startOfMonth();

        // ============================
        // 1. KPIs PRINCIPALES
        // ============================
        
        // Citas de hoy
        $queryCitasHoy = Cita::whereDate('fecha', $hoy);
        if ($esMedicoPuro) $queryCitasHoy->where('medico_id', $user->medico->id);
        $citasHoy = $queryCitasHoy->count();

       // Ingresos Hoy (en Bs)
        $queryPagosHoy = Pago::where('estado', 'ACTIVO')->whereDate('created_at', $hoy);
        if ($esMedicoPuro) $queryPagosHoy->whereHas('cita', fn($q) => $q->where('medico_id', $user->medico->id));
        $ingresosHoy = $queryPagosHoy->sum(DB::raw("
            CASE 
                WHEN moneda = 'bs' THEN monto 
                ELSE monto * COALESCE(tasa_momento, 1) 
            END
        "));

        // Ingresos Mes (en Bs)
        $queryPagosMes = Pago::where('estado', 'ACTIVO')->whereBetween('created_at', [$inicioMes, now()]);
        if ($esMedicoPuro) $queryPagosMes->whereHas('cita', fn($q) => $q->where('medico_id', $user->medico->id));
        $ingresosMes = $queryPagosMes->sum(DB::raw("
            CASE 
                WHEN moneda = 'bs' THEN monto 
                ELSE monto * COALESCE(tasa_momento, 1) 
            END
        "));

        // Gastos del Mes (Solo visible para admin)
        $gastosMes = 0;
        if (!$esMedicoPuro) {
            $gastosMes = Gasto::whereBetween('fecha_gasto', [$inicioMes, now()])
                ->sum(DB::raw("
                    CASE 
                        WHEN moneda = 'bs' THEN monto 
                        ELSE monto * COALESCE(tasa_cambio, 1) 
                    END
                "));
        }

        // Consultas pendientes
        $queryPendientes = Cita::where('estado', 'CONFIRMADA')->whereDoesntHave('consulta');
        if ($esMedicoPuro) $queryPendientes->where('medico_id', $user->medico->id);
        $consultasPendientes = $queryPendientes->count();

        // ============================
        // 2. ALERTAS OPERATIVAS
        // ============================
        $alertas = [];
        if (!$esMedicoPuro) {
            $cajaVencida = Caja::abierta();
            if ($cajaVencida && $cajaVencida->estaVencida()) {
                $alertas[] = ['tipo' => 'error', 'mensaje' => 'Hay una caja del día anterior sin cerrar.', 'ruta' => route('facturacion.index')];
            }
        }

        // ============================
        // 3. PRÓXIMAS CITAS (Hoy y Mañana)
        // ============================
        $queryProximas = Cita::with(['paciente', 'medico.especialidades'])
            ->where('fecha', '>=', now())
            ->whereIn('estado', ['PENDIENTE', 'CONFIRMADA'])
            ->orderBy('fecha', 'asc')
            ->limit(6);
        if ($esMedicoPuro) $queryProximas->where('medico_id', $user->medico->id);
        $proximasCitas = $queryProximas->get();

        // ============================
        // 4. RENDIMIENTO (Solo Admin)
        // ============================
        $topMedicos = [];
        if (!$esMedicoPuro) {
            $topMedicos = Medico::withCount(['citas' => function($q) use ($inicioMes) {
                $q->where('estado', 'COMPLETADA')->where('fecha', '>=', $inicioMes);
            }])->orderByDesc('citas_count')->limit(3)->get();
        }

        // ============================
        // 5. GRÁFICO (Últimos 7 días)
        // ============================
        $chartLabels = [];
        $chartData = [];
        for ($i = 6; $i >= 0; $i--) {
            $fecha = now()->subDays($i);
            $chartLabels[] = $fecha->format('d/m');
            
            $queryChart = Pago::where('estado', 'ACTIVO')->whereDate('created_at', $fecha);
            if ($esMedicoPuro) $queryChart->whereHas('cita', fn($q) => $q->where('medico_id', $user->medico->id));
            
            $chartData[] = round($queryChart->sum(DB::raw("
                CASE 
                    WHEN moneda = 'bs' THEN monto 
                    ELSE monto * COALESCE(tasa_momento, 1) 
                END
            ")), 2);
        }

        return view('auth.dashboard', compact(
            'citasHoy',
            'ingresosHoy',
            'ingresosMes',
            'gastosMes',
            'consultasPendientes',
            'alertas',
            'proximasCitas',
            'topMedicos',
            'chartLabels',
            'chartData',
            'esMedicoPuro'
        ));
    }
}