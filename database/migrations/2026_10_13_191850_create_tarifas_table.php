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
        Schema::create('tarifas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->enum('tipo', ['CONSULTA', 'PROCEDIMIENTO', 'EXAMEN', 'URGENCIA','SEGUIMIENTO'])
                  ->default('CONSULTA');
            $table->foreignId('especialidad_id')
                  ->nullable()
                  ->constrained('especialidades')
                  ->nullOnDelete();
            $table->decimal('precio_usd', 10, 2);
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index(['especialidad_id', 'tipo', 'activo']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tarifas');
    }
};
