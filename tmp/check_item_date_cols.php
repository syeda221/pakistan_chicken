<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$res = DB::select("DESCRIBE inward_gatepass_items");
foreach($res as $r) {
    if (strpos($r->Field, 'date') !== false) {
        var_dump($r->Field);
    }
}
