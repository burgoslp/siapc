<?php

namespace Database\Seeders;

use App\Models\Especialidad;
use Illuminate\Database\Seeder;

class EspecialidadSeeder extends Seeder
{
    public function run(): void
    {
        $especialidades = [
            ['nombre' => 'Medicina General',      'descripcion' => 'Atención médica primaria y preventiva para adultos.'],
            ['nombre' => 'Cardiología',           'descripcion' => 'Diagnóstico y tratamiento de enfermedades del corazón y sistema circulatorio.'],
            ['nombre' => 'Pediatría',             'descripcion' => 'Atención médica integral de niños y adolescentes.'],
            ['nombre' => 'Dermatología',          'descripcion' => 'Estudio y tratamiento de la piel, cabello y uñas.'],
            ['nombre' => 'Neurología',            'descripcion' => 'Trastornos del sistema nervioso central y periférico.'],
            ['nombre' => 'Traumatología',         'descripcion' => 'Lesiones del aparato locomotor: huesos, músculos y articulaciones.'],
            ['nombre' => 'Ginecología y Obstetricia', 'descripcion' => 'Salud reproductiva femenina y atención del embarazo.'],
            ['nombre' => 'Oftalmología',          'descripcion' => 'Diagnóstico y tratamiento de enfermedades oculares.'],
            ['nombre' => 'Otorrinolaringología',  'descripcion' => 'Trastornos de oído, nariz y garganta.'],
            ['nombre' => 'Endocrinología',        'descripcion' => 'Trastornos hormonales y metabólicos (diabetes, tiroides).'],
            ['nombre' => 'Gastroenterología',     'descripcion' => 'Enfermedades del aparato digestivo.'],
            ['nombre' => 'Urología',              'descripcion' => 'Enfermedades del aparato urinario y sistema reproductor masculino.'],
            ['nombre' => 'Psiquiatría',           'descripcion' => 'Diagnóstico y tratamiento de trastornos mentales.'],
            ['nombre' => 'Neumología',            'descripcion' => 'Enfermedades del aparato respiratorio.'],
            ['nombre' => 'Nefrología',            'descripcion' => 'Estudio y tratamiento de las enfermedades renales.'],
        ];

        foreach ($especialidades as $esp) {
            Especialidad::updateOrCreate(
                ['nombre' => $esp['nombre']],
                $esp
            );
        }

        $this->command->info('✅ ' . count($especialidades) . ' especialidades cargadas.');
    }
}