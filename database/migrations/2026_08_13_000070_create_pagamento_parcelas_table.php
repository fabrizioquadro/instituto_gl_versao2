<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pagamento_parcelas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pagamento_id'); // prescricao_pagamentos.id
            $table->unsignedBigInteger('financeiro_parcela_id'); // parcela que esse valor cobre
            $table->decimal('valor', 10, 2); // valor que o pagamento fez naquela parcela
            $table->timestamps();

            $table->foreign('pagamento_id')->references('id')->on('prescricao_pagamentos')->onDelete('cascade');
            $table->foreign('financeiro_parcela_id')->references('id')->on('financeiro_parcelas');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagamento_parcelas');
    }
};
