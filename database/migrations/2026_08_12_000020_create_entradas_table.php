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
        Schema::create('entradas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('fornecedor_id');
            $table->string('nota')->nullable();
            $table->date('data');
            $table->double('valor');
            $table->string('arquivo')->nullable();
            $table->timestamps();

            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('fornecedor_id')->references('id')->on('fornecedores');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('entradas');
    }
};
