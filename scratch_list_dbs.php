<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach (DB::select('SHOW DATABASES') as $d) {
    echo $d->Database . PHP_EOL;
}
