<!DOCTYPE html>
<html lang="es" class="h-full bg-slate-100">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agendar Cita - SIAPC</title>
    <link rel="icon" type="image/png" href="{{ asset('images/favicon/favicon.png') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full antialiased text-slate-800">
    <div class="min-h-screen py-6 px-4">
        <livewire:agenda-publica :token="request()->route('token')" />
    </div>
    
    @livewireScripts
</body>
</html>