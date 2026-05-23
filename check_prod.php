<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$items = DB::table('production_entry_items as pei')
    ->leftJoin('products as p', 'p.id', '=', 'pei.product_id')
    ->where('pei.production_entry_id', 1)
    ->select('pei.unit', 'pei.qty_entered', 'pei.qty_stock', 'p.item_name', 'p.unit_type')
    ->get();

foreach ($items as $i) {
    echo $i->item_name . ' | unit=' . $i->unit . ' | unit_type=' . $i->unit_type . ' | entered=' . $i->qty_entered . ' | stock=' . $i->qty_stock . PHP_EOL;
}
