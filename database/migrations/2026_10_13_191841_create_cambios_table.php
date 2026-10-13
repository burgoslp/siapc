<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cambios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tasa_id')
                  ->constrained('tasas')
                  ->cascadeOnDelete();

            $table->enum('moneda', ['dolar', 'euro'])->default('dolar');
            $table->decimal('monto', 12, 4);
            $table->timestamps();

            $table->index(['tasa_id', 'moneda', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cambios');
    }
};