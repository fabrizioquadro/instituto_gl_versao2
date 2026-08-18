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
        Schema::create('soro_medicamentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('soro_id');
            $table->unsignedBigInteger('medicamento_id');
            $table->double('quantidade');
            $table->double('valor_unitario', 10, 2);
            $table->timestamps();

            $table->foreign('soro_id')->references('id')->on('soros')->onDelete('cascade');
            $table->foreign('medicamento_id')->references('id')->on('medicamentos');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('soro_medicamentos');
    }
};
