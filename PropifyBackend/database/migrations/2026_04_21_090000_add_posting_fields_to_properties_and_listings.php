<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('properties', function (Blueprint $table) {
            $table->json('amenities')->nullable()->after('poster_type');
            $table->json('legal_paper_types')->nullable()->after('amenities');
            $table->boolean('public_info_agreed')->default(false)->after('legal_paper_types');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->dateTime('appointment_at')->nullable()->after('request_verification');
            $table->json('appointment_days')->nullable()->after('appointment_at');
            $table->string('appointment_time_slot', 50)->nullable()->after('appointment_days');
            $table->string('appointment_contact_name', 100)->nullable()->after('appointment_time_slot');
            $table->string('appointment_contact_phone', 20)->nullable()->after('appointment_contact_name');
            $table->string('appointment_contact_email', 255)->nullable()->after('appointment_contact_phone');
            $table->string('appointment_note')->nullable()->after('appointment_contact_email');
        });
    }

    public function down(): void
    {
        // Keep additive-only migration: no column removals on rollback.
    }
};
