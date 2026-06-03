<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('listing_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->foreignId('reporter_id')->constrained('users')->cascadeOnDelete();
            $table->string('reason', 80)->comment('Enum: WRONG_PRICE, WRONG_ADDRESS, SOLD_OR_RENTED, WRONG_INFORMATION, UNREACHABLE_OWNER, DUPLICATE_LISTING');
            $table->text('description')->nullable();
            $table->json('image_urls')->nullable();
            $table->string('status', 30)->default('WARNING')->comment('Enum: WARNING');
            $table->timestamps();

            $table->index(['listing_id', 'status']);
            $table->index(['reporter_id', 'listing_id', 'created_at'], 'idx_listing_reports_spam_guard');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_reports');
    }
};
