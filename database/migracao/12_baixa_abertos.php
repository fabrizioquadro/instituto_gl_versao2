<?php

/*
|--------------------------------------------------------------------------
| 12_baixa_abertos.php — Migração de baixas de abertos (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `baixa_abertos` preservando ids (FKs estoque_abertos/users/clinicas
| continuam válidas).
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('baixa_abertos')) {
        throw new RuntimeException('Tabela baixa_abertos não encontrada no banco V1.');
    }

    $resumo = [
        'baixas_abertos_migradas' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('baixa_abertos')->get() as $b) {
        if (DB::table('baixa_abertos')->where('id_versao1', $b->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            $userId = ($b->user_id && $b->user_id > 0 && DB::table('users')->where('id', $b->user_id)->exists()) ? $b->user_id : null;

            DB::table('baixa_abertos')->insert([
                'id' => $b->id,
                'id_versao1' => $b->id,
                'clinica_id' => $b->clinica_id,
                'estoque_aberto_id' => $b->estoque_aberto_id,
                'user_id' => $userId,
                'quantidade' => $b->quantidade,
                'motivo' => $b->motivo,
                'created_at' => $b->created_at,
                'updated_at' => $b->updated_at,
            ]);
        }

        $resumo['baixas_abertos_migradas']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
