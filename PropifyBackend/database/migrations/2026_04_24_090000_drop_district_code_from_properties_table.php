<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('properties', 'district_code')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table): void {
            // Composite index was created in create_properties_table migration.
            try {
                $table->dropIndex('idx_properties_region');
            } catch (\Throwable $e) {
                // Ignore when index does not exist.
            }

            $table->dropColumn('district_code');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('properties', 'district_code')) {
            return;
        }

        Schema::table('properties', function (Blueprint $table): void {
            $table->string('district_code', 20)->after('province_code');
            $table->index(['province_code', 'district_code'], 'idx_properties_region');
        });
    }
};
