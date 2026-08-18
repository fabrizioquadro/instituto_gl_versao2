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
        // Na V1 a coluna estado guarda o nome completo (ex.: "São Paulo"),
        // então precisa de espaço maior que 2 caracteres.
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('estado', 60)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pacientes', function (Blueprint $table) {
            $table->string('estado', 2)->nullable()->change();
        });
    }
};
