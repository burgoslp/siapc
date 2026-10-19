<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_entre_cuentas', function (Blueprint $table) {
            $table->id();

            // Tipo de movimiento
            $table->enum('tipo', ['RETIRO', 'DEPOSITO', 'TRANSFERENCIA']);

            // Cuentas involucradas
            $table->foreignId('cuenta_origen_id')
                  ->constrained('cuentas')
                  ->restrictOnDelete();

            $table->foreignId('cuenta_destino_id')
                  ->constrained('cuentas')
                  ->restrictOnDelete();

            // Monto origen
            $table->decimal('monto_origen', 16, 2);
            $table->enum('moneda_origen', ['dolar', 'euro', 'bs']);

            // Monto destino
            $table->decimal('monto_destino', 16, 2);
            $table->enum('moneda_destino', ['dolar', 'euro', 'bs']);

            // Tasa (solo si cambio de moneda)
            $table->boolean('hay_cambio_moneda')->default(false);
            $table->foreignId('tasa_id')
                  ->nullable()
                  ->constrained('tasas')
                  ->nullOnDelete();
            $table->foreignId('cambio_id')
                  ->nullable()
                  ->constrained('cambios')
                  ->nullOnDelete();
            $table->decimal('tasa_momento', 12, 4)->nullable();
            $table->boolean('tasa_personalizada')->default(false);

            // Comisiones (snapshot)
            $table->decimal('comision_origen_porcentaje', 5, 2)->default(0);
            $table->decimal('comision_origen_monto_fijo', 10, 2)->default(0);
            $table->decimal('comision_origen_monto', 16, 2)->default(0);

            $table->decimal('comision_destino_porcentaje', 5, 2)->default(0);
            $table->decimal('comision_destino_monto_fijo', 10, 2)->default(0);
            $table->decimal('comision_destino_monto', 16, 2)->default(0);

            // Montos netos
            $table->decimal('debito_total', 16, 2);
            $table->decimal('credito_total', 16, 2);

            // Datos opcionales
            $table->string('referencia', 100)->nullable();
            $table->text('notas')->nullable();

            // Auditoría
            $table->foreignId('registrado_por')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->timestamps();

            // Índices
            $table->index(['cuenta_origen_id', 'created_at']);
            $table->index(['cuenta_destino_id', 'created_at']);
            $table->index('tipo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_entre_cuentas');
    }
};