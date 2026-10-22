<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Empresa;

class EmpresaSeeder extends Seeder
{
    public function run(): void
    {
        $datos = [
            // Identidad
            ['clave' => 'nombre_clinica', 'valor' => 'Consultorio Dental Sonrisa', 'grupo' => 'identidad'],
            ['clave' => 'rif', 'valor' => 'J-40999888-7', 'grupo' => 'identidad'],
            ['clave' => 'direccion', 'valor' => 'Av. Principal, Sector Centro, Caracas', 'grupo' => 'identidad'],
            
            // Contacto (Los que solicitaste)
            ['clave' => 'telefono_1', 'valor' => '0212-1234567', 'grupo' => 'contacto'],
            ['clave' => 'telefono_2', 'valor' => '0414-1234567', 'grupo' => 'contacto'],
            ['clave' => 'whatsapp', 'valor' => '0414-1234567', 'grupo' => 'contacto'],
            ['clave' => 'email', 'valor' => 'contacto@dentalsonrisa.com', 'grupo' => 'contacto'],
            ['clave' => 'sitio_web', 'valor' => 'www.dentalsonrisa.com', 'grupo' => 'contacto'],
            
            // Redes Sociales (Opcional, para el pie de página del PDF)
            ['clave' => 'instagram', 'valor' => '@dentalsonrisa', 'grupo' => 'redes'],
            
            // Logo
            ['clave' => 'logo', 'valor' => null, 'grupo' => 'identidad'],
        ];

        foreach ($datos as $dato) {
            Empresa::create($dato);
        }
    }
}