<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Relación con la agenda de origen (null si fue creada manualmente)
            $table->foreignId('agenda_compartida_id')
                  ->nullable()
                  ->after('medico_id') 
                  ->constrained('agendas_compartidas')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropForeign(['agenda_compartida_id']);
            $table->dropColumn('agenda_compartida_id');
        });
    }
};