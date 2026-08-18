<?php

/*
|--------------------------------------------------------------------------
| 05_fornecedores.php — Migração de fornecedores (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `fornecedors` da V1 preservando os ids (necessário pois
| `entradas.fornecedor_id` referencia `fornecedores`).
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('fornecedors')) {
        throw new RuntimeException('Tabela fornecedors não encontrada no banco V1.');
    }

    $resumo = [
        'fornecedores_migrados' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('fornecedors')->get() as $f) {
        if (DB::table('fornecedores')->where('id_versao1', $f->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('fornecedores')->insert([
                'id' => $f->id,
                'id_versao1' => $f->id,
                'nome' => $f->nome,
                'cnpj' => $f->cnpj,
                'email' => $f->email,
                'tel' => $f->tel,
                'cel' => $f->cel,
                'situacao' => $f->situacao,
                'created_at' => $f->created_at,
                'updated_at' => $f->updated_at,
            ]);
        }

        $resumo['fornecedores_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
