<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_semana_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('prescricao_semana_id');
            $table->unsignedBigInteger('medicamento_id');
            $table->unsignedBigInteger('combo_id')->nullable();
            $table->unsignedBigInteger('clinica_id_aplicacao')->nullable(); // clínica onde ESTA medicação foi aplicada
            $table->boolean('is_soro')->default(false);
            $table->boolean('gera_aplicacao')->default(false); // derivado de medicamento.aplicacao == 'Sim'
            $table->double('quantidade');
            $table->string('situacao')->default('Aberta'); // Aberta | Aplicada | Cancelada
            $table->date('data_prevista')->nullable();
            $table->dateTime('dt_hr_chegada')->nullable();
            $table->dateTime('dt_hr_atendimento')->nullable();
            $table->dateTime('aplicado_em')->nullable();
            $table->unsignedBigInteger('user_id_aplicacao')->nullable();
            $table->text('obs')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_semana_id')->references('id')->on('prescricao_semanas')->onDelete('cascade');
            $table->foreign('medicamento_id')->references('id')->on('medicamentos');
            $table->foreign('combo_id')->references('id')->on('combos')->onDelete('set null');
            $table->foreign('clinica_id_aplicacao')->references('id')->on('clinicas');
            $table->foreign('user_id_aplicacao')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_semana_medicamentos');
    }
};
