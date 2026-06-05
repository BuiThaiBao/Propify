<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->enum('type', ['private', 'group'])->default('private')->after('id');
            $table->string('name', 100)->nullable()->after('type');
            $table->string('avatar_url')->nullable()->after('name');
            $table->unsignedBigInteger('creator_id')->nullable()->after('avatar_url');
            $table->foreign('creator_id')->references('id')->on('users')->nullOnDelete();
            $table->index('type');
            $table->index('creator_id');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE conversations MODIFY participant_a_id BIGINT UNSIGNED NULL');
            DB::statement('ALTER TABLE conversations MODIFY participant_b_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE conversations MODIFY participant_a_id BIGINT UNSIGNED NOT NULL');
            DB::statement('ALTER TABLE conversations MODIFY participant_b_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['creator_id']);
            $table->dropIndex(['type']);
            $table->dropIndex(['creator_id']);
            $table->dropColumn(['type', 'name', 'avatar_url', 'creator_id']);
        });
    }
};
