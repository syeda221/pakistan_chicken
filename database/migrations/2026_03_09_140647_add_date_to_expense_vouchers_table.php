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
        if (!Schema::hasColumn('expense_vouchers', 'date')) {
            Schema::table('expense_vouchers', function (Blueprint $table) {
                $table->date('date')->nullable()->after('entry_date');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('expense_vouchers', 'date')) {
            Schema::table('expense_vouchers', function (Blueprint $table) {
                $table->dropColumn('date');
            });
        }
    }
};
