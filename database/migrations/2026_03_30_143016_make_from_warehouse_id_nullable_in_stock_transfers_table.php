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
            // Drop foreign key first
            $table->dropForeign(['from_warehouse_id']);
            // Make nullable
            $table->unsignedBigInteger('from_warehouse_id')->nullable()->change();
            // Re-add foreign key
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['from_warehouse_id']);
            $table->unsignedBigInteger('from_warehouse_id')->nullable(false)->change();
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('cascade');
        });
    }
};
