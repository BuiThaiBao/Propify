# Lý Thuyết & Triển Khai Chi Tiết Các Design Pattern Trong Dự Án Propify

> **Mục đích:** Tài liệu này giải thích cho từng Design Pattern trong hệ thống: **Tại sao pattern này được chọn?**, **Nó giải quyết vấn đề gì?**, **Nó mang lại tác dụng ra sao đối với chức năng?** và **Code được triển khai như thế nào?**. Thích hợp để trình bày với giảng viên hướng dẫn đồ án.

---

## MỤC LỤC

1. [Command Pattern](#1-command-pattern)
2. [Chain of Responsibility Pattern](#2-chain-of-responsibility-pattern)
3. [Strategy Pattern](#3-strategy-pattern)
4. [Factory Method Pattern](#4-factory-method-pattern)
5. [Adapter Pattern](#5-adapter-pattern)
6. [Observer Pattern](#6-observer-pattern)
7. [State Pattern](#7-state-pattern)
8. [Template Method Pattern](#8-template-method-pattern)
9. [Specification Pattern](#9-specification-pattern)
10. [Facade Pattern](#10-facade-pattern)

---

## 1. Command Pattern

### 1.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Đóng gói một yêu cầu (request) thành một đối tượng độc lập, cho phép tham số hóa client với các yêu cầu khác nhau, xếp hàng (queue) hoặc ghi log các yêu cầu, và hỗ trợ các thao tác khôi phục (undo/redo)."

**Cấu trúc GoF:**
- **Command (Interface)**: Khai báo phương thức `execute()`.
- **ConcreteCommand**: Triển khai `execute()`, gọi các phương thức của Receiver để thực hiện nghiệp vụ.
- **Invoker**: Gọi `execute()` trên Command để kích hoạt hành động.
- **Receiver**: Là đối tượng thực sự thực hiện công việc.

---

### 1.2. Chức năng: Đăng ký tài khoản

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `RegisterUserCommand` |
| **Lý do sử dụng** | Luồng đăng ký gồm nhiều bước: validation -> hash password -> create user -> sinh OTP -> dispatch event. Đặt tất cả vào Controller sẽ tạo "Fat Controller", khó test, khó bảo trì. |
| **Tác dụng** | Tách Controller khỏi nghiệp vụ, dễ unit test (mock dependencies), có thể tái sử dụng Command từ CLI, Queue, hay API. |
| **Code triển khai** | `RegisterUserCommand` nhận các dependency qua constructor (injection). Phương thức `execute()` gọi lần lượt: `RegistrationValidationChain::validate()`, `UserRepository::create()`, `OtpService::generate()` và `dispatch(new UserRegistered(...))`. Controller chỉ việc tạo DTO và gọi `execute()`. |

```php
// Trích code: RegisterUserCommand
final class RegisterUserCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RegistrationValidationChain $validationChain,
        private readonly OtpService $otpService,
    ) {}

    public function execute(RegisterUserDto $dto): void
    {
        $this->validationChain->validate($dto);           // 1. Validate
        $user = $this->userRepository->create([...]);     // 2. Lưu DB
        $this->otpService->generate($user, ...);          // 3. Sinh OTP
        UserRegistered::dispatch($user->id);              // 4. Phát Event
    }
}
```

---

### 1.3. Chức năng: Đặt lịch hẹn (Confirm / Reject / Cancel)

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `ConfirmBookingCommand`, `RejectBookingCommand`, `CancelBookingCommand` |
| **Lý do sử dụng** | Các thao tác xử lý lịch hẹn cần thực hiện: gọi State check -> ghi note -> persist DB -> dispatch event. Nếu để State đảm nhiệm luôn phần persist/event thì State sẽ bị phụ thuộc vào Infrastructure. |
| **Tác dụng** | Tách biệt State (chỉ lo logic biến đổi trạng thái) khỏi Command (lo persist và event). Mỗi Command chỉ 3 dòng code gọn gàng, dễ đọc, dễ bảo trì. |
| **Code triển khai** | Mỗi Command có phương thức `execute()`, bên trong gọi `$booking->state()->hànhVi()`, sau đó `$booking->save()`, và `Event::dispatch()`. |

```php
// Trích code: ConfirmBookingCommand
final class ConfirmBookingCommand
{
    public function execute(AppointmentBooking $booking): void
    {
        $booking->state()->confirm($booking);   // State xác nhận (PENDING -> APPROVED)
        $booking->save();                        // Command persist
        AppointmentBookingStatusUpdated::dispatch($booking->id, 'APPROVED'); // Event
    }
}
```

---

### 1.4. Chức năng: Nâng cấp tin đăng

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `CreateUpgradePaymentCommand`, `UpgradeListingCommand` |
| **Lý do sử dụng** | Quy trình nâng cấp có 2 giai đoạn riêng biệt: (1) Tạo giao dịch PENDING và link thanh toán VNPAY. (2) Nhận callback thành công -> update gói tin. Mỗi giai đoạn phải đóng gói độc lập. |
| **Tác dụng** | Cô lập từng bước nghiệp vụ có transaction riêng. Nếu bước 1 lỗi (VD: VNPAY không khả dụng), không ảnh hưởng đến bước 2. Dễ dàng thêm các bước tiền xử lý (fraud detection) hoặc hậu xử lý (gửi email xác nhận). |
| **Code triển khai** | `CreateUpgradePaymentCommand::execute()` tạo Transaction PENDING, tạo Job tự động hủy quá hạn 15 phút, gọi `PaymentProviderFactory` để lấy Gateway và sinh URL thanh toán. `UpgradeListingCommand::execute()` sử dụng Database Transaction, gọi Policy kiểm tra tính hợp lệ, Factory tính hạn dùng, và dispatch event. |

```php
// Trích code: UpgradeListingCommand
final class UpgradeListingCommand
{
    public function execute(...): Listing
    {
        $this->policy->assertEligible($context);                    // 1. Policy check
        $expiresAt = $this->expiryFactory->make($context)->calculate($context); // 2. Tính hạn
        $benefitStrategy = $this->benefitFactory->make($newPackage); // 3. Strategy quyền lợi

        return DB::transaction(function () use (...) {
            $transaction->update(['status' => 'SUCCESS']);          // 4. Update giao dịch
            $listing->update(['package_id' => ..., 'package_expires_at' => $expiresAt]); // 5. Nâng cấp
            $benefitStrategy->apply($context);                      // 6. Áp dụng quyền lợi
        });
    }
}
```

---

### 1.5. Chức năng: Tạo tin đăng

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `CreateListingCommand` |
| **Lý do sử dụng** | Tạo tin đăng là nghiệp vụ phức tạp nhất: validate pipeline -> tạo Property -> lưu attributes -> lưu images -> lưu video -> lưu verification documents -> tính score -> phát event. Nếu ghi trong Controller sẽ có hơn 200 dòng. |
| **Tác dụng** | Cô lập toàn bộ quy trình tạo tin trong một Command duy nhất. Controller chỉ cần nhận request và gọi 1 method. Dễ dàng thêm bước xử lý (VD: tích hợp AI gợi ý giá) mà không sửa Controller. |
| **Code triển khai** | `CreateListingCommand::handle()` nhận `User` và `CreateListingDto`, gọi `ListingSubmissionValidationPipeline::validate()`, thực thi tất cả các bước trong `DB::transaction()`, và phát `ListingSaved::dispatch()` ở cuối. |

```php
// Trích code: CreateListingCommand
final class CreateListingCommand
{
    public function handle(User $user, CreateListingDto $dto): Listing
    {
        $this->validationPipeline->validate(new ListingSubmissionValidationContext($user, $dto));

        return DB::transaction(function () use ($user, $dto) {
            $property = $this->listingRepository->createProperty($this->propertyAttributes($dto));
            $listing = $this->listingRepository->createListing([...]);
            $this->saveImages($listing, $dto->images);
            $this->saveVideo($listing, $dto->video);
            $this->saveVerificationDocuments($listing, $dto);
            return $listing;
        });
    }
}
```

---

## 2. Chain of Responsibility Pattern

### 2.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Cho phép nhiều đối tượng có cơ hội xử lý một yêu cầu bằng cách xâu chuỗi chúng lại với nhau. Yêu cầu được truyền dọc theo chuỗi cho đến khi có một đối tượng xử lý nó."

---

### 2.2. Chức năng: Đăng ký tài khoản & Đăng nhập

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `RegistrationValidationChain`, `LoginValidationChain` |
| **Lý do sử dụng** | Nếu dùng `if-else` lồng nhau để validate (if checkEmail -> if checkPassword -> if checkUnique) sẽ tạo "Arrow Anti-pattern". Mỗi lần thêm luật mới (VD: blacklist email, rate limiting) phải đào sâu vào block if-else hiện có. |
| **Tác dụng** | Mỗi luật kiểm tra là một phương thức độc lập. Dễ dàng thêm/bớt/sắp xếp lại thứ tự kiểm tra. Luật vi phạm đầu tiên sẽ ngắt chuỗi và ném Exception ngay, không chạy các luật sau, tiết kiệm tài nguyên. |
| **Code triển khai** | `RegistrationValidationChain` có các phương thức private: `checkEmailFormat()`, `checkPasswordStrength()`, `checkEmailUnique()`. Phương thức `validate()` gọi chúng tuần tự. Nếu phương thức nào thất bại, nó ném `BusinessException`. |

```php
// Trích code: RegistrationValidationChain
final class RegistrationValidationChain
{
    public function validate(RegisterUserDto $dto): void
    {
        $this->checkEmailFormat($dto->email);         // Bước 1: Định dạng email
        $this->checkPasswordStrength($dto->password); // Bước 2: Độ mạnh mật khẩu
        $this->checkEmailUnique($dto->email);          // Bước 3: Email chưa tồn tại
    }

    private function checkEmailUnique(string $email): void
    {
        if ($this->userRepository->findByEmail($email)) {
            throw new BusinessException(ErrorCode::EmailAlreadyExists);
        }
    }
}
```

---

## 3. Strategy Pattern

### 3.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Định nghĩa một họ các thuật toán, đóng gói từng thuật toán và làm cho chúng có thể hoán đổi cho nhau. Strategy cho phép thuật toán biến đổi độc lập với client sử dụng nó."

---

### 3.2. Chức năng: Đăng nhập

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `AuthStrategy` |
| **Các Strategies** | `EmailPasswordAuthStrategy`, `GoogleOAuthAuthStrategy` |
| **Lý do sử dụng** | Hệ thống hỗ trợ nhiều phương thức đăng nhập (email/password, Google OAuth). Nếu dùng switch-case trong Controller, mỗi lần thêm phương thức mới phải sửa Controller và vi phạm OCP. |
| **Tác dụng** | **Open/Closed Principle**: Thêm Facebook/Apple ID login chỉ cần tạo class mới implement `AuthStrategy`. Không cần sửa code cũ. `AuthStrategyResolver` tự động phân giải strategy phù hợp. |
| **Code triển khai** | `AuthStrategy` định nghĩa `authenticate(AuthPayload $payload)`. `EmailPasswordAuthStrategy` so sánh hash password, `GoogleOAuthAuthStrategy` xác thực qua token Google. `AuthStrategyResolver` duyệt danh sách strategy đã inject và trả về strategy có method phù hợp. |

```php
// Trích code: AuthStrategy và các triển khai
interface AuthStrategy {
    public function method(): AuthMethod;
    public function authenticate(AuthPayload $payload): AuthResultDto;
}

final class EmailPasswordAuthStrategy implements AuthStrategy
{
    public function authenticate(AuthPayload $payload): AuthResultDto
    {
        $user = $this->loginValidationChain->validate($payload->email, $payload->password);
        $token = $this->tokenIssuer->issueForUser($user);
        return new AuthResultDto($user, $token);
    }
}

// AuthStrategyResolver
final class AuthStrategyResolver {
    public function resolve(AuthMethod $method): AuthStrategy {
        foreach ($this->strategies as $strategy) {
            if ($strategy->method() === $method) return $strategy;
        }
    }
}
```

---

### 3.3. Chức năng: Sắp xếp hiển thị tin đăng

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `ListingSortingStrategy` |
| **Các Strategies** | `DefaultPackageScoreSortingStrategy`, `NewestListingSortingStrategy`, `OldestListingSortingStrategy`, `PriceLowToHighSortingStrategy`, `PriceHighToLowSortingStrategy`, `AreaLowToHighSortingStrategy`, `AreaHighToLowSortingStrategy` |
| **Lý do sử dụng** | Có 7+ cách sắp xếp danh sách tin đăng. Nếu dùng `switch-case` với 7 nhánh trong Repository, mỗi lần thêm 1 cách sắp xếp mới (VD: khoảng cách GPS) sẽ phải mở Repository ra sửa. |
| **Tác dụng** | Mỗi Strategy đóng gói 1 câu lệnh `ORDER BY` riêng. Có thể thêm Strategy mới mà không sửa Repository hay Service. Đặc biệt `DefaultPackageScoreSortingStrategy` chứa công thức SQL phức tạp (tính điểm theo gói VIP, decay_rate, thời gian) được cô lập hoàn toàn. |
| **Code triển khai** | Interface `ListingSortingStrategy` có phương thức `apply(Builder $query): Builder`. Mỗi ConcreteStrategy can thiệp vào Eloquent Query Builder để thêm `orderBy()`. `ListingSortingStrategyFactory::make($sortBy)` trả về Strategy tương ứng từ match expression. |

```php
// Trích code: DefaultPackageScoreSortingStrategy
final class DefaultPackageScoreSortingStrategy implements ListingSortingStrategy
{
    public function apply(Builder $query): Builder
    {
        $hoursSincePublished = 'TIMESTAMPDIFF(HOUR, COALESCE(listings.published_at, listings.created_at), NOW())';
        $finalScore = "(COALESCE(listings.score, 0) * COALESCE(packages.multiplier, 1.0) * (1.0 / (1.0 + {$hoursSincePublished} / 24.0)) * EXP(-COALESCE(packages.decay_rate, 0.05) * {$hoursSincePublished}))";

        return $query->leftJoin('packages', 'listings.package_id', '=', 'packages.id')
            ->orderByDesc('pkg_priority')
            ->orderByDesc('final_score');
    }
}
```

---

### 3.4. Chức năng: Lọc dữ liệu tìm kiếm

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `SearchFieldStrategy` |
| **Các Strategies** | `TitleSearchStrategy`, `OwnerSearchStrategy`, `AddressSearchStrategy` |
| **Lý do sử dụng** | Người dùng có thể tìm tin theo: tiêu đề, tên chủ tin, hoặc địa chỉ. Mỗi trường tìm kiếm có logic khác nhau (JOIN bảng owner, LIKE trên cột). Nếu gom chung vào 1 hàm, code sẽ dài và khó đọc. |
| **Tác dụng** | Thêm trường tìm kiếm mới (VD: mã tin, loại BĐS) chỉ cần thêm 1 class Strategy mới. `TitleSearchStrategy` còn có logic "thông minh": phát hiện từ khóa mua/thuê để mở rộng filter. |
| **Code triển khai** | `SearchFieldStrategy::apply(Builder $query, string $keyword)` thêm điều kiện `WHERE` phù hợp. `SearchFieldStrategyFactory::for($searchField)` chọn Strategy. |

```php
// Trích code: TitleSearchStrategy
final class TitleSearchStrategy implements SearchFieldStrategy
{
    public function apply(Builder $query, string $keyword): void
    {
        $like = '%'.$keyword.'%';
        $query->whereRaw('title LIKE ?', [$like]);

        if (str_contains($keyword, 'cho') || str_contains($keyword, 'thue'))
            $query->orWhere('demand_type', 'RENT');
    }
}
```

---

### 3.5. Chức năng: Nâng cấp tin - Tính hạn dùng

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `ExpiryCalculationStrategy` |
| **Các Strategies** | `FreshPurchaseExpiryStrategy`, `RenewalExpiryStrategy` |
| **Lý do sử dụng** | Mua gói mới: hạn tính từ hôm nay. Gia hạn gói cũ: hạn cộng dồn vào ngày cũ. Hai công thức khác nhau. Viết chung sẽ có if-else phức tạp. |
| **Tác dụng** | Cô lập thuật toán tính ngày. Dễ kiểm thử độc lập (test FreshPurchase, test Renewal riêng). Dễ thêm chiến lược mới (VD: FREE trial). |
| **Code triển khai** | `FreshPurchaseExpiryStrategy` trả về `now()->addDays($durationDays)`. `RenewalExpiryStrategy` kiểm tra `package_expires_at`, nếu còn hạn thì cộng dồn, nếu hết hạn thì tính từ hôm nay. |

```php
final class FreshPurchaseExpiryStrategy implements ExpiryCalculationStrategy
{
    public function calculate(UpgradeContext $context): CarbonInterface
    {
        return $context->now->copy()->addDays($context->durationDays);
    }
}

final class RenewalExpiryStrategy implements ExpiryCalculationStrategy
{
    public function calculate(UpgradeContext $context): CarbonInterface
    {
        $baseTime = $context->listing->package_expires_at;
        if (!$baseTime || $baseTime->lessThanOrEqualTo($context->now))
            $baseTime = $context->now;
        return $baseTime->copy()->addDays($context->durationDays);
    }
}
```

---

### 3.6. Chức năng: Đặt lịch hẹn - Hạn xác nhận

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `DeadlineStrategy` |
| **Các Strategies** | `DefaultDeadlineStrategy`, `UrgentDeadlineStrategy` |
| **Lý do sử dụng** | Quy tắc SRS: đặt hẹn mặc định hạn chờ 6 tiếng. Đặt gấp (<6h trước giờ gặp) thì hạn chờ = (thời gian gặp - 1 tiếng). Công thức này có thể thay đổi theo chính sách kinh doanh. |
| **Tác dụng** | Cho phép thay đổi chính sách "hạn chờ" mà không động vào code tạo booking. Có thể mở rộng cho khách VIP (VD: hạn chờ 24h). |
| **Code triển khai** | `AppointmentBookingServiceImpl` kiểm tra `$ctx->isUrgent()`, nếu urgent dùng `UrgentDeadlineStrategy` nếu không dùng `DefaultDeadlineStrategy`. |

```php
$strategy = $ctx->isUrgent()
    ? new UrgentDeadlineStrategy          // Công thức: max(hoursUntilMeet - 1, 1)
    : new DefaultDeadlineStrategy;        // Công thức: +6h từ lúc đặt
$confirmDeadline = $strategy->deadlineFor($ctx);
```

---

## 4. Factory Method Pattern

### 4.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Định nghĩa một interface để tạo đối tượng, nhưng để các lớp con quyết định lớp nào được khởi tạo. Factory Method cho phép một lớp trì hoãn việc khởi tạo cho các lớp con."

---

### 4.2. Chức năng: Đăng nhập (AuthStrategyResolver)

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `AuthStrategyResolver` |
| **Lý do sử dụng** | Controller không thể biết trước phương thức đăng nhập người dùng chọn. Cần một nơi tập trung để "tra cứu" và trả về Strategy phù hợp. |
| **Tác dụng** | Controller chỉ việc gọi `$resolver->resolve($method)`, không cần `switch-case` hay `if-else`. Khi thêm phương thức đăng nhập mới, chỉ cần đăng ký thêm strategy vào resolver. |
| **Code triển khai** | `AuthStrategyResolver` nhận mảng `AuthStrategy[]` từ DI container. Phương thức `resolve()` duyệt mảng và trả về strategy có `method()` khớp. |

---

### 4.3. Chức năng: Sắp xếp hiển thị (ListingSortingStrategyFactory)

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `ListingSortingStrategyFactory` |
| **Lý do sử dụng** | Client gửi tham số `sort_by=price_asc`, Service cần tạo Strategy tương ứng mà không cần biết class cụ thể. |
| **Tác dụng** | Thêm 1 cách sắp xếp mới = thêm 1 class ConcreteStrategy + 1 nhánh match trong Factory. Service và Repository không thay đổi. |
| **Code triển khai** | `ListingSortingStrategyFactory::make(?string $sortBy)` dùng `match` expression: `'price_asc' => new PriceLowToHighSortingStrategy`, `default => new DefaultPackageScoreSortingStrategy`. |

---

### 4.4. Chức năng: Nâng cấp tin (ExpiryCalculationStrategyFactory & PackageBenefitStrategyFactory)

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `ExpiryCalculationStrategyFactory`, `PackageBenefitStrategyFactory` |
| **Lý do sử dụng** | `UpgradeListingCommand` cần strategy tính hạn dùng và strategy áp dụng quyền lợi tương ứng với ngữ cảnh (mới/gia hạn) và gói (VIP1/VIP2). |
| **Tác dụng** | Tách logic chọn lựa strategy ra khỏi command. Khi thêm loại gói mới, chỉ cần thêm nhánh trong Factory mà không cần mở Command. |
| **Code triển khai** | `ExpiryCalculationStrategyFactory::make(UpgradeContext $context)` kiểm tra `$context->isRenewal()` để chọn RenewalExpiryStrategy hay FreshPurchaseExpiryStrategy. |

---

### 4.5. Chức năng: Thanh toán (PaymentProviderFactory)

| Nội dung | Chi tiết |
|----------|----------|
| **Class chính** | `PaymentProviderFactory` |
| **Lý do sử dụng** | `CreateUpgradePaymentCommand` cần tạo payment URL nhưng không biết cổng thanh toán nào được chọn (VNPAY, MOMO...). |
| **Tác dụng** | Khi tích hợp thêm Momo, chỉ cần thêm `MomoGateway implements PaymentGateway` và đăng ký vào Factory. Command không thay đổi. |
| **Code triển khai** | `PaymentProviderFactory::for(string $method)` dùng `match(strtoupper($method))` trả về `$vnpayGateway` hoặc throw exception cho method không hỗ trợ. |

```php
// Trích code: PaymentProviderFactory
final class PaymentProviderFactory
{
    public function for(string $method): PaymentGateway
    {
        return match (strtoupper($method)) {
            'VNPAY' => $this->vnpayGateway,
            default => throw new RuntimeException("Unsupported: {$method}"),
        };
    }
}
```

---

## 5. Adapter Pattern

### 5.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Chuyển đổi interface của một lớp thành interface khác mà client mong đợi. Adapter cho phép các lớp làm việc cùng nhau mà bình thường không thể do interface không tương thích."

---

### 5.2. Chức năng: Đăng nhập Google

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `SocialUserAdapter` |
| **Adapter** | `GoogleSocialiteAdapter` |
| **Adaptee** | `Laravel\Socialite\Two\User` (SDK bên thứ ba) |
| **Lý do sử dụng** | Google Socialite SDK trả về đối tượng User với các method đặc thù (`user['email'][0]`, `getId()`, `getAvatar()`). Domain của chúng ta không thể phụ thuộc trực tiếp vào SDK này. |
| **Tác dụng** | Nếu SDK Socialite thay đổi (VD: update version 5.0 đổi tên method), chỉ cần sửa duy nhất `GoogleSocialiteAdapter`. Toàn bộ hệ thống không bị ảnh hưởng. |
| **Code triển khai** | `GoogleSocialiteAdapter` implement `SocialUserAdapter`, bọc `SocialiteUser` bên trong. Các method `getEmail()`, `getName()`, `getAvatar()` gọi các method tương ứng từ SocialiteUser và map về kiểu dữ liệu chuẩn. |

```php
final class GoogleSocialiteAdapter implements SocialUserAdapter
{
    public function __construct(private readonly SocialiteUser $socialiteUser) {}

    public function getEmail(): string { return $this->socialiteUser->getEmail(); }
    public function getName(): string  { return $this->socialiteUser->getName(); }
    public function getAvatar(): string { return $this->socialiteUser->getAvatar(); }
}
```

---

### 5.3. Chức năng: Thanh toán

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `PaymentGateway` |
| **Adapter** | `VnpayGateway` |
| **Adaptee** | `VnpayService` |
| **Lý do sử dụng** | Mỗi cổng thanh toán có API khác nhau (tham số, thuật toán HMAC, format callback). Không thể để các Use Case phụ thuộc trực tiếp vào format VNPAY. |
| **Tác dụng** | Có thể tích hợp thêm Momo, ZaloPay, Stripe bằng cách tạo Adapter mới. `PaymentGateway` interface chuẩn hóa các hành vi: tạo URL thanh toán, xác thực callback, lấy mã giao dịch. |
| **Code triển khai** | `VnpayGateway` implement `PaymentGateway`, bọc `VnpayService`. `createPaymentUrl()` gọi `vnpayService->createPaymentUrl()`. `verifyCallback()` map `vnp_*` params thành `CallbackResult` chuẩn. |

```php
// Trích code: VnpayGateway
final class VnpayGateway implements PaymentGateway
{
    public function createPaymentUrl(Transaction $transaction, string $clientIp): string
    {
        return $this->vnpayService->createPaymentUrl($transaction, $clientIp);
    }

    public function verifyCallback(Request $request): CallbackResult
    {
        return new CallbackResult(
            isValidSignature: $this->vnpayService->isValidReturn($request),
            responseCode: $request->query('vnp_ResponseCode'),
            reference: (string) $request->query('vnp_TxnRef', ''),
            gatewayFields: ['vnp_transaction_no' => $request->query('vnp_TransactionNo'), ...],
        );
    }
}
```

---

### 5.4. Chức năng: Media Storage (Cloudflare R2)

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `FileStorageAdapter` |
| **Adapter** | `R2FileStorageAdapter` |
| **Adaptee** | Laravel `Storage` (disk r2) + AWS `S3Client` |
| **Lý do sử dụng** | Hệ thống lưu file lên Cloudflare R2 (S3-compatible). Nếu sau này đổi sang AWS S3 hoặc Google Cloud Storage, code hiện tại đang gọi `Storage::disk('r2')->put()` sẽ phải sửa hàng loạt. |
| **Tác dụng** | Interface `FileStorageAdapter` định nghĩa 2 phương thức: `upload()` và `getPublicUrl()`. Khi chuyển nhà cung cấp, chỉ cần viết Adapter mới (VD: `S3FileStorageAdapter`). Hỗ trợ Presigned URL (URL có chữ ký thời hạn 7 ngày) để bảo vệ bucket private. |
| **Code triển khai** | `R2FileStorageAdapter::upload()` gọi `Storage::disk('r2')->put()` với ContentType. `getPublicUrl()` tạo PresignedRequest qua S3Client với thời hạn +7 days. |

```php
final class R2FileStorageAdapter implements FileStorageAdapter
{
    public function upload(string $path, string $contents, string $mimeType): bool
    {
        return Storage::disk('r2')->put($path, $contents, ['ContentType' => $mimeType]);
    }

    public function getPublicUrl(string $path): string
    {
        $getCommand = $this->getS3Client()->getCommand('GetObject', [
            'Bucket' => config('filesystems.disks.r2.bucket'),
            'Key' => $path,
        ]);
        return (string) $this->getS3Client()->createPresignedRequest($getCommand, '+7 days')->getUri();
    }
}
```

---

### 5.5. Chức năng: Cloudinary Upload Signature

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `UploadSignatureAdapter` |
| **Adapter** | `CloudinaryUploadSignatureAdapter` |
| **Adaptee** | `CloudinaryService` |
| **Lý do sử dụng** | Cloudinary yêu cầu chữ ký HMAC-SHA1 để Frontend upload file trực tiếp. Logic này hoàn toàn khác so với các CDN khác (Imgix, Cloudflare Images). |
| **Tác dụng** | Nếu đổi CDN, chỉ cần tạo Adapter mới implement `UploadSignatureAdapter`. Backend trả về chữ ký cho Frontend để upload thẳng lên CDN, giảm tải server. |
| **Code triển khai** | `CloudinaryUploadSignatureAdapter::generateSignature()` gọi `CloudinaryService::generateSignature()` và trả về mảng chứa signature, api_key, cloud_name, timestamp, folder, upload_preset. |

---

### 5.6. Chức năng: Chat WebSocket (Laravel Reverb)

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | Laravel Broadcasting (ShouldBroadcast) |
| **Adapter** | Laravel Reverb |
| **Adaptee** | Redis Queue + Reverb Server |
| **Lý do sử dụng** | Tin nhắn chat cần được gửi realtime qua WebSocket. Laravel Event Broadcasting đóng vai trò Adapter: nhận Event PHP, chuyển đổi thành message Redis/Pusher/Reverb và gửi đến client. |
| **Tác dụng** | Có thể đổi từ Reverb sang Pusher, Socket.io, hoặc tự host WebSocket server chỉ bằng config, không cần sửa code. |
| **Code triển khai** | `MessageSent` Event implement `ShouldBroadcast`. `ChatServiceImpl::sendMessage()` dispatch event này. Laravel Queue nhận event và đẩy qua Reverb đến client. |

```php
ChatServiceImpl::sendMessage(SendMessageDto $dto): Message
{
    $message = $this->chatRepository->createMessage([...]);
    MessageSent::dispatch($message); // Broadcast WebSocket qua Reverb
    return $message;
}
```

---

## 6. Observer Pattern

### 6.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Định nghĩa mối quan hệ một-nhiều giữa các đối tượng sao cho khi một đối tượng thay đổi trạng thái, tất cả các đối tượng phụ thuộc vào nó được thông báo và cập nhật tự động."

---

### 6.2. Tổng quan các Observer trong hệ thống

| # | Subject (Event) | Observer (Listener) | Chức năng | Mục đích |
|---|---|---|---|---|
| 1 | `UserRegistered` | `SendWelcomeMailListener` | Đăng ký | Gửi email chào mừng khi user đăng ký thành công |
| 2 | `UserLoggedIn` | `LogSuccessfulLoginListener` | Đăng nhập | Ghi log audit địa chỉ IP, trình duyệt khi đăng nhập |
| 3 | `MessageSent` | Laravel Reverb Broadcast | Chat | Phát tin nhắn realtime WebSocket đến người nhận |
| 4 | `ListingPackageUpgraded` | `CacheInvalidationListener` | Nâng cấp tin | Xóa cache danh sách tin để cập nhật thứ tự hiển thị |

| Nội dung | Chi tiết |
|----------|----------|
| **Lý do sử dụng** | Các nghiệp vụ phụ (gửi email, ghi log, broadcast, xóa cache) không phải là trách nhiệm của use case chính. Nếu viết trực tiếp vào luồng xử lý, code sẽ bị nhiễu, thời gian phản hồi API tăng, và lỗi ở nghiệp vụ phụ có thể làm hỏng nghiệp vụ chính. |
| **Tác dụng** | **Giảm coupling**: Use case chính (RegisterUserCommand, ChatServiceImpl) không biết và không cần biết ai đang lắng nghe sự kiện của nó. **Fail-safe**: Lỗi ở Listener không ảnh hưởng đến luồng chính. **Async**: Các Listener có thể chạy queue (bất đồng bộ). |
| **Code triển khai** | Dùng cơ chế Event/Listener của Laravel. Command/Service phát Event qua `Event::dispatch()` hoặc `Bus::dispatch()`. Các Listener đăng ký trong `EventServiceProvider`. Listener cài `ShouldQueue` để xử lý bất đồng bộ. |

```php
// Định nghĩa Event
class UserRegistered {
    public function __construct(public readonly int $userId) {}
}

// Định nghĩa Listener (chạy queue - async)
class SendWelcomeMailListener implements ShouldQueue
{
    public function handle(UserRegistered $event): void
    {
        $user = User::find($event->userId);
        Mail::to($user->email)->send(new WelcomeMail($user));
    }
}

// Phát Event (ở RegisterUserCommand)
UserRegistered::dispatch($user->id);
// Luồng chính không bị chậm vì gửi mail!
```

---

## 7. State Pattern

### 7.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Cho phép một đối tượng thay đổi hành vi khi trạng thái nội tại của nó thay đổi. Đối tượng sẽ có vẻ như thay đổi lớp của nó."

---

### 7.2. Chức năng: Đặt lịch hẹn

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `BookingState` |
| **Các State** | `PendingState`, `ApprovedState`, `TerminalState` |
| **Context** | `AppointmentBooking` (Model Eloquent) |
| **Lý do sử dụng** | Lịch hẹn có vòng đời phức tạp: PENDING -> APPROVED/COMPLETED/CANCELLED. Ở mỗi trạng thái, hành vi confirm/reject/cancel/complete khác nhau. Nếu dùng `if (status == 'PENDING')` rải rác khắp service, code cực kỳ khó kiểm soát. |
| **Tác dụng** | Mỗi trạng thái là 1 class riêng, chỉ override các hành vi được phép. Các hành vi không được phép sẽ ném `BusinessException` từ lớp cha. Quy tắc "2 tiếng trước giờ hẹn" (Business Rule BR-01) được đóng gói trong `AbstractBookingState::guardTwoHourRule()` dùng chung. |
| **Code triển khai** | `AppointmentBooking` có method `state()` trả về State tương ứng với `$this->status` (PendingState nếu PENDING, ApprovedState nếu APPROVED, TerminalState nếu khác). Mỗi Command gọi `$booking->state()->confirm($booking)` và state tự kiểm tra tính hợp lệ rồi đổi trạng thái trên model. |

```php
// Context: AppointmentBooking
public function state(): BookingState
{
    return match (BookingStatus::from($this->status)) {
        BookingStatus::PENDING => new PendingState,
        BookingStatus::APPROVED => new ApprovedState,
        default => new TerminalState,
    };
}

// PendingState: cho phép confirm, reject, cancel
final class PendingState extends AbstractBookingState
{
    public function confirm(AppointmentBooking $booking): void
    {
        $booking->status = BookingStatus::APPROVED->value; // PENDING -> APPROVED
    }
    public function reject(AppointmentBooking $booking, ?string $note): void
    {
        $booking->status = BookingStatus::CANCELLED_BY_POSTER->value; // PENDING -> CANCELLED
    }
    public function cancel(AppointmentBooking $booking, BookingRole $by, string $reason): void
    {
        $this->applyCancel($booking, $by, $reason); // Gọi guardTwoHourRule
    }
}

// TerminalState: mọi thao tác đều ném BusinessException (không thể tương tác)
final class TerminalState extends AbstractBookingState {}
```

---

### 7.3. Chức năng: Quản lý trạng thái tin đăng

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `ListingStatusState` |
| **Các State** | `DraftListingState`, `PendingListingState`, `ActiveListingState`, `RejectedListingState`, `LockedListingState`, `UnlistedListingState` |
| **Factory** | `ListingStatusStateFactory` |
| **Lý do sử dụng** | Tin đăng đi qua nhiều trạng thái: DRAFT -> PENDING -> ACTIVE/REJECTED -> LOCKED/UNLISTED. Mỗi trạng thái có danh sách "trạng thái đích" được phép chuyển đến. Nếu không kiểm soát, tin DRAFT có thể nhảy cóc lên ACTIVE. |
| **Tác dụng** | Mỗi State khai báo `allowedTransitions()` - danh sách các trạng thái được phép chuyển đến. `ListingStatusStateFactory::assertCanTransition()` gọi `canTransitionTo()` trước khi thực hiện chuyển đổi. Đảm bảo toàn vẹn dữ liệu và ngăn chặn chuyển trạng thái bất hợp pháp. |
| **Code triển khai** | `AbstractListingStatusState::canTransitionTo(string $nextStatus)` kiểm tra `in_array($nextStatus, $this->allowedTransitions())`. Mỗi ConcreteState override `allowedTransitions()`. VD: `DraftListingState` chỉ cho `['PENDING']`, `PendingListingState` chỉ cho `['ACTIVE', 'REJECTED']`. |

```php
// DraftListingState: chỉ có thể chuyển -> PENDING
final class DraftListingState extends AbstractListingStatusState
{
    protected function allowedTransitions(): array { return ['PENDING']; }
}

// ActiveListingState: có thể bị khóa, từ chối, hoặc ẩn
final class ActiveListingState extends AbstractListingStatusState
{
    protected function allowedTransitions(): array { return ['LOCKED', 'REJECTED', 'UNLISTED']; }
}

// Sử dụng trong AbstractListingModerationCommand
$this->statusStateFactory->assertCanTransition($listing->status, $this->targetStatus());
// Nếu tin đang DRAFT mà admin bấm "Duyệt" (target="ACTIVE") -> ném Exception ngay!
```

---

## 8. Template Method Pattern

### 8.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Xác định khung (skeleton) của một thuật toán trong một phương thức, để các lớp con định nghĩa lại các bước cụ thể mà không thay đổi cấu trúc thuật toán."

---

### 8.2. Chức năng: Admin duyệt / từ chối / khóa tin đăng

| Nội dung | Chi tiết |
|----------|----------|
| **AbstractClass** | `AbstractListingModerationCommand` |
| **ConcreteClass** | `ApproveListingCommand`, `RejectListingCommand`, `LockListingCommand` |
| **Lý do sử dụng** | Cả 3 thao tác duyệt/từ chối/khóa tin đều có cùng khung xử lý: (1) validate -> (2) DB transaction -> (3) lockForUpdate -> (4) State::assertCanTransition -> (5) mutate -> (6) save -> (7) ghi ListingStatusHistory -> (8) dispatch ListingSaved. Nếu viết riêng lẻ, mỗi command sẽ copy-paste toàn bộ khung này, dễ bỏ sót bước ghi history hoặc dispatch event. |
| **Tác dụng** | Toàn bộ khung xử lý (transaction, lock, lịch sử, event) được viết 1 lần duy nhất. Lớp con chỉ cần implement `targetStatus()` và `mutate()`. Giảm thiểu lỗi bảo mật (quên lock row) và lỗi nghiệp vụ (quên ghi history, quên phát event). |
| **Code triển khai** | `AbstractListingModerationCommand::execute()` là Template Method (`final` - không thể override). Nó gọi các hook: `validate()` (mặc định rỗng, có thể override), `mutate()` (abstract - bắt buộc), `targetStatus()` (abstract). |

```php
// Template Method (khung cố định)
abstract class AbstractListingModerationCommand
{
    final public function execute(int $listingId, ModerationContext $ctx): Listing
    {
        $this->validate($ctx); // Hook 1: Kiểm tra tiền điều kiện

        $listing = DB::transaction(function () use ($listingId, $ctx) {
            $listing = Listing::query()->lockForUpdate()->findOrFail($listingId);
            $this->statusStateFactory->assertCanTransition($listing->status, $this->targetStatus());
            $this->mutate($listing, $ctx); // Hook 2: Thay đổi listing (abstract)
            $listing->save();
            ListingStatusHistory::create([...]); // Ghi lịch sử
            return $listing;
        });

        ListingSaved::dispatch($listing, null, 'admin_status_changed'); // Event
        return $listing;
    }

    abstract protected function targetStatus(): string;
    abstract protected function mutate(Listing $listing, ModerationContext $ctx): void;
    protected function validate(ModerationContext $ctx): void {} // Hook optional
}

// Lớp con chỉ cần 3 dòng
final class ApproveListingCommand extends AbstractListingModerationCommand
{
    protected function targetStatus(): string { return 'ACTIVE'; }
    protected function mutate(Listing $listing, ModerationContext $ctx): void
    {
        $listing->status = 'ACTIVE';
        $listing->published_at = now();
        $listing->approved_by = $ctx->adminUserId;
    }
}
```

---

## 9. Specification Pattern

### 9.1. Lý thuyết (Eric Evans - Domain Driven Design)

> **Định nghĩa:** "Specification thông báo kiểm tra một đối tượng xem nó có thỏa mãn một tiêu chí nào đó không. Nó đóng gói một luật nghiệp vụ thành một đối tượng độc lập."

---

### 9.2. Chức năng: Nâng cấp tin đăng

| Nội dung | Chi tiết |
|----------|----------|
| **Specifications** | `CanUpgradeSpecification`, `CanRenewSpecification` |
| **Policy** | `UpgradeEligibilityPolicy` |
| **Lý do sử dụng** | Quy tắc nâng cấp rất phức tạp: tin phải ACTIVE, người nâng cấp là chủ tin, gói mới phải active, VÀ (chuyển lên gói cao hơn HOẶC gia hạn cùng gói). Viết bằng `if-else` lồng nhau sẽ rất khó đọc, và không thể tái sử dụng các luật kiểm tra riêng lẻ (VD: Frontend cần biết "người dùng có thể nâng cấp?" để ẩn/hiện nút UI). |
| **Tác dụng** | `CanUpgradeSpecification` và `CanRenewSpecification` là các class độc lập, có thể tái sử dụng ở bất kỳ đâu (Service, Controller, Frontend API). `UpgradeEligibilityPolicy` kết hợp chúng và bổ sung các kiểm tra chung (quyền sở hữu, trạng thái tin). |
| **Code triển khai** | Mỗi Specification có method `isSatisfiedBy(UpgradeContext): bool`. `UpgradeEligibilityPolicy::assertEligible()` gọi tuần tự các kiểm tra: ownership -> status -> package active -> `canRenew->isSatisfiedBy() || canUpgrade->isSatisfiedBy()`. Nếu tất cả pass, transaction mới được thực thi. |

```php
final class CanUpgradeSpecification
{
    public function isSatisfiedBy(UpgradeContext $context): bool
    {
        if ($context->isRenewal()) return false; // Không phải renewal
        return $context->newPackage->priority > ($context->currentPackage?->priority ?? 0);
    }
}

final class CanRenewSpecification
{
    public function isSatisfiedBy(UpgradeContext $context): bool
    {
        return $context->isRenewal(); // Gói mới giống gói cũ
    }
}
```

---

## 10. Facade Pattern

### 10.1. Lý thuyết (Gang of Four)

> **Định nghĩa:** "Cung cấp một interface thống nhất cho một tập hợp các interface trong một hệ thống con. Facade định nghĩa một interface cao cấp hơn làm cho hệ thống con dễ sử dụng hơn."

---

### 10.2. Chức năng: Chat

| Nội dung | Chi tiết |
|----------|----------|
| **Interface** | `ChatService` |
| **Implementation** | `ChatServiceImpl` |
| **Subsystems** | `ChatRepository`, `Conversation`, `Message`, `GroupMember` |
| **Lý do sử dụng** | Phân hệ Chat có nhiều thực thể phức tạp (Conversation, Message, Group, Member, Participant). Controller không thể trực tiếp phối hợp tất cả các thực thể này. Cần một Facade che giấu sự phức tạp. |
| **Tác dụng** | Controller chỉ gọi 1 phương thức duy nhất (`chatService->sendMessage(dto)`) thay vì phải tự kiểm tra participant, lưu message, cập nhật last_seen, broadcast event. Các quy tắc nghiệp vụ (check participant) tập trung trong Facade. |
| **Code triển khai** | `ChatServiceImpl` nhận `ChatRepository` (thực hiện tất cả thao tác DB). Phương thức `sendMessage()`: (1) `assertParticipant()` check quyền, (2) `chatRepository->createMessage()`, (3) `MessageSent::dispatch()`, (4) return `$message->load('sender')`. |

```php
// Facade: ChatServiceImpl - che giấu toàn bộ nghiệp vụ Chat
final class ChatServiceImpl implements ChatService
{
    public function sendMessage(SendMessageDto $dto): Message
    {
        $this->assertParticipant($dto->conversationId, $dto->senderId);
        $message = $this->chatRepository->createMessage([...]);
        MessageSent::dispatch($message);
        return $message->loadMissing('sender:id,full_name,avatar_url');
    }

    public function getOrCreateConversation(GetOrCreateConversationDto $dto): Conversation
    {
        $existing = $this->chatRepository->findConversation(...);
        return $existing ?? $this->chatRepository->createConversation(...);
    }
}

// Controller sử dụng Facade:
class ChatController extends Controller
{
    public function sendMessage(SendMessageRequest $request): JsonResponse
    {
        $message = $this->chatService->sendMessage($request->toDto());
        return response()->json($message);
    }
}
```

---

## TỔNG KẾT

| # | Design Pattern | Số chức năng áp dụng | Tác dụng chính |
|---|---|---|---|
| 1 | **Command** | 4 (Đăng ký, Đặt lịch, Nâng cấp, Tạo tin) | Đóng gói use case, tách Controller, dễ test, dễ reuse. |
| 2 | **Chain of Responsibility** | 2 (Đăng ký, Đăng nhập) | Validate tuần tự, tránh if-else lồng nhau, dễ thêm luật. |
| 3 | **Strategy** | 5 (Đăng nhập, Sắp xếp, Lọc, Nâng cấp hạn, Nâng cấp quyền lợi, Lịch hẹn) | Hoán đổi thuật toán linh hoạt, tuân thủ OCP. |
| 4 | **Factory Method** | 4 (Đăng nhập, Sắp xếp, Nâng cấp, Thanh toán) | Tập trung khởi tạo strategy/gateway, giảm dependency. |
| 5 | **Adapter** | 5 (Google Login, VNPAY, R2 Storage, Cloudinary, Chat WebSocket) | Cách ly SDK ngoài, dễ đổi nhà cung cấp. |
| 6 | **Observer** | 4 (Đăng ký->mail, Đăng nhập->log, Chat->broadcast, Nâng cấp->xóa cache) | Decoupling event-driven, async, fail-safe. |
| 7 | **State** | 2 (Lịch hẹn, Tin đăng) | Đóng gói trạng thái, kiểm soát chuyển đổi, chặn hành vi bất hợp pháp. |
| 8 | **Template Method** | 1 (Admin duyệt tin) | Định nghĩa khung xử lý, lớp con chỉ điền business logic. |
| 9 | **Specification** | 1 (Nâng cấp tin) | Đóng gói business rule, tái sử dụng, dễ đọc. |
| 10 | **Facade** | 1 (Chat) | Che giấu độ phức tạp hệ thống con, API đơn giản cho Controller. |

> **Tổng cộng: 10 Design Pattern khác nhau, 35 lần áp dụng (gồm pattern xuất hiện nhiều lần ở nhiều chức năng).**
