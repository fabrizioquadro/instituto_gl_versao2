<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

echo "=== Users da V2 com imagem (por origem) ===\n";
$users = User::whereNotNull('imagem')->orderBy('origem_versao1')->orderBy('id')->get();
foreach ($users as $u) {
    echo "  id {$u->id} | origem {$u->origem_versao1} | imagem: " . ($u->imagem ?: '-') . " | nome: {$u->nome}\n";
}

// Verifica se algum nome de imagem é usado por mais de um user (colisão real)
echo "\n=== Colisões de nome de imagem entre pessoas diferentes ===\n";
$dups = User::whereNotNull('imagem')->get()->groupBy('imagem')->filter(fn ($g) => $g->count() > 1);
if ($dups->isEmpty()) {
    echo "  (nenhuma colisão — cada nome de imagem é único)\n";
} else {
    foreach ($dups as $img => $grupo) {
        echo "  $img usado por: " . $grupo->map(fn ($u) => "id {$u->id} ({$u->origem_versao1})")->implode(', ') . "\n";
    }
}
