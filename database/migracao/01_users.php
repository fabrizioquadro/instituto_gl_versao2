<?php

/*
|--------------------------------------------------------------------------
| 01_users.php — Migração de usuários e administradores (V1 → V2)
|--------------------------------------------------------------------------
|
| Lê as tabelas `users` e `administradors` do banco da V1 (conexão
| `mysql_versao1`) e as unifica na tabela `users` da V2, conforme o
| documento implementacoes/01-users.md.
|
| Idempotente: registros já migrados (por id_versao1 + origem_versao1)
| são ignorados.
|
| Parâmetro opcional: $dryRun (bool) — apenas simula e conta.
|
*/

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return function ($dryRun = false) {
    $v1 = DB::connection('mysql_versao1');

    if (! Schema::connection('mysql_versao1')->hasTable('users') ||
        ! Schema::connection('mysql_versao1')->hasTable('administradors')) {
        throw new RuntimeException('Banco V1 inacessível ou tabelas users/administradors não encontradas. Verifique as variáveis DB_V1_* no .env.');
    }

    // 1. Identificar e-mails duplicados entre as duas tabelas (4.2)
    // Decisão (2026-08-12): quando duplicado, migra apenas o registro de
    // administradors (role=admin), ignorando o registro de users.
    $emailsUsers = $v1->table('users')->pluck('email')->map(fn ($e) => strtolower(trim($e)))->filter();
    $emailsAdmins = $v1->table('administradors')->pluck('email')->map(fn ($e) => strtolower(trim($e)))->filter();
    $conflitos = $emailsUsers->intersect($emailsAdmins)->unique()->values();

    $resumo = [
        'users_migrados' => 0,
        'admins_migrados' => 0,
        'conflitos_email' => $conflitos->count(),
        'users_ignorados_conflito' => 0,
        'ja_existentes' => 0,
    ];

    // 2. Migrar `users` da V1 preservando os ids originais
    foreach ($v1->table('users')->get() as $u) {
        // Ignora usuários com e-mail duplicado (a pessoa entra como admin)
        if ($conflitos->contains(strtolower(trim($u->email)))) {
            $resumo['users_ignorados_conflito']++;
            continue;
        }

        if (DB::table('users')->where('origem_versao1', 'users')->where('id_versao1', $u->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('users')->insert([
                'id' => $u->id,
                'id_versao1' => $u->id,
                'origem_versao1' => 'users',
                'clinica_id' => $u->clinica_id,
                'nome' => $u->nome,
                'email' => $u->email,
                'password' => $u->password,
                'role' => $u->tipo === 'Enfermagem' ? 'enfermagem' : 'secretaria',
                'coren' => $u->coren,
                'imagem' => $u->imagem,
                'imagem_carimbo' => $u->imagem_carimbo,
                'senha_certificado' => $u->senha_certificado,
                'dashboard_secretaria' => $u->dashboard_sec === 'S' ? 1 : 0,
                'dashboard_enfermagem' => $u->dashboard_enf === 'S' ? 1 : 0,
                'controle_medicamentos' => $u->controle_medicamentos === 'S' ? 1 : 0,
                'pacientes' => $u->pacientes === 'S' ? 1 : 0,
                'procedimentos' => $u->procedimentos === 'S' ? 1 : 0,
                'financeiro' => $u->financeiro === 'S' ? 1 : 0,
                'ativo' => $u->st_usuario === 'Ativo' ? 1 : 0,
                'created_at' => $u->created_at,
                'updated_at' => $u->updated_at,
            ]);
        }

        $resumo['users_migrados']++;
    }

    // 3. Migrar `administradors` da V1 com novos ids (role = admin)
    foreach ($v1->table('administradors')->get() as $a) {
        if (DB::table('users')->where('origem_versao1', 'administradores')->where('id_versao1', $a->id)->exists()) {
            $resumo['ja_existentes']++;
            continue;
        }

        if (! $dryRun) {
            DB::table('users')->insert([
                'id_versao1' => $a->id,
                'origem_versao1' => 'administradores',
                'clinica_id' => null,
                'nome' => $a->nome,
                'email' => $a->email,
                'password' => $a->password,
                'role' => 'admin',
                'coren' => null,
                'imagem' => $a->imagem,
                'imagem_carimbo' => null,
                'senha_certificado' => null,
                'dashboard_secretaria' => 1,
                'dashboard_enfermagem' => 1,
                'controle_medicamentos' => 1,
                'pacientes' => 1,
                'procedimentos' => 1,
                'financeiro' => 1,
                'ativo' => $a->st_usuario === 'Ativo' ? 1 : 0,
                'created_at' => $a->created_at,
                'updated_at' => $a->updated_at,
            ]);
        }

        $resumo['admins_migrados']++;
    }

    if ($dryRun) {
        $resumo['dry_run'] = true;
    }

    return $resumo;
};
