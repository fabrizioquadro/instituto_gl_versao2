<?php

/*
|--------------------------------------------------------------------------
| 07_baixas.php — Migração de baixas (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `baixas` da V1 preservando os ids. `user_id` da V1 referencia
| `users` (ids preservados na V2 para usuários de users).
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('baixas')) {
        throw new RuntimeException('Tabela baixas não encontrada no banco V1.');
    }

    $resumo = [
        'baixas_migradas' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('baixas')->get() as $b) {
        if (DB::table('baixas')->where('id_versao1', $b->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            $userId = ($b->user_id && $b->user_id > 0 && DB::table('users')->where('id', $b->user_id)->exists()) ? $b->user_id : null;

            DB::table('baixas')->insert([
                'id' => $b->id,
                'id_versao1' => $b->id,
                'clinica_id' => $b->clinica_id,
                'user_id' => $userId,
                'motivo' => $b->motivo,
                'data' => $b->data,
                'valor' => $b->valor,
                'created_at' => $b->created_at,
                'updated_at' => $b->updated_at,
            ]);
        }

        $resumo['baixas_migradas']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
