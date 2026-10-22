<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('empresas', function (Blueprint $table) {
            $table->id();
            $table->string('clave')->unique(); // Ej: 'nombre', 'rif', 'whatsapp'
            $table->text('valor')->nullable(); // El dato real
            $table->string('grupo')->default('general'); // Para agrupar en la vista (general, contacto, redes)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('empresas');
    }
};