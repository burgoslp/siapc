<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 30)->unique();

            $table->foreignId('abierta_por')
                  ->constrained('users')
                  ->restrictOnDelete();
            $table->dateTime('abierta_en');

            // Monto inicial con su moneda
            $table->decimal('monto_inicial', 14, 2)->default(0);
            $table->enum('moneda_inicial', ['dolar', 'euro', 'bs'])->default('bs');

            // Cierre
            $table->foreignId('cerrada_por')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->dateTime('cerrada_en')->nullable();

            // ============================================================
            // Totales al cierre (todos en Bs)
            // ============================================================
            $table->decimal('total_efectivo', 16, 2)->default(0);
            $table->decimal('total_transferencia', 16, 2)->default(0);
            $table->decimal('total_pago_movil', 16, 2)->default(0);
            $table->decimal('total_tarjeta_debito', 16, 2)->default(0);
            $table->decimal('total_tarjeta_credito', 16, 2)->default(0);
            $table->decimal('total_zelle', 16, 2)->default(0);
            $table->decimal('total_binance', 16, 2)->default(0);
            $table->decimal('total_otros', 16, 2)->default(0);

            $table->decimal('total_general', 16, 2)->default(0);

            $table->enum('estado', ['ABIERTA', 'CERRADA'])->default('ABIERTA');
            $table->text('observaciones_cierre')->nullable();

            $table->timestamps();

            $table->index(['estado', 'abierta_en']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cajas');
    }
};