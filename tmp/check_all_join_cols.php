<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

foreach(['inward_gatepasses', 'inward_gatepass_items', 'products', 'vendors'] as $tbl) {
    echo "Table: $tbl\n";
    $res = DB::select("DESCRIBE $tbl");
    foreach($res as $r) {
        if (strpos($r->Field, 'gatepass') !== false || strpos($r->Field, 'date') !== false) {
            echo "  Column: " . $r->Field . "\n";
        }
    }
}
