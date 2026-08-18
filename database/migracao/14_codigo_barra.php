<?php

/*
|--------------------------------------------------------------------------
| 14_codigo_barra.php — Migração de contadores de código de barras (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `codigo_barra_medicamentos` (contador por medicamento) preservando
| ids, para que a geração de novos códigos continue de onde parou na V1.
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('codigo_barra_medicamentos')) {
        throw new RuntimeException('Tabela codigo_barra_medicamentos não encontrada no banco V1.');
    }

    $resumo = [
        'contadores_migrados' => 0,
        'ja_existentes' => 0,
    ];

    foreach ($v1->table('codigo_barra_medicamentos')->get() as $c) {
        if (DB::table('codigo_barra_medicamentos')->where('id_versao1', $c->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        // ignora contadores de medicamentos que não existem mais (excluídos na V1)
        if (! DB::table('medicamentos')->where('id', $c->medicamento_id)->exists()) {
            $resumo['ignorados_medicamento_inexistente'] = ($resumo['ignorados_medicamento_inexistente'] ?? 0) + 1;
            continue;
        }

        if (! $dryRun) {
            DB::table('codigo_barra_medicamentos')->insert([
                'id' => $c->id,
                'id_versao1' => $c->id,
                'medicamento_id' => $c->medicamento_id,
                'contador' => $c->contador,
                'created_at' => $c->created_at,
                'updated_at' => $c->updated_at,
            ]);
        }

        $resumo['contadores_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
