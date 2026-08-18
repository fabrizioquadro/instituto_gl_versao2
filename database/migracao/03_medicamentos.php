<?php

/*
|--------------------------------------------------------------------------
| 03_medicamentos.php — Migração de medicamentos (V1 → V2)
|--------------------------------------------------------------------------
|
| Lê a tabela `medicamentos` da V1 e copia para a V2 preservando ids
| (necessário pois `combo_medicamentos.medicamento_id` referencia
| `medicamentos`).
|
| Mudanças aplicadas (decisão do usuário):
|   - `unidade`  -> `tipo`  (Ampola | Vasilhame | Procedimento)
|   - mapeamento: Ampola -> Ampola, Procedimento -> Procedimento,
|                 Miligrama -> Vasilhame
|   - `estoque_medio` = 0 (não existe na V1; alerta amarelo)
|   - `estoque_minimo` mantido (alerta vermelho)
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('medicamentos')) {
        throw new RuntimeException('Tabela medicamentos não encontrada no banco V1.');
    }

    $resumo = [
        'medicamentos_migrados' => 0,
        'ja_existentes' => 0,
        'mapeados_miligrama_para_vasilhame' => 0,
    ];

    foreach ($v1->table('medicamentos')->get() as $m) {
        if (DB::table('medicamentos')->where('id_versao1', $m->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        $tipo = strtoupper((string) $m->unidade);

        if ($tipo === 'MILIGRAMA') {
            $tipo = 'Vasilhame';
            $resumo['mapeados_miligrama_para_vasilhame']++;
        } elseif ($tipo === 'PROCEDIMENTO') {
            $tipo = 'Procedimento';
        } else {
            $tipo = 'Ampola';
        }

        if (! $dryRun) {
            DB::table('medicamentos')->insert([
                'id' => $m->id,
                'id_versao1' => $m->id,
                'grupo_id' => $m->grupo_id,
                'nome' => $m->nome,
                'fabricante' => $m->fabricante,
                'tipo' => $tipo,
                'vasilhame' => $m->vasilhame,
                'ultimo_valor_pg' => $m->ultimo_valor_pg,
                'vl_venda' => $m->vl_venda,
                'estoque_minimo' => $m->estoque_minimo ?? 0,
                'estoque_medio' => 0,
                'situacao' => $m->situacao,
                'aplicacao' => $m->aplicacao,
                'aplicacao_feegow_id' => $m->aplicacao_feegow_id,
                'created_at' => $m->created_at,
                'updated_at' => $m->updated_at,
            ]);
        }

        $resumo['medicamentos_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
