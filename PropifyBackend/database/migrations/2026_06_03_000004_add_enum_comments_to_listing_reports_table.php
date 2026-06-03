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

        DB::statement('ALTER TABLE listing_reports MODIFY reason VARCHAR(80) NOT NULL COMMENT '.$this->quote('Enum: WRONG_PRICE, WRONG_ADDRESS, SOLD_OR_RENTED, WRONG_INFORMATION, UNREACHABLE_OWNER, DUPLICATE_LISTING'));
        DB::statement('ALTER TABLE listing_reports MODIFY status VARCHAR(30) NOT NULL DEFAULT \'WARNING\' COMMENT '.$this->quote('Enum: WARNING'));
    }

    public function down(): void
    {
        // Comment-only migration; keep schema metadata on rollback.
    }

    private function quote(string $value): string
    {
        return DB::getPdo()->quote($value);
    }
};
