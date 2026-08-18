<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$v1 = DB::connection('mysql_versao1');

$emailsUsers = $v1->table('users')->pluck('email')->map(fn ($e) => strtolower(trim($e)))->filter();
$emailsAdmins = $v1->table('administradors')->pluck('email')->map(fn ($e) => strtolower(trim($e)))->filter();
$conflitos = $emailsUsers->intersect($emailsAdmins)->unique()->values();

echo "E-mails duplicados: " . $conflitos->count() . "\n\n";

foreach ($conflitos as $email) {
    echo "================ $email ================\n";

    $u = $v1->table('users')->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
    if ($u) {
        echo "  [users] id: {$u->id} | nome: {$u->nome} | tipo: {$u->tipo} | st: {$u->st_usuario} | clinica_id: " . var_export($u->clinica_id, true) . "\n";
    }

    $a = $v1->table('administradors')->whereRaw('LOWER(TRIM(email)) = ?', [$email])->first();
    if ($a) {
        echo "  [administradors] id: {$a->id} | nome: {$a->nome} | st: {$a->st_usuario}\n";
    }
    echo "\n";
}
