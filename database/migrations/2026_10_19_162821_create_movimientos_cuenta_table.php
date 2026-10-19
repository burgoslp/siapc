<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_cuenta', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cuenta_id')
                  ->constrained('cuentas')
                  ->restrictOnDelete();

            $table->enum('tipo', ['INGRESO', 'EGRESO']);

            $table->enum('origen_tipo', [
                'PAGO',
                'GASTO',
                'RETIRO',
                'DEPOSITO',
                'TRANSFERENCIA',
                'CAMBIO_MONEDA',
                'AJUSTE',
            ]);

            $table->unsignedBigInteger('origen_id')->nullable();

            // Vínculo con movimiento entre cuentas
            $table->foreignId('movimiento_entre_cuenta_id')
                  ->nullable()
                  ->constrained('movimientos_entre_cuentas')
                  ->cascadeOnDelete();

            // Monto en la moneda de la cuenta
            $table->decimal('monto', 16, 2);
            $table->decimal('saldo_despues', 16, 2);

            $table->dateTime('fecha');
            $table->text('descripcion')->nullable();

            $table->timestamps();

            // Índices
            $table->index(['cuenta_id', 'fecha']);
            $table->index(['origen_tipo', 'origen_id']);
            $table->index('movimiento_entre_cuenta_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_cuenta');
    }
};