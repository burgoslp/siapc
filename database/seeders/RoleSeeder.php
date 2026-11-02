<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'nombre' => 'Admin',
                'etiqueta' => 'Administrador',
                'descripcion' => 'Acceso total al sistema, gestión de usuarios y configuración.',
            ],
            [
                'nombre' => 'medico',
                'etiqueta' => 'Médico',
                'descripcion' => 'Acceso a sus citas, consultas, todos los pacientes y facturación.',
            ],
            [
                'nombre' => 'recepcion',
                'etiqueta' => 'Recepción',
                'descripcion' => 'Agendamiento de citas, gestión de pacientes y facturación.',
            ],
        ];

        foreach ($roles as $rol) {
            Role::updateOrCreate(
                ['nombre' => $rol['nombre']],
                $rol
            );
        }

        $this->command->info('✅ ' . count($roles) . ' roles cargados.');
    }
}