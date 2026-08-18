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
        Schema::create('estoque_abertos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('medicamento_id');
            $table->unsignedBigInteger('procedimento_id')->nullable(); // sem FK (módulo procedimentos ainda não existe)
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('clinica_id');
            $table->date('dt_cadastro');
            $table->double('qt_inicial'); // corrigido (era qt_inical na V1)
            $table->double('qt_utilizado');
            $table->double('qt_restante');
            $table->string('lote');
            $table->string('codigo_barras')->nullable();
            $table->string('situacao')->default('Aberto'); // Aberto | Finalizado
            $table->timestamps();

            $table->foreign('medicamento_id')->references('id')->on('medicamentos');
            $table->foreign('user_id')->references('id')->on('users');
            $table->foreign('clinica_id')->references('id')->on('clinicas');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoque_abertos');
    }
};
