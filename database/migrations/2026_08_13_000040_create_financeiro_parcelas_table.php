<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financeiro_parcelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescricao_id');
            $table->unsignedBigInteger('prescricao_semana_id'); // semana que a parcela representa
            $table->integer('nr_parcela');
            $table->decimal('valor_parcela', 10, 2);
            $table->decimal('valor_pago', 10, 2)->default(0);
            $table->string('situacao')->default('Em Aberto'); // Em Aberto | Parcial | Paga | Cancelada
            $table->date('dt_vencimento')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('prescricao_semana_id')->references('id')->on('prescricao_semanas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financeiro_parcelas');
    }
};
