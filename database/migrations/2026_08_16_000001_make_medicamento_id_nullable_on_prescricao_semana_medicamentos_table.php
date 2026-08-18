<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Combos e soros não têm medicamento_id direto (medicamento_id = NULL).
        Schema::table('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('medicamento_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('medicamento_id')->nullable(false)->change();
        });
    }
};
