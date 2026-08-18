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
        Schema::create('estoques', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('entrada_id')->nullable();
            $table->unsignedBigInteger('baixa_id')->nullable();
            $table->unsignedBigInteger('transferencia_id')->nullable();
            $table->unsignedBigInteger('procedimento_id')->nullable(); // sem FK (módulo procedimentos ainda não existe na V2)
            $table->unsignedBigInteger('medicamento_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable(); // auditoria (novo)
            $table->string('origem');
            $table->string('tipo')->default('Entrada'); // Entrada | Saida
            $table->double('quantidade');
            $table->double('valor', 10, 2);
            $table->double('total', 10, 2);
            $table->string('lote');
            $table->date('dt_vencimento')->nullable();
            $table->string('codigo_barras')->nullable();
            $table->timestamps();

            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('entrada_id')->references('id')->on('entradas')->onDelete('set null');
            $table->foreign('baixa_id')->references('id')->on('baixas')->onDelete('set null');
            $table->foreign('transferencia_id')->references('id')->on('transferencias')->onDelete('set null');
            $table->foreign('medicamento_id')->references('id')->on('medicamentos');
            $table->foreign('user_id')->references('id')->on('users');

            $table->index(['clinica_id', 'codigo_barras']);
            $table->index(['medicamento_id', 'lote']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoques');
    }
};
