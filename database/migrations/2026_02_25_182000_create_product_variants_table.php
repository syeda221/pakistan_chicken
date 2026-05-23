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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('product_id');
            $table->string('variant_name', 255)->nullable();   // e.g. "1 Pound", "2 Pound", "500g"
            $table->string('size_label', 100)->nullable();      // Display label like "1 Pound", "Half KG"
            $table->decimal('size_value', 10, 2)->default(0);   // numeric value (1, 2, 0.5, 500)
            $table->string('size_unit', 50)->nullable();        // "pound", "kg", "gram", "piece"
            $table->decimal('price', 12, 2)->default(0);        // Retail/Sale price for this variant
            $table->decimal('wholesale_price', 12, 2)->default(0); // Wholesale price for this variant
            $table->decimal('cost_price', 12, 2)->default(0);   // Purchase/Cost price
            $table->decimal('stock_qty', 10, 2)->default(0);    // Current stock of this variant
            $table->integer('alert_quantity')->default(0);
            $table->boolean('is_default')->default(false);      // Mark one variant as default
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
