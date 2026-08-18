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
        Schema::create('baixa_abertos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('estoque_aberto_id');
            $table->unsignedBigInteger('user_id');
            $table->double('quantidade');
            $table->text('motivo');
            $table->timestamps();

            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('estoque_aberto_id')->references('id')->on('estoque_abertos');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('baixa_abertos');
    }
};
