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
        Schema::table('product_bookings', function (Blueprint $table) {
            if (!Schema::hasColumn('product_bookings', 'variant_id')) {
                $table->text('variant_id')->nullable()->after('total_items');
            }
            if (!Schema::hasColumn('product_bookings', 'total_pieces')) {
                $table->text('total_pieces')->nullable()->after('variant_id');
            }
            if (!Schema::hasColumn('product_bookings', 'total_meter')) {
                $table->text('total_meter')->nullable()->after('total_pieces');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_bookings', function (Blueprint $table) {
            $table->dropColumn(['variant_id', 'total_pieces', 'total_meter']);
        });
    }
};
