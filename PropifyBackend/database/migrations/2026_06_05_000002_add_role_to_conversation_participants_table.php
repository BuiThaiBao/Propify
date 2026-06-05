<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->enum('role', ['admin', 'member'])->default('member')->after('user_id');
            $table->string('nickname', 50)->nullable()->after('role');
            $table->timestamp('joined_at')->nullable()->after('nickname');
        });
    }

    public function down(): void
    {
        Schema::table('conversation_participants', function (Blueprint $table) {
            $table->dropColumn(['role', 'nickname', 'joined_at']);
        });
    }
};
