<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "Migrations executadas: " . DB::table('migrations')->count() . "\n";
foreach (DB::table('migrations')->orderBy('id')->get() as $m) {
    echo "  - {$m->migration}\n";
}

echo "\nClinicas (colunas): " . implode(', ', Schema::getColumnListing('clinicas')) . "\n";
echo "Clinicas (registros): " . DB::table('clinicas')->count() . "\n";
echo "Users (registros): " . DB::table('users')->count() . "\n";
