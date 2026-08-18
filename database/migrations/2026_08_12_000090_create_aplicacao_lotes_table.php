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
        Schema::create('aplicacao_lotes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('aplicacao_id'); // sem FK (módulo procedimentos/aplicações ainda não existe na V2)
            $table->double('quantidade');
            $table->string('lote');
            $table->string('codigo_barras')->nullable();
            $table->unsignedBigInteger('estoque_aberto_id')->nullable();
            $table->timestamps();

            $table->foreign('estoque_aberto_id')->references('id')->on('estoque_abertos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aplicacao_lotes');
    }
};
