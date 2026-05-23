<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $res = DB::select("SELECT gatepass_date FROM inward_gatepasses LIMIT 5");
    var_dump($res);
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
