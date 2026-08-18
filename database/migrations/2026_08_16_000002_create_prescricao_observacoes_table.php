<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('prescricao_observacoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('prescricao_id')->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('observacao');
            $table->timestamps();

            $table->foreign('prescricao_id')->references('id')->on('prescricaos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('prescricao_observacoes');
    }
};
