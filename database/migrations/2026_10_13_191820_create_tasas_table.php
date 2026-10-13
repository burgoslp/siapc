<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tasas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 50)->unique();
            $table->string('etiqueta', 100);
            $table->text('descripcion')->nullable();
            $table->boolean('predeterminado')->default(false);
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->index(['predeterminado', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tasas');
    }
};