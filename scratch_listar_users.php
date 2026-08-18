<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$users = User::orderBy('id')->get();
echo "Total de users na V2: " . $users->count() . "\n";
foreach ($users as $u) {
    echo "  id {$u->id} | {$u->email} | role {$u->role} | origem " . ($u->origem_versao1 ?? '-') . "\n";
}
