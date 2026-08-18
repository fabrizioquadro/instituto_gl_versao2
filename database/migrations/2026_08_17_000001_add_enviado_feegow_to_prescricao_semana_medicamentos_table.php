<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->boolean('enviado_feegow')->default(false)->after('obs');
        });
    }

    public function down(): void
    {
        Schema::table('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->dropColumn('enviado_feegow');
        });
    }
};
