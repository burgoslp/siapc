<?php

namespace Database\Seeders;

use App\Models\Paciente;
use Illuminate\Database\Seeder;

class PacienteSeeder extends Seeder
{
    public function run(): void
    {
        $pacientes = [
            ['María',     'González',  'V', '20001001', '1985-03-15', 'femenino',  '0414-1110001', 'maria.gonzalez@example.com'],
            ['José',      'Rodríguez', 'V', '20002002', '1978-07-22', 'masculino', '0424-1110002', 'jose.rodriguez@example.com'],
            ['Ana',       'Martínez',  'V', '20003003', '1992-11-08', 'femenino',  '0412-1110003', 'ana.martinez@example.com'],
            ['Carlos',    'Pérez',     'V', '20004004', '1965-01-30', 'masculino', '0416-1110004', 'carlos.perez@example.com'],
            ['Luisa',     'Fernández', 'V', '20005005', '2001-05-12', 'femenino',  '0414-1110005', 'luisa.fernandez@example.com'],
            ['Pedro',     'Sánchez',   'V', '20006006', '1970-09-25', 'masculino', '0424-1110006', 'pedro.sanchez@example.com'],
            ['Carmen',    'Ramírez',   'V', '20007007', '1988-12-04', 'femenino',  '0412-1110007', 'carmen.ramirez@example.com'],
            ['Luis',      'Torres',    'V', '20008008', '1955-04-18', 'masculino', '0416-1110008', 'luis.torres@example.com'],
            ['Sofía',     'Vargas',    'V', '20009009', '1995-06-27', 'femenino',  '0414-1110009', 'sofia.vargas@example.com'],
            ['Diego',     'Herrera',   'V', '20010010', '1982-02-14', 'masculino', '0424-1110010', 'diego.herrera@example.com'],
            ['Valentina', 'Silva',     'V', '20011011', '2003-08-09', 'femenino',  '0412-1110011', 'valentina.silva@example.com'],
            ['Andrés',    'Castro',    'V', '20012012', '1975-10-21', 'masculino', '0416-1110012', 'andres.castro@example.com'],
            ['Isabel',    'Rojas',     'V', '20013013', '1990-03-05', 'femenino',  '0414-1110013', 'isabel.rojas@example.com'],
            ['Ricardo',   'Mendoza',   'V', '20014014', '1968-07-19', 'masculino', '0424-1110014', 'ricardo.mendoza@example.com'],
            ['Gabriela',  'Ortiz',     'V', '20015015', '1998-11-30', 'femenino',  '0412-1110015', 'gabriela.ortiz@example.com'],
            ['Fernando',  'Guerrero',  'V', '20016016', '1980-05-16', 'masculino', '0416-1110016', 'fernando.guerrero@example.com'],
            ['Alejandra', 'Reyes',     'V', '20017017', '1993-09-08', 'femenino',  '0414-1110017', 'alejandra.reyes@example.com'],
            ['Miguel',    'Cruz',      'V', '20018018', '1972-01-24', 'masculino', '0424-1110018', 'miguel.cruz@example.com'],
            ['Patricia',  'Morales',   'V', '20019019', '1987-04-11', 'femenino',  '0412-1110019', 'patricia.morales@example.com'],
            ['Héctor',    'Gutiérrez', 'V', '20020020', '1960-12-02', 'masculino', '0416-1110020', 'hector.gutierrez@example.com'],
            ['Daniela',   'Chávez',    'V', '20021021', '2000-06-19', 'femenino',  '0414-1110021', 'daniela.chavez@example.com'],
            ['Javier',    'Ramos',     'V', '20022022', '1976-10-07', 'masculino', '0424-1110022', 'javier.ramos@example.com'],
            ['Verónica',  'Navarro',   'V', '20023023', '1991-02-28', 'femenino',  '0412-1110023', 'veronica.navarro@example.com'],
            ['Raúl',      'Domínguez', 'V', '20024024', '1984-08-03', 'masculino', '0416-1110024', 'raul.dominguez@example.com'],
            ['Natalia',   'Vega',      'V', '20025025', '1997-05-22', 'femenino',  '0414-1110025', 'natalia.vega@example.com'],
            ['Alejandro', 'Campos',    'V', '20026026', '1969-11-14', 'masculino', '0424-1110026', 'alejandro.campos@example.com'],
            ['Mariana',   'Salazar',   'V', '20027027', '1994-07-01', 'femenino',  '0412-1110027', 'mariana.salazar@example.com'],
            ['Gustavo',   'Fuentes',   'V', '20028028', '1958-03-20', 'masculino', '0416-1110028', 'gustavo.fuentes@example.com'],
            ['Rosa',      'Cabrera',   'V', '20029029', '1986-09-17', 'femenino',  '0414-1110029', 'rosa.cabrera@example.com'],
            ['Alberto',   'Peña',      'V', '20030030', '1973-12-11', 'masculino', '0424-1110030', 'alberto.pena@example.com'],
        ];

        foreach ($pacientes as $data) {
            [$nombre, $apellido, $lci, $ci, $fechaNac, $genero, $telefono, $email] = $data;

            Paciente::updateOrCreate(
                ['ci' => $ci],
                [
                    'nombre' => $nombre,
                    'apellido' => $apellido,
                    'lci' => $lci,
                    'fecha_nacimiento' => $fechaNac,
                    'genero' => $genero,
                    'telefono' => $telefono,
                    'email' => $email,
                    'direccion' => 'Av. Principal, sector Centro, ClíniCare',
                    'antecedentes_familiares' => fake()->randomElement([
                        null,
                        'Padre con hipertensión',
                        'Madre con diabetes tipo 2',
                        'Hermano con asma',
                        'Abuelo con cardiopatía',
                        'Sin antecedentes relevantes',
                    ]),
                    'estado' => 'activo',
                ]
            );
        }

        $this->command->info('✅ ' . count($pacientes) . ' pacientes cargados.');
    }
}