# Kế hoạch triển khai: Hệ thống thông báo Nâng cấp gói tin & Sắp hết hạn (Refined)

Kế hoạch này tích hợp chức năng thông báo thời gian thực trên trình duyệt (in-app notification qua WebSockets/Reverb) và qua email khi nâng cấp gói tin thành công hoặc khi gói tin sắp hết hạn, tuân thủ các chỉ đạo bổ sung của người dùng.

---

## 1. Thiết kế Design Pattern & Kiến trúc Refined

### A. Observer Pattern (Laravel Events & Queue Listeners)
*   **Mục đích**: Tách biệt luồng nghiệp vụ khỏi luồng gửi thông báo, tránh làm nghẽn phản hồi HTTP.
*   **Triển khai**:
    *   **Nâng cấp thành công**: `UpgradeListingCommand` phát `ListingPackageUpgraded` event. Listener `SendPackageUpgradeNotification` lắng nghe và xử lý.
    *   **Sắp hết hạn**: Command `NotifyExpiringPackages` chạy hàng ngày, phát `ListingPackageExpiring` cho từng tin đăng đủ điều kiện. Listener `SendPackageExpiringNotification` lắng nghe và xử lý.
    *   **Tất cả Listeners** sẽ implements `ShouldQueue` để đẩy tác vụ gửi mail/lưu DB vào hàng đợi xử lý bất đồng bộ (Queue).

### B. Strategy Pattern (Notification Channels)
*   `NotificationService` quản lý danh sách các `NotificationChannel` strategies (Email, Database).
*   Ta sẽ thiết kế `DatabaseChannel` thực hiện 2 nhiệm vụ: Lưu bản ghi DB và phát event broadcast real-time.

### C. Real-time Broadcasting & Channel Name
*   Broadcast event `NotificationSent` (implement `ShouldBroadcast`).
*   **Channel Routing**: Sử dụng kênh private channel chuẩn của Laravel: `user.{id}` (không hardcode tiền tố `private-` ở backend vì Laravel/Pusher tự động thêm tiền tố này cho PrivateChannel).
*   Xác thực quyền subscribe channel `user.{userId}` trong `routes/channels.php` bằng `api` guard JWT.

---

## 2. Các thay đổi chi tiết

### A. Tách biệt NotificationType và MailType
Tách biệt rõ ràng: `MailType` chỉ phục vụ các mẫu mail thô, `NotificationType` định danh cho các sự kiện thông báo trong hệ thống (in-app & email).

#### [NEW] [NotificationType.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Enums/NotificationType.php)
```php
namespace App\Enums;

enum NotificationType: string
{
    case WELCOME = 'welcome';
    case PASSWORD_RESET = 'password_reset';
    case VERIFY_EMAIL = 'verify_email';
    case FORGOT_PASSWORD = 'forgot_password';
    case PACKAGE_UPGRADED = 'package_upgraded';
    case PACKAGE_EXPIRING = 'package_expiring';
}
```

#### [MODIFY] [NotificationService.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Notification/NotificationService.php) & [NotificationServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Notification/Impl/NotificationServiceImpl.php)
*   Cập nhật signature của method `send()` để nhận `NotificationType` thay vì `MailType`.
*   Cập nhật các code gọi dịch vụ này hiện tại (như `OtpServiceImpl`, `SendWelcomeNotification`) để truyền `NotificationType`.

#### [MODIFY] [EmailChannel.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Notification/Channel/EmailChannel.php)
*   Ánh xạ `NotificationType` sang mailable tương ứng:
    *   `NotificationType::WELCOME` -> `WelcomeMail`
    *   `NotificationType::VERIFY_EMAIL` -> `VerifyEmailMail`
    *   `NotificationType::FORGOT_PASSWORD` -> `ForgotPasswordMail`
    *   `NotificationType::PACKAGE_UPGRADED` -> `PackageUpgradedMail`
    *   `NotificationType::PACKAGE_EXPIRING` -> `PackageExpiringMail` (lấy dữ liệu từ data array).

---

### B. Database & Models
#### [NEW] [create_notifications_table.php](file:///d:/PROJECT/Meyland/PropifyBackend/database/migrations/2026_06_03_000003_create_notifications_table.php)
```php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('type'); // Lưu NotificationType->value
    $table->string('title');
    $table->text('content');
    $table->json('data')->nullable(); // Lưu {listing_id, package_name, package_expires_at, ...}
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
});
```

#### [NEW] [Notification.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Models/Notification.php)
Model quản lý thông báo, cast `data` thành `array`, thêm các helper query scope `unread()` và `read()`.

#### [NEW] [DatabaseChannel.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Notification/Channel/DatabaseChannel.php)
Strategy in-app notification:
*   Tạo bản ghi `Notification` trong cơ sở dữ liệu.
*   Sau khi tạo, dispatch `NotificationSent` event để truyền phát real-time.

---

### C. Chống gửi cảnh báo hết hạn lặp lại
Để tránh gửi liên tục mỗi ngày khi tin đăng nằm trong khoảng "sắp hết hạn" (7 ngày):
*   Khi gửi thông báo `package_expiring`, ta lưu thông tin `package_expires_at` của listing vào cột `data->package_expires_at` trong DB.
*   Trong command `NotifyExpiringPackages`, trước khi phát sự kiện gửi thông báo, thực hiện kiểm tra:
    ```php
    $alreadyNotified = Notification::where('user_id', $owner->id)
        ->where('type', NotificationType::PACKAGE_EXPIRING->value)
        ->where('data->listing_id', $listing->id)
        ->where('data->package_expires_at', $listing->package_expires_at->toDateTimeString())
        ->exists();
    ```
*   Nếu đã tồn tại bản ghi cảnh báo ứng với thời gian hết hạn cụ thể này, bỏ qua không gửi lại.
*   Khi người dùng tiến hành gia hạn thành công, `package_expires_at` của listing sẽ thay đổi, do đó cơ chế trên tự động reset cho chu kỳ tiếp theo.

---

### D. Xử lý Real-time (Reverb/Echo)

#### [NEW] [NotificationSent.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Events/Notification/NotificationSent.php)
*   Implement `ShouldBroadcastNow` (hoặc `ShouldBroadcast` với queue mặc định).
*   Định nghĩa channel: `new PrivateChannel('user.'.$this->notification->user_id)`.
*   Payload: Toàn bộ thông tin bản ghi `Notification` mới tạo.

#### [MODIFY] [channels.php](file:///d:/PROJECT/Meyland/PropifyBackend/routes/channels.php)
```php
Broadcast::channel('user.{userId}', function (User $user, int $userId) {
    return (int) $user->id === (int) $userId;
}, ['guards' => ['api']]);
```

---

### E. Quy trình Event & Queue Listeners
*   `SendPackageUpgradeNotification` implements `ShouldQueue` -> lắng nghe `ListingPackageUpgraded`.
*   `SendPackageExpiringNotification` implements `ShouldQueue` -> lắng nghe `ListingPackageExpiring`.

---

### F. API Scoped theo Auth User & Unread Count

#### [NEW] [NotificationController.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Controllers/Api/V1/Notification/NotificationController.php)
Mọi API đều lấy user qua `auth()->user()` để bảo mật:
1.  `GET /api/v1/notifications`:
    *   Lấy danh sách thông báo của user: `$request->user()->notifications()->latest()->paginate(...)`.
    *   Response bao gồm cả tổng số thông báo chưa đọc (`unread_count`) trong metadata.
2.  `POST /api/v1/notifications/{id}/read`:
    *   Đánh dấu một thông báo là đã đọc (chỉ cho phép nếu thông báo thuộc về `$request->user()`).
3.  `POST /api/v1/notifications/read-all`:
    *   Đánh dấu toàn bộ thông báo chưa đọc của user là đã đọc: `$request->user()->notifications()->unread()->update(['read_at' => now()])`.

#### [MODIFY] [api.php](file:///d:/PROJECT/Meyland/PropifyBackend/routes/api.php)
Đăng ký các endpoint trong nhóm bảo vệ bởi `auth:api`.

---

### G. Frontend (Vue 3) & Cleanup Echo Subscription

#### [NEW] [notificationService.js](file:///d:/PROJECT/Meyland/PropifyFrontend/src/services/notificationService.js)
Service giao tiếp với các API thông báo.

#### [MODIFY] [AppHeader.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/common/AppHeader.vue)
*   Thêm icon chiếc chuông thông báo (Notification bell) và dropdown danh sách thông báo nhanh.
*   **Real-time Echo & Cleanup**:
    *   Khi `authStore.isAuthenticated` thay đổi thành `true`, lấy Echo instance và subscribe:
        ```javascript
        const channelName = `user.${authStore.user.id}`;
        echo.private(channelName).listen('.NotificationSent', (e) => {
            // Cập nhật danh sách thông báo, tăng unread_count, show toast
        });
        ```
    *   **Cleanup**: Khi component bị unmount (`onUnmounted`) hoặc khi người dùng đăng xuất (`handleLogout`), bắt buộc gọi:
        ```javascript
        if (echo) {
            echo.leave(`user.${authStore.user.id}`);
        }
        ```
        để hủy lắng nghe và dọn dẹp kết nối WebSocket.

#### [MODIFY] [index.vue (Profile)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Profile/index.vue)
*   Thêm tab "Thông báo" (Notifications) hiển thị danh sách đầy đủ có phân trang và nút "Đánh dấu tất cả là đã đọc".

---

## 3. Kế hoạch Kiểm thử & Xác minh

### A. Automated Tests
*   `tests/Feature/NotificationControllerTest.php`: Test phân quyền (chỉ lấy thông báo của bản thân), test mark read, mark all read.
*   `tests/Feature/NotifyExpiringPackagesTest.php`: Test command quét hết hạn, kiểm tra chống trùng lặp cảnh báo.

### B. Manual Verification
*   Giả lập nâng cấp/gia hạn gói -> Kiểm tra mail (Mailpit) và thông báo giao diện.
*   Đăng xuất -> Đảm bảo Echo connection đã hủy subscribe kênh `user.{id}`.
