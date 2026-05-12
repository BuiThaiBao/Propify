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
        if (Schema::hasColumn('packages', 'push_price')) {
            try {
                Schema::table('packages', function (Blueprint $table) {
                    $table->renameColumn('push_price', 'price');
                });
            } catch (\Exception $e) {}
        }

        if (Schema::hasColumn('listings', 'pushed_at')) {
            try {
                Schema::table('listings', function (Blueprint $table) {
                    $table->dropIndex(['pushed_at']);
                    $table->dropColumn('pushed_at');
                });
            } catch (\Exception $e) {}
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('packages', 'price')) {
            try {
                Schema::table('packages', function (Blueprint $table) {
                    $table->renameColumn('price', 'push_price');
                });
            } catch (\Exception $e) {}
        }

        if (!Schema::hasColumn('listings', 'pushed_at')) {
            try {
                Schema::table('listings', function (Blueprint $table) {
                    $table->timestamp('pushed_at')->nullable()->after('package_expires_at');
                    $table->index('pushed_at');
                });
            } catch (\Exception $e) {}
        }
    }
};
