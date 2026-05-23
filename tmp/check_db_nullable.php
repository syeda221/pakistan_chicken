<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$res = DB::select("DESCRIBE stock_transfers");
foreach($res as $r) {
    if ($r->Field === 'from_warehouse_id') {
        print_r($r);
    }
}
