<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        try {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->dropForeign(['product_id']);
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->dropIndex('stock_transfers_product_id_index');
            });
        } catch (\Exception $e) {}

        try {
            Schema::table('stock_transfers', function (Blueprint $table) {
                $table->dropIndex(['product_id']);
            });
        } catch (\Exception $e) {}

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->longText('product_id')->change();
            $table->longText('quantity')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('product_id')->change();
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->integer('quantity')->change();
        });
    }
};
