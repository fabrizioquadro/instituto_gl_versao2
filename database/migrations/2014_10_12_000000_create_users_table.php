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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->string('origem_versao1')->nullable();
            $table->unsignedBigInteger('clinica_id')->nullable();
            $table->string('nome');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role')->default('secretaria');
            $table->string('coren')->nullable();
            $table->string('imagem')->nullable();
            $table->string('imagem_carimbo')->nullable();
            $table->string('senha_certificado')->nullable();
            $table->boolean('dashboard_secretaria')->default(false);
            $table->boolean('dashboard_enfermagem')->default(false);
            $table->boolean('controle_medicamentos')->default(false);
            $table->boolean('pacientes')->default(false);
            $table->boolean('procedimentos')->default(false);
            $table->boolean('financeiro')->default(false);
            $table->boolean('ativo')->default(true);
            $table->rememberToken();
            $table->timestamps();

            $table->foreign('clinica_id')->references('id')->on('clinicas')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
