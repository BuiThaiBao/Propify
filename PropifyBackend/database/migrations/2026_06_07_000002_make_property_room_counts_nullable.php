<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('ALTER TABLE properties MODIFY bedrooms INT NULL DEFAULT NULL');
        DB::statement('ALTER TABLE properties MODIFY bathrooms INT NULL DEFAULT NULL');
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement('UPDATE properties SET bedrooms = 0 WHERE bedrooms IS NULL');
        DB::statement('UPDATE properties SET bathrooms = 0 WHERE bathrooms IS NULL');
        DB::statement('ALTER TABLE properties MODIFY bedrooms INT NOT NULL DEFAULT 0');
        DB::statement('ALTER TABLE properties MODIFY bathrooms INT NOT NULL DEFAULT 0');
    }
};
