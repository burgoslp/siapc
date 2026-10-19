<?php

namespace Database\Seeders;

use App\Models\Comision;
use App\Models\Cuenta;
use Illuminate\Database\Seeder;

class ComisionSeeder extends Seeder
{
    public function run(): void
    {
        // [forma_pago, %ingreso, fijo_ingreso, %egreso, fijo_egreso]
        $comisionesPorCuenta = [
            'Caja Chica Bs' => [
                ['EFECTIVO', 0, 0, 0, 0],
            ],
            'Caja Chica USD' => [
                ['EFECTIVO', 0, 0, 0, 0],
            ],
            'Banesco Principal Bs' => [
                ['TARJETA_DEBITO',  2.00, 0, 0, 0],
                ['TARJETA_CREDITO', 3.00, 0, 0, 0],
                ['TRANSFERENCIA',   0.30, 0, 0.50, 0],
                ['PAGO_MOVIL',      0, 0, 0.30, 0],
            ],
            'Banesco USD' => [
                ['ZELLE',         1.00, 0, 0, 0],
                ['TRANSFERENCIA', 0.50, 0, 0.50, 0],
            ],
            'Zelle USD' => [
                ['ZELLE', 1.00, 0, 0, 0],
            ],
            'Binance USD' => [
                ['BINANCE', 0.50, 0, 0.50, 0],
            ],
        ];

        $total = 0;

        foreach ($comisionesPorCuenta as $nombreCuenta => $comisiones) {
            $cuenta = Cuenta::where('nombre', $nombreCuenta)->first();
            if (!$cuenta) continue;

            foreach ($comisiones as $c) {
                [$formaPago, $pctIngreso, $fijoIngreso, $pctEgreso, $fijoEgreso] = $c;

                Comision::updateOrCreate(
                    [
                        'cuenta_id' => $cuenta->id,
                        'forma_pago' => $formaPago,
                    ],
                    [
                        'porcentaje_ingreso' => $pctIngreso,
                        'monto_fijo_ingreso' => $fijoIngreso,
                        'porcentaje_egreso' => $pctEgreso,
                        'monto_fijo_egreso' => $fijoEgreso,
                        'activo' => true,
                    ]
                );
                $total++;
            }
        }

        $this->command->info("✅ {$total} comisiones cargadas.");
    }
}