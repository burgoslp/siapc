<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('especialidades_medicos', function (Blueprint $table) {
            $table->foreignId('especialidad_id')
                  ->constrained('especialidades')
                  ->cascadeOnDelete();

            $table->foreignId('medico_id')
                  ->constrained('medicos')
                  ->cascadeOnDelete();

            $table->timestamps();

            // PK compuesta: evita duplicados
            $table->primary(['especialidad_id', 'medico_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('especialidades_medicos');
    }
};