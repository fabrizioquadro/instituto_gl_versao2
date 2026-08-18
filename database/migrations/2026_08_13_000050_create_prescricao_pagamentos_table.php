<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_pagamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescricao_id');
            $table->date('dt_pagamento');
            $table->decimal('vl_total', 10, 2); // total do evento (soma das formas)
            $table->text('obs')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_pagamentos');
    }
};
