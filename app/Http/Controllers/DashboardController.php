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
        // 1. KPIs PRINCIPALES (Separados por moneda)
        // ============================
        
        // Citas de hoy
        $queryCitasHoy = Cita::whereDate('fecha', $hoy);
        if ($esMedicoPuro) $queryCitasHoy->where('medico_id', $user->medico->id);
        $citasHoy = $queryCitasHoy->count();

        // --- INGRESOS HOY ---
        $queryPagosHoy = Pago::where('estado', 'ACTIVO')->whereDate('created_at', $hoy);
        if ($esMedicoPuro) $queryPagosHoy->whereHas('cita', fn($q) => $q->where('medico_id', $user->medico->id));
        
        $pagosHoy = $queryPagosHoy->get();
        $ingresosHoyBs = $pagosHoy->where('moneda', 'bs')->sum('monto') ?? 0;
        $ingresosHoyUsd = $pagosHoy->where('moneda', 'dolar')->sum('monto') ?? 0;

        // --- INGRESOS MES ---
        $queryPagosMes = Pago::where('estado', 'ACTIVO')->whereBetween('created_at', [$inicioMes, now()]);
        if ($esMedicoPuro) $queryPagosMes->whereHas('cita', fn($q) => $q->where('medico_id', $user->medico->id));
        
        $pagosMes = $queryPagosMes->get();
        $ingresosMesBs = $pagosMes->where('moneda', 'bs')->sum('monto') ?? 0;
        $ingresosMesUsd = $pagosMes->where('moneda', 'dolar')->sum('monto') ?? 0;

        // --- GASTOS MES ---
        $gastosMesBs = 0;
        $gastosMesUsd = 0;
        if (!$esMedicoPuro) {
            $gastosMesData = Gasto::whereBetween('fecha_gasto', [$inicioMes, now()])->get();
            $gastosMesBs = $gastosMesData->where('moneda', 'bs')->sum('monto')?? 0;
            $gastosMesUsd = $gastosMesData->where('moneda', 'dolar')->sum('monto')?? 0;
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

        // 1. Alertas de Gastos Fijos por vencer (próximos 7 días)
        $alertasGastosFijos = collect();
        
        // Solo mostramos esto al admin o al que pueda ver todo
        if (!$esMedicoPuro) {
            $hoy = \Carbon\Carbon::today();
            $enUnaSemana = $hoy->copy()->addDays(7);

            // Buscamos las recurrencias activas
            $recurrencias = \App\Models\Recurrencia::where('activo', true)->get();

            foreach ($recurrencias as $rec) {
                // Calculamos la fecha del próximo pago (usando la misma lógica del componente de recurrencias)
                $proximoPago = $hoy->copy();

                if ($rec->frecuencia === 'MENSUAL') {
                    $dia = $rec->fecha_pago ? \Carbon\Carbon::parse($rec->fecha_pago)->day : 1;
                    $proximoPago->day($dia);
                    if ($proximoPago->isPast()) $proximoPago->addMonth();
                    
                    $ultimoGasto = \App\Models\Gasto::where('recurrencia_id', $rec->id)->orderByDesc('fecha_gasto')->first();
                    if ($ultimoGasto && $ultimoGasto->fecha_gasto >= $hoy->copy()->day($dia)) {
                        $proximoPago->addMonth();
                    }
                } elseif ($rec->frecuencia === 'SEMANAL') {
                    $diasMap = ['LUNES'=>1, 'MARTES'=>2, 'MIERCOLES'=>3, 'JUEVES'=>4, 'VIERNES'=>5, 'SABADO'=>6, 'DOMINGO'=>7];
                    $proximoPago->dayOfWeek($diasMap[$rec->dia_semana] ?? 1);
                    if ($proximoPago->isPast()) $proximoPago->addWeek();
                    
                    $ultimoGasto = \App\Models\Gasto::where('recurrencia_id', $rec->id)->orderByDesc('fecha_gasto')->first();
                    if ($ultimoGasto && $ultimoGasto->fecha_gasto >= $hoy->copy()->startOfWeek()) {
                        $proximoPago->addWeek();
                    }
                } elseif ($rec->frecuencia === 'ANUAL') {
                    $mes = $rec->fecha_pago ? \Carbon\Carbon::parse($rec->fecha_pago)->month : 1;
                    $dia = $rec->fecha_pago ? \Carbon\Carbon::parse($rec->fecha_pago)->day : 1;
                    $proximoPago->setDate($hoy->year, $mes, $dia);
                    if ($proximoPago->isPast()) $proximoPago->addYear();
                    
                    $ultimoGasto = \App\Models\Gasto::where('recurrencia_id', $rec->id)->orderByDesc('fecha_gasto')->first();
                    if ($ultimoGasto && $ultimoGasto->fecha_gasto >= $hoy->copy()->setDate($hoy->year, $mes, $dia)) {
                        $proximoPago->addYear();
                    }
                }

                // Si el próximo pago está en el rango (hoy hasta +7 días)
                if ($proximoPago->between($hoy, $enUnaSemana)) {
                    $rec->fecha_proximo = $proximoPago;
                    $rec->dias_restantes = $hoy->diffInDays($proximoPago, false);
                    $alertasGastosFijos->push($rec);
                }
            }
        }

        return view('auth.dashboard', compact(
            'citasHoy',
            'ingresosHoyBs', 'ingresosHoyUsd',
            'ingresosMesBs', 'ingresosMesUsd',
            'gastosMesBs', 'gastosMesUsd',
            'consultasPendientes',
            'alertasGastosFijos',
            'proximasCitas',
            'topMedicos',
            'chartLabels',
            'chartData',
            'esMedicoPuro',
            'alertas'
        ));
    }
}