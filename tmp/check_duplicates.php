<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$duplicates = DB::table('stocks')
    ->select('product_id', 'variant_id', 'branch_id', 'warehouse_id', DB::raw('count(*) as count'), DB::raw('sum(qty) as total_qty'))
    ->groupBy('product_id', 'variant_id', 'branch_id', 'warehouse_id')
    ->having('count', '>', 1)
    ->get();

if($duplicates->count() > 0) {
    echo "Found Duplicate Stock Records:\n";
    foreach($duplicates as $d) {
        $p = \App\Models\Product::find($d->product_id);
        $v = \App\Models\ProductVariant::find($d->variant_id);
        echo "Product: " . ($p ? $p->item_name : 'N/A') . " (ID: " . $d->product_id . ")\n";
        echo "Variant: " . ($v ? $v->variant_name : 'None') . " (ID: " . ($d->variant_id ?? 'N/A') . ")\n";
        echo "Branch/WH: " . $d->branch_id . "/" . $d->warehouse_id . "\n";
        echo "Count: " . $d->count . " | Total Qty: " . $d->total_qty . "\n";
        
        $records = DB::table('stocks')
            ->where('product_id', $d->product_id)
            ->where('variant_id', $d->variant_id)
            ->where('branch_id', $d->branch_id)
            ->where('warehouse_id', $d->warehouse_id)
            ->get();
        foreach($records as $r) {
            echo "  - ID: " . $r->id . " | Qty: " . $r->qty . "\n";
        }
        echo "-------------------\n";
    }
} else {
    echo "No duplicate stock records found.\n";
}
