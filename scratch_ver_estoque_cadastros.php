<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$v1 = DB::connection('mysql_versao1');

$tabelas = ['grupos', 'medicamentos', 'combos', 'combo_medicamentos'];

foreach ($tabelas as $tabela) {
    if (Schema::connection('mysql_versao1')->hasTable($tabela)) {
        echo "$tabela: " . $v1->table($tabela)->count() . " registros\n";
    } else {
        echo "$tabela: TABELA NÃO EXISTE\n";
    }
}

echo "\n=== Situação dos medicamentos ===\n";
if (Schema::connection('mysql_versao1')->hasTable('medicamentos')) {
    $situacoes = $v1->table('medicamentos')->select('situacao')->groupBy('situacao')->get();
    foreach ($situacoes as $s) {
        echo "  situacao {$s->situacao}: " . $v1->table('medicamentos')->where('situacao', $s->situacao)->count() . "\n";
    }
    $aplicacoes = $v1->table('medicamentos')->select('aplicacao')->groupBy('aplicacao')->get();
    foreach ($aplicacoes as $a) {
        echo "  aplicacao {$a->aplicacao}: " . $v1->table('medicamentos')->where('aplicacao', $a->aplicacao)->count() . "\n";
    }
}
