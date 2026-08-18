<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;

$email = 'admin_teste_v2@institutogl.test';
$user = User::where('email', $email)->first();

if ($user) {
    echo "Admin de teste já existe (id {$user->id}).\n";
} else {
    $user = User::create([
        'nome' => 'Admin Teste V2',
        'email' => $email,
        'password' => Hash::make('AdminTeste123'),
        'role' => 'admin',
        'ativo' => true,
    ]);
    echo "Admin de teste criado (id {$user->id}).\n";
}
