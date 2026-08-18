<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('configuracaos', function (Blueprint $table) {
            $table->integer('id')->primary();
            $table->date('ultima_atualizacao_pacientes');
            $table->timestamps();
        });

        // linha inicial (o script de importação 16 atualiza com o valor da V1)
        DB::table('configuracaos')->insert([
            'id' => 1,
            'ultima_atualizacao_pacientes' => '2025-07-14',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuracaos');
    }
};
