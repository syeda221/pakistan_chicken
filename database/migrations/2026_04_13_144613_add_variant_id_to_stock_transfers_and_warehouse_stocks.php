<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->longText('variant_id')->nullable()->after('product_id');
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
            
            // If you want to enforce uniqueness per variant in warehouse
            // $table->unique(['warehouse_id', 'product_id', 'variant_id'], 'warehouse_product_variant_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropColumn('variant_id');
        });

        Schema::table('warehouse_stocks', function (Blueprint $table) {
            // $table->dropUnique('warehouse_product_variant_unique');
            $table->dropColumn('variant_id');
        });
    }
};
