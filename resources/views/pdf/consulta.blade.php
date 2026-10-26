<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Receta Médica - {{ $consulta->paciente->nombre }}</title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 13px;
            color: #333;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }
        .header {
            width: 100%;
            border-bottom: 2px solid #0f766e; /* teal-700 */
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header td {
            vertical-align: middle;
        }
        .logo {
            max-width: 120px;
            max-height: 80px;
        }
        .clinica-info {
            text-align: right;
            font-size: 11px;
            color: #475569;
        }
        .clinica-nombre {
            font-size: 18px;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            margin-bottom: 3px;
        }
        .paciente-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 15px;
            margin-bottom: 20px;
            width: 100%;
        }
        .paciente-box td {
            padding: 3px 0;
            font-size: 12px;
        }
        .seccion-titulo {
            font-size: 12px;
            font-weight: bold;
            color: #0f766e;
            text-transform: uppercase;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 3px;
            margin-top: 20px;
            margin-bottom: 8px;
        }
        .contenido {
            font-size: 13px;
            text-align: justify;
            min-height: 40px;
        }
        .reposo-box {
            background-color: #fffbeb;
            border-left: 4px solid #f59e0b;
            padding: 10px;
            margin-top: 15px;
            font-size: 12px;
        }
        .firma-container {
            margin-top: 60px;
            text-align: center;
            width: 250px;
            float: right;
        }
        .firma-linea {
            border-top: 1px solid #333;
            margin-bottom: 5px;
        }
        .firma-texto {
            font-size: 11px;
            color: #475569;
        }
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }
    </style>
</head>
<body>

    <!-- Encabezado -->
    <table class="header">
        <tr>
            <td width="30%">
                @if($logoPath)
                    <img src="{{ $logoPath }}" class="logo" alt="Logo">
                @else
                    <div class="clinica-nombre">{{ $empresa['nombre_clinica'] ?? 'CLÍNICA' }}</div>
                @endif
            </td>
            <td width="70%" class="clinica-info">
                <div class="clinica-nombre">{{ $empresa['nombre_clinica'] ?? 'Consultorio Médico' }}</div>
                @if(!empty($empresa['rif'])) <div>RIF: {{ $empresa['rif'] }}</div> @endif
                @if(!empty($empresa['direccion'])) <div>{{ $empresa['direccion'] }}</div> @endif
                <div>
                    @if(!empty($empresa['telefono_1'])) Tel: {{ $empresa['telefono_1'] }} @endif
                    @if(!empty($empresa['telefono_2'])) / {{ $empresa['telefono_2'] }} @endif
                    @if(!empty($empresa['whatsapp'])) | WhatsApp: {{ $empresa['whatsapp'] }} @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- Datos del Paciente -->
    <table class="paciente-box">
        <tr>
            <td width="50%"><strong>Paciente:</strong> {{ $consulta->paciente->nombre }} {{ $consulta->paciente->apellido }}</td>
            <td width="50%"><strong>Fecha:</strong> {{ $consulta->created_at->format('d/m/Y h:i A') }}</td>
        </tr>
        <tr>
            <td><strong>Cédula:</strong> {{ $consulta->paciente->lci }}-{{ $consulta->paciente->ci }}</td>
            <td><strong>Edad:</strong> {{ $consulta->paciente->fecha_nacimiento ? $consulta->paciente->fecha_nacimiento->age . ' años' : 'N/A' }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Motivo:</strong> {{ $consulta->motivo ?: 'No registrado' }}</td>
        </tr>
    </table>

    <!-- Signos Vitales (Breve resumen) -->
    @if($consulta->peso || $consulta->tension_sistolica || $consulta->temperatura)
    <div style="font-size: 11px; color: #475569; margin-bottom: 15px;">
        <strong>Signos Vitales:</strong>
        @if($consulta->peso) Peso: {{ $consulta->peso }} kg | @endif
        @if($consulta->altura) Talla: {{ $consulta->altura }} m | @endif
        @if($consulta->imc) IMC: {{ $consulta->imc }} ({{ $consulta->imc_clasificacion }}) | @endif
        @if($consulta->tension_formateada) TA: {{ $consulta->tension_formateada }} | @endif
        @if($consulta->temperatura) Temp: {{ $consulta->temperatura }}°C | @endif
        @if($consulta->frecuencia_cardiaca) FC: {{ $consulta->frecuencia_cardiaca }} lpm @endif
    </div>
    @endif

    <!-- Cuerpo de la Consulta -->
    @if($consulta->sintomas)
        <div class="seccion-titulo">Síntomas</div>
        <div class="contenido">{!! nl2br(e($consulta->sintomas)) !!}</div>
    @endif

    @if($consulta->diagnostico)
        <div class="seccion-titulo">Diagnóstico</div>
        <div class="contenido">{!! nl2br(e($consulta->diagnostico)) !!}</div>
    @endif

    @if($consulta->tratamiento)
        <div class="seccion-titulo">Tratamiento / Indicaciones</div>
        <div class="contenido">{!! nl2br(e($consulta->tratamiento)) !!}</div>
    @endif

    <!-- Reposo -->
    @if($consulta->reposo === 'SI')
        <div class="reposo-box">
            <strong>REPOSO MÉDICO:</strong> El paciente requiere reposo por {{ $consulta->reposo_dias }} días, 
            desde el {{ $consulta->reposo_fecha_inicio?->format('d/m/Y') }} 
            hasta el {{ $consulta->reposo_fecha_fin?->format('d/m/Y') }}.
        </div>
    @endif

    <!-- Firma -->
    <div class="firma-container">
        {{-- Imagen de la firma si existe --}}
        @if($consulta->medico->firma_path && Storage::disk('public')->exists($consulta->medico->firma_path))
            <div style="text-align: center; margin-bottom: 5px;">
                <img src="{{ Storage::disk('public')->path($consulta->medico->firma_path) }}" 
                     style="max-height: 60px; max-width: 200px;" 
                     alt="Firma del Médico">
            </div>
        @endif
        
        <div class="firma-linea"></div>
        <div class="firma-texto">
            <strong>Dr(a). {{ $consulta->medico->nombre }} {{ $consulta->medico->apellido }}</strong><br>
            {{ $consulta->medico->licencia_medica ?? '' }}<br>
            {{ $consulta->medico->especialidades->pluck('nombre')->join(', ') }}
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        Documento generado por SIAPC el {{ now()->format('d/m/Y h:i A') }}. 
        @if(!empty($empresa['sitio_web'])) {{ $empresa['sitio_web'] }} @endif
    </div>

</body>
</html>