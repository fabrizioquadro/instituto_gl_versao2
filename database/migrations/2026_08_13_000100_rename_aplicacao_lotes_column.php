<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ajusta `aplicacao_lotes` para o novo modelo: a "aplicação" agora é a
     * medicação da semana (prescricao_semana_medicamentos).
     * Usa CHANGE COLUMN (MariaDB não aceita RENAME COLUMN do Laravel).
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE aplicacao_lotes CHANGE COLUMN aplicacao_id prescricao_semana_medicamento_id BIGINT UNSIGNED NULL');

        Schema::table('aplicacao_lotes', function (Blueprint $table) {
            $table->foreign('prescricao_semana_medicamento_id')->references('id')->on('prescricao_semana_medicamentos');
        });
    }

    public function down(): void
    {
        Schema::table('aplicacao_lotes', function (Blueprint $table) {
            $table->dropForeign(['prescricao_semana_medicamento_id']);
        });

        DB::statement('ALTER TABLE aplicacao_lotes CHANGE COLUMN prescricao_semana_medicamento_id aplicacao_id BIGINT UNSIGNED NULL');
    }
};
