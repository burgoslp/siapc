<?php

namespace Database\Seeders;

use App\Models\Banco;
use App\Models\Cuenta;
use Illuminate\Database\Seeder;

class CuentaSeeder extends Seeder
{
    public function run(): void
    {
        $banesco = Banco::where('nombre', 'Banesco')->first();

        // Datos del titular (ficticio por ahora)
        $titular = 'Clínica San Rafael';
        $lci = 'J';
        $ci = '40123456-7';

        $cuentas = [
            [
                'nombre' => 'Caja Chica Bs',
                'tipo' => 'EFECTIVO',
                'moneda' => 'bs',
                'banco_id' => null,
                'lci' => $lci,
                'ci' => $ci,
                'titular' => $titular,
                'numero_cuenta' => null,
                'saldo_inicial' => 0,
                'activa' => true,
                'es_predeterminada' => true,
                'notas' => 'Efectivo en bolívares para gastos menores del día a día.',
            ],
            [
                'nombre' => 'Caja Chica USD',
                'tipo' => 'EFECTIVO',
                'moneda' => 'dolar',
                'banco_id' => null,
                'lci' => $lci,
                'ci' => $ci,
                'titular' => $titular,
                'numero_cuenta' => null,
                'saldo_inicial' => 0,
                'activa' => true,
                'es_predeterminada' => false,
                'notas' => 'Efectivo en dólares para pagos en divisas.',
            ],
            [
                'nombre' => 'Banesco Principal Bs',
                'tipo' => 'BANCO',
                'moneda' => 'bs',
                'banco_id' => $banesco?->id,
                'lci' => $lci,
                'ci' => $ci,
                'titular' => $titular,
                'numero_cuenta' => '0134-5587-55-4466552233',  
                'saldo_inicial' => 0,
                'activa' => true,
                'es_predeterminada' => false,
                'notas' => 'Cuenta principal en bolívares para cobros por transferencia y pago móvil.',
            ],
            [
                'nombre' => 'Banesco USD',
                'tipo' => 'BANCO',
                'moneda' => 'dolar',
                'banco_id' => $banesco?->id,
                'lci' => $lci,
                'ci' => $ci,
                'titular' => $titular,
                'numero_cuenta' => '0134-5587-55-1155448899', 
                'saldo_inicial' => 0,
                'activa' => true,
                'es_predeterminada' => false,
                'notas' => 'Cuenta en dólares para cobros en divisas.',
            ],
            [
                'nombre' => 'Zelle USD',
                'tipo' => 'DIVISA',
                'moneda' => 'dolar',
                'banco_id' => null,
                'lci' => $lci,
                'ci' => $ci,
                'titular' => $titular,
                'numero_cuenta' => null,
                'saldo_inicial' => 0,
                'activa' => true,
                'es_predeterminada' => false,
                'notas' => 'Cuenta virtual para cobros por Zelle.',
            ],
            [
                'nombre' => 'Binance USD',
                'tipo' => 'DIVISA',
                'moneda' => 'dolar',
                'banco_id' => null,
                'lci' => $lci,
                'ci' => $ci,
                'titular' => $titular,
                'numero_cuenta' => null,
                'saldo_inicial' => 0,
                'activa' => true,
                'es_predeterminada' => false,
                'notas' => 'Cuenta virtual para cobros por Binance.',
            ],
        ];

        foreach ($cuentas as $cuenta) {
            Cuenta::updateOrCreate(
                [
                    'lci' => $cuenta['lci'],
                    'ci' => $cuenta['ci'],
                    'nombre' => $cuenta['nombre'],
                ],
                $cuenta
            );
        }

        $this->command->info('✅ ' . count($cuentas) . ' cuentas cargadas.');
    }
}