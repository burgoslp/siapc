<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->foreignId('editada_por')
                ->nullable()
                ->after('updated_at')
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('editada_en')
                ->nullable()
                ->after('editada_por');
        });
    }

    public function down(): void
    {
        Schema::table('consultas', function (Blueprint $table) {
            $table->dropForeign(['editada_por']);
            $table->dropColumn(['editada_por', 'editada_en']);
        });
    }
};