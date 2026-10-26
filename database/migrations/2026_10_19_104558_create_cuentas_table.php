<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cuentas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('banco_id')
                  ->nullable()
                  ->constrained('bancos')
                  ->nullOnDelete();

            $table->string('nombre', 100);
            $table->enum('tipo', ['EFECTIVO', 'BANCO', 'PLATAFORMA DIGITAL']);
            $table->enum('moneda', ['dolar', 'euro', 'bs']);

            // Datos del titular
            $table->enum('lci', ['V', 'J'])->default('J');
            $table->string('ci', 20);

            $table->string('numero_cuenta', 50)->nullable();
            $table->string('titular', 150)->nullable();

            $table->decimal('saldo_inicial', 16, 2)->default(0);
            $table->boolean('activa')->default(true);
            $table->boolean('es_predeterminada')->default(false);

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['lci', 'ci', 'nombre']);
            $table->index(['tipo', 'activa']);
            $table->index('es_predeterminada');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cuentas');
    }
};