<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->unsignedBigInteger('soro_id')->nullable()->after('combo_id');
            $table->foreign('soro_id')->references('id')->on('soros')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->dropForeign(['soro_id']);
            $table->dropColumn('soro_id');
        });
    }
};
