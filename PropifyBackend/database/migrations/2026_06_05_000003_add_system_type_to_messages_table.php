<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file', 'system') DEFAULT 'text'");
        }

        Schema::table('messages', function (Blueprint $table) {
            $table->json('metadata')->nullable()->after('file_url');
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropColumn('metadata');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image', 'file') DEFAULT 'text'");
        }
    }
};
