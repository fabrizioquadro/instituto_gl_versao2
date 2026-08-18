<?php

/*
|--------------------------------------------------------------------------
| 13_aplicacao_lotes.php — Migração de lotes de aplicação (V1 → V2)
|--------------------------------------------------------------------------
|
| ADIADO: a tabela `aplicacao_lotes` referencia `aplicacaos`, e o módulo
| de procedimentos/aplicações ainda não existe na V2. Este script apenas
| verifica e reporta; os dados serão migrados junto com o módulo de
| procedimentos.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    $resumo = [
        'aplicacao_lotes_migrados' => 0,
        'ja_existentes' => 0,
        'status' => 'ADIADO — aguardando módulo de procedimentos/aplicações na V2',
    ];

    if (! Schema::connection('mysql_versao1')->hasTable('aplicacao_lotes')) {
        $resumo['status'] = 'Tabela aplicacao_lotes não existe na V1.';
        return $resumo;
    }

    if (! Schema::hasTable('aplicacaos')) {
        return $resumo;
    }

    foreach ($v1->table('aplicacao_lotes')->get() as $al) {
        if (DB::table('aplicacao_lotes')->where('id_versao1', $al->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('aplicacao_lotes')->insert([
                'id' => $al->id,
                'id_versao1' => $al->id,
                'aplicacao_id' => $al->aplicacao_id,
                'quantidade' => $al->quantidade,
                'lote' => $al->lote,
                'codigo_barras' => $al->codigo_barras,
                'estoque_aberto_id' => $al->estoque_aberto_id,
                'created_at' => $al->created_at,
                'updated_at' => $al->updated_at,
            ]);
        }

        $resumo['aplicacao_lotes_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
