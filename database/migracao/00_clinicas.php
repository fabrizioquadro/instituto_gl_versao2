<?php

/*
|--------------------------------------------------------------------------
| 00_clinicas.php — Migração de clínicas (V1 → V2)
|--------------------------------------------------------------------------
|
| Lê a tabela `clinicas` do banco da V1 (conexão `mysql_versao1`) e copia
| para a V2, preservando os ids originais (necessário pois `users.clinica_id`
| referencia `clinicas` e os usuários são migrados preservando seus ids).
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('clinicas')) {
        throw new RuntimeException('Tabela clinicas não encontrada no banco V1.');
    }

    $resumo = [
        'clinicas_migradas' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('clinicas')->get() as $c) {
        if (DB::table('clinicas')->where('id_versao1', $c->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('clinicas')->insert([
                'id' => $c->id,
                'id_versao1' => $c->id,
                'nome' => $c->nome,
                'cnpj' => $c->cnpj,
                'id_unidade_feegow' => $c->id_unidade_feegow,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
            ]);
        }

        $resumo['clinicas_migradas']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
