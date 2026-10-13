<?php

namespace Database\Seeders;

use App\Models\Tasa;
use Illuminate\Database\Seeder;

class TasaSeeder extends Seeder
{
    public function run(): void
    {
        $tasas = [
            [
                'nombre' => 'bcv',
                'etiqueta' => 'BCV',
                'descripcion' => 'Tasa oficial del Banco Central de Venezuela',
                'predeterminado' => true,
                'activa' => true,
            ],
            [
                'nombre' => 'paralelo',
                'etiqueta' => 'Paralelo',
                'descripcion' => 'Tasa del mercado paralelo',
                'predeterminado' => false,
                'activa' => true,
            ],
            [
                'nombre' => 'binance',
                'etiqueta' => 'Binance',
                'descripcion' => 'Tasa P2P de Binance',
                'predeterminado' => false,
                'activa' => true,
            ],
        ];

        foreach ($tasas as $tasa) {
            Tasa::updateOrCreate(
                ['nombre' => $tasa['nombre']],
                $tasa
            );
        }

        $this->command->info('✅ ' . count($tasas) . ' tasas cargadas.');
    }
}