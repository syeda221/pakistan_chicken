<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    echo "Dropping foreign key...\n";
    DB::statement('ALTER TABLE stock_transfers DROP FOREIGN KEY stock_transfers_product_id_foreign');
} catch (\Exception $e) {
    echo "Foreign key drop failed: " . $e->getMessage() . "\n";
}

try {
    echo "Dropping index...\n";
    DB::statement('ALTER TABLE stock_transfers DROP INDEX stock_transfers_product_id_foreign');
} catch (\Exception $e) {
    echo "Index drop failed: " . $e->getMessage() . "\n";
}

try {
    echo "Changing columns...\n";
    Schema::table('stock_transfers', function (Blueprint $table) {
        $table->longText('product_id')->change();
        $table->longText('quantity')->change();
    });
    echo "Columns changed!\n";
} catch (\Exception $e) {
    echo "Column change failed: " . $e->getMessage() . "\n";
}
