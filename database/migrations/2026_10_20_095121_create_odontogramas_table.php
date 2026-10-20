<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('odontogramas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('paciente_id')
                  ->constrained('pacientes')
                  ->cascadeOnDelete();

            $table->foreignId('consulta_id')
                  ->nullable()
                  ->constrained('consultas')
                  ->nullOnDelete();

            // JSON con el estado completo del odontograma
            $table->json('datos');

            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index('paciente_id');
            $table->index('consulta_id');
            $table->index(['paciente_id', 'consulta_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('odontogramas');
    }
};