<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$res = DB::select("SHOW TABLES");
foreach($res as $r) {
    foreach($r as $k => $v) {
        if (strpos($v, 'inward') !== false) {
            echo $v . "\n";
        }
    }
}
