<?php

namespace Database\Seeders;

use App\Models\Medico;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdmin = Role::where('nombre', 'admin')->first();
        $rolMedico = Role::where('nombre', 'medico')->first();
        $rolRecepcion = Role::where('nombre', 'recepcion')->first();

        // ==========================================
        // 1. ADMINISTRADOR
        // ==========================================
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'username' => 'admin',
                'email' => 'admin@clinicare.test',
                'password' => Hash::make('admin123'),
                'activo' => true,
            ]
        );
        $admin->roles()->sync([$rolAdmin->id]);

        // ==========================================
        // 2. RECEPCIONISTA
        // ==========================================
        $recepcion = User::updateOrCreate(
            ['username' => 'recepcion'],
            [
                'username' => 'recepcion',
                'email' => 'recepcion@clinicare.test',
                'password' => Hash::make('recepcion123'),
                'activo' => true,
            ]
        );
        $recepcion->roles()->sync([$rolRecepcion->id]);

        // ==========================================
        // 3. MÉDICOS
        // Crea un User para cada Medico existente y lo ancla
        // ==========================================
        $medicos = Medico::all();

        foreach ($medicos as $medico) {
            // Genera username desde nombre+apellido: "cmendoza", "aramirez", etc.
            $username = strtolower(substr($medico->nombre, 0, 1) . $medico->apellido);

            // Asegura unicidad
            $base = $username;
            $i = 1;
            while (User::where('username', $username)->exists()) {
                $username = $base . $i;
                $i++;
            }

            $user = User::create([
                'username' =>  $medico->nombre . ' ' . $medico->apellido,
                'email' => $username . '@clinicare.test',
                'password' => Hash::make('medico123'),
                'activo' => true,
            ]);

            $user->roles()->sync([$rolMedico->id]);

            // Anclar el médico al user
            $medico->update(['user_id' => $user->id]);
        }

        $this->command->info('✅ Usuarios cargados:');
        $this->command->info('   - admin / admin123');
        $this->command->info('   - recepcion / recepcion123');
        $this->command->info('   - ' . $medicos->count() . ' médicos con password: medico123');
    }
}