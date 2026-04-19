<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Index tổng hợp cho query messages: WHERE conversation_id = ? AND is_deleted = false ORDER BY created_at DESC
        Schema::table('messages', function (Blueprint $table) {
            $table->index(['conversation_id', 'is_deleted', 'created_at'], 'messages_conv_active_time');
        });

        // Index cho conversations: WHERE participant_a_id = ? OR participant_b_id = ? ORDER BY updated_at DESC
        Schema::table('conversations', function (Blueprint $table) {
            $table->index(['participant_a_id', 'updated_at'], 'conv_participant_a_time');
            $table->index(['participant_b_id', 'updated_at'], 'conv_participant_b_time');
        });

        // Index cho conversation_participants: WHERE conversation_id = ? AND user_id = ?
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->index(['conversation_id', 'user_id'], 'conv_part_conv_user');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropIndex('messages_conv_active_time');
        });
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropIndex('conv_participant_a_time');
            $table->dropIndex('conv_participant_b_time');
        });
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropIndex('conv_part_conv_user');
        });
    }
};
