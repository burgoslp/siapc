<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cita;
use App\Models\Consulta;
use App\Models\Paciente;
use App\Models\Medico;
use App\Models\Tarifa;
use App\Models\Tasa;
use App\Models\Caja;
use App\Models\Pago;
use App\Models\Cuenta;
use App\Models\User;
use Carbon\Carbon;

class HistorialClinicoSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('📅 Generando 30 días de historial clínico y financiero...');

        $admin = User::where('username', 'admin')->first();
        if (!$admin) {
            $this->command->error('❌ Ejecuta primero ConsultorioOdontologiaSeeder');
            return;
        }

        $pacientes = Paciente::all();
        $medicos = Medico::all();
        $tarifas = Tarifa::where('tipo', 'CONSULTA')->get();
        
        $tasaBcv = Tasa::where('nombre', 'bcv')->first();
        $cambioDolar = $tasaBcv->cambioActualDe('dolar');
        $montoTasa = $cambioDolar ? $cambioDolar->monto : 36.50;

        // Cuentas de tesorería
        $cuentaCajaBs = Cuenta::where('nombre', 'Caja Chica Bs')->first();
        $cuentaCajaUsd = Cuenta::where('nombre', 'Caja Chica USD')->first();
        $cuentaBanescoBs = Cuenta::where('nombre', 'Banesco Principal Bs')->first();
        $cuentaZelle = Cuenta::where('nombre', 'Zelle USD')->first();

        // Iterar por los últimos 30 días
        for ($i = 29; $i >= 0; $i--) {
            $fecha = Carbon::now()->subDays($i);
            $cantidadCitas = rand(2, 5);

            // 1. Crear la Caja del día (ABIERTA al principio)
            $caja = Caja::create([
                'codigo' => 'CAJA-' . $fecha->format('Y-m-d') . '-001',
                'abierta_por' => $admin->id,
                'abierta_en' => $fecha->copy()->setHour(8)->setMinute(0),
                'monto_inicial' => 0,
                'moneda_inicial' => 'bs',
                'estado' => 'ABIERTA',
            ]);

            $totalEfectivoBs = 0;
            $totalEfectivoUsd = 0;
            $totalTransferencia = 0;
            $totalPagoMovil = 0;
            $totalZelle = 0;

            for ($j = 0; $j < $cantidadCitas; $j++) {
                $paciente = $pacientes->random();
                $medico = $medicos->random();
                $tarifa = $tarifas->random();

                // Generar el numero_cita manualmente para el seeder
                $ultimaCita = Cita::orderByDesc('id')->first();
                $siguienteNumero = $ultimaCita && $ultimaCita->numero_cita 
                    ? ((int) str_replace('CIT-', '', $ultimaCita->numero_cita)) + 1 
                    : 1;
                $numeroCita = 'CIT-' . str_pad($siguienteNumero, 4, '0', STR_PAD_LEFT);

                // 2. Crear la cita
                $cita = Cita::create([
                    'numero_cita' => $numeroCita,
                    'paciente_id' => $paciente->id,
                    'medico_id' => $medico->id,
                    'fecha' => $fecha->copy()->setHour(rand(8, 16))->setMinute(0),
                    'motivo' => 'Consulta de rutina',
                    'estado' => 'COMPLETADA',
                    'tarifa_id' => $tarifa->id,
                    'precio' => $tarifa->precio_usd,
                    'descuento' => 0,
                    'total' => $tarifa->precio_usd,
                    'total_pagado' => $tarifa->precio_usd * $montoTasa,
                    'estado_pago' => 'PAGADA',
                    'tasa_id' => $tasaBcv->id,
                    'cambio_id' => $cambioDolar->id,
                    'monto_tasa_cambio' => $montoTasa,
                    'created_at' => $fecha->copy()->setHour(rand(8, 16)),
                    'updated_at' => $fecha->copy()->setHour(rand(8, 16)),
                ]);

                // 3. Crear la consulta
                Consulta::create([
                    'cita_id' => $cita->id,
                    'paciente_id' => $paciente->id,
                    'medico_id' => $medico->id,
                    'motivo' => 'Dolor dental',
                    'sintomas' => 'Sensibilidad al frío',
                    'diagnostico' => 'Caries oclusal',
                    'tratamiento' => 'Resina compuesta',
                    'created_at' => $cita->created_at,
                    'updated_at' => $cita->created_at,
                ]);

                // 4. Crear el Pago
                $esDolar = rand(0, 1) === 1;
                $formaPago = $esDolar ? ['ZELLE', 'EFECTIVO'][rand(0, 1)] : ['EFECTIVO', 'TRANSFERENCIA', 'PAGO_MOVIL'][rand(0, 2)];
                
                $cuentaId = null;
                if ($formaPago === 'ZELLE') $cuentaId = $cuentaZelle->id;
                elseif ($formaPago === 'EFECTIVO' && $esDolar) $cuentaId = $cuentaCajaUsd->id;
                elseif ($formaPago === 'EFECTIVO' && !$esDolar) $cuentaId = $cuentaCajaBs->id;
                elseif ($formaPago === 'TRANSFERENCIA' || $formaPago === 'PAGO_MOVIL') $cuentaId = $cuentaBanescoBs->id;

                $montoPago = $esDolar ? $tarifa->precio_usd : $tarifa->precio_usd * $montoTasa;

                $pago = Pago::create([
                    'caja_id' => $caja->id,
                    'cuenta_id' => $cuentaId,
                    'cita_id' => $cita->id,
                    'paciente_id' => $paciente->id,
                    'monto' => $montoPago,
                    'tasa_id' => $tasaBcv->id,
                    'cambio_id' => $cambioDolar->id,
                    'tasa_momento' => $montoTasa,
                    'moneda' => $esDolar ? 'dolar' : 'bs',
                    'forma_pago' => $formaPago,
                    'banco' => $esDolar ? null : 'Banesco',
                    'referencia' => $esDolar ? rand(100000, 999999) : null,
                    'registrado_por' => $admin->id,
                    'estado' => 'ACTIVO',
                    'created_at' => $cita->created_at,
                    'updated_at' => $cita->created_at,
                ]);

                // Acumular totales para el cierre
                if ($formaPago === 'ZELLE') $totalZelle += $pago->monto_bs;
                elseif ($formaPago === 'EFECTIVO' && $esDolar) $totalEfectivoUsd += $pago->monto_bs;
                elseif ($formaPago === 'EFECTIVO' && !$esDolar) $totalEfectivoBs += $pago->monto_bs;
                elseif ($formaPago === 'TRANSFERENCIA') $totalTransferencia += $pago->monto_bs;
                elseif ($formaPago === 'PAGO_MOVIL') $totalPagoMovil += $pago->monto_bs;
            }

            // 5. CERRAR LA CAJA (Aquí se calculan los totales)
            $caja->update([
                'cerrada_por' => $admin->id,
                'cerrada_en' => $fecha->copy()->setHour(18)->setMinute(0),
                'total_efectivo' => $totalEfectivoBs + $totalEfectivoUsd,
                'total_transferencia' => $totalTransferencia,
                'total_pago_movil' => $totalPagoMovil,
                'total_zelle' => $totalZelle,
                'total_general' => $totalEfectivoBs + $totalEfectivoUsd + $totalTransferencia + $totalPagoMovil + $totalZelle,
                'estado' => 'CERRADA',
            ]);

            // 6. LIQUIDAR A TESORERÍA (Un movimiento por cada pago de la caja)
            $pagosDeLaCaja = Pago::where('caja_id', $caja->id)->where('estado', 'ACTIVO')->get();
            
            foreach ($pagosDeLaCaja as $pago) {
                $cuenta = Cuenta::find($pago->cuenta_id);
                if (!$cuenta) continue;

                $montoEnMonedaCuenta = (float) $pago->monto;
                if ($cuenta->moneda !== $pago->moneda) {
                    if ($cuenta->moneda === 'bs' && in_array($pago->moneda, ['dolar', 'euro'])) {
                        $montoEnMonedaCuenta = (float) $pago->monto * (float) $pago->tasa_momento;
                    }
                }

                $cuenta->movimientos()->create([
                    'tipo' => 'INGRESO',
                    'origen_tipo' => 'PAGO',
                    'origen_id' => $pago->id,
                    'monto' => $montoEnMonedaCuenta,
                    'saldo_despues' => $cuenta->saldo_actual + $montoEnMonedaCuenta, 
                    'fecha' => $caja->cerrada_en,
                    'descripcion' => "Pago {$pago->cita->numero_cita}",
                    'created_at' => $caja->cerrada_en,
                    'updated_at' => $caja->cerrada_en,
                ]);
            }
        }

        $this->command->info('✅ Historial clínico, cajas y tesorería generados con éxito.');
    }
}