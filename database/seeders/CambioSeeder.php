<?php

namespace Database\Seeders;

use App\Models\Cambio;
use App\Models\Tasa;
use Illuminate\Database\Seeder;

class CambioSeeder extends Seeder
{
    public function run(): void
    {
        // Monto inicial por cada tasa (en dólar)
        $montosInicialesDolares = [
            'bcv' => 36.5000,
            'paralelo' => 42.0000,
            'binance' => 41.5000,
        ];

        $montosInicialesEuros = [
            'bcv' => 80.5000,
            'paralelo' => 100.0000,
            'binance' => 140.5000,
        ];

        // ============================
        // DÓLARES
        // ============================
        foreach ($montosInicialesDolares as $nombreTasa => $monto) {
            $tasa = Tasa::where('nombre', $nombreTasa)->first();

            if (!$tasa) {
                $this->command->warn("⚠️  Tasa no encontrada: {$nombreTasa}");
                continue;
            }

            // Verifica por tasa + moneda
            $tieneCambio = Cambio::where('tasa_id', $tasa->id)
                ->where('moneda', 'dolar')
                ->exists();

            if (!$tieneCambio) {
                Cambio::create([
                    'tasa_id' => $tasa->id,
                    'moneda' => 'dolar',
                    'monto' => $monto,
                ]);

                $this->command->info("   - {$tasa->etiqueta}: {$monto} Bs/$");
            } else {
                $this->command->info("   - {$tasa->etiqueta} (dólar): ya tenía cambio, omitido.");
            }
        }

        // ============================
        // EUROS
        // ============================
        foreach ($montosInicialesEuros as $nombreTasa => $monto) {
            $tasa = Tasa::where('nombre', $nombreTasa)->first();

            if (!$tasa) {
                $this->command->warn("⚠️  Tasa no encontrada: {$nombreTasa}");
                continue;
            }

            // Verifica por tasa + moneda
            $tieneCambio = Cambio::where('tasa_id', $tasa->id)
                ->where('moneda', 'euro')
                ->exists();

            if (!$tieneCambio) {
                Cambio::create([
                    'tasa_id' => $tasa->id,
                    'moneda' => 'euro',
                    'monto' => $monto,
                ]);

                $this->command->info("   - {$tasa->etiqueta}: {$monto} Bs/€");
            } else {
                $this->command->info("   - {$tasa->etiqueta} (euro): ya tenía cambio, omitido.");
            }
        }

        $total = Cambio::count();
        $this->command->info("✅ Cambios en BD: {$total}");
    }
}