<?php

use App\Enums\ErrorCode;
use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Route;

Route::get('/up', function () {
    return response()->json(['status' => 'ok']);
})->name('health');

Route::get('/health', function () {
    $checks = [
        'database' => 'ok',
        'redis' => 'ok',
        'queue' => 'ok',
    ];

    $statusCode = 200;

    // Check Database
    try {
        DB::connection()->getPdo();
    } catch (\Throwable $e) {
        $checks['database'] = 'failed: ' . $e->getMessage();
        $statusCode = 503;
    }

    // Check Redis
    try {
        Redis::ping();
    } catch (\Throwable $e) {
        $checks['redis'] = 'failed: ' . $e->getMessage();
        $statusCode = 503;
    }

    // Check Queue (database driver)
    try {
        DB::table('jobs')->limit(1)->get();
    } catch (\Throwable $e) {
        $checks['queue'] = 'failed: ' . $e->getMessage();
        $statusCode = 503;
    }

    return response()->json([
        'status' => $statusCode === 200 ? 'healthy' : 'unhealthy',
        'checks' => $checks,
        'timestamp' => now()->toIso8601String(),
    ], $statusCode);
})->name('health.detailed');