<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_pagamento_formas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pagamento_id');
            $table->string('forma_pagamento'); // Dinheiro | Pix | Cartão | Débito ...
            $table->decimal('vl_pagamento', 10, 2);
            $table->integer('parcelas')->default(1); // p/ cartão crédito (1x, 2x...)
            $table->string('id_transacao')->nullable(); // TID / nº autorização
            $table->text('obs')->nullable();
            $table->timestamps();

            $table->foreign('pagamento_id')->references('id')->on('prescricao_pagamentos')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_pagamento_formas');
    }
};
