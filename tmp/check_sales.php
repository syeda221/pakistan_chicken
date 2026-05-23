<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sales = \App\Models\Sale::all();
foreach($sales as $s) {
    echo "Sale ID: " . $s->id . " | Product Names: " . $s->product_names . "\n"; // Check column names
}
