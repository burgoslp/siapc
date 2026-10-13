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
        Schema::create('condiciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 200);
            $table->string('codigo_cie10', 10)->nullable()->index();
            $table->enum('categoria', [
                'METABOLICA',
                'CARDIOVASCULAR',
                'RESPIRATORIA',
                'DIGESTIVA',
                'NEUROLOGICA',
                'MUSCULOESQUELETICA',
                'RENAL',
                'AUTOINMUNE',
                'ONCOLOGICA',
                'INFECCIOSA',
                'PSIQUIATRICA',
                'OTRA'
            ]);
            $table->text('descripcion')->nullable();
            $table->boolean('requiere_seguimiento')->default(false);
            $table->integer('frecuencia_seguimiento_dias')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('condiciones');
    }
};
