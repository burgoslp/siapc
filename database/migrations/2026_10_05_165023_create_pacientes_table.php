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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->date('fecha_nacimiento');
            $table->string('genero', 20)->nullable();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->unique();
            $table->enum('lci', ['V', 'J', 'E', 'P'])->default('V');
            $table->string('ci', 20)->unique();
            $table->string('direccion')->nullable();
            $table->text('antecedentes_familiares')->nullable();
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
