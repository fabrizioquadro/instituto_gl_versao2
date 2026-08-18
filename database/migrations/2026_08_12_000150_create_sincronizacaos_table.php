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
        Schema::create('sincronizacaos', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');                 // ex.: pacientes-feegow
            $table->timestamp('data_inicio');
            $table->timestamp('data_fim')->nullable();
            $table->string('status');               // rodando | sucesso | erro
            $table->integer('criados')->default(0);
            $table->integer('atualizados')->default(0);
            $table->integer('erros')->default(0);
            $table->text('detalhes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sincronizacaos');
    }
};
