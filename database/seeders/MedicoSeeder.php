<?php

namespace Database\Seeders;

use App\Models\Medico;
use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class MedicoSeeder extends Seeder
{
    public function run(): void
    {
        // [nombre, apellido, lci, ci, licencia, telefono, email, estado, [especialidades...]]
        $medicos = [
            ['Carlos',   'Mendoza',   'V', '12345678', 'MPPS-10001', '0414-1112233', 'carlos.mendoza@clinicare.test',  'ACTIVO',   ['Cardiología', 'Medicina General']],
            ['Ana',      'Ramírez',   'V', '11223344', 'MPPS-10002', '0424-2223344', 'ana.ramirez@clinicare.test',     'ACTIVO',   ['Pediatría']],
            ['Luis',     'Fernández', 'V', '10203040', 'MPPS-10003', '0412-3334455', 'luis.fernandez@clinicare.test',  'ACTIVO',   ['Dermatología']],
            ['María',    'González',  'V', '13579246', 'MPPS-10004', '0416-4445566', 'maria.gonzalez@clinicare.test',  'ACTIVO',   ['Neurología']],
            ['José',     'Rodríguez', 'V', '24680135', 'MPPS-10005', '0414-5556677', 'jose.rodriguez@clinicare.test',  'ACTIVO',   ['Traumatología']],
            ['Gabriela', 'Pérez',     'V', '19283746', 'MPPS-10006', '0424-6667788', 'gabriela.perez@clinicare.test',  'ACTIVO',   ['Ginecología y Obstetricia']],
            ['Roberto',  'Silva',     'V', '28374659', 'MPPS-10007', '0412-7778899', 'roberto.silva@clinicare.test',   'ACTIVO',   ['Oftalmología', 'Medicina General']],
            ['Carmen',   'Torres',    'V', '37465829', 'MPPS-10008', '0416-8889900', 'carmen.torres@clinicare.test',   'ACTIVO',   ['Endocrinología']],
            ['Pedro',    'Vargas',    'V', '46582937', 'MPPS-10009', '0414-9990011', 'pedro.vargas@clinicare.test',    'LICENCIA', ['Gastroenterología']],
            ['Sofía',    'Herrera',   'V', '55667788', 'MPPS-10010', '0424-1011122', 'sofia.herrera@clinicare.test',   'ACTIVO',   ['Psiquiatría', 'Medicina General']],
        ];

        foreach ($medicos as $data) {
            [$nombre, $apellido, $lci, $ci, $licencia, $telefono, $email, $estado, $especialidades] = $data;

            $medico = Medico::updateOrCreate(
                ['licencia_medica' => $licencia],
                [
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'lci' => $lci,
                    'ci' => $ci,
                    'telefono' => $telefono,
                    'email' => $email,
                    'estado' => $estado,
                ]
            );

            // Asignar especialidades
            $ids = Especialidad::whereIn('nombre', $especialidades)->pluck('id');
            $medico->especialidades()->sync($ids);
        }

        $this->command->info('✅ ' . count($medicos) . ' médicos cargados.');
    }
}