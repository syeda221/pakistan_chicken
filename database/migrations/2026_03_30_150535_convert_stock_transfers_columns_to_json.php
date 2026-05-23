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
        Schema::table('stock_transfers', function (Blueprint $table) {
            // Check if foreign key exists before dropping
            $foreignKeys = $this->getForeignKeys('stock_transfers');
            if (in_array('stock_transfers_product_id_foreign', $foreignKeys)) {
                 $table->dropForeign(['product_id']);
            }

            // Check if index exists before dropping
            $indexes = $this->getIndexes('stock_transfers');
            if (in_array('stock_transfers_product_id_index', $indexes) || in_array('product_id', $indexes)) {
                try {
                    $table->dropIndex(['product_id']);
                } catch (\Exception $e) {}
            }

            // Change product_id and quantity to longText/json
            $table->longText('product_id')->change();
            $table->longText('quantity')->change();
        });
    }

    private function getForeignKeys($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        return array_keys($conn->listTableForeignKeys($table));
    }

    private function getIndexes($table)
    {
        $conn = Schema::getConnection()->getDoctrineSchemaManager();
        return array_keys($conn->listTableIndexes($table));
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
