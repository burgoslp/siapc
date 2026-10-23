<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->string('concepto'); 
            $table->text('descripcion')->nullable();
            $table->decimal('monto', 14, 2);
            $table->enum('moneda', ['dolar', 'euro', 'bs'])->default('bs');
            $table->decimal('tasa_cambio', 12, 4)->nullable(); 
            $table->foreignId('cuenta_id')->nullable()->constrained('cuentas')->nullOnDelete();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete();
            $table->date('fecha_gasto');
            $table->string('referencia')->nullable();
            $table->timestamps();
            
            $table->index('fecha_gasto');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
