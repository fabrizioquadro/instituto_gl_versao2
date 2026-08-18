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
        Schema::create('pacientes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_versao1')->nullable()->index();
            $table->unsignedBigInteger('paciente_id_feegow')->unique(); // fonte (Feegow)
            $table->string('nm_paciente');
            $table->date('dt_nascimento')->nullable();
            $table->string('cpf', 20)->nullable();
            $table->string('endereco')->nullable();
            $table->string('numero')->nullable();
            $table->string('complemento')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cidade')->nullable();
            $table->string('estado', 2)->nullable();
            $table->string('cep', 10)->nullable();
            $table->string('telefone')->nullable();
            $table->string('email')->nullable();
            $table->text('obs')->nullable();                // observação local
            $table->boolean('st_google')->default(false);
            $table->boolean('ativo')->default(true);        // invisível p/ histórico quando false
            $table->timestamp('sincronizado_em')->nullable(); // última atualização vinda da Feegow
            $table->timestamps();

            $table->index('nm_paciente');
            $table->index('cpf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pacientes');
    }
};
