<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\GoogleController;
use App\Http\Controllers\Api\V1\Chat\ChatController;
use App\Http\Controllers\Api\V1\Cloudinary\CloudinaryController;
use App\Http\Controllers\Api\V1\Listing\FavoriteController;
use App\Http\Controllers\Api\V1\Listing\ListingController;
use App\Http\Controllers\Api\V1\User\UserController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Package\PackageController;
use App\Http\Controllers\Api\V1\Geocoding\GeocodingController;

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

// ==================== BROADCASTING AUTH (JWT) ====================
// Overide mặc định /broadcasting/auth để sử dụng JWT thay vì session
Broadcast::routes(['middleware' => ['auth:api']]);

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
    // Tìm user theo SĐT để bắt đầu chat
    Route::get('/search', [UserController::class, 'searchByPhone'])->name('search');
});

// ==================== CLOUDINARY ROUTES ====================
Route::prefix('v1/cloudinary')->as('cloudinary.')->middleware('auth:api')->group(function () {
    // Tạo signed signature để frontend upload ảnh trực tiếp lên Cloudinary
    // Query param: ?type=avatar | ?type=listing
    Route::post('/sign', [CloudinaryController::class, 'sign'])->name('sign');
});


// ==================== APPOINTMENT ROUTES ====================
// Public: Khách xem danh sách khung giờ hẹn (không cần đăng nhập)
Route::prefix('v1/appointment-slots')->as('appointment-slots.')->group(function () {
    Route::post('/', [\App\Http\Controllers\Api\V1\Appointment\AppointmentSlotController::class, 'index'])
        ->name('index');
});
// Protected: Quản lý khung giờ hẹn (cần đăng nhập)
Route::prefix('v1/appointment-slots')->as('appointment-slots.')->middleware('auth:api')->group(function () {
    Route::post('/create', [\App\Http\Controllers\Api\V1\Appointment\AppointmentSlotController::class, 'create'])
        ->name('create');
    Route::post('/replace', [\App\Http\Controllers\Api\V1\Appointment\AppointmentSlotController::class, 'replace'])
        ->name('replace');
    Route::put('/', [\App\Http\Controllers\Api\V1\Appointment\AppointmentSlotController::class, 'update'])
        ->name('update');
    Route::post('/disable', [\App\Http\Controllers\Api\V1\Appointment\AppointmentSlotController::class, 'disable'])
        ->name('disable');
});

Route::prefix('v1/appointment-bookings')->as('appointment-bookings.')->middleware('auth:api')->group(function () {
    Route::get('/', [\App\Http\Controllers\Api\V1\Appointment\AppointmentBookingController::class, 'index'])
        ->name('index');
    Route::get('/received', [\App\Http\Controllers\Api\V1\Appointment\AppointmentBookingController::class, 'received'])
        ->name('received');
    Route::post('/', [\App\Http\Controllers\Api\V1\Appointment\AppointmentBookingController::class, 'store'])
        ->name('store');
    Route::post('/update-status', [\App\Http\Controllers\Api\V1\Appointment\AppointmentBookingController::class, 'updateStatus'])
        ->name('update-status');
    Route::post('/cancel', [\App\Http\Controllers\Api\V1\Appointment\AppointmentBookingController::class, 'cancel'])
        ->name('cancel');
});

// ==================== CHAT ROUTES ====================
Route::prefix('v1/chat')->as('chat.')->middleware('auth:api')->group(function () {
    // Danh sách conversations của user
    Route::get('/conversations', [ChatController::class, 'getConversations'])
        ->name('conversations.index');

    // Lấy hoặc tạo conversation (idempotent — không duplicate)
    Route::post('/conversations', [ChatController::class, 'getOrCreateConversation'])
        ->name('conversations.get-or-create');

    // Messages với cursor pagination
    Route::get('/conversations/{conversationId}/messages', [ChatController::class, 'getMessages'])
        ->name('conversations.messages.index');

    // Gửi message (broadcast async qua queue)
    Route::post('/conversations/{conversationId}/messages', [ChatController::class, 'sendMessage'])
        ->name('conversations.messages.send');

    // Đánh dấu đã đọc
    Route::post('/conversations/{conversationId}/read', [ChatController::class, 'markAsRead'])
        ->name('conversations.read');
});

// ==================== GEOCODING PROXY ROUTES (public — no auth needed) ====================
Route::prefix('v1/geocoding')->as('geocoding.')->group(function () {
    Route::get('/reverse', [GeocodingController::class, 'reverse'])->name('reverse');
    Route::get('/search', [GeocodingController::class, 'search'])->name('search');
});

Route::prefix('v1/listings')->as('listings.')->group(function () {
    Route::get('/', [ListingController::class, 'index'])->name('index');
    Route::get('/{id}', [ListingController::class, 'show'])->where('id', '[0-9]+')->name('show');

    // View tracking — public, throttle 60/min/IP
    Route::post('/{id}/view', [\App\Http\Controllers\Api\V1\Listing\ViewTrackingController::class, 'track'])
        ->where('id', '[0-9]+')
        ->middleware('throttle:60,1')
        ->name('view');

    // Yêu cầu đăng nhập
    Route::middleware('auth:api')->group(function () {
        Route::post('/', [ListingController::class, 'store'])->name('store');
        Route::get('/my', [ListingController::class, 'myListings'])->name('my');
        Route::put('/{id}', [ListingController::class, 'update'])->where('id', '[0-9]+')->name('update');
        Route::patch('/{id}/verification', [ListingController::class, 'updateVerification'])->where('id', '[0-9]+')->name('verification.update');
        Route::post('/{id}/lock', [ListingController::class, 'lock'])->where('id', '[0-9]+')->name('lock');
        Route::post('/{id}/upgrade', [\App\Http\Controllers\Api\V1\Listing\ListingUpgradeController::class, 'upgrade'])
            ->where('id', '[0-9]+')
            ->name('upgrade');
    });
});

Route::prefix('v1/favorites')->as('favorites.')->middleware('auth:api')->group(function () {
    Route::get('/', [FavoriteController::class, 'index'])->name('index');
    Route::get('/ids', [FavoriteController::class, 'ids'])->name('ids');
    Route::post('/{listingId}', [FavoriteController::class, 'toggle'])->where('listingId', '[0-9]+')->name('toggle');
});

// ==================== PACKAGES: PUBLIC ROUTES ====================
Route::prefix('v1/packages')->as('packages.')->group(function () {
    Route::get('/', [PackageController::class, 'index'])->name('index');
    Route::get('/{id}', [PackageController::class, 'show'])->where('id', '[0-9]+')->name('show');
});

// ==================== PACKAGES: PROTECTED ROUTES ====================
Route::prefix('v1/packages')->as('packages.admin.')->middleware('auth:api')->group(function () {
    Route::get('/duration-options', [\App\Http\Controllers\Api\V1\Package\PackageDurationOptionController::class, 'index'])
        ->name('duration-options.index');
    Route::post('/duration-options', [\App\Http\Controllers\Api\V1\Package\PackageDurationOptionController::class, 'store'])
        ->name('duration-options.store');

    Route::post('/', [PackageController::class, 'create'])->name('create');
    Route::put('/{id}', [PackageController::class, 'update'])->name('update');
    Route::delete('/{id}', [PackageController::class, 'destroy'])->name('destroy');

    // Package Pricings CRUD (admin)
    Route::get('/{packageId}/pricings', [\App\Http\Controllers\Api\V1\Package\PackagePricingController::class, 'index'])
        ->name('pricings.index');
    Route::post('/{packageId}/pricings', [\App\Http\Controllers\Api\V1\Package\PackagePricingController::class, 'store'])
        ->name('pricings.store');
    Route::put('/{packageId}/pricings/{pricingId}', [\App\Http\Controllers\Api\V1\Package\PackagePricingController::class, 'update'])
        ->name('pricings.update');
    Route::delete('/{packageId}/pricings/{pricingId}', [\App\Http\Controllers\Api\V1\Package\PackagePricingController::class, 'destroy'])
        ->name('pricings.destroy');
});

// ==================== ADMIN ROUTES ====================
Route::prefix('v1/admin')->as('admin.')->middleware('auth:api')->group(function () {
    Route::get('/listings', [\App\Http\Controllers\Api\V1\Admin\AdminListingController::class, 'index'])->name('listings.index');
    Route::patch('/listings/{id}/status', [\App\Http\Controllers\Api\V1\Admin\AdminListingController::class, 'changeStatus'])->name('listings.change-status');
});
