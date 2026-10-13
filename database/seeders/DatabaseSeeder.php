<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // 1. Roles y usuarios
            RoleSeeder::class,

            // 2. Catálogos médicos
            EspecialidadSeeder::class,
            CondicionSeeder::class,
            AlergenoSeeder::class,

            // 3. Tarifas y tasas
            TarifaSeeder::class,
            TasaSeeder::class,
            CambioSeeder::class,

            // 4. Pacientes
            PacienteSeeder::class,

            // 5. Médicos (dependen de especialidades)
            MedicoSeeder::class,

            // 6. Usuarios (dependen de roles y médicos)
            UserSeeder::class,

            // 7. Citas y consultas (dependen de pacientes, médicos, tarifas, tasas)
            CitaSeeder::class,
            ConsultaSeeder::class,
        ]);
    }
}