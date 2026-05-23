<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "Database: " . DB::getDatabaseName() . "\n";
$tables = DB::select('SHOW TABLES');
foreach($tables as $table) {
    foreach($table as $key => $value) {
        echo $value . "\n";
    }
}
