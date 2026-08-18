<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\DB;

echo "=== CLÍNICAS (" . DB::table('clinicas')->count() . ") ===\n";
foreach (DB::table('clinicas')->orderBy('id')->get() as $c) {
    echo "  id {$c->id} (v1 {$c->id_versao1}) | {$c->nome}\n";
}

echo "\n=== USERS (" . DB::table('users')->count() . ") ===\n";
$porRole = User::select('role', DB::raw('count(*) as total'))->groupBy('role')->get();
foreach ($porRole as $r) {
    echo "  role {$r->role}: {$r->total}\n";
}

echo "\n=== Origem ===\n";
$porOrigem = User::select('origem_versao1', DB::raw('count(*) as total'))->groupBy('origem_versao1')->get();
foreach ($porOrigem as $o) {
    echo "  origem {$o->origem_versao1}: {$o->total}\n";
}

echo "\n=== Pessoas que estavam duplicadas (devem ser só admin) ===\n";
$dups = ['bruna.westhofer@institutogl.com', 'karol.institutogl@gmail.com', 'luara.resende@institutogl.com', 'manoela.saraiva@institutogl.com'];
foreach (User::whereIn('email', $dups)->orderBy('email')->get() as $u) {
    echo "  {$u->email} -> id {$u->id} | role {$u->role} | origem {$u->origem_versao1} (v1 {$u->id_versao1})\n";
}

echo "\n=== Ids preservados? (primeiros 5 users de origem users) ===\n";
foreach (User::where('origem_versao1', 'users')->orderBy('id')->take(5)->get() as $u) {
    echo "  id {$u->id} (v1 {$u->id_versao1}) | {$u->email} | role {$u->role} | clinica {$u->clinica_id}\n";
}
