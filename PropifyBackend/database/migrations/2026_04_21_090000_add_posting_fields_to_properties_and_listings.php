<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            if (! Schema::hasColumn('properties', 'amenities')) {
                $table->json('amenities')->nullable()->comment('Enum array: Sân chơi, Bể bơi, Sân vườn, Thang máy, Wifi, Khu để xe');
            }
            if (! Schema::hasColumn('properties', 'legal_paper_types')) {
                $table->json('legal_paper_types')->nullable()->comment('Enum array: LAND_USE_CERTIFICATE, SALE_CONTRACT, CAPITAL_CONTRIBUTION_CONTRACT, ALLOTTED_OR_SUBDIVIDED_LAND, BORROWED_LAND, LEASED_LAND, ORIGIN_PROOF_DOCUMENT, NO_LAND_CERTIFICATE, PROCESSING_LAND_CERTIFICATE, APPOINTMENT_FOR_CERTIFICATE, BUSINESS_TRANSFER, SHARE_TRANSFER, INVESTMENT_COOPERATION, HANDWRITTEN');
            }
            if (! Schema::hasColumn('properties', 'public_info_agreed')) {
                $table->boolean('public_info_agreed')->default(false);
            }
        });

        Schema::table('listings', function (Blueprint $table) {
            if (! Schema::hasColumn('listings', 'appointment_at')) {
                $table->dateTime('appointment_at')->nullable();
            }
            if (! Schema::hasColumn('listings', 'appointment_days')) {
                $table->json('appointment_days')->nullable();
            }
            if (! Schema::hasColumn('listings', 'appointment_time_slot')) {
                $table->string('appointment_time_slot', 50)->nullable();
            }
            if (! Schema::hasColumn('listings', 'appointment_contact_name')) {
                $table->string('appointment_contact_name', 100)->nullable();
            }
            if (! Schema::hasColumn('listings', 'appointment_contact_phone')) {
                $table->string('appointment_contact_phone', 20)->nullable();
            }
            if (! Schema::hasColumn('listings', 'appointment_contact_email')) {
                $table->string('appointment_contact_email', 255)->nullable();
            }
            if (! Schema::hasColumn('listings', 'appointment_note')) {
                $table->string('appointment_note')->nullable();
            }
        });
    }

    public function down(): void
    {
        // Keep additive-only migration: no column removals on rollback.
    }
};
