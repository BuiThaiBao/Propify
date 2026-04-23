<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            if (!Schema::hasColumn('listings', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->after('property_id')->constrained('users')->cascadeOnDelete();
                $table->index('owner_id', 'idx_listings_owner_id');
            }
        });

        if (Schema::hasColumn('listings', 'owner_id') && Schema::hasColumn('properties', 'owner_id')) {
            $listingOwners = DB::table('listings')
                ->join('properties', 'properties.id', '=', 'listings.property_id')
                ->whereNotNull('properties.owner_id')
                ->whereNull('listings.owner_id')
                ->select('listings.id as listing_id', 'properties.owner_id as owner_id')
                ->get();

            foreach ($listingOwners as $row) {
                DB::table('listings')
                    ->where('id', $row->listing_id)
                    ->update(['owner_id' => $row->owner_id]);
            }
        }

        Schema::table('properties', function (Blueprint $table) {
            if (Schema::hasColumn('properties', 'owner_id')) {
                try {
                    $table->dropForeign(['owner_id']);
                } catch (\Throwable) {
                    // Ignore if foreign key does not exist in current DB state.
                }

                $table->dropColumn('owner_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (!Schema::hasColumn('properties', 'owner_id')) {
                $table->foreignId('owner_id')->nullable()->first()->constrained('users')->cascadeOnDelete();
                $table->index('owner_id', 'idx_properties_owner_id');
            }
        });

        if (Schema::hasColumn('properties', 'owner_id') && Schema::hasColumn('listings', 'owner_id')) {
            $rows = DB::table('listings')
                ->whereNotNull('owner_id')
                ->orderBy('id')
                ->get(['property_id', 'owner_id']);

            foreach ($rows as $row) {
                DB::table('properties')
                    ->where('id', $row->property_id)
                    ->whereNull('owner_id')
                    ->update(['owner_id' => $row->owner_id]);
            }
        }

        Schema::table('listings', function (Blueprint $table) {
            if (Schema::hasColumn('listings', 'owner_id')) {
                try {
                    $table->dropForeign(['owner_id']);
                } catch (\Throwable) {
                    // Ignore if foreign key does not exist in current DB state.
                }

                $table->dropIndex('idx_listings_owner_id');
                $table->dropColumn('owner_id');
            }
        });
    }
};