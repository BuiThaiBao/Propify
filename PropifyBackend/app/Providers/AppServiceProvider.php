<?php

namespace App\Providers;

use App\Events\Auth\PasswordChanged;
use App\Events\Auth\UserRegistered;
use App\Events\Appointment\AppointmentBooked;
use App\Events\Appointment\AppointmentBookingStatusUpdated;
use App\Events\Listing\FavoriteToggled;
use App\Events\Listing\ListingPackageExpiring;
use App\Events\Listing\ListingPackageUpgraded;
use App\Events\Listing\ListingSaved;
use App\Events\Listing\ListingVerificationRequested;
use App\Events\Package\PackageCreated;
use App\Events\Package\PackageStatusChanged;
use App\Events\User\ProfileUpdated;
use App\Listeners\Auth\SendWelcomeNotification;
use App\Listeners\Appointment\SendAppointmentBookedNotification;
use App\Listeners\Appointment\SendAppointmentBookingStatusNotification;
use App\Listeners\Listing\ClearPublicListingCache;
use App\Listeners\Listing\LogListingPackageUpgrade;
use App\Listeners\Listing\LogListingSaved;
use App\Listeners\Listing\SendPackageExpiringNotification;
use App\Listeners\Listing\SendPackageUpgradeNotification;
use App\Repositories\AmenityRepository;
use App\Repositories\AppointmentBookingRepository;
use App\Repositories\AppointmentSlotRepository;
use App\Repositories\ChatRepository;
use App\Repositories\Eloquent\EloquentAmenityRepository;
use App\Repositories\Eloquent\EloquentAppointmentBookingRepository;
use App\Repositories\Eloquent\EloquentAppointmentSlotRepository;
use App\Repositories\Eloquent\EloquentChatRepository;
use App\Repositories\Eloquent\EloquentFavoriteRepository;
use App\Repositories\Eloquent\EloquentListingAmenityRepository;
use App\Repositories\Eloquent\EloquentListingRepository;
use App\Repositories\Eloquent\EloquentPackageRepository;
use App\Repositories\Eloquent\EloquentProjectSearchRepository;
use App\Repositories\Eloquent\EloquentUserRepository;
use App\Repositories\FavoriteRepository;
use App\Repositories\ListingAmenityRepository;
use App\Repositories\ListingRepository;
use App\Repositories\PackageRepository;
use App\Repositories\ProjectSearchRepository;
use App\Repositories\UserRepository;
use App\Services\Amenity\AmenityService;
use App\Services\Amenity\Impl\AmenityServiceImpl;
use App\Services\Amenity\Impl\ListingAmenityServiceImpl;
use App\Services\Amenity\ListingAmenityService;
use App\Services\Appointment\AppointmentBookingService;
use App\Services\Appointment\AppointmentSlotService;
use App\Services\Appointment\Impl\AppointmentBookingServiceImpl;
use App\Services\Appointment\Impl\AppointmentSlotServiceImpl;
use App\Services\Auth\AuthGoogleService;
use App\Services\Auth\AuthService;
use App\Services\Auth\AuthStrategyResolver;
use App\Services\Auth\ForgotPassword\ForgotPasswordChain;
use App\Services\Auth\ForgotPassword\Handlers\FindResetUserHandler;
use App\Services\Auth\ForgotPassword\Handlers\LogResetAttemptHandler;
use App\Services\Auth\ForgotPassword\Handlers\SendResetOtpHandler;
use App\Services\Auth\Impl\AuthGoogleServiceImpl;
use App\Services\Auth\Impl\AuthServiceImpl;
use App\Services\Auth\Impl\TokenProcessServiceImpl;
use App\Services\Auth\Impl\UserUpsertServiceImpl;
use App\Services\Auth\Strategies\EmailPasswordAuthStrategy;
use App\Services\Auth\Strategies\GoogleOAuthAuthStrategy;
use App\Services\Auth\TokenProcessService;
use App\Services\Auth\UserUpsertService;
use App\Services\Chat\ChatService;
use App\Services\Chat\Impl\ChatServiceImpl;
use App\Services\Cloudinary\CloudinaryService;
use App\Services\Cloudinary\Impl\CloudinaryServiceImpl;
use App\Services\Listing\Favorite\FavoriteService;
use App\Services\Listing\Favorite\Impl\FavoriteServiceImpl;
use App\Services\Listing\impl\ListingServiceImpl;
use App\Services\Listing\ListingService;
use App\Services\Listing\Reports\ListingReportValidationChain;
use App\Services\Listing\Reports\Rules\EnsureListingCanBeReportedHandler;
use App\Services\Listing\Reports\Rules\PreventDuplicateListingReportHandler;
use App\Services\Listing\Verification\Impl\ListingVerificationServiceImpl;
use App\Services\Listing\Verification\ListingVerificationService;
use App\Services\Media\CloudinaryUploadSignatureAdapter;
use App\Services\Media\UploadSignatureAdapter;
use App\Services\Notification\Channel\DatabaseChannel;
use App\Services\Notification\Channel\EmailChannel;
use App\Services\Notification\Impl\NotificationServiceImpl;
use App\Services\Notification\NotificationService;
use App\Services\Otp\Adapters\RedisOtpStorageAdapter;
use App\Services\Otp\Impl\OtpServiceImpl;
use App\Services\Otp\OtpService;
use App\Services\Otp\OtpStoragePort;
use App\Services\Packages\Impl\PackageServiceImpl;
use App\Services\Packages\PackageService;
use App\Services\ProjectSearch\Impl\ProjectSearchServiceImpl;
use App\Services\ProjectSearch\ProjectSearchService;
use App\Services\User\Impl\UserServiceImpl;
use App\Services\User\UserService;
use App\Services\ViewTracking\Impl\ViewTrackingServiceImpl;
use App\Services\ViewTracking\ViewTrackingService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
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
        $this->app->bind(ProjectSearchRepository::class, EloquentProjectSearchRepository::class);
        $this->app->bind(AmenityRepository::class, EloquentAmenityRepository::class);
        $this->app->bind(ListingAmenityRepository::class, EloquentListingAmenityRepository::class);
        $this->app->bind(FavoriteRepository::class, EloquentFavoriteRepository::class);

        // ── Auth bindings ─────────────────────────────────────────────────
        $this->app->bind(AuthService::class, AuthServiceImpl::class);
        $this->app->bind(AuthGoogleService::class, AuthGoogleServiceImpl::class);
        $this->app->bind(AuthStrategyResolver::class, function () {
            return new AuthStrategyResolver([
                app(EmailPasswordAuthStrategy::class),
                app(GoogleOAuthAuthStrategy::class),
            ]);
        });
        $this->app->bind(TokenProcessService::class, TokenProcessServiceImpl::class);
        $this->app->bind(UserUpsertService::class, UserUpsertServiceImpl::class);
        $this->app->bind(ForgotPasswordChain::class, function () {
            $findUser = app(FindResetUserHandler::class);
            $sendOtp = app(SendResetOtpHandler::class);
            $logAttempt = app(LogResetAttemptHandler::class);

            $findUser->setNext($sendOtp)->setNext($logAttempt);

            return new ForgotPasswordChain($findUser);
        });

        // ── Appointment bindings ──────────────────────────────────────────
        $this->app->bind(AppointmentSlotService::class, AppointmentSlotServiceImpl::class);
        $this->app->bind(AppointmentBookingService::class, AppointmentBookingServiceImpl::class);
        $this->app->bind(ListingService::class, ListingServiceImpl::class);
        $this->app->bind(ProjectSearchService::class, ProjectSearchServiceImpl::class);
        $this->app->bind(AmenityService::class, AmenityServiceImpl::class);
        $this->app->bind(ListingAmenityService::class, ListingAmenityServiceImpl::class);
        $this->app->bind(FavoriteService::class, FavoriteServiceImpl::class);
        $this->app->bind(ListingVerificationService::class, ListingVerificationServiceImpl::class);
        $this->app->bind(ListingReportValidationChain::class, function () {
            $activeStatus = app(EnsureListingCanBeReportedHandler::class);
            $spamGuard = app(PreventDuplicateListingReportHandler::class);

            $activeStatus->setNext($spamGuard);

            return new ListingReportValidationChain($activeStatus);
        });

        // ── Notification bindings ─────────────────────────────────────────
        // NotificationServiceImpl nhận array $channels qua constructor
        // Thêm channel mới chỉ cần append vào array này
        $this->app->bind(NotificationService::class, function () {
            return new NotificationServiceImpl(channels: [
                app(DatabaseChannel::class),
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
        $this->app->bind(UploadSignatureAdapter::class, CloudinaryUploadSignatureAdapter::class);

        // ── Chat bindings ─────────────────────────────────────────────────
        $this->app->bind(ChatRepository::class, EloquentChatRepository::class);
        $this->app->bind(ChatService::class, ChatServiceImpl::class);

        // ── Package bindings ──────────────────────────────────────────────
        $this->app->bind(PackageRepository::class, EloquentPackageRepository::class);
        $this->app->bind(PackageService::class, PackageServiceImpl::class);

        // ── View Tracking bindings ────────────────────────────────────────
        $this->app->bind(
            ViewTrackingService::class,
            ViewTrackingServiceImpl::class,
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            $pdo = DB::connection()->getPdo();

            if (method_exists($pdo, 'sqliteCreateFunction')) {
                $pdo->sqliteCreateFunction('normalize_text', function ($value) {
                    return Str::ascii(mb_strtolower((string) $value, 'UTF-8'));
                }, 1);
            }
        }

        // ── Event → Listener mapping (Laravel 11 style) ───────────────────
        Event::listen(UserRegistered::class, SendWelcomeNotification::class);
        Event::listen(ListingSaved::class, ClearPublicListingCache::class);
        Event::listen(ListingSaved::class, LogListingSaved::class);
        Event::listen(ListingPackageUpgraded::class, ClearPublicListingCache::class);
        Event::listen(ListingPackageUpgraded::class, LogListingPackageUpgrade::class);
        Event::listen(ListingPackageUpgraded::class, SendPackageUpgradeNotification::class);
        Event::listen(ListingPackageExpiring::class, SendPackageExpiringNotification::class);
        Event::listen(AppointmentBooked::class, SendAppointmentBookedNotification::class);
        Event::listen(AppointmentBookingStatusUpdated::class, SendAppointmentBookingStatusNotification::class);
        Event::listen(FavoriteToggled::class, static function () {});
        Event::listen(ListingVerificationRequested::class, static function () {});
        Event::listen(ProfileUpdated::class, static function () {});
        Event::listen(PasswordChanged::class, static function () {});
        Event::listen(PackageCreated::class, static function () {});
        Event::listen(PackageStatusChanged::class, static function () {});
    }
}
