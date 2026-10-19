<?php

namespace Database\Seeders;

use App\Models\Banco;
use Illuminate\Database\Seeder;

class BancoSeeder extends Seeder
{
    public function run(): void
    {
        $bancos = [
            ['nombre' => 'Banesco', 'codigo_banco' => '0134'],
            ['nombre' => 'Banco de Venezuela', 'codigo_banco' => '0102'],
            ['nombre' => 'Mercantil', 'codigo_banco' => '0105'],
            ['nombre' => 'BBVA Provincial', 'codigo_banco' => '0108'],
            ['nombre' => 'Banco Nacional de Crédito', 'codigo_banco' => '0191'],
            ['nombre' => 'Bancamiga', 'codigo_banco' => '0172'],
            ['nombre' => 'Banco Bicentenario', 'codigo_banco' => '0175'],
        ];

        foreach ($bancos as $banco) {
            Banco::updateOrCreate(
                ['codigo_banco' => $banco['codigo_banco']],
                $banco
            );
        }

        $this->command->info('✅ ' . count($bancos) . ' bancos cargados.');
    }
}