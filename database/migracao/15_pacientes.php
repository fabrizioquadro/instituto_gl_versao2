<?php

/*
|--------------------------------------------------------------------------
| 15_pacientes.php — Migração de pacientes (V1 → V2)
|--------------------------------------------------------------------------
|
| Copia `pacientes` da V1 preservando ids + `id_versao1` (necessário p/
| futuros `procedimentos.paciente_id`). `paciente_id_feegow` é a fonte
| (Feegow) e na V2 é UNIQUE — por isso registros duplicados da V1
| (mesmo paciente_id_feegow) são ignorados, mantendo o de menor id.
|
| Idempotente: verifica por `id_versao1`.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('pacientes')) {
        throw new RuntimeException('Tabela pacientes não encontrada no banco V1.');
    }

    $resumo = [
        'pacientes_migrados' => 0,
        'duplicados_feegow_ignorados' => 0,
        'sem_feegow_ignorados' => 0,
        'ja_existentes' => 0,
    ];

    $vistos = [];

    foreach ($v1->table('pacientes')->orderBy('id')->get() as $p) {
        if (! $p->paciente_id_feegow) {
            $resumo['sem_feegow_ignorados']++;
            continue;
        }

        // dedupe: a V2 tem UNIQUE em paciente_id_feegow (mantém o menor id)
        if (isset($vistos[$p->paciente_id_feegow])) {
            $resumo['duplicados_feegow_ignorados']++;
            continue;
        }
        $vistos[$p->paciente_id_feegow] = true;

        if (DB::table('pacientes')->where('id_versao1', $p->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            // sanitiza datas (a V1 tem '0000-00-00' em alguns registros)
            $dtNascimento = ($p->dt_nascimento && $p->dt_nascimento !== '0000-00-00') ? $p->dt_nascimento : null;
            $createdAt = ($p->created_at && $p->created_at !== '0000-00-00 00:00:00' && $p->created_at !== '0000-00-00') ? $p->created_at : now();
            $updatedAt = ($p->updated_at && $p->updated_at !== '0000-00-00 00:00:00' && $p->updated_at !== '0000-00-00') ? $p->updated_at : now();

            DB::table('pacientes')->insert([
                'id' => $p->id,
                'id_versao1' => $p->id,
                'paciente_id_feegow' => $p->paciente_id_feegow,
                'nm_paciente' => $p->nm_paciente,
                'dt_nascimento' => $dtNascimento,
                'cpf' => $p->cpf,
                'endereco' => $p->endereco,
                'numero' => $p->numero,
                'complemento' => $p->complemento,
                'bairro' => $p->bairro,
                'cidade' => $p->cidade,
                'estado' => $p->estado,
                'cep' => $p->cep,
                'telefone' => $p->telefone,
                'email' => $p->email,
                'obs' => $p->obs,
                'st_google' => $p->st_google ? 1 : 0,
                'ativo' => 1,
                'sincronizado_em' => null,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt,
            ]);
        }

        $resumo['pacientes_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
