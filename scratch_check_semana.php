<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Prescricao;
use App\Models\PrescricaoSemana;
use App\Models\PrescricaoLog;

echo "== ÚLTIMAS PRESCRIÇÕES ==\n";
$prescricoes = Prescricao::with('paciente')->orderBy('updated_at', 'desc')->take(5)->get();
foreach ($prescricoes as $p) {
    echo "  #{$p->id} | {$p->paciente?->nm_paciente} | clin:{$p->clinica_id} | sit:{$p->situacao} | sem:{$p->qt_semanas} | upd:{$p->updated_at}\n";
}

$alvo = $prescricoes->first();
if (! $alvo) {
    echo "Nenhuma prescrição encontrada.\n";
    exit;
}

echo "\n== PRESCRIÇÃO ALVO #{$alvo->id} — todas as semanas ==\n";
$semanas = PrescricaoSemana::with(['medicamentos.medicamento', 'userAplicacao', 'financeiroParcela'])
    ->where('prescricao_id', $alvo->id)
    ->orderBy('nr_semana')
    ->get();
foreach ($semanas as $s) {
    echo "  Sem {$s->nr_semana} | sit:{$s->situacao} | chegada:{$s->dt_hr_chegada} | atend:{$s->dt_hr_atendimento} | final:{$s->dt_hr_finalizacao} | enf:{$s->userAplicacao?->nome} | autorizador:{$s->autorizador_sem_pagamento} | tem_aplicacao:{$s->tem_aplicacao}\n";
}

echo "\n== DETALHE SEMANA 1 ==\n";
$semana1 = PrescricaoSemana::with([
    'prescricao.paciente', 'prescricao.clinica',
    'medicamentos.medicamento', 'medicamentos.combo', 'medicamentos.lotes.estoqueAberto',
    'userAplicacao', 'financeiroParcela',
])->where('prescricao_id', $alvo->id)->where('nr_semana', 1)->first();

if (! $semana1) {
    echo "Semana 1 não encontrada.\n";
    exit;
}

echo "Prescrição: #{$semana1->prescricao_id} | Paciente: {$semana1->prescricao->paciente?->nm_paciente} | Clínica: {$semana1->prescricao->clinica?->nome}\n";
echo "Semana: {$semana1->nr_semana} | Data prevista: {$semana1->data_prevista} | Situação: {$semana1->situacao}\n";
echo "Chegada: {$semana1->dt_hr_chegada} | Atendimento: {$semana1->dt_hr_atendimento} | Finalização: {$semana1->dt_hr_finalizacao}\n";
echo "Enfermeira: {$semana1->userAplicacao?->nome} (id {$semana1->user_id_aplicacao}) | Autorizador sem pagamento: {$semana1->autorizador_sem_pagamento}\n";
if ($semana1->financeiroParcela) {
    $fp = $semana1->financeiroParcela;
    echo "Parcela: #{$fp->id} | valor: {$fp->valor_parcela} | pago: {$fp->valor_pago} | situacao: {$fp->situacao}\n";
}
echo "Medicações:\n";
foreach ($semana1->medicamentos as $m) {
    $nome = $m->medicamento?->nome ?? ($m->combo?->nome ?? 'soro');
    echo "  - [{$m->id}] {$nome} | tipo:{$m->medicamento?->tipo} | qtd:{$m->quantidade} | gera_aplicacao:{$m->gera_aplicacao} | sit:{$m->situacao} | aplicado_em:{$m->aplicado_em} | enf:{$m->userAplicacao?->nome}\n";
    foreach ($m->lotes as $l) {
        echo "      lote: {$l->lote} | cod: {$l->codigo_barras} | qtd: {$l->quantidade} | frasco_id: {$l->estoque_aberto_id}\n";
    }
}

echo "\n== LOGS da prescrição (últimos 15) ==\n";
$logs = PrescricaoLog::with('user')->where('prescricao_id', $alvo->id)->orderBy('id', 'desc')->take(15)->get();
foreach ($logs as $log) {
    echo "  [{$log->created_at}] {$log->acao} | {$log->entidade}:{$log->entidade_id} | {$log->user?->nome} | {$log->descricao}\n";
}
