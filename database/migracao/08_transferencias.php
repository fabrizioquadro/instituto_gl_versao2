<?php

/*
|--------------------------------------------------------------------------
| 08_transferencias.php — Migração de transferências (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `transferencias` preservando ids. `user_id` referencia `users`
| (ids preservados). `administrador_id` na V1 referencia `administradors`
| (que na V2 foram fundidos em `users` com origem_versao1='administradores'
| e id_versao1 = id antigo) — por isso mapeamos para o novo id de usuário.
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('transferencias')) {
        throw new RuntimeException('Tabela transferencias não encontrada no banco V1.');
    }

    // Mapa de administradores V1 -> usuários V2 (fundidos)
    $adminMap = [];
    foreach ($v1->table('administradors')->get() as $a) {
        $v2 = DB::table('users')->where('origem_versao1', 'administradores')->where('id_versao1', $a->id)->first();
        if ($v2) {
            $adminMap[$a->id] = $v2->id;
        }
    }

    $resumo = [
        'transferencias_migradas' => 0,
        'ja_existentes' => 0,
        'administradores_mapeados' => 0,
    ];

    foreach ($v1->table('transferencias')->get() as $t) {
        if (DB::table('transferencias')->where('id_versao1', $t->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        $administradorId = null;
        if ($t->administrador_id && isset($adminMap[$t->administrador_id])) {
            $administradorId = $adminMap[$t->administrador_id];
            $resumo['administradores_mapeados']++;
        }

        if (! $dryRun) {
            $userId = ($t->user_id && $t->user_id > 0 && DB::table('users')->where('id', $t->user_id)->exists()) ? $t->user_id : null;

            DB::table('transferencias')->insert([
                'id' => $t->id,
                'id_versao1' => $t->id,
                'clinica_id' => $t->clinica_id,
                'clinica_destino_id' => $t->clinica_destino_id,
                'user_id' => $userId,
                'administrador_id' => $administradorId,
                'motivo' => $t->motivo,
                'data' => $t->data,
                'valor' => $t->valor,
                'created_at' => $t->created_at,
                'updated_at' => $t->updated_at,
            ]);
        }

        $resumo['transferencias_migradas']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
