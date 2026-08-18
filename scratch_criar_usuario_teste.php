<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// Usuário de teste da V2 (sem origem_versao1 -> claramente um usuário V2)
$email = 'admin@institutogl.test';
$user = User::where('email', $email)->first();

if ($user) {
    echo "Usuário de teste já existe (id {$user->id}).\n";
} else {
    $user = User::create([
        'nome' => 'Admin Teste V2',
        'email' => $email,
        'password' => Hash::make('12345678'),
        'role' => 'admin',
        'ativo' => true,
        'dashboard_secretaria' => true,
        'dashboard_enfermagem' => true,
        'controle_medicamentos' => true,
        'pacientes' => true,
        'procedimentos' => true,
        'financeiro' => true,
    ]);
    echo "Usuário de teste criado (id {$user->id}).\n";
}
