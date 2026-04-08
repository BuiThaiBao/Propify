# Thiết Kế Hệ Thống — Design Patterns & SOLID Principles
## Dự án: Propify — Nền tảng Bất Động Sản

> **Ngày tạo:** 2026-04-08  
> **Nguồn phân tích:** Product Backlog (BẤT ĐỘNG SẢN.xlsx) + Source code thực tế  
> **Stack:** Laravel (Backend) · Vue.js (Frontend) · MySQL · Redis

---

## Mục lục

1. [Tổng quan chức năng hệ thống](#1-tổng-quan-chức-năng-hệ-thống)
2. [Kiến trúc tổng thể](#2-kiến-trúc-tổng-thể)
3. [SOLID Principles](#3-solid-principles)
4. [Design Patterns](#4-design-patterns)
5. [Ánh xạ Pattern → Chức năng](#5-ánh-xạ-pattern--chức-năng)
6. [Lộ trình triển khai](#6-lộ-trình-triển-khai)

---

## 1. Tổng quan chức năng hệ thống

### 1.1 Khách hàng (Customer)

| Nhóm | Chức năng |
|------|-----------|
| **Tài khoản** | Đăng ký · Đăng nhập (Email/Google) · Xem/Sửa thông tin · Đổi mật khẩu · Quên mật khẩu |
| **Tin đăng** | Tạo · Xem danh sách · Xem chi tiết · Chỉnh sửa · Nhân đôi · Xóa · Lọc · Tìm kiếm |
| **Gói tin** | Nâng cấp gói · Thanh toán · Hủy thanh toán · Xác thực tin đăng |
| **Tương tác** | Tin yêu thích · Chat với người đăng |
| **Lịch hẹn** | Đặt · Xem · Sửa · Hủy · Xác nhận · Từ chối |
| **Lịch sử GD** | Tìm kiếm · Lọc · Xem chi tiết lịch sử giao dịch |

### 1.2 Admin

| Nhóm | Chức năng |
|------|-----------|
| **Dashboard** | Xem tổng quan hệ thống |
| **Tin đăng** | Tìm kiếm · Lọc · Xem danh sách/chi tiết · Duyệt · Từ chối · Khóa · Xác thực |
| **Gói tin** | Thêm · Sửa · Khóa/Kích hoạt · Tìm kiếm · Lọc · Xem thông tin |
| **Tài khoản** | Tìm kiếm · Lọc · Xem · Khóa · Mở khóa |
| **Tiện ích** | Thêm · Sửa · Cài đặt hiển thị |
| **Doanh thu** | Xem doanh thu · Xuất báo cáo |

---

## 2. Kiến trúc tổng thể

```
┌─────────────────────────────────────────────────────────────────┐
│                        CLIENT LAYER                             │
│   PropifyFrontend (Vue.js)    PropifyAdmin (Vue.js)             │
└─────────────────────┬───────────────────────────────────────────┘
                      │ HTTP / WebSocket
┌─────────────────────▼───────────────────────────────────────────┐
│                      API LAYER (Laravel)                         │
│  Controllers → FormRequests → DTOs                              │
└─────────────────────┬───────────────────────────────────────────┘
                      │
┌─────────────────────▼───────────────────────────────────────────┐
│                    SERVICE LAYER                                 │
│  AuthService · ListingService · PaymentService · ...            │
└──────────┬───────────────────────────┬──────────────────────────┘
           │                          │
┌──────────▼──────────┐   ┌───────────▼────────────────────────┐
│  REPOSITORY LAYER   │   │     INFRASTRUCTURE LAYER           │
│  UserRepository     │   │  Redis (OTP/Cache) · Mail · Queue  │
│  ListingRepository  │   │  Socialite · VNPay · Pusher        │
│  ...                │   └────────────────────────────────────┘
└──────────┬──────────┘
┌──────────▼──────────┐
│   DATABASE (MySQL)  │
│  users · properties │
│  packages · ...     │
└─────────────────────┘
```

---

## 3. SOLID Principles

### 3.1 Single Responsibility Principle (SRP)

> **"Mỗi class chỉ có một lý do để thay đổi."**

#### ✅ Đang áp dụng tốt

| Class | Trách nhiệm duy nhất |
|-------|---------------------|
| `AuthServiceImpl` | Xử lý logic xác thực người dùng |
| `OtpServiceImpl` | Quản lý vòng đời OTP (sinh, xác thực, hủy) |
| `NotificationServiceImpl` | Dispatch thông báo qua các channel |
| `EmailChannel` | Gửi thông báo qua email |
| `EloquentUserRepository` | Truy vấn DB cho User |
| `TokenProcessServiceImpl` | Quản lý JWT blacklist |

#### ⚠️ Cần cải thiện khi mở rộng

**Vấn đề:** `AuthGoogleServiceImpl` đang đảm nhận 3 trách nhiệm:
1. Redirect tới Google OAuth
2. Xử lý callback từ Google
3. Upsert User vào DB (create/link account)

**Giải pháp:**
```php
// Tách thành:
AuthGoogleServiceImpl  → chỉ làm OAuth flow (redirect + callback)
UserUpsertService      → logic tạo/liên kết tài khoản Social
```

**Vấn đề tương lai:** Khi có `ListingService`, tránh nhồi quá nhiều logic:
- Validate tin đăng
- Tính phí gói
- Gửi thông báo duyệt
- Cập nhật trạng thái

→ Mỗi nhóm nên tách thành class riêng.

---

### 3.2 Open/Closed Principle (OCP)

> **"Mở cho mở rộng, đóng cho sửa đổi."**

#### ✅ Đang áp dụng tốt

`NotificationServiceImpl` + `NotificationChannel` interface:
- Thêm kênh SMS/Zalo mới → **chỉ tạo class mới**, không sửa `NotificationServiceImpl`.

```php
// Mở rộng mà không sửa code cũ:
class SmsChannel implements NotificationChannel { ... }
class ZaloChannel implements NotificationChannel { ... }
```

#### 📋 Áp dụng cho chức năng mới

**Thanh toán (Payment):**
```php
interface PaymentGateway {
    public function createPayment(PaymentRequest $request): PaymentResult;
    public function handleCallback(array $data): PaymentResult;
}

class VnPayGateway implements PaymentGateway { ... }
class MomoGateway implements PaymentGateway { ... }  // thêm sau
class ZaloPayGateway implements PaymentGateway { ... } // thêm sau
```

**Xuất báo cáo (Admin - Xuất báo cáo):**
```php
interface ReportExporter {
    public function export(ReportData $data): ExportResult;
    public function getMimeType(): string;
}

class ExcelReportExporter implements ReportExporter { ... }
class PdfReportExporter implements ReportExporter { ... }
class CsvReportExporter implements ReportExporter { ... }
```

**Đăng nhập Social (Login Provider):**
```php
interface SocialAuthProvider {
    public function redirect(): RedirectResponse;
    public function getUser(): SocialUserAdapter;
}

class GoogleAuthProvider implements SocialAuthProvider { ... }
class FacebookAuthProvider implements SocialAuthProvider { ... }
```

---

### 3.3 Liskov Substitution Principle (LSP)

> **"Subtype phải thay thế được supertype mà không thay đổi tính đúng đắn."**

#### ✅ Đang áp dụng tốt

Tất cả các implementation đều thay thế được interface:
- `EloquentUserRepository` → thay thế `UserRepository` ✅
- `EmailChannel` → thay thế `NotificationChannel` ✅
- `OtpServiceImpl` → thay thế `OtpService` ✅

#### 📋 Lưu ý khi mở rộng

Khi tạo `ListingRepository`, `PaymentGateway`, `ReportExporter` — mỗi implementation phải:
- Không throw exception không được định nghĩa trong interface
- Không bỏ qua behaviors mà interface cam kết
- Test bằng contract test (unit test chạy cho tất cả implementations)

```php
// Contract test cho mọi PaymentGateway implementation
abstract class PaymentGatewayContractTest extends TestCase {
    abstract protected function makeGateway(): PaymentGateway;

    public function test_creates_payment_successfully(): void { ... }
    public function test_handles_callback_correctly(): void { ... }
}
```

---

### 3.4 Interface Segregation Principle (ISP)

> **"Client không nên bị buộc phụ thuộc vào interface mà nó không sử dụng."**

#### 📋 Áp dụng cho chức năng `Listing`

Thay vì một interface to:
```php
// ❌ Quá lớn - Admin và Customer dùng khác nhau
interface ListingService {
    public function create(...);
    public function update(...);
    public function delete(...);
    public function approve(...);  // chỉ Admin
    public function reject(...);   // chỉ Admin
    public function lock(...);     // chỉ Admin
    public function search(...);
    public function filter(...);
    public function duplicate(...); // chỉ Customer
}
```

Tách nhỏ theo người dùng:
```php
// ✅ Tách theo vai trò và trách nhiệm
interface ListingWriteService {
    public function create(CreateListingDto $dto): Listing;
    public function update(int $id, UpdateListingDto $dto): Listing;
    public function delete(int $id): void;
    public function duplicate(int $id): Listing;
}

interface ListingReadService {
    public function findById(int $id): ?Listing;
    public function paginate(ListingFilterDto $filter): LengthAwarePaginator;
    public function search(string $keyword): Collection;
}

interface ListingModerationService {
    public function approve(int $id): void;
    public function reject(int $id, string $reason): void;
    public function lock(int $id): void;
}
```

#### 📋 Áp dụng cho `AppointmentService`

```php
interface AppointmentBookingService {
    public function book(BookAppointmentDto $dto): Appointment;
    public function cancel(int $id): void;
    public function reschedule(int $id, RescheduleDto $dto): Appointment;
}

interface AppointmentResponseService {
    public function confirm(int $id): void;
    public function decline(int $id, string $reason): void;
}

interface AppointmentQueryService {
    public function findById(int $id): ?Appointment;
    public function listForUser(int $userId): Collection;
    public function listForPoster(int $posterId): Collection;
}
```

---

### 3.5 Dependency Inversion Principle (DIP)

> **"Module cao tầng không phụ thuộc module thấp tầng — cả hai phụ thuộc vào abstraction."**

#### ✅ Đang áp dụng tốt

```php
// AuthServiceImpl phụ thuộc INTERFACE, không phụ thuộc Eloquent trực tiếp
public function __construct(
    private readonly UserRepository $userRepository,      // interface ✅
    private readonly OtpService $otpService,              // interface ✅
    private readonly TokenProcessService $tokenProcessService, // interface ✅
)
```

#### 📋 Phải duy trì khi mở rộng

```php
// ✅ Đúng
class ListingServiceImpl {
    public function __construct(
        private readonly ListingRepository $repo,         // interface
        private readonly PaymentGateway $payment,         // interface
        private readonly NotificationService $notify,     // interface
        private readonly StorageAdapter $storage,         // interface
    ) {}
}

// ❌ Sai — phụ thuộc concrete class
class ListingServiceImpl {
    public function __construct(
        private readonly EloquentListingRepository $repo, // concrete ❌
        private readonly VnPayGateway $payment,           // concrete ❌
    ) {}
}
```

---

## 4. Design Patterns

### 4.1 Repository Pattern *(Đang dùng)*

**Áp dụng:** User management, và tất cả module mới.

```
Interface:  UserRepository
Impl:       EloquentUserRepository
```

**Cần tạo thêm:**

```php
// Listing Module
interface ListingRepository {
    public function create(array $data): Listing;
    public function findById(int $id): ?Listing;
    public function paginate(array $filters, int $perPage): LengthAwarePaginator;
    public function update(int $id, array $data): Listing;
    public function delete(int $id): void;
    public function findByUserId(int $userId): Collection;
}

// Package Module
interface PackageRepository {
    public function findActive(): Collection;
    public function findById(int $id): ?Package;
    public function create(array $data): Package;
    public function toggleStatus(int $id): Package;
}

// Appointment Module
interface AppointmentRepository {
    public function create(array $data): Appointment;
    public function findById(int $id): ?Appointment;
    public function findByListingId(int $listingId): Collection;
    public function updateStatus(int $id, string $status): Appointment;
}

// Payment Module
interface PaymentRepository {
    public function create(array $data): Payment;
    public function findByOrderCode(string $code): ?Payment;
    public function findByUserId(int $userId): Collection;
}
```

---

### 4.2 Strategy Pattern *(Đang dùng - mở rộng thêm)*

**Đang có:** `NotificationChannel` (Email · SMS · Zalo)

**Mở rộng cho Thanh toán:**

```php
interface PaymentGateway
{
    public function name(): PaymentProvider;           // VNPAY, MOMO, ZALOPAY
    public function createPayment(PaymentRequest $req): PaymentUrl;
    public function handleCallback(array $payload): PaymentResult;
    public function verifySignature(array $payload): bool;
}

// PaymentService sử dụng Strategy
class PaymentServiceImpl implements PaymentService
{
    /** @param PaymentGateway[] $gateways */
    public function __construct(private readonly array $gateways) {}

    public function pay(PaymentProvider $provider, PaymentRequest $req): PaymentUrl
    {
        $gateway = collect($this->gateways)
            ->first(fn($g) => $g->name() === $provider);

        if (!$gateway) {
            throw new UnsupportedPaymentProviderException($provider);
        }

        return $gateway->createPayment($req);
    }
}
```

**Mở rộng cho Xuất báo cáo:**

```php
interface ReportExporter
{
    public function format(): ExportFormat;  // EXCEL, PDF, CSV
    public function export(ReportData $data): StreamedResponse;
}
```

**Mở rộng cho Tìm kiếm/Lọc tin đăng:**

```php
interface ListingFilter
{
    public function apply(Builder $query, array $params): Builder;
}

class PriceRangeFilter implements ListingFilter { ... }
class LocationFilter implements ListingFilter { ... }
class PropertyTypeFilter implements ListingFilter { ... }
class AreaRangeFilter implements ListingFilter { ... }
class ListingStatusFilter implements ListingFilter { ... }
```

---

### 4.3 Adapter Pattern *(Cần tạo thêm)*

**Chi tiết:** xem file `adapter-pattern-analysis.md`

#### SocialUserAdapter — Login Google/Facebook

```php
interface SocialUserAdapter {
    public function getProviderId(): string;
    public function getProviderName(): string;  // 'google', 'facebook'
    public function getName(): string;
    public function getEmail(): string;
    public function getAvatarUrl(): ?string;
}

class GoogleSocialiteAdapter implements SocialUserAdapter { ... }
class FacebookSocialiteAdapter implements SocialUserAdapter { ... }
```

#### OtpStorageAdapter — Tách Redis khỏi OtpService

```php
interface OtpStoragePort {
    public function store(string $key, string $value, int $ttlSeconds): void;
    public function retrieve(string $key): ?string;
    public function delete(string $key): void;
}

class RedisOtpStorageAdapter implements OtpStoragePort { ... }
class CacheOtpStorageAdapter implements OtpStoragePort { ... }  // cho test
```

#### StorageAdapter — Upload ảnh tin đăng

```php
interface StorageAdapter {
    public function upload(UploadedFile $file, string $path): string;  // URL
    public function delete(string $path): void;
    public function getUrl(string $path): string;
}

class LocalStorageAdapter implements StorageAdapter { ... }
class S3StorageAdapter implements StorageAdapter { ... }
class CloudinaryAdapter implements StorageAdapter { ... }
```

---

### 4.4 Observer Pattern / Event-Listener *(Đang dùng - mở rộng)*

**Đang có:** `UserRegistered` event → gửi Welcome Mail

**Mở rộng theo chức năng:**

```
Event: ListingCreated
  → Listener: NotifyAdminForModeration    (gửi mail cho admin duyệt)
  → Listener: LogListingActivity          (ghi log)

Event: ListingApproved
  → Listener: NotifyPosterApproved        (gửi mail cho chủ tin)
  → Listener: UpdateListingStatus         (cập nhật trạng thái)

Event: PaymentSucceeded
  → Listener: ActivateListingPackage      (kích hoạt gói tin)
  → Listener: SendPaymentReceipt          (gửi hóa đơn mail)
  → Listener: RecordRevenue               (ghi doanh thu)

Event: AppointmentBooked
  → Listener: NotifyPoster                (SMS/Email cho chủ nhà)
  → Listener: SendCalendarInvite          (gửi lịch)

Event: AppointmentConfirmed
  → Listener: NotifyViewer                (thông báo khách hẹn)

Event: AppointmentCancelled
  → Listener: NotifyBothParties           (thông báo cả hai)
  → Listener: FreeAppointmentSlot         (giải phóng khung giờ)
```

```php
// Ví dụ triển khai
class PaymentSucceeded
{
    public function __construct(
        public readonly Payment $payment,
        public readonly User $user,
        public readonly Listing $listing,
    ) {}
}

class ActivateListingPackage implements ShouldQueue
{
    public function handle(PaymentSucceeded $event): void
    {
        // Kích hoạt gói tin sau khi thanh toán thành công
    }
}
```

---

### 4.5 Factory Pattern

**Áp dụng cho:** Tạo Email Mailable objects (đang có), mở rộng cho Payment & Report.

#### Hiện tại (implicit factory trong EmailChannel):

```php
// Đang dùng match expression như một Factory
$mailable = match ($mailType) {
    MailType::WELCOME         => new WelcomeMail($user, $data),
    MailType::VERIFY_EMAIL    => new VerifyEmailMail($user, $data),
    MailType::FORGOT_PASSWORD => new ForgotPasswordMail($user, $data),
};
```

#### Mở rộng — MailableFactory:

```php
class MailableFactory
{
    public function create(MailType $type, User $user, array $data): Mailable
    {
        return match ($type) {
            MailType::WELCOME          => new WelcomeMail($user, $data),
            MailType::VERIFY_EMAIL     => new VerifyEmailMail($user, $data),
            MailType::FORGOT_PASSWORD  => new ForgotPasswordMail($user, $data),
            MailType::LISTING_APPROVED => new ListingApprovedMail($user, $data),
            MailType::PAYMENT_SUCCESS  => new PaymentSuccessMail($user, $data),
            MailType::APPOINTMENT_BOOKED => new AppointmentBookedMail($user, $data),
        };
    }
}
```

#### PaymentGatewayFactory:

```php
class PaymentGatewayFactory
{
    /** @param PaymentGateway[] $gateways */
    public function __construct(private readonly array $gateways) {}

    public function make(PaymentProvider $provider): PaymentGateway
    {
        $gateway = collect($this->gateways)
            ->first(fn($g) => $g->name() === $provider);

        return $gateway ?? throw new UnsupportedPaymentProviderException($provider);
    }
}
```

---

### 4.6 Decorator Pattern

**Áp dụng cho:** Cache layer trên Repository.

Khi danh sách tin đăng cần cache để giảm tải DB:

```php
// Base repository
class EloquentListingRepository implements ListingRepository
{
    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        return Listing::filter($filters)->paginate($perPage);
    }
}

// Decorator thêm cache
class CachedListingRepository implements ListingRepository
{
    public function __construct(
        private readonly ListingRepository $inner,  // decorated
        private readonly Cache $cache,
    ) {}

    public function paginate(array $filters, int $perPage): LengthAwarePaginator
    {
        $key = 'listings:' . md5(serialize($filters) . $perPage);

        return $this->cache->remember($key, 300, function () use ($filters, $perPage) {
            return $this->inner->paginate($filters, $perPage);
        });
    }

    // Invalidate cache khi write
    public function create(array $data): Listing
    {
        $result = $this->inner->create($data);
        $this->cache->tags('listings')->flush();
        return $result;
    }
}
```

---

### 4.7 Builder Pattern

**Áp dụng cho:** Tạo `Listing` phức tạp và Query Builder cho tìm kiếm/lọc.

#### ListingBuilder — Tạo tin đăng:

```php
class ListingBuilder
{
    private array $data = [];
    private array $images = [];
    private array $amenities = [];

    public function setBasicInfo(string $title, string $description, float $price): self
    {
        $this->data['title'] = $title;
        $this->data['description'] = $description;
        $this->data['price'] = $price;
        return $this;
    }

    public function setLocation(string $province, string $district, string $address): self
    {
        $this->data['province'] = $province;
        $this->data['district'] = $district;
        $this->data['address'] = $address;
        return $this;
    }

    public function setPropertyDetails(float $area, int $bedrooms, int $bathrooms): self
    {
        $this->data['area'] = $area;
        $this->data['bedrooms'] = $bedrooms;
        $this->data['bathrooms'] = $bathrooms;
        return $this;
    }

    public function addImages(array $uploadedFiles): self
    {
        $this->images = $uploadedFiles;
        return $this;
    }

    public function attachAmenities(array $amenityIds): self
    {
        $this->amenities = $amenityIds;
        return $this;
    }

    public function build(): Listing
    {
        // Tạo Listing + lưu images + gắn amenities
        $listing = Listing::create($this->data);
        // ... xử lý images, amenities
        return $listing;
    }
}

// Sử dụng:
$listing = (new ListingBuilder())
    ->setBasicInfo('Căn hộ 2PN Q1', '...', 15_000_000)
    ->setLocation('TP.HCM', 'Quận 1', '123 Lê Lợi')
    ->setPropertyDetails(65.5, 2, 2)
    ->addImages($request->file('images'))
    ->attachAmenities([1, 3, 5]) // gym, pool, parking
    ->build();
```

#### ListingQueryBuilder — Bộ lọc tin đăng:

```php
class ListingQueryBuilder
{
    private Builder $query;

    public function __construct()
    {
        $this->query = Listing::query()->where('status', 'approved');
    }

    public function inProvince(string $province): self
    {
        $this->query->where('province', $province);
        return $this;
    }

    public function priceRange(float $min, float $max): self
    {
        $this->query->whereBetween('price', [$min, $max]);
        return $this;
    }

    public function areaRange(float $min, float $max): self
    {
        $this->query->whereBetween('area', [$min, $max]);
        return $this;
    }

    public function type(string $type): self  // sale, rent
    {
        $this->query->where('type', $type);
        return $this;
    }

    public function keyword(string $keyword): self
    {
        $this->query->where(function ($q) use ($keyword) {
            $q->where('title', 'like', "%{$keyword}%")
              ->orWhere('description', 'like', "%{$keyword}%")
              ->orWhere('address', 'like', "%{$keyword}%");
        });
        return $this;
    }

    public function sortBy(string $field, string $direction = 'desc'): self
    {
        $this->query->orderBy($field, $direction);
        return $this;
    }

    public function paginate(int $perPage = 20): LengthAwarePaginator
    {
        return $this->query->paginate($perPage);
    }
}

// Sử dụng trong Controller/Service:
$listings = (new ListingQueryBuilder())
    ->inProvince('TP.HCM')
    ->priceRange(5_000_000, 20_000_000)
    ->type('rent')
    ->keyword('quận 1')
    ->sortBy('created_at')
    ->paginate(20);
```

---

### 4.8 State Pattern

**Áp dụng cho:** Trạng thái Tin đăng và Lịch hẹn.

#### Trạng thái Tin đăng (Listing Status):

```
PENDING → (Admin duyệt) → APPROVED
                       → REJECTED → (User sửa, nộp lại) → PENDING
APPROVED → (Admin khóa) → LOCKED
APPROVED → (User xóa)  → DELETED
APPROVED → (Hết hạn gói) → EXPIRED
```

```php
interface ListingState
{
    public function approve(Listing $listing): void;
    public function reject(Listing $listing, string $reason): void;
    public function lock(Listing $listing): void;
    public function delete(Listing $listing): void;
    public function getName(): string;
}

class PendingState implements ListingState
{
    public function approve(Listing $listing): void
    {
        $listing->update(['status' => 'approved']);
        ListingApproved::dispatch($listing);
    }

    public function reject(Listing $listing, string $reason): void
    {
        $listing->update(['status' => 'rejected', 'reject_reason' => $reason]);
        ListingRejected::dispatch($listing, $reason);
    }

    public function lock(Listing $listing): void
    {
        throw new InvalidListingStateException('Cannot lock a pending listing');
    }
}

class ApprovedState implements ListingState
{
    public function approve(Listing $listing): void
    {
        throw new InvalidListingStateException('Already approved');
    }

    public function lock(Listing $listing): void
    {
        $listing->update(['status' => 'locked']);
    }
}
```

#### Trạng thái Lịch hẹn (Appointment Status):

```
PENDING → (Chủ nhà xác nhận) → CONFIRMED
        → (Chủ nhà từ chối) → DECLINED
        → (Khách hủy)       → CANCELLED
CONFIRMED → (Khách hủy)     → CANCELLED
CONFIRMED → (Hoàn thành)    → COMPLETED
```

```php
interface AppointmentState
{
    public function confirm(Appointment $apt): void;
    public function decline(Appointment $apt, string $reason): void;
    public function cancel(Appointment $apt, User $cancelledBy): void;
}
```

---

### 4.9 Command Pattern (CQRS-lite)

**Áp dụng cho:** Tách Read/Write để tối ưu và audit trail.

```php
// Commands (write side)
class CreateListingCommand {
    public function __construct(
        public readonly int $userId,
        public readonly CreateListingDto $dto,
    ) {}
}

class ApprovListingCommand {
    public function __construct(
        public readonly int $listingId,
        public readonly int $adminId,
    ) {}
}

// Queries (read side)
class GetListingsQuery {
    public function __construct(
        public readonly ListingFilterDto $filter,
        public readonly int $perPage = 20,
    ) {}
}

// Handlers
class CreateListingHandler {
    public function handle(CreateListingCommand $command): Listing { ... }
}

class GetListingsHandler {
    public function handle(GetListingsQuery $query): LengthAwarePaginator { ... }
}
```

---

### 4.10 Template Method Pattern

**Áp dụng cho:** Luồng xác thực OTP có cấu trúc giống nhau nhưng khác context.

```php
abstract class OtpVerificationHandler
{
    // Template method — định nghĩa luồng, không thay đổi
    final public function handle(string $email, string $otp): AuthResultDto
    {
        $user = $this->findUser($email);
        $this->validateUserState($user);         // Hook — subclass override
        $this->verifyOtp($user, $otp);
        $this->onVerificationSuccess($user);     // Hook — subclass override
        return $this->generateToken($user);
    }

    abstract protected function validateUserState(?User $user): void;
    abstract protected function onVerificationSuccess(User $user): void;

    private function verifyOtp(User $user, string $otp): void { ... }
    private function generateToken(User $user): AuthResultDto { ... }
}

// Đăng ký: user phải ở trạng thái Pending
class RegisterOtpHandler extends OtpVerificationHandler
{
    protected function validateUserState(?User $user): void
    {
        if (!$user || $user->status !== UserStatus::Pending) {
            throw new OtpInvalidException();
        }
    }

    protected function onVerificationSuccess(User $user): void
    {
        $user->update(['status' => UserStatus::Active]);
        UserRegistered::dispatch($user);
    }
}

// Reset password: user phải Active
class ResetPasswordOtpHandler extends OtpVerificationHandler { ... }
```

---

## 5. Ánh xạ Pattern → Chức năng

| Chức năng | Design Pattern(s) | SOLID Principles |
|-----------|-------------------|-----------------|
| **Đăng ký / Đăng nhập** | Template Method, Observer | SRP, DIP |
| **Đăng nhập Google/Facebook** | Adapter (SocialUserAdapter), Strategy | OCP, DIP |
| **OTP (Đăng ký, Quên mật khẩu)** | Template Method, Adapter (OTP Storage) | SRP, DIP |
| **Tạo tin đăng** | Builder (ListingBuilder), Observer | SRP, OCP |
| **Tìm kiếm / Lọc tin đăng** | Builder (QueryBuilder), Strategy (Filters) | OCP, ISP |
| **Nhân đôi tin đăng** | Builder (copy constructor) | SRP |
| **Trạng thái tin đăng (Duyệt/Từ chối/Khóa)** | State Pattern, Observer | OCP, SRP |
| **Nâng cấp gói tin / Thanh toán** | Strategy (PaymentGateway), Observer | OCP, DIP |
| **Xuất báo cáo doanh thu** | Strategy (ReportExporter) | OCP, ISP |
| **Gửi thông báo (Email/SMS/Zalo)** | Strategy (NotificationChannel) ✅ | OCP, DIP ✅ |
| **Upload ảnh tin đăng** | Adapter (StorageAdapter) | OCP, DIP |
| **Chat** | Observer (Broadcast Events) | SRP |
| **Đặt / Xác nhận / Hủy lịch hẹn** | State Pattern, Observer | OCP, SRP |
| **Lịch sử giao dịch** | Repository, Query Builder | ISP |
| **Dashboard Admin** | Repository, Decorator (Cache) | DIP |
| **Quản lý tài khoản (Khóa/Mở)** | State Pattern | SRP |
| **Token Blacklist (Logout)** | Adapter (TokenProcessService) ✅ | SRP, DIP ✅ |

---

## 6. Lộ trình triển khai

### Phase 1 — Foundation (Hiện tại)
- [x] Repository Pattern (UserRepository)
- [x] Strategy Pattern (NotificationChannel)
- [x] Observer Pattern (UserRegistered)
- [x] Adapter (EloquentUserRepository, TokenProcessService)
- [ ] Adapter (SocialUserAdapter) ← **nên làm ngay**
- [ ] Adapter (OtpStoragePort) ← **nên làm ngay**

### Phase 2 — Listing Module
- [ ] Repository (ListingRepository, ImageRepository)
- [ ] Builder (ListingBuilder, ListingQueryBuilder)
- [ ] State (ListingState: Pending → Approved → Locked)
- [ ] Observer (ListingCreated, ListingApproved, ListingRejected)
- [ ] Adapter (StorageAdapter — upload ảnh)
- [ ] Strategy (ListingFilter — lọc/tìm kiếm)

### Phase 3 — Payment Module
- [ ] Strategy (PaymentGateway — VNPay, Momo)
- [ ] Factory (PaymentGatewayFactory)
- [ ] Observer (PaymentSucceeded → ActivatePackage, SendReceipt)
- [ ] Repository (PaymentRepository)

### Phase 4 — Appointment Module
- [ ] State (AppointmentState: Pending → Confirmed/Declined/Cancelled)
- [ ] Observer (AppointmentBooked, AppointmentConfirmed, AppointmentCancelled)
- [ ] Repository (AppointmentRepository)
- [ ] ISP (tách AppointmentBookingService và AppointmentResponseService)

### Phase 5 — Admin & Analytics
- [ ] Strategy (ReportExporter — Excel, PDF, CSV)
- [ ] Decorator (CachedListingRepository — cache Dashboard)
- [ ] Builder (ReportQueryBuilder)

---

## Phụ lục: Cấu trúc thư mục đề xuất

```
app/
├── Services/
│   ├── Auth/
│   │   ├── SocialUserAdapter.php          ← Interface
│   │   └── Adapters/
│   │       ├── GoogleSocialiteAdapter.php
│   │       └── FacebookSocialiteAdapter.php
│   │
│   ├── Listing/
│   │   ├── ListingWriteService.php        ← Interface (ISP)
│   │   ├── ListingReadService.php         ← Interface (ISP)
│   │   ├── ListingModerationService.php   ← Interface (ISP)
│   │   ├── Impl/
│   │   │   └── ListingServiceImpl.php
│   │   ├── Filters/
│   │   │   ├── ListingFilter.php          ← Interface (Strategy)
│   │   │   ├── PriceRangeFilter.php
│   │   │   ├── LocationFilter.php
│   │   │   └── PropertyTypeFilter.php
│   │   └── States/
│   │       ├── ListingState.php           ← Interface (State)
│   │       ├── PendingState.php
│   │       ├── ApprovedState.php
│   │       └── LockedState.php
│   │
│   ├── Payment/
│   │   ├── PaymentGateway.php             ← Interface (Strategy)
│   │   ├── Gateways/
│   │   │   ├── VnPayGateway.php
│   │   │   └── MomoGateway.php
│   │   └── PaymentGatewayFactory.php      ← Factory
│   │
│   ├── Notification/                      ← ✅ Đã hoàn thiện
│   │   ├── NotificationService.php
│   │   ├── Channel/
│   │   │   ├── NotificationChannel.php
│   │   │   ├── EmailChannel.php
│   │   │   ├── SmsChannel.php
│   │   │   └── ZaloChannel.php
│   │   └── Impl/
│   │       └── NotificationServiceImpl.php
│   │
│   ├── Otp/
│   │   ├── OtpService.php
│   │   ├── OtpStoragePort.php             ← Interface (Adapter)
│   │   ├── Adapters/
│   │   │   ├── RedisOtpStorageAdapter.php
│   │   │   └── CacheOtpStorageAdapter.php
│   │   └── Impl/
│   │       └── OtpServiceImpl.php
│   │
│   └── Report/
│       ├── ReportExporter.php             ← Interface (Strategy)
│       ├── Exporters/
│       │   ├── ExcelReportExporter.php
│       │   ├── PdfReportExporter.php
│       │   └── CsvReportExporter.php
│
├── Repositories/
│   ├── UserRepository.php              ← ✅ Có rồi
│   ├── ListingRepository.php
│   ├── PackageRepository.php
│   ├── AppointmentRepository.php
│   ├── PaymentRepository.php
│   └── Eloquent/
│       ├── EloquentUserRepository.php  ← ✅ Có rồi
│       ├── EloquentListingRepository.php
│       ├── Cached/
│       │   └── CachedListingRepository.php ← Decorator
│
├── Events/
│   ├── Auth/
│   │   └── UserRegistered.php          ← ✅ Có rồi
│   ├── Listing/
│   │   ├── ListingCreated.php
│   │   ├── ListingApproved.php
│   │   └── ListingRejected.php
│   ├── Payment/
│   │   └── PaymentSucceeded.php
│   └── Appointment/
│       ├── AppointmentBooked.php
│       ├── AppointmentConfirmed.php
│       └── AppointmentCancelled.php
│
└── Builders/
    ├── ListingBuilder.php
    ├── ListingQueryBuilder.php
    └── ReportQueryBuilder.php
```

---

*Tài liệu này được tạo dựa trên phân tích Product Backlog (BẤT ĐỘNG SẢN.xlsx) và source code thực tế của dự án Propify.*
