<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricao_semanas', function (Blueprint $table) {
            $table->unsignedBigInteger('autorizador_sem_pagamento')->nullable()->after('user_id_aplicacao');
            $table->foreign('autorizador_sem_pagamento')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::table('prescricao_semanas', function (Blueprint $table) {
            $table->dropForeign(['autorizador_sem_pagamento']);
            $table->dropColumn('autorizador_sem_pagamento');
        });
    }
};
