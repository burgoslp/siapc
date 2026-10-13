<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('citas', function (Blueprint $table) {
            $table->id();

            // Relaciones principales
            $table->foreignId('paciente_id')
                  ->constrained('pacientes')
                  ->cascadeOnDelete();

            $table->foreignId('medico_id')
                  ->constrained('medicos')
                  ->cascadeOnDelete();

            // Fecha de la cita
            $table->dateTime('fecha');
            $table->text('motivo')->nullable();

            // Estado clínico de la cita
            $table->enum('estado', [
                'PENDIENTE', 'CONFIRMADA', 'COMPLETADA', 'CANCELADA', 'NO_ASISTIO'
            ])->default('PENDIENTE');

            // =====================================================
            // FACTURACIÓN
            // =====================================================

            // Tarifa aplicada
            $table->foreignId('tarifa_id')
                  ->nullable()
                  ->constrained('tarifas')
                  ->nullOnDelete();

            // Snapshot del precio al momento de la cita (en USD)
            $table->decimal('precio', 10, 2)->nullable();
            $table->decimal('descuento', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->nullable();

            // Estado del pago
            $table->enum('estado_pago', [
                'PENDIENTE', 'PAGADA', 'PARCIAL','ANULADA'
            ])->default('PENDIENTE');

            // Snapshot de la tasa estimada (informativo, para mostrar en pantalla)
            $table->foreignId('tasa_id')
                  ->nullable()
                  ->constrained('tasas')
                  ->nullOnDelete();

            $table->foreignId('cambio_id')
                  ->nullable()
                  ->constrained('cambios')
                  ->nullOnDelete();
            $table->decimal('monto_tasa_cambio', 12, 4)->nullable();

            $table->timestamps();

            // Índices útiles
            $table->index(['fecha', 'estado']);
            $table->index(['medico_id', 'fecha']);
            $table->index(['paciente_id', 'fecha']);
            $table->index('estado_pago');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('citas');
    }
};