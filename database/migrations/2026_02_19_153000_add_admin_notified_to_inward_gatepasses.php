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
        Schema::table('inward_gatepasses', function (Blueprint $table) {
            if (!Schema::hasColumn('inward_gatepasses', 'admin_notified')) {
                $table->boolean('admin_notified')->default(0);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inward_gatepasses', function (Blueprint $table) {
            $table->dropColumn('admin_notified');
        });
    }
};
