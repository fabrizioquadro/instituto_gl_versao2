<?php

/*
|--------------------------------------------------------------------------
| 11_estoque_abertos.php — Migração de estoques abertos (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `estoque_abertos` preservando ids. `user_id` referencia `users`
| (ids preservados). Corrige o nome `qt_inical` -> `qt_inicial`.
| `procedimento_id` copiado como valor (sem FK na V2 por enquanto).
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('estoque_abertos')) {
        throw new RuntimeException('Tabela estoque_abertos não encontrada no banco V1.');
    }

    $resumo = [
        'estoques_abertos_migrados' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('estoque_abertos')->get() as $ea) {
        if (DB::table('estoque_abertos')->where('id_versao1', $ea->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            $userId = ($ea->user_id && $ea->user_id > 0 && DB::table('users')->where('id', $ea->user_id)->exists()) ? $ea->user_id : null;

            DB::table('estoque_abertos')->insert([
                'id' => $ea->id,
                'id_versao1' => $ea->id,
                'medicamento_id' => $ea->medicamento_id,
                'procedimento_id' => $ea->procedimento_id,
                'user_id' => $userId,
                'clinica_id' => $ea->clinica_id,
                'dt_cadastro' => $ea->dt_cadastro,
                'qt_inicial' => $ea->qt_inical,
                'qt_utilizado' => $ea->qt_utilizado,
                'qt_restante' => $ea->qt_restante,
                'lote' => $ea->lote,
                'codigo_barras' => $ea->codigo_barras,
                'situacao' => $ea->situacao,
                'created_at' => $ea->created_at,
                'updated_at' => $ea->updated_at,
            ]);
        }

        $resumo['estoques_abertos_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
