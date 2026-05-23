<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$problematic = DB::table('stocks')
    ->whereNull('variant_id')
    ->where('qty', '<', 0)
    ->get();

if($problematic->count() > 0) {
    echo "Found " . $problematic->count() . " products with negative 'No-Variant' stock:\n";
    foreach($problematic as $s) {
        $p = \App\Models\Product::find($s->product_id);
        echo " - " . ($p ? $p->item_name : 'Unknown') . " (ID: " . $s->product_id . "): " . $s->qty . "\n";
    }
    echo "\nWould you like to merge these into the main variants to correct the stock?\n";
} else {
    echo "No negative 'No-Variant' stock found.\n";
}
