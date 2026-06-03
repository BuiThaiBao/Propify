# Kế hoạch triển khai: Hệ thống thông báo (Refined)

Kế hoạch này tích hợp chức năng thông báo thời gian thực (in-app qua WebSockets/Reverb) và qua email khi nâng cấp gói tin thành công, khi gói tin sắp hết hạn, và khi có người đặt lịch xem nhà. Đồng thời, kế hoạch giải quyết triệt để lỗi đồng bộ dữ liệu trang cá nhân và refactor các tên gọi sai chính tả trong hệ thống.

---

## 1. Thiết kế Design Pattern & Kiến trúc Refined

### A. Observer Pattern & Queue (Laravel Events & Listeners)
*   **Sự kiện nâng cấp**: `ListingPackageUpgraded` -> Listener `SendPackageUpgradeNotification` (`ShouldQueue`).
*   **Sắp hết hạn**: Command `NotifyExpiringPackages` -> `ListingPackageExpiring` -> Listener `SendPackageExpiringNotification` (`ShouldQueue`).
*   **Đặt lịch xem nhà**: `AppointmentBookingServiceImpl::createBooking()` -> `AppointmentBooked` -> Listener `SendAppointmentBookedNotification` (`ShouldQueue`).
*   **Queue Safety**: Các events chỉ truyền `Id` của model (ví dụ: `bookingId`, `listingId`). Listener chịu trách nhiệm query lại model kèm relationships cần thiết (`with(...)`) để đảm bảo không bị stale/mất data khi deserialize từ hàng đợi (Queue).
*   **Database Transaction Safety**: Mọi event liên quan đến DB queue/broadcast sẽ implement `ShouldDispatchAfterCommit` để đảm bảo chúng chỉ được dispatch sau khi DB transaction được commit thành công.

### B. Strategy Pattern (Notification Channels)
*   `NotificationService` quản lý các channel strategy.
*   **Tách biệt Trách nhiệm**:
    *   `MailType` đại diện cho các email hệ thống (WELCOME, VERIFY_EMAIL, PASSWORD_RESET, FORGOT_PASSWORD). Chỉ được gửi qua `EmailChannel`, không lưu DB.
    *   `NotificationType` đại diện cho thông báo nghiệp vụ (PACKAGE_UPGRADED, PACKAGE_EXPIRING, APPOINTMENT_BOOKED). Được lưu DB qua `DatabaseChannel` và có thể gửi email tương ứng qua `EmailChannel`.
    *   Method `send()` nhận union type `NotificationType|MailType $type`. `DatabaseChannel` sẽ bỏ qua nếu nhận được `MailType`.

---

## 2. Các thay đổi chi tiết

### A. Sửa lỗi giao diện trang Cá nhân (Profile Reactivity Fix)
Tránh deep watch ghi đè dữ liệu đang nhập:
```javascript
const syncProfileForm = (user) => {
  if (!user) return;
  profileForm.fullName = user.full_name || '';
  profileForm.phone = user.phone || '';
};

watch(
  () => authStore.user?.id,
  (newId) => {
    if (newId && !isEditing.value) {
      syncProfileForm(authStore.user);
    }
  },
  { immediate: true }
);
```

---

### B. Refactor & Tạo Enums mới

#### [MODIFY] [NotificationChannelType.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Enums/NotificationChannelType.php) (Rename từ `NotificationChanelType.php`)
Sửa lỗi chính tả từ `Chanel` thành `Channel`. Cập nhật tất cả các file liên quan sử dụng enum này (`EmailChannel.php`, `AppServiceProvider.php`, `NotificationServiceImpl.php`, `OtpServiceImpl.php`, `SendWelcomeNotification.php`).

#### [NEW] [NotificationType.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Enums/NotificationType.php)
```php
namespace App\Enums;

enum NotificationType: string
{
    case PACKAGE_UPGRADED = 'package_upgraded';
    case PACKAGE_EXPIRING = 'package_expiring';
    case APPOINTMENT_BOOKED = 'appointment_booked';
}
```

#### [MODIFY] [MailType.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Enums/MailType.php)
Giữ nguyên vai trò chỉ cho system auth email:
```php
namespace App\Enums;

enum MailType: string
{
    case WELCOME = 'welcome';
    case PASSWORD_RESET = 'password_reset';
    case VERIFY_EMAIL = 'verify_email';
    case FORGOT_PASSWORD = 'forgot_password';
}
```

#### [MODIFY] [NotificationService.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Notification/NotificationService.php)
```php
interface NotificationService
{
    public function send(
        User $user,
        NotificationType|MailType $type,
        array $data = [],
        array $channels = [NotificationChannelType::EMAIL]
    ): void;
}
```

---

### C. Quản lý Đặt lịch (Events & Listeners)

#### [NEW] [AppointmentBooked.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Events/Appointment/AppointmentBooked.php)
```php
namespace App\Events\Appointment;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AppointmentBooked implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly int $bookingId) {}
}
```

#### [MODIFY] [AppointmentBookingServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Appointment/Impl/AppointmentBookingServiceImpl.php)
```php
        // ... tạo booking ...
        $booking = $this->bookingRepository->create([...]);
        event(new \App\Events\Appointment\AppointmentBooked($booking->id));
        return $booking;
```

#### [NEW] [SendAppointmentBookedNotification.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Listeners/Appointment/SendAppointmentBookedNotification.php)
```php
final class SendAppointmentBookedNotification implements ShouldQueue
{
    public function __construct(private readonly NotificationService $notificationService) {}

    public function handle(AppointmentBooked $event): void
    {
        $booking = AppointmentBooking::with(['slot.listing.property', 'slot.poster', 'viewer'])
            ->findOrFail($event->bookingId);

        $this->notificationService->send(
            user: $booking->slot->poster,
            type: NotificationType::APPOINTMENT_BOOKED,
            data: [
                'booking_id' => $booking->id,
                'viewer_name' => $booking->full_name,
                'viewer_phone' => $booking->phone_number ?? $booking->viewer?->phone,
                'viewer_email' => $booking->email ?? $booking->viewer?->email,
                'meet_time' => $booking->meet_time->format('H:i - d/m/Y'),
                'listing_id' => $booking->slot->listing_id,
                'listing_title' => $booking->slot->listing->title ?? $booking->slot->listing->property?->title ?? 'Tin đăng',
            ],
            channels: [NotificationChannelType::EMAIL, NotificationChannelType::DATABASE]
        );
    }
}
```

#### [NEW] [AppointmentBookedMail.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Mail/AppointmentBookedMail.php)
Mailable quản lý việc gửi email cho chủ nhà.

#### [NEW] [appointment-booked.blade.php](file:///d:/PROJECT/Meyland/PropifyBackend/resources/views/emails/appointment-booked.blade.php)
Email view HTML lịch sự, hiển thị: Tên khách đặt, SĐT, Email, Thời gian hẹn, và Tin đăng tương ứng.

---

### D. Cơ chế chống gửi lặp cảnh báo hết hạn (Listing + Threshold Days)
Trong `NotifyExpiringPackages` Command:
1.  Định nghĩa ngưỡng ngày cảnh báo hết hạn (ví dụ: `7` ngày trước khi hết hạn).
2.  Khi quét, tính `$daysLeft`. Nếu trùng với ngưỡng (ví dụ: 7 ngày), kiểm tra in-app notification xem đã gửi cảnh báo mốc này chưa:
    ```php
    $alreadyNotified = Notification::where('user_id', $owner->id)
        ->where('type', NotificationType::PACKAGE_EXPIRING->value)
        ->whereJsonContains('data->listing_id', $listing->id)
        ->whereJsonContains('data->threshold_days', $daysLeft)
        ->exists();
    ```
3.  Nếu chưa tồn tại -> dispatch `ListingPackageExpiring($listing->id, $daysLeft)`.
4.  Lưu `threshold_days` vào JSON `data` của thông báo lúc tạo.

---

### E. Database & Real-time (Reverb)

#### [NEW] [DatabaseChannel.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Notification/Channel/DatabaseChannel.php)
```php
final class DatabaseChannel implements NotificationChannel
{
    public function name(): NotificationChannelType { return NotificationChannelType::DATABASE; }

    public function send(User $user, NotificationType|MailType $type, array $data = []): void
    {
        if ($type instanceof MailType) return; // Hệ thống email không lưu DB

        $notification = Notification::create([
            'user_id' => $user->id,
            'type' => $type->value,
            'title' => $this->resolveTitle($type, $data),
            'content' => $this->resolveContent($type, $data),
            'data' => $data,
        ]);

        event(new NotificationSent($notification));
    }
}
```

#### [NEW] [NotificationSent.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Events/Notification/NotificationSent.php)
```php
final class NotificationSent implements ShouldBroadcastNow, ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    public function __construct(public readonly Notification $notification) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel('user.'.$this->notification->user_id)];
    }

    public function broadcastAs(): string
    {
        return 'notification.sent';
    }
}
```

---

### F. API Endpoints (NotificationController)

#### [NEW] [NotificationController.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Controllers/Api/V1/Notification/NotificationController.php)
1.  `GET /api/v1/notifications`:
    *   `$request->user()->notifications()->latest()->paginate($perPage)`
    *   Metadata trả về gồm `unread_count`.
2.  `GET /api/v1/notifications/unread-count`:
    *   `$request->user()->notifications()->unread()->count()`
3.  `POST /api/v1/notifications/{id}/read`:
    *   Query: `Notification::where('user_id', $request->user()->id)->where('id', $id)->firstOrFail()`
    *   Update `read_at => now()`.
4.  `POST /api/v1/notifications/read-all`:
    *   `$request->user()->notifications()->unread()->update(['read_at' => now()])`.

---

### G. Frontend (Vue 3)

#### [MODIFY] [AppHeader.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/common/AppHeader.vue)
*   Thực hiện lắng nghe sự kiện bằng tên sự kiện rút gọn:
    ```javascript
    echo.private(`user.${userId}`)
        .listen('.notification.sent', (event) => {
            // Toast, update unread count, prepend list
        });
    ```
*   Dọn dẹp đăng ký WebSocket khi unmounted/logout bằng `echo.leave()`.

#### [MODIFY] [index.vue (Profile)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Profile/index.vue)
*   Sửa lỗi watch đồng bộ dữ liệu.
*   Thêm tab "Thông báo" có icon chuông trong sidebar để click chuyển active tab.

---

## 3. Kế hoạch Kiểm thử & Xác minh

### A. Automated Tests
*   `tests/Feature/NotificationControllerTest.php`: Test bảo mật API scoped theo `auth()->id()`, mark read/read-all.
*   `tests/Feature/AppointmentBookingNotificationTest.php`: Kiểm tra khi đặt lịch mới, listener tạo đúng DB notification và gửi mail.
*   `tests/Feature/NotifyExpiringPackagesTest.php`: Kiểm tra chống trùng lặp gửi cảnh báo bằng `threshold_days`.

### B. Manual Verification
*   Thực hiện kiểm tra thực tế (kết hợp Reverb + mail sandbox) xem hệ thống in-app và email hoạt động chính xác.
