<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('appointment_bookings', function (Blueprint $table) {
            $table->dateTime('confirm_deadline')->nullable()->after('status')
                ->comment('Thời hạn poster phải xác nhận. Null = chưa tính hoặc không áp dụng');
            $table->boolean('is_urgent')->default(false)->after('confirm_deadline')
                ->comment('Đặt lịch gấp (T_hẹn - T_đặt < 6h)');
        });
    }

    public function down(): void
    {
        Schema::table('appointment_bookings', function (Blueprint $table) {
            $table->dropColumn(['confirm_deadline', 'is_urgent']);
        });
    }
};
