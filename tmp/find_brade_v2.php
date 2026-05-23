<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$variants = \App\Models\ProductVariant::where('variant_name', 'like', '%BRADE%')
    ->orWhere('variant_name', 'like', '%BREAD%')
    ->get();

foreach($variants as $v) {
    $p = $v->product;
    echo "Found Variant: " . $v->variant_name . " (ID: " . $v->id . ") for Product: " . ($p ? $p->item_name : 'N/A') . " (ID: " . ($p ? $p->id : 'N/A') . ")\n";
    
    if($p) {
        $totalSum = \App\Models\Stock::where('product_id', $p->id)->sum('qty');
        echo " - Product Total Sum Stock: " . $totalSum . "\n";
        
        $variantStocks = \App\Models\Stock::where('product_id', $p->id)->get();
        foreach($variantStocks as $vs) {
             $vn = \App\Models\ProductVariant::find($vs->variant_id);
             echo "   - Stock Record: Variant: " . ($vn ? $vn->variant_name : 'None') . " | Qty: " . $vs->qty . " | Branch: " . $vs->branch_id . " | WH: " . $vs->warehouse_id . "\n";
        }
    }
}
