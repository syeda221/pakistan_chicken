<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "--- stock_transfers ---\n";
foreach(DB::select('DESCRIBE stock_transfers') as $col) {
    echo "{$col->Field}: {$col->Type}\n";
}

echo "\n--- warehouse_stocks ---\n";
foreach(DB::select('DESCRIBE warehouse_stocks') as $col) {
    echo "{$col->Field}: {$col->Type}\n";
}
