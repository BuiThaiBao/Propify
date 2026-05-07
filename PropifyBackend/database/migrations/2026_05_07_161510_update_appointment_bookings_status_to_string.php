<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('appointment_bookings', function (Blueprint $table) {
            $table->string('status')->default('PENDING')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('appointment_bookings', function (Blueprint $table) {
            // Note: Reverting to enum requires knowing all current string values and mapping them back.
            // Leaving as string or throwing exception is safer, but we can try reverting if needed.
            // For simplicity, we just leave it as string in down().
        });
    }
};
