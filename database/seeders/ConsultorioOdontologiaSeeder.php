<?php

namespace Database\Seeders;

use App\Models\Alergeno;
use App\Models\Banco;
use App\Models\Cambio;
use App\Models\Cita;
use App\Models\Comision;
use App\Models\Condicion;
use App\Models\Consulta;
use App\Models\Cuenta;
use App\Models\Especialidad;
use App\Models\Medico;
use App\Models\Paciente;
use App\Models\Role;
use App\Models\Tarifa;
use App\Models\Tasa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ConsultorioOdontologiaSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🦷 Iniciando seeder para consultorio de odontología...');

        // ============================================================
        // 1. ROLES (solo si no existen)
        // ============================================================
        $roles = [
            ['nombre' => 'admin', 'etiqueta' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'medico', 'etiqueta' => 'Odontólogo', 'descripcion' => 'Acceso a sus citas y consultas'],
            ['nombre' => 'recepcion', 'etiqueta' => 'Recepción', 'descripcion' => 'Gestión de citas y pacientes'],
        ];

        foreach ($roles as $rol) {
            Role::updateOrCreate(['nombre' => $rol['nombre']], $rol);
        }

        $this->command->info('✅ Roles listos.');

        // ============================================================
        // 2. ESPECIALIDADES DE ODONTOLOGÍA
        // ============================================================
        $especialidades = [
            ['nombre' => 'Odontología General', 'descripcion' => 'Atención dental integral y preventiva.'],
            ['nombre' => 'Ortodoncia', 'descripcion' => 'Corrección de la posición de los dientes y mandíbula.'],
            ['nombre' => 'Endodoncia', 'descripcion' => 'Tratamiento de conductos radiculares.'],
            ['nombre' => 'Periodoncia', 'descripcion' => 'Tratamiento de encías y tejidos de soporte dental.'],
            ['nombre' => 'Cirugía Oral', 'descripcion' => 'Extracciones y procedimientos quirúrgicos.'],
            ['nombre' => 'Odontopediatría', 'descripcion' => 'Atención dental para niños y adolescentes.'],
            ['nombre' => 'Implantología', 'descripcion' => 'Colocación de implantes dentales.'],
        ];

        foreach ($especialidades as $esp) {
            Especialidad::updateOrCreate(['nombre' => $esp['nombre']], $esp);
        }

        $this->command->info('✅ ' . count($especialidades) . ' especialidades cargadas.');

        // ============================================================
        // 3. TARIFAS DE ODONTOLOGÍA
        // ============================================================
        // [especialidad, tipo, nombre, precio_usd]
        $tarifas = [
            // Odontología General
            ['Odontología General', 'CONSULTA', 'Consulta de Evaluación', 20.00],
            ['Odontología General', 'PROCEDIMIENTO', 'Limpieza Dental', 40.00],
            ['Odontología General', 'PROCEDIMIENTO', 'Resina / Empaste Simple', 50.00],
            ['Odontología General', 'PROCEDIMIENTO', 'Resina / Empaste Múltiple', 80.00],
            ['Odontología General', 'PROCEDIMIENTO', 'Blanqueamiento Dental', 200.00],
            ['Odontología General', 'PROCEDIMIENTO', 'Corona de Porcelana', 300.00],
            ['Odontología General', 'PROCEDIMIENTO', 'Corona Metal-Porcelana', 250.00],
            ['Odontología General', 'SEGUIMIENTO', 'Control Post-Operatorio', 0.00],

            // Endodoncia
            ['Endodoncia', 'PROCEDIMIENTO', 'Endodoncia 1 Conducto', 150.00],
            ['Endodoncia', 'PROCEDIMIENTO', 'Endodoncia 2 Conductos', 200.00],
            ['Endodoncia', 'PROCEDIMIENTO', 'Endodoncia 3+ Conductos', 250.00],
            ['Endodoncia', 'SEGUIMIENTO', 'Control de Endodoncia', 0.00],

            // Cirugía Oral
            ['Cirugía Oral', 'PROCEDIMIENTO', 'Extracción Simple', 60.00],
            ['Cirugía Oral', 'PROCEDIMIENTO', 'Extracción Muela del Juicio', 120.00],
            ['Cirugía Oral', 'PROCEDIMIENTO', 'Extracción Múltiple', 180.00],
            ['Cirugía Oral', 'URGENCIA', 'Urgencia Odontológica', 50.00],

            // Ortodoncia
            ['Ortodoncia', 'PROCEDIMIENTO', 'Instalación de Brackets', 400.00],
            ['Ortodoncia', 'SEGUIMIENTO', 'Control Ortodoncia Mensual', 50.00],
            ['Ortodoncia', 'PROCEDIMIENTO', 'Retiro de Brackets', 100.00],
            ['Ortodoncia', 'PROCEDIMIENTO', 'Ajuste de Brackets', 30.00],

            // Periodoncia
            ['Periodoncia', 'PROCEDIMIENTO', 'Raspado y Alisado Radicular', 100.00],
            ['Periodoncia', 'PROCEDIMIENTO', 'Curetaje por Cuadrante', 80.00],
            ['Periodoncia', 'CONSULTA', 'Consulta Periodontal', 30.00],

            // Odontopediatría
            ['Odontopediatría', 'CONSULTA', 'Consulta Pediátrica', 25.00],
            ['Odontopediatría', 'PROCEDIMIENTO', 'Sellantes', 40.00],
            ['Odontopediatría', 'PROCEDIMIENTO', 'Flúor', 25.00],
            ['Odontopediatría', 'PROCEDIMIENTO', 'Pulpotomía', 80.00],

            // Implantología
            ['Implantología', 'PROCEDIMIENTO', 'Implante Dental Simple', 800.00],
            ['Implantología', 'PROCEDIMIENTO', 'Implante Dental Múltiple', 1500.00],
            ['Implantología', 'PROCEDIMIENTO', 'Prótesis sobre Implante', 500.00],

            // Estudios (sin especialidad)
            [null, 'EXAMEN', 'Radiografía Periapical', 15.00],
            [null, 'EXAMEN', 'Radiografía Panorámica', 30.00],
            [null, 'EXAMEN', 'Tomografía Dental (CBCT)', 100.00],
        ];

        foreach ($tarifas as [$nombreEsp, $tipo, $nombre, $precio]) {
            $especialidadId = null;
            if ($nombreEsp) {
                $especialidadId = Especialidad::where('nombre', $nombreEsp)->first()?->id;
            }

            Tarifa::updateOrCreate(
                ['nombre' => $nombre],
                [
                    'tipo' => $tipo,
                    'especialidad_id' => $especialidadId,
                    'precio_usd' => $precio,
                    'activo' => true,
                ]
            );
        }

        $this->command->info('✅ ' . count($tarifas) . ' tarifas cargadas.');

        // ============================================================
        // 4. CONDICIONES MÉDICAS
        // ============================================================
        $condiciones = [
            ['nombre' => 'Diabetes Mellitus Tipo 2', 'codigo_cie10' => 'E11', 'categoria' => 'METABOLICA'],
            ['nombre' => 'Diabetes Mellitus Tipo 1', 'codigo_cie10' => 'E10', 'categoria' => 'METABOLICA'],
            ['nombre' => 'Hipertensión Arterial', 'codigo_cie10' => 'I10', 'categoria' => 'CARDIOVASCULAR'],
            ['nombre' => 'Cardiopatía Isquémica', 'codigo_cie10' => 'I25', 'categoria' => 'CARDIOVASCULAR'],
            ['nombre' => 'Asma Bronquial', 'codigo_cie10' => 'J45', 'categoria' => 'RESPIRATORIA'],
            ['nombre' => 'EPOC', 'codigo_cie10' => 'J44', 'categoria' => 'RESPIRATORIA'],
            ['nombre' => 'Embarazo', 'codigo_cie10' => 'Z33', 'categoria' => 'OTRA'],
            ['nombre' => 'Coagulopatías', 'codigo_cie10' => 'D68', 'categoria' => 'OTRA'],
            ['nombre' => 'Epilepsia', 'codigo_cie10' => 'G40', 'categoria' => 'NEUROLOGICA'],
            ['nombre' => 'Hipotiroidismo', 'codigo_cie10' => 'E03.9', 'categoria' => 'METABOLICA'],
            ['nombre' => 'Hipertiroidismo', 'codigo_cie10' => 'E05', 'categoria' => 'METABOLICA'],
            ['nombre' => 'VIH / SIDA', 'codigo_cie10' => 'B24', 'categoria' => 'INFECCIOSA'],
            ['nombre' => 'Hepatitis B', 'codigo_cie10' => 'B18.1', 'categoria' => 'INFECCIOSA'],
            ['nombre' => 'Hepatitis C', 'codigo_cie10' => 'B18.2', 'categoria' => 'INFECCIOSA'],
            ['nombre' => 'Osteoporosis', 'codigo_cie10' => 'M81', 'categoria' => 'MUSCULOESQUELETICA'],
            ['nombre' => 'Artritis Reumatoide', 'codigo_cie10' => 'M06', 'categoria' => 'AUTOINMUNE'],
            ['nombre' => 'Lupus Eritematoso Sistémico', 'codigo_cie10' => 'M32', 'categoria' => 'AUTOINMUNE'],
            ['nombre' => 'Depresión Mayor', 'codigo_cie10' => 'F32', 'categoria' => 'PSIQUIATRICA'],
            ['nombre' => 'Trastorno de Ansiedad', 'codigo_cie10' => 'F41.1', 'categoria' => 'PSIQUIATRICA'],
            ['nombre' => 'Anemia', 'codigo_cie10' => 'D50', 'categoria' => 'OTRA'],
            ['nombre' => 'Insuficiencia Renal Crónica', 'codigo_cie10' => 'N18', 'categoria' => 'RENAL'],
        ];

        foreach ($condiciones as $cond) {
            Condicion::updateOrCreate(
                ['nombre' => $cond['nombre']],
                array_merge($cond, [
                    'requiere_seguimiento' => false,
                    'activo' => true,
                ])
            );
        }

        $this->command->info('✅ ' . count($condiciones) . ' condiciones cargadas.');

        // ============================================================
        // 5. ALÉRGENOS
        // ============================================================
        $alergenos = [
            // Medicamentos (muy relevantes en odontología)
            ['nombre' => 'Penicilina', 'categoria' => 'Medicamento', 'descripcion' => 'Antibiótico. Común en odontología.'],
            ['nombre' => 'Amoxicilina', 'categoria' => 'Medicamento', 'descripcion' => 'Antibiótico frecuente en infecciones dentales.'],
            ['nombre' => 'Clindamicina', 'categoria' => 'Medicamento', 'descripcion' => 'Antibiótico alternativo en alérgicos a penicilina.'],
            ['nombre' => 'Ibuprofeno', 'categoria' => 'Medicamento', 'descripcion' => 'AINE usado para dolor dental.'],
            ['nombre' => 'Aspirina (AAS)', 'categoria' => 'Medicamento', 'descripcion' => 'Puede causar broncoespasmo.'],
            ['nombre' => 'Paracetamol', 'categoria' => 'Medicamento', 'descripcion' => 'Analgésico común.'],
            ['nombre' => 'Lidocaína', 'categoria' => 'Medicamento', 'descripcion' => 'Anestésico local usado en odontología.'],
            ['nombre' => 'Mepivacaína', 'categoria' => 'Medicamento', 'descripcion' => 'Anestésico local.'],
            ['nombre' => 'Artacaína', 'categoria' => 'Medicamento', 'descripcion' => 'Anestésico local.'],
            ['nombre' => 'Bupivacaína', 'categoria' => 'Medicamento', 'descripcion' => 'Anestésico local de larga duración.'],
            ['nombre' => 'Codeína', 'categoria' => 'Medicamento', 'descripcion' => 'Analgésico opioide.'],

            // Materiales dentales
            ['nombre' => 'Níquel', 'categoria' => 'Material dental', 'descripcion' => 'Presente en brackets y coronas metálicas.'],
            ['nombre' => 'Cromo', 'categoria' => 'Material dental', 'descripcion' => 'Aleaciones dentales.'],
            ['nombre' => 'Acrílico', 'categoria' => 'Material dental', 'descripcion' => 'Prótesis dentales y resinas.'],
            ['nombre' => 'Resina compuesta', 'categoria' => 'Material dental', 'descripcion' => 'Empastes estéticos.'],
            ['nombre' => 'Látex', 'categoria' => 'Material dental', 'descripcion' => 'Guantes y diques de goma.'],
            ['nombre' => 'Eugenol', 'categoria' => 'Material dental', 'descripcion' => 'Cementos dentales.'],
            ['nombre' => 'Metacrilato', 'categoria' => 'Material dental', 'descripcion' => 'Prótesis y adhesivos.'],

            // Alimentos comunes
            ['nombre' => 'Leche de vaca', 'categoria' => 'Alimento', 'descripcion' => 'Proteína láctea.'],
            ['nombre' => 'Maní / Cacahuate', 'categoria' => 'Alimento', 'descripcion' => 'Alergia frecuentemente severa.'],
            ['nombre' => 'Frutos secos', 'categoria' => 'Alimento', 'descripcion' => 'Nueces, almendras, etc.'],
            ['nombre' => 'Mariscos', 'categoria' => 'Alimento', 'descripcion' => 'Camarones, cangrejos, etc.'],
            ['nombre' => 'Huevo', 'categoria' => 'Alimento', 'descripcion' => 'Clara y yema.'],

            // Ambientales
            ['nombre' => 'Polen', 'categoria' => 'Ambiental', 'descripcion' => 'Rinitis alérgica estacional.'],
            ['nombre' => 'Ácaros del polvo', 'categoria' => 'Ambiental', 'descripcion' => 'Alergia respiratoria.'],
            ['nombre' => 'Mohos', 'categoria' => 'Ambiental', 'descripcion' => 'Hongos ambientales.'],
            ['nombre' => 'Caspa de animales', 'categoria' => 'Ambiental', 'descripcion' => 'Perros, gatos, etc.'],
        ];

        foreach ($alergenos as $alergeno) {
            Alergeno::updateOrCreate(
                ['nombre' => $alergeno['nombre']],
                $alergeno
            );
        }

        $this->command->info('✅ ' . count($alergenos) . ' alérgenos cargados.');

        // ============================================================
        // 6. TASAS DE CAMBIO
        // ============================================================
        $tasas = [
            ['nombre' => 'bcv', 'etiqueta' => 'BCV', 'descripcion' => 'Tasa oficial del Banco Central de Venezuela', 'predeterminado' => true],
            ['nombre' => 'paralelo', 'etiqueta' => 'Paralelo', 'descripcion' => 'Tasa del mercado paralelo', 'predeterminado' => false],
            ['nombre' => 'binance', 'etiqueta' => 'Binance', 'descripcion' => 'Tasa P2P de Binance', 'predeterminado' => false],
        ];

        foreach ($tasas as $tasa) {
            Tasa::updateOrCreate(
                ['nombre' => $tasa['nombre']],
                array_merge($tasa, ['activa' => true])
            );
        }

        // Cambios iniciales (montos de ejemplo)
        $montosIniciales = [
            'bcv' => 36.5000,
            'paralelo' => 42.0000,
            'binance' => 41.5000,
        ];

        foreach ($montosIniciales as $nombreTasa => $monto) {
            $tasa = Tasa::where('nombre', $nombreTasa)->first();
            if ($tasa && !$tasa->cambios()->exists()) {
                Cambio::create([
                    'tasa_id' => $tasa->id,
                    'moneda' => 'dolar',
                    'monto' => $monto,
                ]);
            }
        }

        $this->command->info('✅ 3 tasas cargadas con cambios iniciales.');

        // ============================================================
        // 7. BANCOS
        // ============================================================
        $bancos = [
            ['nombre' => 'Banesco', 'codigo_banco' => '0134'],
            ['nombre' => 'Banco de Venezuela', 'codigo_banco' => '0102'],
            ['nombre' => 'Mercantil', 'codigo_banco' => '0105'],
            ['nombre' => 'BBVA Provincial', 'codigo_banco' => '0108'],
            ['nombre' => 'BNC', 'codigo_banco' => '0191'],
            ['nombre' => 'Bancamiga', 'codigo_banco' => '0172'],
        ];

        foreach ($bancos as $banco) {
            Banco::updateOrCreate(
                ['codigo_banco' => $banco['codigo_banco']],
                array_merge($banco, ['activo' => true])
            );
        }

        $this->command->info('✅ ' . count($bancos) . ' bancos cargados.');

        // ============================================================
        // 8. CUENTAS
        // ============================================================
        $banesco = Banco::where('nombre', 'Banesco')->first();

        $titular = 'Consultorio Dental Sonrisa';
        $lci = 'J';
        $ci = '40999888-7';

        $cuentas = [
            [
                'nombre' => 'Caja Chica Bs',
                'tipo' => 'EFECTIVO',
                'moneda' => 'bs',
                'banco_id' => null,
                'numero_cuenta' => null,
                'es_predeterminada' => true,
                'notas' => 'Efectivo en bolívares para gastos menores.',
            ],
            [
                'nombre' => 'Caja Chica USD',
                'tipo' => 'EFECTIVO',
                'moneda' => 'dolar',
                'banco_id' => null,
                'numero_cuenta' => null,
                'es_predeterminada' => false,
                'notas' => 'Efectivo en dólares.',
            ],
            [
                'nombre' => 'Banesco Principal Bs',
                'tipo' => 'BANCO',
                'moneda' => 'bs',
                'banco_id' => $banesco?->id,
                'numero_cuenta' => '0134-0000-00-0000000000',
                'es_predeterminada' => false,
                'notas' => 'Cuenta principal en bolívares.',
            ],
            [
                'nombre' => 'Banesco USD',
                'tipo' => 'BANCO',
                'moneda' => 'dolar',
                'banco_id' => $banesco?->id,
                'numero_cuenta' => '0134-0000-00-0000000001',
                'es_predeterminada' => false,
                'notas' => 'Cuenta en dólares.',
            ],
            [
                'nombre' => 'Zelle USD',
                'tipo' => 'DIVISA',
                'moneda' => 'dolar',
                'banco_id' => null,
                'numero_cuenta' => null,
                'es_predeterminada' => false,
                'notas' => 'Cuenta virtual para Zelle.',
            ],
            [
                'nombre' => 'Binance USD',
                'tipo' => 'DIVISA',
                'moneda' => 'dolar',
                'banco_id' => null,
                'numero_cuenta' => null,
                'es_predeterminada' => false,
                'notas' => 'Cuenta virtual para Binance.',
            ],
        ];

        foreach ($cuentas as $cuenta) {
            Cuenta::updateOrCreate(
                ['lci' => $lci, 'ci' => $ci, 'nombre' => $cuenta['nombre']],
                array_merge($cuenta, [
                    'lci' => $lci,
                    'ci' => $ci,
                    'titular' => $titular,
                    'saldo_inicial' => 0,
                    'activa' => true,
                ])
            );
        }

        $this->command->info('✅ ' . count($cuentas) . ' cuentas cargadas.');

        // ============================================================
        // 9. COMISIONES
        // ============================================================
        $comisionesPorCuenta = [
            'Caja Chica Bs' => [
                ['EFECTIVO', 0, 0, 0, 0],
            ],
            'Caja Chica USD' => [
                ['EFECTIVO', 0, 0, 0, 0],
            ],
            'Banesco Principal Bs' => [
                ['TARJETA_DEBITO', 2.00, 0, 0, 0],
                ['TARJETA_CREDITO', 3.00, 0, 0, 0],
                ['TRANSFERENCIA', 0.30, 0, 0.50, 0],
                ['PAGO_MOVIL', 0, 0, 0.30, 0],
            ],
            'Banesco USD' => [
                ['ZELLE', 1.00, 0, 0, 0],
                ['TRANSFERENCIA', 0.50, 0, 0.50, 0],
            ],
            'Zelle USD' => [
                ['ZELLE', 1.00, 0, 0, 0],
            ],
            'Binance USD' => [
                ['BINANCE', 0.50, 0, 0.50, 0],
            ],
        ];

        $totalComisiones = 0;

        foreach ($comisionesPorCuenta as $nombreCuenta => $comisiones) {
            $cuenta = Cuenta::where('nombre', $nombreCuenta)->first();
            if (!$cuenta) continue;

            foreach ($comisiones as [$formaPago, $pctIngreso, $fijoIngreso, $pctEgreso, $fijoEgreso]) {
                Comision::updateOrCreate(
                    ['cuenta_id' => $cuenta->id, 'forma_pago' => $formaPago],
                    [
                        'porcentaje_ingreso' => $pctIngreso,
                        'monto_fijo_ingreso' => $fijoIngreso,
                        'porcentaje_egreso' => $pctEgreso,
                        'monto_fijo_egreso' => $fijoEgreso,
                        'activo' => true,
                    ]
                );
                $totalComisiones++;
            }
        }

        $this->command->info("✅ {$totalComisiones} comisiones cargadas.");

        // ============================================================
        // 10. USUARIOS
        // ============================================================
        $rolAdmin = Role::where('nombre', 'admin')->first();
        $rolMedico = Role::where('nombre', 'medico')->first();
        $rolRecepcion = Role::where('nombre', 'recepcion')->first();

        // Admin
        $admin = User::updateOrCreate(
            ['username' => 'admin'],
            [
                'email' => 'admin@dental.test',
                'password' => Hash::make('admin123'),
                'activo' => true,
            ]
        );
        $admin->roles()->sync([$rolAdmin->id]);

        // Recepción
        $recepcion = User::updateOrCreate(
            ['username' => 'recepcion'],
            [
                'email' => 'recepcion@dental.test',
                'password' => Hash::make('recepcion123'),
                'activo' => true,
            ]
        );
        $recepcion->roles()->sync([$rolRecepcion->id]);

        $this->command->info('✅ Usuarios admin y recepción cargados.');

        // ============================================================
        // 11. MEDICOS / ODONTÓLOGOS
        // ============================================================
        $medicos = [
            ['nombre' => 'Carlos', 'apellido' => 'Mendoza', 'lci' => 'V', 'ci' => '12345678', 'licencia' => 'MPPS-20001', 'telefono' => '0414-1234567', 'username' => 'cmendoza', 'especialidades' => ['Odontología General', 'Endodoncia']],
            ['nombre' => 'Ana', 'apellido' => 'Ramírez', 'lci' => 'V', 'ci' => '11223344', 'licencia' => 'MPPS-20002', 'telefono' => '0424-2345678', 'username' => 'aramirez', 'especialidades' => ['Ortodoncia', 'Odontopediatría']],
        ];

        foreach ($medicos as $data) {
            // Crear user
            $user = User::updateOrCreate(
                ['username' => $data['username']],
                [
                    'email' => $data['username'] . '@dental.test',
                    'password' => Hash::make('medico123'),
                    'activo' => true,
                ]
            );
            $user->roles()->sync([$rolMedico->id]);

            // Crear médico
            $medico = Medico::updateOrCreate(
                ['ci' => $data['ci']],
                [
                    'user_id' => $user->id,
                    'nombre' => $data['nombre'],
                    'apellido' => $data['apellido'],
                    'lci' => $data['lci'],
                    'licencia_medica' => $data['licencia'],
                    'telefono' => $data['telefono'],
                    'estado' => 'ACTIVO',
                ]
            );

            // Asignar especialidades
            $ids = Especialidad::whereIn('nombre', $data['especialidades'])->pluck('id');
            $medico->especialidades()->sync($ids);
        }

        $this->command->info('✅ ' . count($medicos) . ' odontólogos cargados.');

        // ============================================================
        // 12. PACIENTES DE PRUEBA
        // ============================================================
        $pacientes = [
            ['María', 'González', 'V', '20001001', '1985-03-15', 'femenino', '0414-1110001', 'maria.gonzalez@example.com'],
            ['José', 'Rodríguez', 'V', '20002002', '1978-07-22', 'masculino', '0424-1110002', 'jose.rodriguez@example.com'],
            ['Ana', 'Martínez', 'V', '20003003', '1992-11-08', 'femenino', '0412-1110003', 'ana.martinez@example.com'],
            ['Carlos', 'Pérez', 'V', '20004004', '1965-01-30', 'masculino', '0416-1110004', 'carlos.perez@example.com'],
            ['Luisa', 'Fernández', 'V', '20005005', '2001-05-12', 'femenino', '0414-1110005', 'luisa.fernandez@example.com'],
            ['Pedro', 'Sánchez', 'V', '20006006', '1970-09-25', 'masculino', '0424-1110006', 'pedro.sanchez@example.com'],
            ['Carmen', 'Ramírez', 'V', '20007007', '1988-12-04', 'femenino', '0412-1110007', 'carmen.ramirez@example.com'],
            ['Luis', 'Torres', 'V', '20008008', '1955-04-18', 'masculino', '0416-1110008', 'luis.torres@example.com'],
            ['Sofía', 'Vargas', 'V', '20009009', '1995-06-27', 'femenino', '0414-1110009', 'sofia.vargas@example.com'],
            ['Diego', 'Herrera', 'V', '20010010', '1982-02-14', 'masculino', '0424-1110010', 'diego.herrera@example.com'],
            ['Valentina', 'Silva', 'V', '20011011', '2003-08-09', 'femenino', '0412-1110011', 'valentina.silva@example.com'],
            ['Andrés', 'Castro', 'V', '20012012', '1975-10-21', 'masculino', '0416-1110012', 'andres.castro@example.com'],
            ['Isabel', 'Rojas', 'V', '20013013', '1990-03-05', 'femenino', '0414-1110013', 'isabel.rojas@example.com'],
            ['Ricardo', 'Mendoza', 'V', '20014014', '1968-07-19', 'masculino', '0424-1110014', 'ricardo.mendoza@example.com'],
            ['Gabriela', 'Ortiz', 'V', '20015015', '1998-11-30', 'femenino', '0412-1110015', 'gabriela.ortiz@example.com'],
            ['Fernando', 'Guerrero', 'V', '20016016', '1980-05-16', 'masculino', '0416-1110016', 'fernando.guerrero@example.com'],
            ['Alejandra', 'Reyes', 'V', '20017017', '1993-09-08', 'femenino', '0414-1110017', 'alejandra.reyes@example.com'],
            ['Miguel', 'Cruz', 'V', '20018018', '1972-01-24', 'masculino', '0424-1110018', 'miguel.cruz@example.com'],
            ['Patricia', 'Morales', 'V', '20019019', '1987-04-11', 'femenino', '0412-1110019', 'patricia.morales@example.com'],
            ['Héctor', 'Gutiérrez', 'V', '20020020', '1960-12-02', 'masculino', '0416-1110020', 'hector.gutierrez@example.com'],
            ['Daniela', 'Chávez', 'V', '20021021', '2000-06-19', 'femenino', '0414-1110021', 'daniela.chavez@example.com'],
            ['Javier', 'Ramos', 'V', '20022022', '1976-10-07', 'masculino', '0424-1110022', 'javier.ramos@example.com'],
            ['Verónica', 'Navarro', 'V', '20023023', '1991-02-28', 'femenino', '0412-1110023', 'veronica.navarro@example.com'],
            ['Raúl', 'Domínguez', 'V', '20024024', '1984-08-03', 'masculino', '0416-1110024', 'raul.dominguez@example.com'],
            ['Natalia', 'Vega', 'V', '20025025', '1997-05-22', 'femenino', '0414-1110025', 'natalia.vega@example.com'],
            ['Alejandro', 'Campos', 'V', '20026026', '1969-11-14', 'masculino', '0424-1110026', 'alejandro.campos@example.com'],
            ['Mariana', 'Salazar', 'V', '20027027', '1994-07-01', 'femenino', '0412-1110027', 'mariana.salazar@example.com'],
            ['Gustavo', 'Fuentes', 'V', '20028028', '1958-03-20', 'masculino', '0416-1110028', 'gustavo.fuentes@example.com'],
            ['Rosa', 'Cabrera', 'V', '20029029', '1986-09-17', 'femenino', '0414-1110029', 'rosa.cabrera@example.com'],
            ['Alberto', 'Peña', 'V', '20030030', '1973-12-11', 'masculino', '0424-1110030', 'alberto.pena@example.com'],
        ];

        foreach ($pacientes as [$nombre, $apellido, $lci, $ci, $fechaNac, $genero, $telefono, $email]) {
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
                    'direccion' => 'Av. Principal, sector Centro',
                    'estado' => 'activo',
                ]
            );
        }

        $this->command->info('✅ ' . count($pacientes) . ' pacientes cargados.');

        // ============================================================
        // RESUMEN FINAL
        // ============================================================
        $this->command->info('');
        $this->command->info('🦷 ============================================');
        $this->command->info('🦷 CONSULTORIO DE ODONTOLOGÍA CARGADO');
        $this->command->info('🦷 ============================================');
        $this->command->info('📊 Datos cargados:');
        $this->command->info('   • ' . count($especialidades) . ' especialidades de odontología');
        $this->command->info('   • ' . count($tarifas) . ' tarifas / procedimientos');
        $this->command->info('   • ' . count($condiciones) . ' condiciones médicas');
        $this->command->info('   • ' . count($alergenos) . ' alérgenos');
        $this->command->info('   • ' . count($bancos) . ' bancos');
        $this->command->info('   • ' . count($cuentas) . ' cuentas');
        $this->command->info('   • ' . count($medicos) . ' odontólogos');
        $this->command->info('   • ' . count($pacientes) . ' pacientes de prueba');
        $this->command->info('');
        $this->command->info('🔑 Credenciales de acceso:');
        $this->command->info('   • Admin:     admin / admin123');
        $this->command->info('   • Recepción: recepcion / recepcion123');
        $this->command->info('   • Odontólogos: cmendoza, aramirez / medico123');
        $this->command->info('');
        $this->command->info('🦷 ============================================');
    }
}