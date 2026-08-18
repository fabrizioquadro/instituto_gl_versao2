<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$email = 'admin@institutogl.test';
$user = User::where('email', $email)->first();

if ($user) {
    $user->delete();
    echo "Usuário de teste removido (id {$user->id}).\n";
} else {
    echo "Usuário de teste não encontrado (nada a remover).\n";
}
