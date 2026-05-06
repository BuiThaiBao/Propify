<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            // Composite index cho public listing query:
            // WHERE status = 'ACTIVE' AND demand_type = 'SALE' ORDER BY id DESC
            // Covering index: (status, demand_type, id DESC) giúp MySQL dùng index scan
            // thay vì full table scan + filesort
            if (!$this->indexExists('listings', 'idx_listings_status_demand_id')) {
                $table->index(['status', 'demand_type', 'id'], 'idx_listings_status_demand_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex('idx_listings_status_demand_id');
        });
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $indexes = Schema::getIndexes($table);
        foreach ($indexes as $index) {
            if ($index['name'] === $indexName) {
                return true;
            }
        }
        return false;
    }
};
