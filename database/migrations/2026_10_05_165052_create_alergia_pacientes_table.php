<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('alergias_pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')
                  ->constrained('pacientes')
                  ->cascadeOnDelete();
                  
            $table->foreignId('id_alergeno')
                  ->constrained('alergenos')
                  ->cascadeOnDelete();

            $table->enum('tipo_reaccion', [
                'CUTANEA', 'RESPIRATORIA', 'DIGESTIVA', 'ANAFILACTICA', 'OCULAR', 'OTRA'
            ]);
            $table->enum('severidad', ['LEVE', 'MODERADA', 'GRAVE']);
            $table->dateTime('fecha_diagnostico')->nullable();
            $table->text('observaciones')->nullable();
            $table->enum('estado', ['ACTIVA', 'INACTIVA', 'SOSPECHA', 'CONFIRMADA'])->default('ACTIVA');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alergias_pacientes');
    }
};
