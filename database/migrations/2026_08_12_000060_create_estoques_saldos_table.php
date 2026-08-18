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
        Schema::create('estoques_saldos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('medicamento_id');
            $table->string('lote');
            $table->string('codigo_barras')->nullable();
            $table->date('dt_vencimento')->nullable();
            $table->double('saldo')->default(0);
            $table->timestamps();

            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('medicamento_id')->references('id')->on('medicamentos');

            $table->unique(['clinica_id', 'medicamento_id', 'lote', 'codigo_barras'], 'uq_estoques_saldos');
            $table->index(['medicamento_id', 'codigo_barras']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estoques_saldos');
    }
};
