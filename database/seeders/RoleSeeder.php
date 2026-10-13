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
                'nombre' => 'admin',
                'etiqueta' => 'Administrador',
                'descripcion' => 'Acceso total al sistema, gestión de usuarios y configuración.',
            ],
            [
                'nombre' => 'medico',
                'etiqueta' => 'Médico',
                'descripcion' => 'Acceso a sus citas, consultas y todos los pacientes.',
            ],
            [
                'nombre' => 'recepcion',
                'etiqueta' => 'Recepción',
                'descripcion' => 'Agendamiento de citas y gestión de pacientes.',
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