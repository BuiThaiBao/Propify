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

        DB::statement('ALTER TABLE listings MODIFY is_verified VARCHAR(30) NOT NULL DEFAULT \'UNVERIFIED\' COMMENT \'Enum: NOT_REQUIRED, UNVERIFIED, REQUESTED, VERIFIED, REJECTED\'');
        DB::statement("UPDATE listings SET is_verified = 'NOT_REQUIRED' WHERE demand_type = 'RENT'");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE listings SET is_verified = 'UNVERIFIED' WHERE is_verified = 'NOT_REQUIRED'");
        DB::statement('ALTER TABLE listings MODIFY is_verified VARCHAR(30) NOT NULL DEFAULT \'UNVERIFIED\' COMMENT \'Enum: UNVERIFIED, REQUESTED, VERIFIED, REJECTED\'');
    }
};
