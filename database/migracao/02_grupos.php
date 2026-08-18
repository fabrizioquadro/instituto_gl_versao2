<?php

/*
|--------------------------------------------------------------------------
| 02_grupos.php — Migração de grupos (V1 → V2)
|--------------------------------------------------------------------------
|
| Lê a tabela `grupos` do banco da V1 (conexão `mysql_versao1`) e copia
| para a V2, preservando os ids originais (necessário pois
| `medicamentos.grupo_id` referencia `grupos` e os medicamentos também são
| migrados preservando seus ids).
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('grupos')) {
        throw new RuntimeException('Tabela grupos não encontrada no banco V1.');
    }

    $resumo = [
        'grupos_migrados' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('grupos')->get() as $g) {
        if (DB::table('grupos')->where('id_versao1', $g->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('grupos')->insert([
                'id' => $g->id,
                'id_versao1' => $g->id,
                'nome' => $g->nome,
                'created_at' => $g->created_at,
                'updated_at' => $g->updated_at,
            ]);
        }

        $resumo['grupos_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
