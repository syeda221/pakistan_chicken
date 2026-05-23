<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Current Database: " . DB::getDatabaseName() . "\n";

$res = DB::select("DESCRIBE stock_transfers");
foreach($res as $r) {
    if ($r->Field === 'product_id' || $r->Field === 'quantity') {
        print_r($r);
    }
}

echo "Indices on stock_transfers:\n";
$indices = DB::select("SHOW INDEX FROM stock_transfers");
foreach($indices as $idx) {
    echo $idx->Key_name . " on " . $idx->Column_name . "\n";
}

echo "Foreign keys on stock_transfers via Information Schema:\n";
$fks = DB::select("SELECT CONSTRAINT_NAME, COLUMN_NAME FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'stock_transfers' AND REFERENCED_TABLE_NAME IS NOT NULL");
foreach($fks as $fk) {
    echo $fk->CONSTRAINT_NAME . " on " . $fk->COLUMN_NAME . "\n";
}
