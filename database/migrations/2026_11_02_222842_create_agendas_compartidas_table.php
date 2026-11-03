<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas_compartidas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('medico_id')->constrained('medicos')->cascadeOnDelete();
            $table->foreignId('creado_por')->constrained('users')->cascadeOnDelete(); // Quién lo creó (médico o recepción)
            
            $table->string('token', 100)->unique(); // El código único para la URL pública
            $table->dateTime('fecha_expiracion'); // Cuándo deja de funcionar el enlace
            $table->boolean('activo')->default(true);
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas_compartidas');
    }
};