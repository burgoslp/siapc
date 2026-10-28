<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            // Para saber si es un gasto variable o el pago de uno fijo
            $table->enum('tipo_gasto', ['VARIABLE', 'FIJO'])
                  ->default('VARIABLE')
                  ->after('id');

            // Relación con la tabla de recurrencias (si es FIJO)
            $table->foreignId('recurrencia_id')
                  ->nullable()
                  ->after('tipo_gasto')
                  ->constrained('recurrencias')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('gastos', function (Blueprint $table) {
            $table->dropForeign(['recurrencia_id']);
            $table->dropColumn(['tipo_gasto', 'recurrencia_id']);
        });
    }
};