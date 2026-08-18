<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Medicamento;
use App\Models\EstoqueAberto;

echo "== MEDICAMENTOS 'mounjaro' ==\n";
$meds = Medicamento::where('nome', 'like', '%mounjaro%')->get(['id', 'nome', 'tipo', 'vasilhame', 'grupo_id']);
foreach ($meds as $m) {
    echo "  #{$m->id} | {$m->nome} | tipo: {$m->tipo} | vasilhame: {$m->vasilhame} | grupo: {$m->grupo_id}\n";
}
if ($meds->isEmpty()) {
    echo "  (nenhum — tentei 'mounjaro' no nome)\n";
    exit;
}

$ids = $meds->pluck('id');
$nomes = $meds->pluck('nome', 'id');

echo "\n== FRASCOS ABERTOS (situacao = Aberto) ==\n";
$abertos = EstoqueAberto::whereIn('medicamento_id', $ids)->where('situacao', 'Aberto')->get();
echo "Total abertos: {$abertos->count()}\n";
foreach ($abertos as $a) {
    $nome = $nomes[$a->medicamento_id] ?? "med:{$a->medicamento_id}";
    echo "  #{$a->id} | {$nome} | lote:{$a->lote} | cod:{$a->codigo_barras} | restante:{$a->qt_restante} | clin:{$a->clinica_id} | dt:{$a->dt_cadastro}\n";
}

echo "\n== TOTAL de frascos de Mounjaro (qualquer situação) ==\n";
echo EstoqueAberto::whereIn('medicamento_id', $ids)->count() . "\n";
