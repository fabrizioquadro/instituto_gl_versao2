<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$comImagem = User::whereNotNull('imagem')->get();
echo "Usuários V2 com imagem na coluna: " . $comImagem->count() . "\n";
foreach ($comImagem as $u) {
    echo "  id {$u->id} | origem {$u->origem_versao1} (v1 {$u->id_versao1}) | imagem: {$u->imagem}\n";
}
