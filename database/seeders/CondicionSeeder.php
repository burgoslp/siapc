<?php

namespace Database\Seeders;

use App\Models\Condicion;
use Illuminate\Database\Seeder;

class CondicionSeeder extends Seeder
{
    public function run(): void
    {
        $condiciones = [
            // METABÓLICAS
            ['nombre' => 'Diabetes Mellitus Tipo 2', 'codigo_cie10' => 'E11', 'categoria' => 'METABOLICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Trastorno metabólico caracterizado por hiperglucemia crónica.'],
            ['nombre' => 'Diabetes Mellitus Tipo 1', 'codigo_cie10' => 'E10', 'categoria' => 'METABOLICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 60, 'descripcion' => 'Diabetes autoinmune dependiente de insulina.'],
            ['nombre' => 'Hipercolesterolemia', 'codigo_cie10' => 'E78.0', 'categoria' => 'METABOLICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 180, 'descripcion' => 'Niveles elevados de colesterol en sangre.'],
            ['nombre' => 'Hipotiroidismo', 'codigo_cie10' => 'E03.9', 'categoria' => 'METABOLICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Deficiencia en la producción de hormonas tiroideas.'],
            ['nombre' => 'Obesidad', 'codigo_cie10' => 'E66', 'categoria' => 'METABOLICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 60, 'descripcion' => 'Acumulación excesiva de grasa corporal.'],

            // CARDIOVASCULARES
            ['nombre' => 'Hipertensión Arterial', 'codigo_cie10' => 'I10', 'categoria' => 'CARDIOVASCULAR', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 60, 'descripcion' => 'Presión arterial elevada de forma crónica.'],
            ['nombre' => 'Insuficiencia Cardíaca', 'codigo_cie10' => 'I50', 'categoria' => 'CARDIOVASCULAR', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Incapacidad del corazón para bombear sangre adecuadamente.'],
            ['nombre' => 'Cardiopatía Isquémica', 'codigo_cie10' => 'I25', 'categoria' => 'CARDIOVASCULAR', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Enfermedad coronaria por obstrucción de arterias.'],
            ['nombre' => 'Arritmia Cardíaca', 'codigo_cie10' => 'I49', 'categoria' => 'CARDIOVASCULAR', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Alteración del ritmo cardíaco normal.'],

            // RESPIRATORIAS
            ['nombre' => 'Asma Bronquial', 'codigo_cie10' => 'J45', 'categoria' => 'RESPIRATORIA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Enfermedad inflamatoria crónica de las vías respiratorias.'],
            ['nombre' => 'EPOC', 'codigo_cie10' => 'J44', 'categoria' => 'RESPIRATORIA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Enfermedad Pulmonar Obstructiva Crónica.'],
            ['nombre' => 'Neumonía', 'codigo_cie10' => 'J18', 'categoria' => 'RESPIRATORIA', 'requiere_seguimiento' => false, 'frecuencia_seguimiento_dias' => null, 'descripcion' => 'Infección del tracto respiratorio inferior.'],
            ['nombre' => 'Rinitis Alérgica', 'codigo_cie10' => 'J30', 'categoria' => 'RESPIRATORIA', 'requiere_seguimiento' => false, 'frecuencia_seguimiento_dias' => null, 'descripcion' => 'Inflamación de la mucosa nasal por alergias.'],

            // DIGESTIVAS
            ['nombre' => 'Gastritis Crónica', 'codigo_cie10' => 'K29.5', 'categoria' => 'DIGESTIVA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 180, 'descripcion' => 'Inflamación crónica de la mucosa gástrica.'],
            ['nombre' => 'Reflujo Gastroesofágico', 'codigo_cie10' => 'K21', 'categoria' => 'DIGESTIVA', 'requiere_seguimiento' => false, 'frecuencia_seguimiento_dias' => null, 'descripcion' => 'Retorno del contenido gástrico al esófago.'],
            ['nombre' => 'Síndrome de Intestino Irritable', 'codigo_cie10' => 'K58', 'categoria' => 'DIGESTIVA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Trastorno funcional intestinal crónico.'],
            ['nombre' => 'Úlcera Péptica', 'codigo_cie10' => 'K27', 'categoria' => 'DIGESTIVA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 60, 'descripcion' => 'Lesión en la mucosa del estómago o duodeno.'],
            ['nombre' => 'Colelitiasis', 'codigo_cie10' => 'K80', 'categoria' => 'DIGESTIVA', 'requiere_seguimiento' => false, 'frecuencia_seguimiento_dias' => null, 'descripcion' => 'Presencia de cálculos en la vesícula biliar.'],

            // NEUROLÓGICAS
            ['nombre' => 'Migraña', 'codigo_cie10' => 'G43', 'categoria' => 'NEUROLOGICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Cefalea recurrente de intensidad moderada a severa.'],
            ['nombre' => 'Epilepsia', 'codigo_cie10' => 'G40', 'categoria' => 'NEUROLOGICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Trastorno neurológico con crisis convulsivas.'],
            ['nombre' => 'Enfermedad de Parkinson', 'codigo_cie10' => 'G20', 'categoria' => 'NEUROLOGICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 60, 'descripcion' => 'Trastorno neurodegenerativo del movimiento.'],
            ['nombre' => 'Alzheimer', 'codigo_cie10' => 'G30', 'categoria' => 'NEUROLOGICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Demencia neurodegenerativa progresiva.'],

            // MUSCULOESQUELÉTICAS
            ['nombre' => 'Artritis Reumatoide', 'codigo_cie10' => 'M06', 'categoria' => 'AUTOINMUNE', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 60, 'descripcion' => 'Enfermedad inflamatoria autoinmune de las articulaciones.'],
            ['nombre' => 'Osteoartritis', 'codigo_cie10' => 'M19', 'categoria' => 'MUSCULOESQUELETICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Degeneración del cartílago articular.'],
            ['nombre' => 'Osteoporosis', 'codigo_cie10' => 'M81', 'categoria' => 'MUSCULOESQUELETICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 180, 'descripcion' => 'Pérdida de densidad ósea.'],
            ['nombre' => 'Lumbalgia Crónica', 'codigo_cie10' => 'M54.5', 'categoria' => 'MUSCULOESQUELETICA', 'requiere_seguimiento' => false, 'frecuencia_seguimiento_dias' => null, 'descripcion' => 'Dolor lumbar persistente.'],

            // RENALES
            ['nombre' => 'Insuficiencia Renal Crónica', 'codigo_cie10' => 'N18', 'categoria' => 'RENAL', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Pérdida progresiva de la función renal.'],
            ['nombre' => 'Cálculos Renales', 'codigo_cie10' => 'N20', 'categoria' => 'RENAL', 'requiere_seguimiento' => false, 'frecuencia_seguimiento_dias' => null, 'descripcion' => 'Litiasis renal (piedras en el riñón).'],
            ['nombre' => 'Infección Urinaria Recurrente', 'codigo_cie10' => 'N39.0', 'categoria' => 'RENAL', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Infecciones urinarias de repetición.'],

            // AUTOINMUNES
            ['nombre' => 'Lupus Eritematoso Sistémico', 'codigo_cie10' => 'M32', 'categoria' => 'AUTOINMUNE', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Enfermedad autoinmune sistémica.'],
            ['nombre' => 'Tiroiditis de Hashimoto', 'codigo_cie10' => 'E06.3', 'categoria' => 'AUTOINMUNE', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Inflamación autoinmune de la tiroides.'],

            // ONCOLÓGICAS
            ['nombre' => 'Cáncer de Mama', 'codigo_cie10' => 'C50', 'categoria' => 'ONCOLOGICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Neoplasia maligna de mama.'],
            ['nombre' => 'Cáncer de Próstata', 'codigo_cie10' => 'C61', 'categoria' => 'ONCOLOGICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Neoplasia maligna de próstata.'],
            ['nombre' => 'Cáncer de Pulmón', 'codigo_cie10' => 'C34', 'categoria' => 'ONCOLOGICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Neoplasia maligna del pulmón.'],

            // INFECCIOSAS
            ['nombre' => 'VIH / SIDA', 'codigo_cie10' => 'B24', 'categoria' => 'INFECCIOSA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Inmunodeficiencia humana adquirida.'],
            ['nombre' => 'Hepatitis B Crónica', 'codigo_cie10' => 'B18.1', 'categoria' => 'INFECCIOSA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Infección crónica por virus de hepatitis B.'],
            ['nombre' => 'Tuberculosis Pulmonar', 'codigo_cie10' => 'A15', 'categoria' => 'INFECCIOSA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Infección por Mycobacterium tuberculosis.'],

            // PSIQUIÁTRICAS
            ['nombre' => 'Depresión Mayor', 'codigo_cie10' => 'F32', 'categoria' => 'PSIQUIATRICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Trastorno depresivo mayor.'],
            ['nombre' => 'Trastorno de Ansiedad Generalizada', 'codigo_cie10' => 'F41.1', 'categoria' => 'PSIQUIATRICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Ansiedad crónica y excesiva.'],
            ['nombre' => 'Trastorno Bipolar', 'codigo_cie10' => 'F31', 'categoria' => 'PSIQUIATRICA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 30, 'descripcion' => 'Alternancia entre episodios maníacos y depresivos.'],

            // OTRAS
            ['nombre' => 'Anemia Ferropénica', 'codigo_cie10' => 'D50', 'categoria' => 'OTRA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Deficiencia de hierro que causa anemia.'],
            ['nombre' => 'Dermatitis Atópica', 'codigo_cie10' => 'L20', 'categoria' => 'OTRA', 'requiere_seguimiento' => false, 'frecuencia_seguimiento_dias' => null, 'descripcion' => 'Inflamación crónica de la piel.'],
            ['nombre' => 'Psoriasis', 'codigo_cie10' => 'L40', 'categoria' => 'OTRA', 'requiere_seguimiento' => true, 'frecuencia_seguimiento_dias' => 90, 'descripcion' => 'Enfermedad inflamatoria crónica de la piel.'],
        ];

        foreach ($condiciones as $condicion) {
            Condicion::updateOrCreate(
                ['codigo_cie10' => $condicion['codigo_cie10']],
                $condicion
            );
        }

        $this->command->info('✅ ' . count($condiciones) . ' condiciones cargadas correctamente.');
    }
}