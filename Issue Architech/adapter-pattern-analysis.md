# Adapter Pattern — Phân tích & Đề xuất áp dụng trong Propify

> **Ngày tạo:** 2026-04-07  
> **Scope:** `PropifyBackend`  
> **Tác giả:** AI Code Review

---

## 1. Adapter Pattern là gì?

Adapter Pattern là một **structural design pattern** giúp hai interface **không tương thích** làm việc được với nhau, bằng cách tạo ra một lớp trung gian (Adapter) bọc đối tượng bên ngoài và expose interface mà hệ thống domain yêu cầu.

```
Client ──► [Target Interface] ◄── [Adapter] ──► [Adaptee (thư viện bên thứ 3)]
```

**Khi nào dùng?**
- Tích hợp thư viện bên thứ 3 mà không muốn domain phụ thuộc trực tiếp vào nó.
- Muốn hoán đổi implementation (Redis ↔ DB, Twilio ↔ Vonage) mà không sửa business logic.
- Muốn dễ test bằng cách mock qua interface domain thay vì gọi thật SDK.

---

## 2. Những chỗ có thể áp dụng trong Propify

### Tổng quan

| # | Vị trí | File | Ưu tiên | Trạng thái |
|---|--------|------|---------|------------|
| 1 | Google Socialite User → Domain | `AuthGoogleServiceImpl.php` | 🔴 Cao | Chưa làm |
| 2 | OTP Storage (Redis hardcode) | `OtpServiceImpl.php` | 🟡 Trung bình | Chưa làm |
| 3 | SMS / Zalo Notification Channel | *(chưa có file)* | 🟡 Trung bình | Cần khi mở rộng |
| 4 | Eloquent Repository | `EloquentUserRepository.php` | 🟢 Đã làm đúng | ✅ Không cần sửa |

---

## 3. Chi tiết từng vị trí

---

### 3.1 🔴 SocialUserAdapter — Ưu tiên cao nhất

#### Vấn đề hiện tại

File: `app/Services/Impl/AuthGoogleServiceImpl.php`

```php
// ❌ Service phụ thuộc TRỰC TIẾP vào Socialite API (thư viện bên thứ 3)
$googleUser = Socialite::driver('google')->stateless()->user();

$user = $this->userRepository->create([
    'full_name' => $googleUser->getName(),   // Socialite method
    'email'     => $googleUser->getEmail(),  // Socialite method
    'google_id' => $googleUser->getId(),     // Socialite method
]);
```

**Hệ quả:** Nếu thêm Facebook / GitHub login → phải sửa Service, tạo thêm `if/else` hoặc `match` để xử lý từng provider khác nhau.

#### Giải pháp: `SocialUserAdapter`

**Bước 1 — Tạo Target Interface (domain port):**

```php
// app/Services/Auth/SocialUserAdapter.php
namespace App\Services\Auth;

interface SocialUserAdapter
{
    public function getProviderId(): string;
    public function getName(): string;
    public function getEmail(): string;
    public function getAvatarUrl(): ?string;
}
```

**Bước 2 — Tạo Adapter cho Google (Socialite):**

```php
// app/Services/Auth/Adapters/GoogleSocialiteAdapter.php
namespace App\Services\Auth\Adapters;

use App\Services\Auth\SocialUserAdapter;
use Laravel\Socialite\Two\User as SocialiteUser;

final class GoogleSocialiteAdapter implements SocialUserAdapter
{
    public function __construct(
        private readonly SocialiteUser $socialiteUser
    ) {}

    public function getProviderId(): string  { return $this->socialiteUser->getId(); }
    public function getName(): string        { return $this->socialiteUser->getName(); }
    public function getEmail(): string       { return $this->socialiteUser->getEmail(); }
    public function getAvatarUrl(): ?string  { return $this->socialiteUser->getAvatar(); }
}
```

**Bước 3 — Service chỉ dùng interface domain:**

```php
// app/Services/Impl/AuthGoogleServiceImpl.php (sau khi refactor)

public function handleGoogleCallback(): RedirectResponse
{
    $socialiteUser = Socialite::driver('google')->stateless()->user();
    $adapted = new GoogleSocialiteAdapter($socialiteUser); // ← chỉ dùng Adapter ở đây

    return $this->processOAuthUser($adapted);
}

// Logic xử lý hoàn toàn không biết Socialite
private function processOAuthUser(SocialUserAdapter $user): RedirectResponse
{
    $existing = $this->userRepository->findByGoogleId($user->getProviderId())
        ?? $this->userRepository->findByEmail($user->getEmail());

    // ... upsert logic thuần domain
}
```

**Thêm Facebook sau này — chỉ tạo Adapter mới, không sửa gì:**

```php
// app/Services/Auth/Adapters/FacebookSocialiteAdapter.php
final class FacebookSocialiteAdapter implements SocialUserAdapter
{
    // implement theo Facebook Socialite API
}
```

---

### 3.2 🟡 OtpStorageAdapter — Ưu tiên trung bình

#### Vấn đề hiện tại

File: `app/Services/Otp/Impl/OtpServiceImpl.php`

```php
// ❌ Gọi thẳng Redis Facade — hardcode storage driver
Redis::pipeline(function ($pipe) use ($key, $otp) {
    $pipe->del($key);
    $pipe->setex($key, self::OTP_TTL_SECONDS, $otp);
});

$stored = Redis::get($this->redisKey($user, $context));
```

**Hệ quả:**
- Môi trường không có Redis (CI, test đơn giản) → phải mock toàn bộ Redis Facade.
- Muốn lưu OTP vào Database hoặc driver khác → phải sửa `OtpServiceImpl`.

#### Giải pháp: `OtpStorageAdapter`

```php
// app/Services/Otp/OtpStoragePort.php
namespace App\Services\Otp;

interface OtpStoragePort
{
    public function store(string $key, string $value, int $ttlSeconds): void;
    public function retrieve(string $key): ?string;
    public function delete(string $key): void;
}
```

```php
// app/Services/Otp/Adapters/RedisOtpStorageAdapter.php
final class RedisOtpStorageAdapter implements OtpStoragePort
{
    public function store(string $key, string $value, int $ttlSeconds): void
    {
        Redis::pipeline(fn($p) => [$p->del($key), $p->setex($key, $ttlSeconds, $value)]);
    }

    public function retrieve(string $key): ?string { return Redis::get($key); }
    public function delete(string $key): void      { Redis::del($key); }
}
```

```php
// app/Services/Otp/Adapters/CacheOtpStorageAdapter.php (dùng cho test / môi trường đơn giản)
final class CacheOtpStorageAdapter implements OtpStoragePort
{
    public function store(string $key, string $value, int $ttlSeconds): void
    {
        Cache::put($key, $value, $ttlSeconds);
    }

    public function retrieve(string $key): ?string { return Cache::get($key); }
    public function delete(string $key): void      { Cache::forget($key); }
}
```

```php
// OtpServiceImpl sau refactor
final class OtpServiceImpl implements OtpService
{
    public function __construct(
        private readonly NotificationService $notificationService,
        private readonly OtpStoragePort $storage // ← inject qua interface
    ) {}

    public function generate(User $user, OtpContext $context): string
    {
        $otp = $this->generateOtp();
        $this->storage->store($this->redisKey($user, $context), $otp, self::OTP_TTL_SECONDS);
        // ...
    }
}
```

**Đăng ký trong `AppServiceProvider`:**
```php
$this->app->bind(OtpStoragePort::class, RedisOtpStorageAdapter::class);

// Trong test:
$this->app->bind(OtpStoragePort::class, CacheOtpStorageAdapter::class);
```

---

### 3.3 🟡 SMS/Zalo Channel Adapter — Khi mở rộng

`NotificationChanelType` đã định nghĩa sẵn `SMS` và `ZALO` nhưng chưa có implementation.

Khi tích hợp SDK của Twilio (SMS) hoặc Zalo OA API — mỗi bên có interface hoàn toàn khác nhau. Adapter sẽ bọc từng SDK và expose `NotificationChannel` (interface đã có):

```php
// NotificationChannel.php — interface đã là Target Interface chuẩn
interface NotificationChannel
{
    public function name(): NotificationChanelType;
    public function send(User $user, MailType $template, array $data = []): void;
}
```

```php
// app/Services/Notification/Channel/SmsChannel.php
final class SmsChannel implements NotificationChannel
{
    public function __construct(
        private readonly TwilioClient $twilio // SDK bên thứ 3
    ) {}

    public function name(): NotificationChanelType
    {
        return NotificationChanelType::SMS;
    }

    public function send(User $user, MailType $template, array $data = []): void
    {
        // Map template → SMS content, rồi gọi Twilio API qua Adapter
        $message = $this->buildSmsContent($template, $data);
        $this->twilio->messages->create($user->phone, [
            'from' => config('services.twilio.from'),
            'body' => $message,
        ]);
    }
}
```

> **Lưu ý:** `NotificationChannel` hiện tại đã là một **Strategy + Adapter hybrid** tốt. Chỉ cần thêm class mới, không cần sửa `NotificationServiceImpl`.

---

### 3.4 🟢 EloquentUserRepository — Đã đúng, không cần sửa

```
UserRepository (interface domain)    ← Target Interface
       ↑ implements
EloquentUserRepository               ← Adapter
       ↓ wraps
Eloquent Model (thư viện Laravel)    ← Adaptee
```

`EloquentUserRepository` thực chất đã là Adapter Pattern — bọc Eloquent và expose interface domain `UserRepository`. Đây là cách đúng đắn. Không cần thay đổi.

---

## 4. So sánh: Những pattern đang dùng trong project

| Pattern | Nơi sử dụng | Nhận xét |
|---------|------------|----------|
| **Repository Pattern** (có Adapter) | `EloquentUserRepository` | ✅ Đúng |
| **Strategy Pattern** | `NotificationChannel` (Email, SMS, Zalo) | ✅ Đúng |
| **Adapter Pattern** | `EloquentUserRepository` (ngầm) | ✅ Đã có, chưa explicit |
| ❌ Thiếu Adapter | `AuthGoogleServiceImpl` → Socialite | 🔴 Nên refactor |
| ❌ Thiếu Adapter | `OtpServiceImpl` → Redis | 🟡 Cân nhắc |

---

## 5. Lộ trình đề xuất

```
Phase 1 (Ngay bây giờ):
  └─ Tạo SocialUserAdapter + GoogleSocialiteAdapter
     → Refactor AuthGoogleServiceImpl dùng adapter

Phase 2 (Trước khi thêm SMS/Zalo):
  └─ Tạo OtpStoragePort + RedisOtpStorageAdapter + CacheOtpStorageAdapter
     → Inject vào OtpServiceImpl

Phase 3 (Khi tích hợp Twilio / Zalo OA):
  └─ Tạo SmsChannel + ZaloChannel implements NotificationChannel
     → Register vào AppServiceProvider
```

---

## 6. Cấu trúc thư mục sau khi áp dụng

```
app/
├── Services/
│   ├── Auth/
│   │   ├── SocialUserAdapter.php           ← Target Interface (NEW)
│   │   └── Adapters/
│   │       ├── GoogleSocialiteAdapter.php  ← Adapter (NEW)
│   │       └── FacebookSocialiteAdapter.php (tương lai)
│   │
│   ├── Otp/
│   │   ├── OtpStoragePort.php              ← Target Interface (NEW)
│   │   ├── Adapters/
│   │   │   ├── RedisOtpStorageAdapter.php  ← Adapter (NEW)
│   │   │   └── CacheOtpStorageAdapter.php  ← Adapter (test) (NEW)
│   │   └── Impl/
│   │       └── OtpServiceImpl.php          ← MODIFY: inject OtpStoragePort
│   │
│   └── Notification/
│       └── Channel/
│           ├── NotificationChannel.php     ← Giữ nguyên ✅
│           ├── EmailChannel.php            ← Giữ nguyên ✅
│           ├── SmsChannel.php              ← (tương lai)
│           └── ZaloChannel.php             ← (tương lai)
```

---

*Tài liệu này được tạo dựa trên phân tích source code thực tế của dự án Propify.*
