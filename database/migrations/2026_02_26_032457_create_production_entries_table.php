<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_entries', function (Blueprint $table) {
            $table->id();
            $table->string('entry_no')->unique();                  // e.g. PROD-2026-001
            $table->date('production_date');
            $table->string('source')->default('kitchen');          // kitchen / warehouse
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('production_entry_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_entry_id');
            $table->unsignedBigInteger('product_id');
            $table->string('unit')->nullable();                    // kg / gram / piece / liter
            $table->decimal('qty_entered', 12, 3);                 // what user typed
            $table->decimal('qty_kg', 12, 3)->default(0);          // converted to kg (if gram-based)
            $table->decimal('qty_stock', 12, 3);                   // qty added to stock
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('production_entry_id')
                  ->references('id')->on('production_entries')->onDelete('cascade');
            $table->foreign('product_id')
                  ->references('id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_entry_items');
        Schema::dropIfExists('production_entries');
    }
};
