<?php

namespace Database\Seeders;

use App\Models\Cita;
use App\Models\Consulta;
use Illuminate\Database\Seeder;

class ConsultaSeeder extends Seeder
{
    public function run(): void
    {
        // Solo generamos consultas para citas COMPLETADAS
        $citasCompletadas = Cita::where('estado', 'COMPLETADA')->get();

        if ($citasCompletadas->isEmpty()) {
            $this->command->warn('⚠️  No hay citas completadas. ConsultaSeeder omitido.');
            return;
        }

        $diagnosticos = [
            'Paciente estable, sin hallazgos significativos.',
            'Cuadro compatible con hipertensión arterial leve.',
            'Infección viral de vías respiratorias altas.',
            'Lumbalgia mecánica sin signos de alarma.',
            'Cefalea tensional. Se recomienda hidratación.',
            'Gastritis leve. Se ajusta dieta.',
            'Dermatitis alérgica en remisión.',
            'Control metabólico adecuado.',
            'Cefalea migrañosa, se indica tratamiento.',
            'Ansiedad reactiva, se sugiere seguimiento.',
        ];

        $tratamientos = [
            'Reposo, hidratación y control en 7 días.',
            'Tratamiento antihipertensivo. Control en 30 días.',
            'Antibiótico oral por 7 días. Control si persisten síntomas.',
            'Analgésico y antiinflamatorio. Fisioterapia.',
            'Analgésico según necesidad. Control en 15 días.',
            'Omeprazol 20mg por 14 días. Dieta blanda.',
            'Antihistamínico por 10 días.',
            'Continuar tratamiento actual. Control en 3 meses.',
            'Sumatriptán según necesidad. Control en 30 días.',
            'Derivación a psicología. Control en 21 días.',
        ];

        foreach ($citasCompletadas as $cita) {
            // Evitar duplicados
            if (Consulta::where('cita_id', $cita->id)->exists()) {
                continue;
            }

            $tieneReposo = fake()->boolean(30);
            $fechaInicio = $cita->fecha;
            $diasReposo = $tieneReposo ? rand(1, 7) : null;

            Consulta::create([
                'cita_id' => $cita->id,
                'paciente_id' => $cita->paciente_id,
                'medico_id' => $cita->medico_id,

                // Signos vitales con rangos realistas
                'peso' => fake()->randomFloat(2, 45, 110),
                'altura' => fake()->randomFloat(2, 1.45, 1.95),
                'tension_sistolica' => rand(95, 160),
                'tension_diastolica' => rand(60, 100),
                'temperatura' => fake()->randomFloat(1, 35.8, 38.8),
                'frecuencia_cardiaca' => rand(55, 110),

                // Clínica
                'motivo' => $cita->motivo,
                'sintomas' => fake()->randomElement([
                    'Dolor, malestar general, fatiga.',
                    'Fiebre leve, dolor de cabeza.',
                    'Náuseas, mareos ocasionales.',
                    'Tos seca, congestión nasal.',
                    'Dolor localizado, sin irradiación.',
                    'Sin síntomas adicionales.',
                ]),
                'diagnostico' => fake()->randomElement($diagnosticos),
                'tratamiento' => fake()->randomElement($tratamientos),

                // Reposo
                'reposo' => $tieneReposo ? 'SI' : 'NO',
                'reposo_dias' => $diasReposo,
                'reposo_fecha_inicio' => $tieneReposo ? $fechaInicio : null,
                'reposo_fecha_fin' => $tieneReposo ? $fechaInicio->copy()->addDays($diasReposo) : null,
            ]);
        }

        $total = Consulta::count();
        $this->command->info("✅ Consultas generadas. Total en BD: {$total}");
    }
}