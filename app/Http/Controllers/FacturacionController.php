<?php

namespace App\Http\Controllers;

use App\Models\Caja;
use App\Models\Cita;
use App\Models\Pago;

class FacturacionController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $esMedicoPuro = $user->esMedico() && !$user->puedeVerTodo();

        // ============================================================
        // Caja abierta (si existe)
        // ============================================================
        $cajaAbierta = Caja::abierta();

        // ============================================================
        // Última caja cerrada (para mostrar el último cierre)
        // ============================================================
        $ultimaCajaCerrada = Caja::where('estado', 'CERRADA')
            ->latest('cerrada_en')
            ->first();

        // ============================================================
        // Citas pendientes por cobrar (CONFIRMADA o COMPLETADA)
        // ============================================================
        $queryPendientes = Cita::with(['paciente', 'medico.especialidades'])
            ->whereIn('estado', ['CONFIRMADA', 'COMPLETADA'])
            ->whereIn('estado_pago', ['PENDIENTE', 'PARCIAL'])
            ->orderBy('fecha', 'asc')
            ->limit(20);

        if ($esMedicoPuro) {
            $queryPendientes->where('medico_id', $user->medico->id);
        }

        $citasPorCobrar = $queryPendientes->get();

        // ============================================================
        // Total pendiente por cobrar (USD)
        // ============================================================
        $totalPendienteUsd = $citasPorCobrar->sum(fn($c) => $c->saldo_pendiente_usd);

        return view('facturacion.index', compact(
            'cajaAbierta',
            'ultimaCajaCerrada',
            'citasPorCobrar',
            'totalPendienteUsd',
        ));
    }

    public function pagos()
    {
        $cajaAbierta = Caja::abierta();
        $ultimaCerrada = Caja::where('estado', 'CERRADA')->latest('cerrada_en')->first();

        $caja = $cajaAbierta ?? $ultimaCerrada;

        return view('facturacion.pagos', compact('caja', 'cajaAbierta'));
    }
}