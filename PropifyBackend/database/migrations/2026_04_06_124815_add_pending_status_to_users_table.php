<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Thêm 'P' (Pending) vào enum status để hỗ trợ xác thực OTP
            $table->enum('status', ['A', 'P', 'IA', 'BAN'])->default('A')->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('status', ['A', 'IA', 'BAN'])->default('A')->change();
        });
    }
};
