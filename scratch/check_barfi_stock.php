<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Stock;

$p = Product::where('item_name', 'like', '%barfi%')->first();
if (!$p) {
    echo "No product found with name 'barfi'.\n";
    exit;
}

echo "Product: {$p->item_name} (ID: {$p->id}, Unit: {$p->unit_type})\n";
$stocks = Stock::where('product_id', $p->id)->get();
foreach ($stocks as $s) {
    echo "  - Shop Stock: Qty: {$s->qty}, Variant ID: " . ($s->variant_id ?? 'NULL') . "\n";
}
