<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\PrescricaoSemana;
use App\Models\AplicacaoLote;
use App\Models\Estoque;
use App\Models\EstoqueSaldo;

echo "== SEMANA 52 ==\n";
$semana = PrescricaoSemana::with('medicamentos.medicamento', 'medicamentos.lotes')->where('id', 52)->first();
if (! $semana) {
    echo "Semana 52 não encontrada\n";
    exit;
}
echo "Situação: {$semana->situacao}\n";

foreach ($semana->medicamentos as $med) {
    echo "\nMed: [{$med->id}] {$med->medicamento?->nome} | sit: {$med->situacao}\n";
    foreach ($med->lotes as $l) {
        echo "  Lote: {$l->lote} | cod: {$l->codigo_barras} | qtd: {$l->quantidade}\n";
        $saldo = EstoqueSaldo::where('codigo_barras', $l->codigo_barras)->where('lote', $l->lote)->first();
        echo "    EstoqueSaldo: " . ($saldo ? "saldo={$saldo->saldo} venc={$saldo->dt_vencimento}" : "NÃO EXISTE (deletado ao zerar)") . "\n";
        $movs = Estoque::where('codigo_barras', $l->codigo_barras)->where('lote', $l->lote)->get(['tipo', 'quantidade', 'dt_vencimento', 'origem', 'created_at']);
        foreach ($movs as $m) {
            echo "    Estoque mov: {$m->tipo} qtd={$m->quantidade} venc={$m->dt_vencimento} origem={$m->origem}\n";
        }
    }
}
