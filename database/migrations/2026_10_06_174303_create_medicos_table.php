<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medicos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('nombre');
            $table->string('apellido');
            $table->enum('lci', ['V', 'E', 'J', 'P'])->default('V');
            $table->string('ci', 20)->unique();
            $table->string('licencia_medica', 50)->unique();
            $table->string('telefono', 30)->nullable();
            $table->string('email')->nullable();
            $table->enum('estado', ['ACTIVO', 'INACTIVO', 'LICENCIA'])->default('ACTIVO');
            $table->timestamps();

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medicos');
    }
};