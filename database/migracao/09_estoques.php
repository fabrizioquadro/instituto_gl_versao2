<?php

/*
|--------------------------------------------------------------------------
| 09_estoques.php — Migração de movimentos de estoque (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `estoques` (livro de movimentos) preservando os ids e FKs
| (clinicas/medicamentos/entradas/baixas/transferencias preservam ids).
| `procedimento_id` é copiado como valor (sem FK na V2 por enquanto).
| `user_id` (auditoria, novo na V2) fica nulo nos dados migrados.
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('estoques')) {
        throw new RuntimeException('Tabela estoques não encontrada no banco V1.');
    }

    $resumo = [
        'movimentos_migrados' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('estoques')->get() as $e) {
        if (DB::table('estoques')->where('id_versao1', $e->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('estoques')->insert([
                'id' => $e->id,
                'id_versao1' => $e->id,
                'clinica_id' => $e->clinica_id,
                'entrada_id' => $e->entrada_id,
                'baixa_id' => $e->baixa_id,
                'transferencia_id' => $e->transferencia_id,
                'procedimento_id' => $e->procedimento_id,
                'medicamento_id' => $e->medicamento_id,
                'user_id' => null,
                'origem' => $e->origem,
                'tipo' => $e->tipo,
                'quantidade' => $e->quantidade,
                'valor' => $e->valor,
                'total' => $e->total,
                'lote' => $e->lote,
                'dt_vencimento' => $e->dt_vencimento,
                'codigo_barras' => $e->codigo_barras,
                'created_at' => $e->created_at,
                'updated_at' => $e->updated_at,
            ]);
        }

        $resumo['movimentos_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
