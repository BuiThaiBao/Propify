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

        DB::statement('ALTER TABLE properties MODIFY type VARCHAR(50) NOT NULL COMMENT '.$this->quote('Enum: APARTMENT, PRIVATE_HOUSE, STREET_HOUSE, MINI_APARTMENT, VILLA_TOWNHOUSE, SHOPHOUSE, KIOSK, OFFICE, RESORT, RESTAURANT_HOTEL, RENT_ROOM, BOARDING_HOUSE; Legacy: HOUSE, LAND, ROOM'));
        DB::statement('ALTER TABLE properties MODIFY direction_code VARCHAR(30) NULL COMMENT '.$this->quote('Enum: N, NE, E, SE, S, SW, W, NW'));
        DB::statement('ALTER TABLE properties MODIFY balcony_direction_code VARCHAR(30) NULL COMMENT '.$this->quote('Enum: N, NE, E, SE, S, SW, W, NW'));
        DB::statement('ALTER TABLE properties MODIFY furniture_status VARCHAR(30) NULL COMMENT '.$this->quote('Enum: FULL, BASIC, NONE'));
        DB::statement('ALTER TABLE properties MODIFY poster_type VARCHAR(20) NULL COMMENT '.$this->quote('Enum: OWNER, BROKER'));
        DB::statement('ALTER TABLE properties MODIFY amenities JSON NULL COMMENT '.$this->quote('Enum array: Sân chơi, Bể bơi, Sân vườn, Thang máy, Wifi, Khu để xe'));
        DB::statement('ALTER TABLE properties MODIFY legal_paper_types JSON NULL COMMENT '.$this->quote('Enum array: LAND_USE_CERTIFICATE, SALE_CONTRACT, CAPITAL_CONTRIBUTION_CONTRACT, ALLOTTED_OR_SUBDIVIDED_LAND, BORROWED_LAND, LEASED_LAND, ORIGIN_PROOF_DOCUMENT, NO_LAND_CERTIFICATE, PROCESSING_LAND_CERTIFICATE, APPOINTMENT_FOR_CERTIFICATE, BUSINESS_TRANSFER, SHARE_TRANSFER, INVESTMENT_COOPERATION, HANDWRITTEN'));

        DB::statement('ALTER TABLE listings MODIFY demand_type VARCHAR(20) NOT NULL COMMENT '.$this->quote('Enum: SALE, RENT'));
        DB::statement('ALTER TABLE listings MODIFY status VARCHAR(255) NOT NULL DEFAULT \'DRAFT\' COMMENT '.$this->quote('Enum: DRAFT, PENDING, ACTIVE, EXPIRED, REJECTED, LOCKED, UNLISTED'));
        DB::statement('ALTER TABLE listings MODIFY is_verified VARCHAR(30) NOT NULL DEFAULT \'UNVERIFIED\' COMMENT '.$this->quote('Enum: UNVERIFIED, REQUESTED, VERIFIED, REJECTED'));
        DB::statement('ALTER TABLE listings MODIFY rent_min_term VARCHAR(50) NULL COMMENT '.$this->quote('Enum: 1_month, 3_months, 6_months, 1_year'));
        DB::statement('ALTER TABLE listings MODIFY rent_payment_interval VARCHAR(50) NULL COMMENT '.$this->quote('Enum: monthly, quarter, half_year, yearly'));
        DB::statement('ALTER TABLE listings MODIFY rent_deposit VARCHAR(50) NULL COMMENT '.$this->quote('Enum: none, 1_month, 3_months, 6_months, 1_year'));
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
