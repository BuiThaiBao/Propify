<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        $comment = 'Enum: APARTMENT, PRIVATE_HOUSE, STREET_HOUSE, MINI_APARTMENT, VILLA_TOWNHOUSE, SHOPHOUSE, KIOSK, OFFICE, RESORT, RESTAURANT_HOTEL, RENT_ROOM, BOARDING_HOUSE; Legacy: HOUSE, LAND, ROOM';

        DB::statement('ALTER TABLE properties MODIFY type VARCHAR(50) NOT NULL COMMENT '.DB::getPdo()->quote($comment));
    }

    public function down(): void
    {
        // Comment-only migration; keep schema metadata on rollback.
    }
};
