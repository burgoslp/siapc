<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cuenta_id')
                  ->constrained('cuentas')
                  ->cascadeOnDelete();

            $table->enum('forma_pago', [
                'EFECTIVO', 'TRANSFERENCIA', 'PAGO_MOVIL',
                'TARJETA_DEBITO', 'TARJETA_CREDITO',
                'ZELLE', 'BINANCE', 'OTRO',
            ]);

            // Comisión de INGRESO (recibir dinero)
            $table->decimal('porcentaje_ingreso', 5, 2)->default(0);
            $table->decimal('monto_fijo_ingreso', 10, 2)->default(0);

            // Comisión de EGRESO (enviar dinero)
            $table->decimal('porcentaje_egreso', 5, 2)->default(0);
            $table->decimal('monto_fijo_egreso', 10, 2)->default(0);

            $table->boolean('activo')->default(true);

            $table->timestamps();

            $table->unique(['cuenta_id', 'forma_pago']);
            $table->index(['cuenta_id', 'activo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('comisiones');
    }
};