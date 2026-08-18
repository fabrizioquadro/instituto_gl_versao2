<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$tokens = DB::table('password_reset_tokens')->get();
echo "Tokens de reset criados: " . $tokens->count() . "\n";
foreach ($tokens as $t) {
    echo "  email: {$t->email} | token: " . substr($t->token, 0, 20) . "... | criado: {$t->created_at}\n";
}
