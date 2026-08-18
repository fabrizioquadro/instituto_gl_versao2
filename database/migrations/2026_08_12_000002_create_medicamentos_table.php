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
        Schema::create('medicamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('grupo_id')->nullable();
            $table->string('nome');
            $table->string('fabricante');
            $table->string('tipo'); // Ampola | Vasilhame | Procedimento (era 'unidade')
            $table->integer('vasilhame')->nullable(); // tamanho do vasilhame (somente p/ tipo=Vasilhame)
            $table->double('ultimo_valor_pg', 10, 2)->nullable();
            $table->string('vl_venda', 10);
            $table->double('estoque_minimo')->default(0); // alerta VERMELHO
            $table->double('estoque_medio')->default(0); // alerta AMARELO (novo)
            $table->string('situacao')->default('Ativo');
            $table->string('aplicacao', 5)->default('Não');
            $table->integer('aplicacao_feegow_id')->nullable();
            $table->timestamps();

            $table->foreign('grupo_id')->references('id')->on('grupos')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medicamentos');
    }
};
