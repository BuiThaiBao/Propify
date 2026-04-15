<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\GoogleController;
use App\Http\Controllers\Api\V1\CloudinaryController;
use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::prefix('v1/auth')->as('auth.')->group(function () {
    // ===== PUBLIC ROUTES (Throttled to prevent brute force) =====
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register');

    Route::post('/resend-register-otp', [AuthController::class, 'resendRegisterOtp'])
        ->middleware('throttle:5,1')
        ->name('resend-register-otp');

    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])
        ->middleware('throttle:10,1')
        ->name('verify-otp');

    Route::post('/forgot-password', [AuthController::class, 'forgotPassword'])
        ->middleware('throttle:5,1')
        ->name('forgot-password');

    Route::post('/check-reset-otp', [AuthController::class, 'checkResetOtp'])
        ->middleware('throttle:10,1')
        ->name('check-reset-otp');

    Route::post('/reset-password', [AuthController::class, 'resetPassword'])
        ->middleware('throttle:10,1')
        ->name('reset-password');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('login');

    Route::get('/google', [GoogleController::class, 'redirectToGoogle'])
        ->middleware('throttle:10,1')
        ->name('google');

    Route::get('/google/callback', [GoogleController::class, 'handleGoogleCallback'])
        ->middleware('throttle:10,1')
        ->name('google.callback');

    // ===== TOKEN REFRESH (Requires current JWT, even if expired) =====
    Route::post('/refresh', [AuthController::class, 'refresh'])
        ->name('refresh');

    // ===== PROTECTED ROUTES (Requires valid JWT token) =====
    Route::middleware('auth:api')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
        Route::get('/me', [AuthController::class, 'me'])->name('me');
    });

});
Route::prefix('v1/user')->as('user.')->middleware('auth:api')->group(function () {
    Route::get('/profile', [UserController::class, 'getProfile'])->name('profile.show');
    Route::put('/profile', [UserController::class, 'updateProfile'])->name('profile.update');
    Route::put('/change-password', [UserController::class, 'changePassword'])->name('password.change');
});

// ==================== CLOUDINARY ROUTES ====================
Route::prefix('v1/cloudinary')->as('cloudinary.')->middleware('auth:api')->group(function () {
    // Tạo signed signature để frontend upload ảnh trực tiếp lên Cloudinary
    // Query param: ?type=avatar | ?type=listing
    Route::post('/sign', [CloudinaryController::class, 'sign'])->name('sign');
});


// ==================== APPOINTMENT ROUTES ====================
Route::prefix('v1/appointment-slots')->as('appointment-slots.')->middleware('auth:api')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\V1\Appointment\AppointmentSlotController::class, 'index'])
        ->name('index');
});
