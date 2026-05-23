<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$products = [3, 5]; // Chocolate Cake, Chicken Cake
foreach($products as $pid) {
    $nullStock = \App\Models\Stock::where('product_id', $pid)
        ->whereNull('variant_id')
        ->where('branch_id', 1)
        ->where('warehouse_id', 1)
        ->first();

    if($nullStock && $nullStock->qty != 0) {
        $defaultVariant = \App\Models\ProductVariant::where('product_id', $pid)
            ->where('is_default', 1)
            ->first() ?? \App\Models\ProductVariant::where('product_id', $pid)->first();

        if($defaultVariant) {
            $vStock = \App\Models\Stock::where('product_id', $pid)
                ->where('variant_id', $defaultVariant->id)
                ->where('branch_id', 1)
                ->where('warehouse_id', 1)
                ->first();

            if($vStock) {
                echo "Merging " . $nullStock->qty . " from Null to Variant '" . $defaultVariant->variant_name . "' for Product ID $pid\n";
                $vStock->qty += $nullStock->qty;
                $vStock->save();
                
                // Set null stock to 0
                $nullStock->qty = 0;
                $nullStock->save();
            }
        }
    }
}
echo "Stock cleanup complete.\n";
