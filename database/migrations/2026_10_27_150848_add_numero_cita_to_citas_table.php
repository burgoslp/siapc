<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            // Lo ponemos nullable porque las citas viejas no tienen este campo aún.
            $table->string('numero_cita')->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropUnique(['numero_cita']);
            $table->dropColumn('numero_cita');
        });
    }
};