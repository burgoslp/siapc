<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agenda_tarifas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('agenda_compartida_id')->constrained('agendas_compartidas')->cascadeOnDelete();
            $table->foreignId('tarifa_id')->constrained('tarifas')->cascadeOnDelete();
            
            $table->timestamps();
            
            $table->unique(['agenda_compartida_id', 'tarifa_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agenda_tarifas');
    }
};