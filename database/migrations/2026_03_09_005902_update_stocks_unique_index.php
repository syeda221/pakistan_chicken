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
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique('stocks_unique_triplet');
            $table->unique(['branch_id', 'warehouse_id', 'product_id', 'variant_id'], 'stocks_unique_variant');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropUnique('stocks_unique_variant');
            $table->unique(['branch_id', 'warehouse_id', 'product_id'], 'stocks_unique_triplet');
        });
    }
};
