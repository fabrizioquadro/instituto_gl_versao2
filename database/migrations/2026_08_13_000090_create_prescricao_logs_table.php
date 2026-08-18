<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescricao_id')->index();
            $table->string('entidade'); // prescricao | semana | medicamento | parcela | pagamento | anexo
            $table->unsignedBigInteger('entidade_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('acao'); // criado | editado | excluido | aplicado | cancelado | pago | anexado ...
            $table->text('descricao')->nullable();
            $table->json('dados_antigos')->nullable();
            $table->json('dados_novos')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_logs');
    }
};
