<?php

/*
|--------------------------------------------------------------------------
| 04_combos.php — Migração de combos e combo_medicamentos (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `combos` e `combo_medicamentos` da V1 preservando os ids (as FKs
| continuam válidas pois medicamentos e combos preservam seus ids).
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('combos')) {
        throw new RuntimeException('Tabela combos não encontrada no banco V1.');
    }

    $resumo = [
        'combos_migrados' => 0,
        'combos_ja_existentes' => 0,
        'itens_migrados' => 0,
        'itens_ja_existentes' => 0,
    ];

    foreach ($v1->table('combos')->get() as $c) {
        if (DB::table('combos')->where('id_versao1', $c->id)->exists()) {
            $resumo['combos_ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('combos')->insert([
                'id' => $c->id,
                'id_versao1' => $c->id,
                'nome' => $c->nome,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
            ]);
        }

        $resumo['combos_migrados']++;
    }

    if (Schema::connection('mysql_versao1')->hasTable('combo_medicamentos')) {
        foreach ($v1->table('combo_medicamentos')->get() as $cm) {
            if (DB::table('combo_medicamentos')->where('id_versao1', $cm->id)->exists()) {
                $resumo['itens_ja_existentes']++;
                continue;
            }

            if (! $dryRun) {
                DB::table('combo_medicamentos')->insert([
                    'id' => $cm->id,
                    'id_versao1' => $cm->id,
                    'combo_id' => $cm->combo_id,
                    'medicamento_id' => $cm->medicamento_id,
                    'quantidade' => $cm->quantidade,
                    'valor_unitario' => $cm->valor_unitario,
                    'created_at' => $cm->created_at ?? now(),
                    'updated_at' => $cm->updated_at ?? now(),
                ]);
            }

            $resumo['itens_migrados']++;
        }
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
