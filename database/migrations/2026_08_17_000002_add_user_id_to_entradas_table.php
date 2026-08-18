<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('entradas', function (Blueprint $table) {
            if (! Schema::hasColumn('entradas', 'user_id')) {
                $table->unsignedBigInteger('user_id')->nullable()->after('clinica_id');
            }
        });

        // FK garantida mesmo se a coluna já existia (criada manualmente antes)
        $userIds = \Illuminate\Support\Facades\DB::select(
            'SELECT COUNT(*) AS total FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_TYPE = ?',
            ['entradas', 'FOREIGN KEY']
        );
        $hasFk = false;
        foreach (\Illuminate\Support\Facades\DB::select(
            'SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['entradas', 'user_id']
        ) as $row) {
            if (! str_starts_with((string) $row->CONSTRAINT_NAME, 'PRIMARY')) {
                $hasFk = true;
            }
        }
        if (! $hasFk) {
            Schema::table('entradas', function (Blueprint $table) {
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        Schema::table('entradas', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropColumn('user_id');
        });
    }
};
