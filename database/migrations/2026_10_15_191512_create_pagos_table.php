<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('caja_id')
                  ->constrained('cajas')
                  ->restrictOnDelete();

            $table->foreignId('cita_id')
                  ->constrained('citas')
                  ->cascadeOnDelete();

            $table->foreignId('paciente_id')
                  ->constrained('pacientes')
                  ->cascadeOnDelete();

            // ============================================================
            // Monto del pago en su moneda original
            // ============================================================
            $table->decimal('monto', 14, 2);

            // ============================================================
            // Snapshot del momento del pago
            // ============================================================
            $table->foreignId('tasa_id')
                  ->constrained('tasas')
                  ->restrictOnDelete();

            $table->foreignId('cambio_id')
                  ->constrained('cambios')
                  ->restrictOnDelete();

            $table->decimal('tasa_momento', 12, 4);
            $table->enum('moneda', ['dolar', 'euro', 'bs']);

            // ============================================================
            // Forma de pago
            // ============================================================
            $table->enum('forma_pago', [
                'EFECTIVO',
                'TRANSFERENCIA',
                'PAGO_MOVIL',
                'TARJETA_DEBITO',
                'TARJETA_CREDITO',
                'ZELLE',
                'BINANCE',
                'OTRO',
            ]);

            // ============================================================
            // Detalles opcionales según forma de pago
            // ============================================================
            $table->string('banco', 100)->nullable();
            $table->string('referencia', 50)->nullable();
            $table->text('notas')->nullable();

            // Auditoría
            $table->foreignId('registrado_por')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();

            $table->index(['caja_id', 'created_at']);
            $table->index(['cita_id']);
            $table->index(['forma_pago']);
            $table->index(['moneda']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
    }
};