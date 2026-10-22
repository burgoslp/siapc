<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; color: #333; line-height: 1.6; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #0f766e; color: white; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { background-color: #f8fafc; padding: 20px; border: 1px solid #e2e8f0; border-top: none; border-radius: 0 0 8px 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>{{ $empresa['nombre_clinica'] ?? 'Clínica Dental' }}</h2>
        </div>
        <div class="content">
            <p>Hola <strong>{{ $consulta->paciente->nombre }} {{ $consulta->paciente->apellido }}</strong>,</p>
            
            <p>Adjunto a este correo encontrarás tu receta médica y las indicaciones de tu consulta del día <strong>{{ $consulta->created_at->format('d/m/Y') }}</strong>.</p>
            
            <p>Por favor, sigue las indicaciones dadas por el Dr(a). <strong>{{ $consulta->medico->nombre }} {{ $consulta->medico->apellido }}</strong>.</p>
            
            <p>Si tienes alguna duda, no dudes en contactarnos al WhatsApp: <strong>{{ $empresa['whatsapp'] ?? '' }}</strong></p>

            <br>
            <p>¡Que tengas un excelente día!</p>
            <p><em>Atentamente,<br>El equipo de {{ $empresa['nombre_clinica'] ?? 'la Clínica' }}</em></p>
        </div>
    </div>
</body>
</html>