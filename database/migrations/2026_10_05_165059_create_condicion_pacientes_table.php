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
        Schema::create('condiciones_pacientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_paciente')
                  ->constrained('pacientes')
                  ->cascadeOnDelete();

            $table->foreignId('id_condicion')
                  ->constrained('condiciones')
                  ->cascadeOnDelete();

            $table->dateTime('fecha_diagnostico')->nullable();
            $table->enum('severidad', ['LEVE', 'MODERADA', 'GRAVE', 'SEVERA']);
            $table->enum('estado', ['ACTIVA', 'CONTROLADA', 'EN_REMISION', 'RESUELTA', 'SOSPECHA'])->default('ACTIVA');
            $table->string('diagnostico_por', 100)->nullable();
            $table->string('especialidad', 100)->nullable();
            $table->text('notas')->nullable();
            $table->dateTime('fecha_ultimo_control')->nullable();
            $table->date('proximo_control_sugerido')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condicions_pacientes');
    }
};
