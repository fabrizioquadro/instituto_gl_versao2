<?php

/*
|--------------------------------------------------------------------------
| 10_estoques_saldos.php — Recalcula saldos de estoque (V1 → V2)
|--------------------------------------------------------------------------
|
| A tabela `estoques_saldos` (nova na V2) é populada a partir dos
| movimentos de `estoques` da V1: saldo por (clínica, medicamento, lote,
| código de barras) = SUM(Entrada) - SUM(Saida), mantendo apenas saldo > 0.
|
| Idempotente: recalcula e substitui (DELETE + INSERT) em transação.
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
        'saldos_recalculados' => 0,
    ];

    $linhas = $v1->table('estoques')
        ->select(
            'clinica_id',
            'medicamento_id',
            'lote',
            'codigo_barras',
            DB::raw('MAX(dt_vencimento) as dt_vencimento'),
            DB::raw("SUM(CASE WHEN tipo = 'Entrada' THEN quantidade ELSE 0 END) as entradas"),
            DB::raw("SUM(CASE WHEN tipo = 'Saida' THEN quantidade ELSE 0 END) as saidas")
        )
        ->groupBy('clinica_id', 'medicamento_id', 'lote', 'codigo_barras')
        ->get();

    if (! $dryRun) {
        // DELETE em vez de TRUNCATE (TRUNCATE faz commit implícito e quebra a transação)
        DB::table('estoques_saldos')->delete();
    }

    foreach ($linhas as $linha) {
        $saldo = (float) $linha->entradas - (float) $linha->saidas;

        // ignora registros sem medicamento ou com saldo zerado/negativo
        if (empty($linha->medicamento_id) || $saldo <= 0) {
            continue;
        }

        if (! $dryRun) {
            DB::table('estoques_saldos')->insert([
                'clinica_id' => $linha->clinica_id,
                'medicamento_id' => $linha->medicamento_id,
                'lote' => $linha->lote,
                'codigo_barras' => $linha->codigo_barras,
                'dt_vencimento' => $linha->dt_vencimento,
                'saldo' => $saldo,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $resumo['saldos_recalculados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
