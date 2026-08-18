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
        Schema::create('transferencias', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('clinica_id');          // origem
            $table->unsignedBigInteger('clinica_destino_id');  // destino
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('administrador_id')->nullable();
            $table->text('motivo');
            $table->date('data');
            $table->double('valor');
            $table->timestamps();

            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('clinica_destino_id')->references('id')->on('clinicas');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transferencias');
    }
};
