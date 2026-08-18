<?php

/*
|--------------------------------------------------------------------------
| 16_configuracao.php — Migração da configuração de sincronização (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `configuracaos.ultima_atualizacao_pacientes` da V1 para a V2, para
| que a integração Feegow continue de onde parou (sync incremental).
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    $resumo = [
        'ultima_atualizacao_pacientes' => null,
    ];

    if (! Schema::connection('mysql_versao1')->hasTable('configuracaos')) {
        $resumo['status'] = 'Tabela configuracaos não existe na V1.';
        return $resumo;
    }

    $config = $v1->table('configuracaos')->where('id', 1)->first();

    if ($config) {
        $resumo['ultima_atualizacao_pacientes'] = $config->ultima_atualizacao_pacientes;

        if (! $dryRun) {
            DB::table('configuracaos')->where('id', 1)->update([
                'ultima_atualizacao_pacientes' => $config->ultima_atualizacao_pacientes,
                'updated_at' => now(),
            ]);
        }
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
