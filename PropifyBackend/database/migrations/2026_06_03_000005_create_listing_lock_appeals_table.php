<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('listing_lock_appeals')) {
            return;
        }

        Schema::create('listing_lock_appeals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('reason');
            $table->string('status', 30)->default('PENDING')->comment('Enum: PENDING, REVIEWED, REJECTED, RESOLVED');
            $table->timestamps();

            $table->index(['listing_id', 'status']);
            $table->index(['user_id', 'listing_id', 'status'], 'idx_listing_lock_appeals_user_listing_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_lock_appeals');
    }
};
