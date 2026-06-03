<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listing_reports', function (Blueprint $table) {
            if (! Schema::hasColumn('listing_reports', 'image_urls')) {
                $table->json('image_urls')->nullable()->after('description');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listing_reports', function (Blueprint $table) {
            if (Schema::hasColumn('listing_reports', 'image_urls')) {
                $table->dropColumn('image_urls');
            }
        });
    }
};
