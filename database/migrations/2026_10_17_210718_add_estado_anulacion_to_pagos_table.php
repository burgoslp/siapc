<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->enum('estado', ['ACTIVO', 'ANULADO'])
                  ->default('ACTIVO')
                  ->after('forma_pago');

            $table->text('motivo_anulacion')->nullable()->after('notas');

            $table->foreignId('anulado_por')
                  ->nullable()
                  ->after('motivo_anulacion')
                  ->constrained('users')
                  ->nullOnDelete();

            $table->dateTime('anulado_en')->nullable()->after('anulado_por');

            $table->index('estado');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['anulado_por']);
            $table->dropColumn([
                'estado',
                'motivo_anulacion',
                'anulado_por',
                'anulado_en',
            ]);
        });
    }
};