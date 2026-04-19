<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tạo bảng conversations.
     *
     * Unique constraint theo normalized pair (participant_a_id < participant_b_id) + listing_id
     * đảm bảo 2 user chỉ có tối đa 1 conversation cho mỗi listing (hoặc 1 conversation chung).
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();

            // Normalized pair: participant_a_id < participant_b_id (tránh duplicate)
            $table->unsignedBigInteger('participant_a_id'); // min(user_a, user_b)
            $table->unsignedBigInteger('participant_b_id'); // max(user_a, user_b)

            // Nullable: chat có thể về listing cụ thể hoặc là chat tự do
            $table->unsignedBigInteger('listing_id')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('participant_a_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('participant_b_id')->references('id')->on('users')->cascadeOnDelete();

            // Unique: 1 cặp user chỉ có 1 conversation cho mỗi listing (hoặc null)
            $table->unique(['participant_a_id', 'participant_b_id', 'listing_id'], 'unique_conversation_pair');

            // Index để query danh sách conversations của user
            $table->index('participant_a_id');
            $table->index('participant_b_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
