<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('listing_reports', 'report_group_id')) {
                $table->uuid('report_group_id')->nullable()->after('reporter_id');
                $table->index(['listing_id', 'report_group_id'], 'idx_listing_reports_group');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listing_reports', function (Blueprint $table) {
            if (Schema::hasColumn('listing_reports', 'report_group_id')) {
                $table->dropIndex('idx_listing_reports_group');
                $table->dropColumn('report_group_id');
            }
        });
    }
};
