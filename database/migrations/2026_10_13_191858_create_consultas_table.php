<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('consultas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cita_id')
                  ->nullable()
                  ->constrained('citas')
                  ->nullOnDelete();

            $table->foreignId('paciente_id')
                  ->constrained('pacientes')
                  ->cascadeOnDelete();

            $table->foreignId('medico_id')
                  ->constrained('medicos')
                  ->cascadeOnDelete();

            // Signos vitales
            $table->decimal('peso', 5, 2)->nullable();              // kg
            $table->decimal('altura', 4, 2)->nullable();            // metros
            $table->integer('tension_sistolica')->nullable();       // mmHg
            $table->integer('tension_diastolica')->nullable();      // mmHg
            $table->decimal('temperatura', 4, 1)->nullable();       // °C
            $table->integer('frecuencia_cardiaca')->nullable();     // bpm

            // Clínica
            $table->text('motivo')->nullable();
            $table->text('sintomas')->nullable();
            $table->text('diagnostico')->nullable();
            $table->text('tratamiento')->nullable();

            // Reposo
            $table->enum('reposo', ['SI', 'NO'])->default('NO');
            $table->integer('reposo_dias')->nullable();
            $table->date('reposo_fecha_inicio')->nullable();
            $table->date('reposo_fecha_fin')->nullable();

            $table->timestamps();

            $table->index(['paciente_id', 'created_at']);
            $table->index('medico_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('consultas');
    }
};