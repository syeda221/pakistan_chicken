<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$count = DB::table('products')->count();
echo "Product Count: " . $count . "\n";

$products = DB::table('products')->get();
foreach($products as $p) {
    echo "ID: " . $p->id . " | Name: " . $p->item_name . "\n";
}
