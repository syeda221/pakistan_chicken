<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$searchTerm = 'BRADE';
$products = \App\Models\Product::where('item_name', 'like', '%' . $searchTerm . '%')
    ->orWhere('item_name', 'like', '%BREAD%')
    ->get();

if($products->count() > 0) {
    foreach($products as $p) {
        echo "Product: " . $p->item_name . " (ID: " . $p->id . ")\n";
        echo "Total Sum Stock (withSum): " . \App\Models\Stock::where('product_id', $p->id)->sum('qty') . "\n";
        echo "Stocks Breakdown:\n";
        foreach(\App\Models\Stock::where('product_id', $p->id)->get() as $s) {
            $variant = \App\Models\ProductVariant::find($s->variant_id);
            $vName = $variant ? $variant->variant_name : 'None';
            echo " - Branch: " . $s->branch_id . ", WH: " . $s->warehouse_id . ", Variant: " . $vName . " (ID: " . ($s->variant_id ?? 'N/A') . "), Qty: " . $s->qty . "\n";
        }
        echo "-------------------\n";
    }
} else {
    echo "No matching products found for '$searchTerm' or 'BREAD'\n";
}
