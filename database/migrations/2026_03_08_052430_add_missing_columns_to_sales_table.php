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
        Schema::table('sales', function (Blueprint $table) {
            $table->string('invoice_no')->nullable()->after('id');
            $table->string('total_pieces')->nullable()->after('total_items');
            $table->string('total_meter')->nullable()->after('total_pieces');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['invoice_no', 'total_pieces', 'total_meter']);
        });
    }
};
