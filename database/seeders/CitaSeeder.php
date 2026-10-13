<?php

namespace Database\Seeders;

use App\Models\Cambio;
use App\Models\Cita;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Tarifa;
use App\Models\Tasa;
use Illuminate\Database\Seeder;

class CitaSeeder extends Seeder
{
    public function run(): void
    {
        $medicos = Medico::with('especialidades')->where('estado', 'ACTIVO')->get();
        $pacientes = Paciente::all();

        if ($pacientes->isEmpty() || $medicos->isEmpty()) {
            $this->command->warn('⚠️  No hay pacientes o médicos activos. CitaSeeder omitido.');
            return;
        }

        // Tasa predeterminada actual
        $tasaPredeterminada = Tasa::predeterminada();
        $cambioActual = $tasaPredeterminada?->cambioActualDe('dolar');

        $motivos = [
            'Consulta general de rutina',
            'Control de presión arterial',
            'Dolor abdominal persistente',
            'Cefalea recurrente',
            'Control post-operatorio',
            'Revisión pediátrica',
            'Evaluación dermatológica',
            'Control de diabetes',
            'Dolor lumbar',
            'Consulta por alergias',
            'Chequeo anual',
            'Seguimiento de tratamiento',
            'Molestias gastrointestinales',
            'Control de peso y nutrición',
            'Revisión oftalmológica',
        ];

        $hoy = now()->startOfDay();
        $creadas = 0;

        for ($i = 0; $i < 40; $i++) {
            $medico = $medicos->random();
            $paciente = $pacientes->random();

            // Fecha aleatoria ±15 días
            $diasOffset = rand(-15, 15);
            $fecha = $hoy->copy()->addDays($diasOffset);

            // Horario 8:00 - 17:00 cada 30 min
            $hora = rand(8, 16);
            $minuto = [0, 30][rand(0, 1)];
            $fecha->setTime($hora, $minuto);

            // Estado según fecha
            if ($fecha->isPast()) {
                $estado = fake()->randomElement(['CONFIRMADA', 'CONFIRMADA', 'CONFIRMADA', 'CANCELADA', 'NO_ASISTIO']);
            } else {
                $estado = fake()->randomElement(['PENDIENTE', 'PENDIENTE', 'CONFIRMADA', 'CONFIRMADA']);
            }

            // Evitar duplicados (mismo médico + fecha)
            $existe = Cita::where('medico_id', $medico->id)
                ->where('fecha', $fecha)
                ->exists();

            if ($existe) continue;

            // Buscar tarifa CONSULTA para la especialidad del médico
            $especialidadId = $medico->especialidades->first()?->id;
            $tarifa = Tarifa::paraEspecialidad($especialidadId, 'CONSULTA');

            // Calcular total y snapshot de tasa
            $precio = $tarifa?->precio_usd ?? 0;
            $descuento = 0;
            $total = $precio - $descuento;

            $tasaId = null;
            $cambioId = null;
            $montoTasaCambio = null;

            if ($tasaPredeterminada && $cambioActual) {
                $tasaId = $tasaPredeterminada->id;
                $cambioId = $cambioActual->id;
                $montoTasaCambio = $cambioActual->monto;
            }

            Cita::create([
                'paciente_id' => $paciente->id,
                'medico_id' => $medico->id,
                'fecha' => $fecha,
                'motivo' => fake()->randomElement($motivos),
                'estado' => $estado,

                // Facturación
                'tarifa_id' => $tarifa?->id,
                'precio' => $precio ?: null,
                'descuento' => $descuento,
                'total' => $total ?: null,
                'estado_pago' => $fecha->isPast() && $estado === 'CONFIRMADA'
                    ? fake()->randomElement(['PENDIENTE', 'PAGADA', 'PAGADA', 'PAGADA'])
                    : 'PENDIENTE',

                // Snapshot de tasa
                'tasa_id' => $tasaId,
                'cambio_id' => $cambioId,
                'monto_tasa_cambio' => $montoTasaCambio,
            ]);

            $creadas++;
        }

        $total = Cita::count();
        $this->command->info("✅ Citas generadas: {$creadas} nuevas. Total en BD: {$total}");
    }
}