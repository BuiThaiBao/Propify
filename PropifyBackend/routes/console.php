<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ── View Tracking: Flush Redis counters → MySQL ──────────────────────
// Chạy mỗi 30 giây. LƯU Ý: cần `php artisan schedule:work` (không phải schedule:run)
// vì cron Linux chỉ hỗ trợ minimum 1 phút.
// Production: thêm supervisor process cho `php artisan schedule:work`
Schedule::command('views:flush')->everyThirtySeconds();
