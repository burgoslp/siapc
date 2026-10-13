<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use App\Models\Tarifa;
use Illuminate\Database\Seeder;

class TarifaSeeder extends Seeder
{
    public function run(): void
    {
        // Precios de consulta por especialidad
        $preciosPorEspecialidad = [
            'Medicina General'             => 20.00,
            'Cardiología'                  => 40.00,
            'Pediatría'                    => 30.00,
            'Dermatología'                 => 35.00,
            'Neurología'                   => 45.00,
            'Traumatología'                => 40.00,
            'Ginecología y Obstetricia'    => 40.00,
            'Oftalmología'                 => 35.00,
            'Otorrinolaringología'         => 35.00,
            'Endocrinología'               => 40.00,
            'Gastroenterología'            => 40.00,
            'Urología'                     => 40.00,
            'Psiquiatría'                  => 50.00,
            'Neumología'                   => 40.00,
            'Nefrología'                   => 40.00,
        ];

        foreach ($preciosPorEspecialidad as $nombreEspecialidad => $precio) {
            $especialidad = Especialidad::where('nombre', $nombreEspecialidad)->first();

            if (!$especialidad) {
                $this->command->warn("⚠️  Especialidad no encontrada: {$nombreEspecialidad}");
                continue;
            }

            Tarifa::updateOrCreate(
                [
                    'especialidad_id' => $especialidad->id,
                    'tipo' => 'CONSULTA',
                ],
                [
                    'nombre' => "Consulta {$nombreEspecialidad}",
                    'precio_usd' => $precio,
                    'activo' => true,
                ]
            );
        }

        // ==========================================
        // Tarifas genéricas (sin especialidad)
        // ==========================================

        Tarifa::updateOrCreate(
            [
                'especialidad_id' => null,
                'tipo' => 'URGENCIA',
            ],
            [
                'nombre' => 'Urgencia General',
                'precio_usd' => 60.00,
                'activo' => true,
            ]
        );

        Tarifa::updateOrCreate(
            [
                'especialidad_id' => null,
                'tipo' => 'SEGUIMIENTO',
            ],
            [
                'nombre' => 'Seguimiento (sin costo)',
                'precio_usd' => 0.00,
                'activo' => true,
            ]
        );

        $total = Tarifa::count();
        $this->command->info("✅ Tarifas cargadas: {$total}");
    }
}