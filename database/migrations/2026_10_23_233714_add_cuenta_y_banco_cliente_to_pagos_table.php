<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            // Cuenta de la empresa a donde entra el dinero (Tesorería)
            $table->foreignId('cuenta_id')->nullable()->after('caja_id')->constrained('cuentas')->nullOnDelete();
            
            // Banco desde donde el cliente hizo la transferencia
            $table->string('banco_cliente')->nullable()->after('banco');
        });
    }

    public function down(): void
    {
        Schema::table('pagos', function (Blueprint $table) {
            $table->dropForeign(['cuenta_id']);
            $table->dropColumn(['cuenta_id', 'banco_cliente']);
        });
    }
};