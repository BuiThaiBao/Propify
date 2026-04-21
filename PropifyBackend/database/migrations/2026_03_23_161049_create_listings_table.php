<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('listings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_id')->constrained('properties')->cascadeOnDelete();
            $table->string('demand_type', 20)->comment('SALE, RENT');
            $table->string('title');
            $table->text('description')->nullable();
            $table->text('ai_description')->nullable();
            $table->string('status')->default('DRAFT')->comment('DRAFT, PENDING, ACTIVE, EXPIRED, REJECTED, LOCKED');
            // Gói tin có thể null nếu tin thường
            $table->foreignId('package_id')->nullable()->constrained('packages');
            $table->integer('score')->default(0);
            $table->boolean('is_verified')->default(false);
            $table->boolean('has_video')->default(false);
            $table->boolean('request_verification')->default(false);
            $table->text('rejection_reason')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expired_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('demand_type');
            $table->index(['status', 'demand_type'], 'idx_listings_status_demand');
        });
    }

    public function down()
    {
        Schema::dropIfExists('listings');
    }
};
