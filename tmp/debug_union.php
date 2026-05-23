<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

$startDate = '2026-03-01';
$endDate = '2026-03-31';

$purchaseQuery = DB::table('purchases')
    ->leftJoin('purchase_items', 'purchases.id', '=', 'purchase_items.purchase_id')
    ->leftJoin('products', 'purchase_items.product_id', '=', 'products.id')
    ->leftJoin('vendors', 'purchases.vendor_id', '=', 'vendors.id')
    ->select(
        DB::raw("'purchase' as source_type"),
        'purchases.purchase_date as purchase_date',
        'purchases.invoice_no',
        'vendors.name as vendor_name',
        'products.item_code',
        'products.item_name',
        'purchase_items.qty',
        'purchase_items.unit',
        'purchase_items.price',
        'purchase_items.item_discount',
        'purchase_items.line_total',
        'purchases.subtotal',
        'purchases.discount',
        'purchases.extra_cost',
        'purchases.net_amount',
        'purchases.paid_amount',
        'purchases.due_amount'
    );

if ($startDate && $endDate) {
    $purchaseQuery->whereBetween('purchases.purchase_date', [$startDate, $endDate]);
}

$inwardQuery = DB::table('inward_gatepasses')
    ->leftJoin('inward_gatepass_items', 'inward_gatepasses.id', '=', 'inward_gatepass_items.inward_gatepass_id')
    ->leftJoin('products', 'inward_gatepass_items.product_id', '=', 'products.id')
    ->leftJoin('vendors', 'inward_gatepasses.vendor_id', '=', 'vendors.id')
    ->where('inward_gatepasses.status', 'linked')
    ->where('inward_gatepasses.bill_status', 'billed')
    ->select(
        DB::raw("'inward' as source_type"),
        'inward_gatepasses.gatepass_date as purchase_date',
        'inward_gatepasses.invoice_no',
        'vendors.name as vendor_name',
        'products.item_code',
        'products.item_name',
        'inward_gatepass_items.qty',
        DB::raw('products.unit_id as unit'),
        DB::raw('COALESCE(inward_gatepass_items.price, products.wholesale_price) as price'), 
        'inward_gatepass_items.discount_value as item_discount',
        DB::raw('((COALESCE(inward_gatepass_items.price, products.wholesale_price) - COALESCE(inward_gatepass_items.discount_value, 0)) * inward_gatepass_items.qty) as line_total'),
        'inward_gatepasses.subtotal',
        'inward_gatepasses.discount',
        'inward_gatepasses.extra_cost',
        'inward_gatepasses.net_amount',
        'inward_gatepasses.paid_amount',
        'inward_gatepasses.due_amount'
    );

if ($startDate && $endDate) {
    $inwardQuery->whereBetween('gatepass_date', [$startDate, $endDate]);
}

try {
    echo "Running Purchase Query...\n";
    $p = $purchaseQuery->get();
    echo "Purchase: " . count($p) . " rows.\n";
    
    echo "Running Inward Query...\n";
    $i = $inwardQuery->get();
    echo "Inward: " . count($i) . " rows.\n";
    
    echo "Running Union Query...\n";
    $u = $purchaseQuery->unionAll($inwardQuery)->get();
    echo "Union: " . count($u) . " rows.\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
