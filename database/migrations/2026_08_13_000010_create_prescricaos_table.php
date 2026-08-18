<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricaos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_versao1')->nullable()->index();
            $table->unsignedBigInteger('paciente_id');
            $table->unsignedBigInteger('clinica_id');
            $table->unsignedBigInteger('user_id_cadastro')->nullable();
            $table->string('medico');
            $table->string('tipo_atendimento')->nullable();
            $table->text('agendamento')->nullable();
            $table->date('data_prescricao');
            $table->integer('qt_semanas')->default(0);
            $table->integer('qt_semanas_aplicacao')->default(0);
            $table->integer('qt_parcelas')->default(0);
            $table->integer('semana_atual')->default(0); // 0 = não iniciado
            $table->decimal('valor_tratamento', 10, 2)->default(0);
            $table->decimal('credito_em_aberto', 10, 2)->default(0); // de outra prescrição (pagou e não usou)
            $table->string('situacao')->default('Agendada'); // Agendada | Em Andamento | Concluída | Cancelada
            $table->string('situacao_financeira')->default('Em Aberto'); // Em Aberto | Parcial | Pago | Cancelado
            $table->text('obs')->nullable();
            $table->timestamps();

            $table->foreign('paciente_id')->references('id')->on('pacientes');
            $table->foreign('clinica_id')->references('id')->on('clinicas');
            $table->foreign('user_id_cadastro')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricaos');
    }
};
