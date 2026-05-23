<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Stock;
use App\Models\WarehouseStock;

$paties = Product::where('item_name', 'like', '%Paties%')->get();

if ($paties->isEmpty()) {
    echo "No products found with name like 'Paties'.\n";
}

foreach ($paties as $p) {
    echo "Product: {$p->item_name} (ID: {$p->id})\n";
    $stocks = Stock::where('product_id', $p->id)->get();
    foreach ($stocks as $s) {
        echo "  - Shop Stock: Qty: {$s->qty}, Variant ID: " . ($s->variant_id ?? 'NULL') . "\n";
    }
    $wStocks = WarehouseStock::where('product_id', $p->id)->get();
    foreach ($wStocks as $ws) {
        echo "  - Warehouse Stock (WH ID: {$ws->warehouse_id}): Qty: {$ws->quantity}, Variant ID: " . ($ws->variant_id ?? 'NULL') . "\n";
    }
}
