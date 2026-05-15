<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('listing_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
            $table->string('action', 20)->comment('ACTIVE, REJECTED, LOCKED');
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['listing_id', 'created_at'], 'listing_status_histories_listing_time');
            $table->index(['user_id', 'created_at'], 'listing_status_histories_user_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('listing_status_histories');
    }
};
