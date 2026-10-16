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
        // Detectar cajas (abierta y vencida)
        // ============================================================
        $cajaAbierta = Caja::abierta();               // puede ser de hoy o vencida
        $cajaVencida = $cajaAbierta?->estaVencida() ? $cajaAbierta : null;
        $cajaDisponible = $cajaVencida ? null : $cajaAbierta;  // caja válida para operar

        // ============================================================
        // Última caja cerrada
        // ============================================================
        $ultimaCajaCerrada = Caja::where('estado', 'CERRADA')
            ->latest('cerrada_en')
            ->first();

        // ============================================================
        // Citas pendientes por cobrar
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
        $totalPendienteUsd = $citasPorCobrar->sum(fn($c) => $c->saldo_pendiente_usd);

        return view('facturacion.index', compact(
            'cajaDisponible',
            'cajaVencida',
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