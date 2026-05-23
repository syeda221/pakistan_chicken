<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Products ---\n";
$products = \App\Models\Product::all();
foreach($products as $p) {
    echo "ID: " . $p->id . " | Name: " . $p->item_name . "\n";
}

echo "\n--- Variants ---\n";
$variants = \App\Models\ProductVariant::all();
foreach($variants as $v) {
    echo "ID: " . $v->id . " | Product ID: " . $v->product_id . " | Name: " . $v->variant_name . "\n";
}
