<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->id();
            $table->string('ref_no')->unique();              // e.g. ADJ-20260305-001
            $table->date('adjustment_date');
            $table->enum('type', ['increase', 'decrease']); // increase=add, decrease=remove
            $table->string('reason');                        // wastage, mix sale, correction, other
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('adjustment_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('variant_id')->nullable();
            $table->string('unit')->nullable();
            $table->decimal('qty', 12, 3);        // qty entered by user (kg or pcs)
            $table->decimal('qty_stock', 12, 3);  // actual qty applied to stock (grams if kg)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('adjustment_id')->references('id')->on('stock_adjustments')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
        Schema::dropIfExists('stock_adjustments');
    }
};
