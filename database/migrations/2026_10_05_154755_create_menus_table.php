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
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('padre_id')->nullable();
            $table->string('nombre');
            $table->string('url');
            $table->boolean('accesso')->default(false);
            $table->boolean('insercion')->default(false);
            $table->boolean('edicion')->default(false);
            $table->boolean('eliminacion')->default(false);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('menus');
    }
};
