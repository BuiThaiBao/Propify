<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packages', function (Blueprint $table) {
            $table->renameColumn('price', 'push_price');
        });

        Schema::table('listings', function (Blueprint $table) {
            $table->timestamp('pushed_at')->nullable()->after('package_expires_at');
            $table->index('pushed_at');
        });
    }

    public function down(): void
    {
        Schema::table('listings', function (Blueprint $table) {
            $table->dropIndex(['pushed_at']);
            $table->dropColumn('pushed_at');
        });

        Schema::table('packages', function (Blueprint $table) {
            $table->renameColumn('push_price', 'price');
        });
    }
};
