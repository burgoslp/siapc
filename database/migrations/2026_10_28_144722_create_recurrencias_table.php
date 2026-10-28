<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recurrencias', function (Blueprint $table) {
            $table->id();
            $table->string('concepto'); 
            $table->decimal('monto_estimado', 14, 2);
            $table->enum('moneda', ['dolar', 'euro', 'bs'])->default('bs');
            
            // Frecuencia
            $table->enum('frecuencia', ['MENSUAL', 'SEMANAL', 'ANUAL']);
            
            $table->date('fecha_pago')->nullable(); // Para mensual y anual (ej: 2026-10-15)
            $table->enum('dia_semana', ['LUNES', 'MARTES', 'MIERCOLES', 'JUEVES', 'VIERNES', 'SABADO', 'DOMINGO'])->nullable(); // Para semanal
            
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->text('notas')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recurrencias');
    }
};