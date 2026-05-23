<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\Product::all();
foreach($products as $p) {
    $stocks = \App\Models\Stock::where('product_id', $p->id)->get();
    $total = $stocks->sum('qty');
    
    $hasNegative = false;
    foreach($stocks as $s) if($s->qty < 0) $hasNegative = true;
    
    if($hasNegative || $total != 0) {
        echo "Product: " . $p->item_name . " (ID: " . $p->id . ")\n";
        echo "Total Stock (Sum): " . $total . "\n";
        foreach($stocks as $s) {
            $v = \App\Models\ProductVariant::find($s->variant_id);
            echo " - Variant: " . ($v ? $v->variant_name : 'None') . " | Qty: " . $s->qty . " | WH: " . $s->warehouse_id . "\n";
        }
        echo "-------------------\n";
    }
}
