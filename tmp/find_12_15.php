<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = \App\Models\Product::all();
foreach($products as $p) {
    $total = \App\Models\Stock::where('product_id', $p->id)
        ->where('branch_id', 1)
        ->where('warehouse_id', 1)
        ->sum('qty');
    
    $variants = \App\Models\ProductVariant::where('product_id', $p->id)->get();
    foreach($variants as $v) {
        $vStock = \App\Models\Stock::where('product_id', $p->id)
            ->where('variant_id', $v->id)
            ->where('branch_id', 1)
            ->where('warehouse_id', 1)
            ->value('qty') ?? 0;
            
        if(($total == 15 && $vStock == 12) || ($total == 12 && $vStock == 15) || $total == 15 || $vStock == 15 || $total == 12 || $vStock == 12) {
            echo "Product: " . $p->item_name . " (ID: " . $p->id . ")\n";
            echo " - Total (Sum): " . $total . "\n";
            echo " - Variant " . $v->variant_name . " (ID: " . $v->id . "): " . $vStock . "\n";
        }
    }
    
    // Check no-variant stock too
    $nullStock = \App\Models\Stock::where('product_id', $p->id)
        ->whereNull('variant_id')
        ->where('branch_id', 1)
        ->where('warehouse_id', 1)
        ->value('qty') ?? 0;
    if($nullStock != 0) {
        echo "Product: " . $p->item_name . " (ID: " . $p->id . ") - Null Variant Stock: " . $nullStock . "\n";
    }
}
