<?php

namespace Database\Seeders;

use App\Models\Alergeno;
use Illuminate\Database\Seeder;

class AlergenoSeeder extends Seeder
{
    public function run(): void
    {
        $alergenos = [
            // MEDICAMENTOS
            ['nombre' => 'Penicilina', 'categoria' => 'Medicamento', 'descripcion' => 'Antibiótico betalactámico. Alergia común y potencialmente grave.'],
            ['nombre' => 'Amoxicilina', 'categoria' => 'Medicamento', 'descripcion' => 'Antibiótico derivado de la penicilina.'],
            ['nombre' => 'Sulfonamidas', 'categoria' => 'Medicamento', 'descripcion' => 'Familia de antibióticos que incluye sulfametoxazol.'],
            ['nombre' => 'Aspirina (AAS)', 'categoria' => 'Medicamento', 'descripcion' => 'Ácido acetilsalicílico. Puede causar broncoespasmo.'],
            ['nombre' => 'Ibuprofeno', 'categoria' => 'Medicamento', 'descripcion' => 'Antiinflamatorio no esteroideo (AINE).'],
            ['nombre' => 'Dipirona / Metamizol', 'categoria' => 'Medicamento', 'descripcion' => 'Analgésico y antipirético.'],
            ['nombre' => 'Codeína', 'categoria' => 'Medicamento', 'descripcion' => 'Analgésico opioide.'],
            ['nombre' => 'Morfina', 'categoria' => 'Medicamento', 'descripcion' => 'Analgésico opioide potente.'],
            ['nombre' => 'Yodo / Contraste yodado', 'categoria' => 'Medicamento', 'descripcion' => 'Usado en estudios de imagen. Reacción puede ser grave.'],
            ['nombre' => 'Vacuna antitetánica', 'categoria' => 'Medicamento', 'descripcion' => 'Puede contener trazas de componentes alérgenos.'],

            // ALIMENTOS
            ['nombre' => 'Leche de vaca', 'categoria' => 'Alimento', 'descripcion' => 'Proteína láctea. Alergia común en niños.'],
            ['nombre' => 'Huevo', 'categoria' => 'Alimento', 'descripcion' => 'Clara y yema pueden causar reacción.'],
            ['nombre' => 'Maní / Cacahuate', 'categoria' => 'Alimento', 'descripcion' => 'Alergia frecuentemente severa.'],
            ['nombre' => 'Frutos secos', 'categoria' => 'Alimento', 'descripcion' => 'Nueces, almendras, avellanas, pistachos, etc.'],
            ['nombre' => 'Mariscos', 'categoria' => 'Alimento', 'descripcion' => 'Camarones, cangrejos, langostas.'],
            ['nombre' => 'Pescado', 'categoria' => 'Alimento', 'descripcion' => 'Atún, salmón, merluza, etc.'],
            ['nombre' => 'Trigo / Gluten', 'categoria' => 'Alimento', 'descripcion' => 'Proteínas del trigo y cereales similares.'],
            ['nombre' => 'Soja', 'categoria' => 'Alimento', 'descripcion' => 'Proteína de la soya.'],
            ['nombre' => 'Sésamo', 'categoria' => 'Alimento', 'descripcion' => 'Semillas de ajonjolí.'],
            ['nombre' => 'Fresa', 'categoria' => 'Alimento', 'descripcion' => 'Fruta con potencial alergénico.'],

            // AMBIENTALES
            ['nombre' => 'Polen de gramíneas', 'categoria' => 'Ambiental', 'descripcion' => 'Causante común de rinitis alérgica estacional.'],
            ['nombre' => 'Polen de árboles', 'categoria' => 'Ambiental', 'descripcion' => 'Abedul, olivo, ciprés, etc.'],
            ['nombre' => 'Ácaros del polvo', 'categoria' => 'Ambiental', 'descripcion' => 'Dermatophagoides. Alergia respiratoria común.'],
            ['nombre' => 'Mohos / Hongos', 'categoria' => 'Ambiental', 'descripcion' => 'Aspergillus, Alternaria, Cladosporium, etc.'],
            ['nombre' => 'Caspa de animales', 'categoria' => 'Ambiental', 'descripcion' => 'Perros, gatos, caballos, roedores.'],
            ['nombre' => 'Cucarachas', 'categoria' => 'Ambiental', 'descripcion' => 'Alérgeno doméstico frecuente.'],
            ['nombre' => 'Látex', 'categoria' => 'Ambiental', 'descripcion' => 'Guantes, preservativos, globos. Alergia ocupacional.'],

            // CONTACTO / PIEL
            ['nombre' => 'Níquel', 'categoria' => 'Contacto', 'descripcion' => 'Metal presente en joyería, monedas, botones.'],
            ['nombre' => 'Cromo', 'categoria' => 'Contacto', 'descripcion' => 'Presente en cuero curtido, cemento.'],
            ['nombre' => 'Fragancias', 'categoria' => 'Contacto', 'descripcion' => 'Perfumes, cosméticos, detergentes.'],
            ['nombre' => 'Parabenos', 'categoria' => 'Contacto', 'descripcion' => 'Conservantes en cosméticos y cremas.'],
            ['nombre' => 'Neomicina', 'categoria' => 'Contacto', 'descripcion' => 'Antibiótico tópico.'],

            // INSECTOS
            ['nombre' => 'Picadura de abeja', 'categoria' => 'Insecto', 'descripcion' => 'Puede causar reacción anafiláctica.'],
            ['nombre' => 'Picadura de avispa', 'categoria' => 'Insecto', 'descripcion' => 'Similar a la abeja, potencialmente grave.'],
            ['nombre' => 'Picadura de hormiga', 'categoria' => 'Insecto', 'descripcion' => 'Hormiga roja o de fuego.'],
            ['nombre' => 'Mosquito', 'categoria' => 'Insecto', 'descripcion' => 'Reacción local exagerada a la picadura.'],
        ];

        foreach ($alergenos as $alergeno) {
            Alergeno::updateOrCreate(
                ['nombre' => $alergeno['nombre']],
                $alergeno
            );
        }

        $this->command->info('✅ ' . count($alergenos) . ' alérgenos cargados correctamente.');
    }
}