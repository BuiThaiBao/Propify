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

        DB::statement('ALTER TABLE listings MODIFY is_verified VARCHAR(30) NOT NULL DEFAULT \'UNVERIFIED\' COMMENT \'Enum: UNVERIFIED, REQUESTED, VERIFIED, REJECTED\'');
        DB::statement("UPDATE listings SET is_verified = CASE
            WHEN is_verified IN ('1', 'VERIFIED') THEN 'VERIFIED'
            WHEN request_verification = 1 THEN 'REQUESTED'
            ELSE 'UNVERIFIED'
        END");
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("UPDATE listings SET is_verified = CASE WHEN is_verified = 'VERIFIED' THEN '1' ELSE '0' END");
        DB::statement('ALTER TABLE listings MODIFY is_verified TINYINT(1) NOT NULL DEFAULT 0');
    }
};
