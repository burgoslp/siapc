<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            EspecialidadSeeder::class,
            CondicionSeeder::class,
            AlergenoSeeder::class,
            TarifaSeeder::class,
            TasaSeeder::class,
            CambioSeeder::class,

            // Tesorería
            BancoSeeder::class,      // ← Antes de cuentas
            CuentaSeeder::class,     // ← Antes de comisiones
            ComisionSeeder::class,

            PacienteSeeder::class,
            MedicoSeeder::class,
            UserSeeder::class,
            CitaSeeder::class,
            ConsultaSeeder::class,
        ]);
    }
}