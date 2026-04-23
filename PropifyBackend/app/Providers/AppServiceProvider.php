<?php

namespace App\Providers;

use App\Events\Auth\UserRegistered;
use App\Listeners\Auth\SendWelcomeNotification;
use App\Repositories\AppointmentBookingRepository;
use App\Repositories\AppointmentSlotRepository;
use App\Repositories\Eloquent\EloquentAppointmentBookingRepository;
use App\Repositories\Eloquent\EloquentAppointmentSlotRepository;
use App\Repositories\Eloquent\EloquentListingRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\ListingRepository;
use App\Repositories\UserRepository;
use App\Services\Auth\Impl\UserUpsertServiceImpl;
use App\Services\Auth\UserUpsertService;
use App\Services\AuthGoogleService;
use App\Services\AuthService;
use App\Services\Impl\AuthGoogleServiceImpl;
use App\Services\Impl\AuthServiceImpl;
use App\Services\Listing\impl\ListingServiceImpl;
use App\Services\Impl\TokenProcessServiceImpl;
use App\Services\Listing\ListingService;
use App\Services\Notification\Channel\EmailChannel;
use App\Services\Notification\Impl\NotificationServiceImpl;
use App\Services\Notification\NotificationService;
use App\Services\Otp\Adapters\RedisOtpStorageAdapter;
use App\Services\Otp\Impl\OtpServiceImpl;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpStoragePort;
use App\Services\Appointment\AppointmentBookingService;
use App\Services\Appointment\AppointmentSlotService;
use App\Services\Appointment\Impl\AppointmentBookingServiceImpl;
use App\Services\Appointment\Impl\AppointmentSlotServiceImpl;
use App\Services\TokenProcessService;
use App\Services\Cloudinary\CloudinaryService;
use App\Services\Cloudinary\Impl\CloudinaryServiceImpl;
use App\Services\User\Impl\UserServiceImpl;
use App\Services\User\UserService;
use App\Repositories\ChatRepository;
use App\Repositories\Eloquent\EloquentChatRepository;
use App\Services\Chat\ChatService;
use App\Services\Chat\Impl\ChatServiceImpl;
use App\Repositories\PackageRepository;
use App\Repositories\Eloquent\EloquentPackageRepository;
use App\Services\Packages\PackageService;
use App\Services\Packages\Impl\PackageServiceImpl;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ── Repository bindings ───────────────────────────────────────────
        $this->app->bind(UserRepository::class, EloquentUserRepository::class);
        $this->app->bind(AppointmentSlotRepository::class, EloquentAppointmentSlotRepository::class);
        $this->app->bind(AppointmentBookingRepository::class, EloquentAppointmentBookingRepository::class);
        $this->app->bind(ListingRepository::class, EloquentListingRepository::class);

        // ── Auth bindings ─────────────────────────────────────────────────
        $this->app->bind(AuthService::class, AuthServiceImpl::class);
        $this->app->bind(AuthGoogleService::class, AuthGoogleServiceImpl::class);
        $this->app->bind(TokenProcessService::class, TokenProcessServiceImpl::class);
        $this->app->bind(UserUpsertService::class, UserUpsertServiceImpl::class);

        // ── Appointment bindings ──────────────────────────────────────────
        $this->app->bind(AppointmentSlotService::class, AppointmentSlotServiceImpl::class);
        $this->app->bind(AppointmentBookingService::class, AppointmentBookingServiceImpl::class);
        $this->app->bind(ListingService::class, ListingServiceImpl::class);

        // ── Notification bindings ─────────────────────────────────────────
        // NotificationServiceImpl nhận array $channels qua constructor
        // Thêm channel mới chỉ cần append vào array này
        $this->app->bind(NotificationService::class, function () {
            return new NotificationServiceImpl(channels: [
                app(EmailChannel::class),
                // app(SmsChannel::class),   // bật khi có
                // app(ZaloChannel::class),  // bật khi có
            ]);
        });

        // ── OTP bindings ──────────────────────────────────────────────────
        // Production: Redis. Test: swap sang CacheOtpStorageAdapter
        $this->app->bind(OtpStoragePort::class, RedisOtpStorageAdapter::class);
        $this->app->bind(OtpService::class, OtpServiceImpl::class);

        // ── User bindings ─────────────────────────────────────────────────
        $this->app->bind(UserService::class, UserServiceImpl::class);

        // ── Cloudinary bindings ───────────────────────────────────────────
        $this->app->bind(CloudinaryService::class, CloudinaryServiceImpl::class);

        // ── Chat bindings ─────────────────────────────────────────────────
        $this->app->bind(ChatRepository::class, EloquentChatRepository::class);
        $this->app->bind(ChatService::class, ChatServiceImpl::class);

        // ── Package bindings ──────────────────────────────────────────────
        $this->app->bind(PackageRepository::class, EloquentPackageRepository::class);
        $this->app->bind(PackageService::class, PackageServiceImpl::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // ── Event → Listener mapping (Laravel 11 style) ───────────────────
        Event::listen(UserRegistered::class, SendWelcomeNotification::class);
    }
}
