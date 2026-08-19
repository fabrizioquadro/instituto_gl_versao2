<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('prescricaos', function (Blueprint $table) {
            $table->unsignedSmallInteger('periodicidade_dias')->default(7)->after('qt_semanas_aplicacao');
        });
    }

    public function down(): void
    {
        Schema::table('prescricaos', function (Blueprint $table) {
            $table->dropColumn('periodicidade_dias');
        });
    }
};
