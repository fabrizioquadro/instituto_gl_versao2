<?php

/*
|--------------------------------------------------------------------------
| 06_entradas.php — Migração de entradas (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `entradas` da V1 preservando os ids (as FKs clinica/fornecedor
| continuam válidas pois clínicas e fornecedores preservam seus ids).
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('entradas')) {
        throw new RuntimeException('Tabela entradas não encontrada no banco V1.');
    }

    $resumo = [
        'entradas_migradas' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('entradas')->get() as $e) {
        if (DB::table('entradas')->where('id_versao1', $e->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('entradas')->insert([
                'id' => $e->id,
                'id_versao1' => $e->id,
                'clinica_id' => $e->clinica_id,
                'fornecedor_id' => $e->fornecedor_id,
                'nota' => $e->nota,
                'data' => $e->data,
                'valor' => $e->valor,
                'arquivo' => $e->arquivo,
                'created_at' => $e->created_at,
                'updated_at' => $e->updated_at,
            ]);
        }

        $resumo['entradas_migradas']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
