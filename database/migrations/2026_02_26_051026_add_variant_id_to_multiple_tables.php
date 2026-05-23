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
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
        });

        Schema::table('production_entry_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
        });

        Schema::table('inward_gatepass_items', function (Blueprint $table) {
            $table->unsignedBigInteger('variant_id')->nullable()->after('product_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->text('variant_id')->nullable()->after('product'); // For CSV storing
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stocks', function (Blueprint $table) {
            $table->dropColumn('variant_id');
        });

        Schema::table('purchase_items', function (Blueprint $table) {
            $table->dropColumn('variant_id');
        });

        Schema::table('production_entry_items', function (Blueprint $table) {
            $table->dropColumn('variant_id');
        });

        Schema::table('inward_gatepass_items', function (Blueprint $table) {
            $table->dropColumn('variant_id');
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('variant_id');
        });
    }
};
