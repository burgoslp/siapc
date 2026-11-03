<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('disponibilidades', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_compartida_id')->constrained('agendas_compartidas')->cascadeOnDelete();
            
            $table->date('fecha'); // Ej: 2026-11-09
            $table->time('hora_inicio'); // Ej: 08:00:00
            $table->time('hora_fin'); // Ej: 12:00:00
            
            $table->timestamps();
            
            // Índice para búsquedas rápidas por fecha
            $table->index(['fecha', 'agenda_compartida_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disponibilidades');
    }
};