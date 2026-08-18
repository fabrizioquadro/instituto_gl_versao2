<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Password;

$email = 'teste@teste.com.br';
$user = User::where('email', $email)->first();

if (! $user) {
    echo "Usuário {$email} não encontrado.\n";
    exit(1);
}

$token = Password::broker()->createToken($user);
echo "URL de reset (teste):\n";
echo url('password/reset/' . $token . '?email=' . urlencode($email)) . "\n";
