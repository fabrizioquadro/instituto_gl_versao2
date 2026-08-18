<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_semanas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('prescricao_id');
            $table->integer('nr_semana'); // 1..N
            $table->date('data_prevista');
            $table->boolean('tem_aplicacao')->default(false); // tem medicação que gera aplicação
            $table->string('situacao')->default('Agendada'); // Agendada | Em Atendimento | Aplicada | Aplicação Parcial | Cancelada
            $table->text('obs')->nullable();
            $table->dateTime('dt_hr_chegada')->nullable();
            $table->dateTime('dt_hr_atendimento')->nullable();
            $table->dateTime('dt_hr_finalizacao')->nullable();
            $table->unsignedBigInteger('user_id_aplicacao')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('user_id_aplicacao')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_semanas');
    }
};
