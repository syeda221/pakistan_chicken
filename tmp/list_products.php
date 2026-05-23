<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\Product::select('id', 'item_name')->get();
foreach($products as $p) {
    echo $p->id . ": " . $p->item_name . "\n";
}
