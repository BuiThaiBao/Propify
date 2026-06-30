# BÁO CÁO BẢO VỆ ĐỒ ÁN: PHÂN TÍCH & TRIỂN KHAI DESIGN PATTERN

> **Dự án:** Propify — Nền tảng dịch vụ bất động sản
> **Tác giả:** [Tên của bạn]
> **Giảng viên hướng dẫn:** [Tên giảng viên]

Tài liệu này được cấu trúc theo **từng chức năng nghiệp vụ** (Đăng ký, Đăng nhập, Nâng cấp tin đăng, Lọc & Sắp xếp, Chat). Với mỗi chức năng, chúng tôi sẽ trình bày các Design Pattern đã được áp dụng, lý do chọn pattern đó, tác dụng của nó và trích dẫn mã nguồn triển khai thực tế.

---

## CHỨC NĂNG 1: ĐĂNG KÝ TÀI KHOẢN

### 1A. Command Pattern

**Vấn đề đặt ra:** Luồng đăng ký gồm nhiều bước: kiểm tra dữ liệu → tạo tài khoản (hash password) → sinh mã OTP → gửi email chào mừng. Nếu viết tất cả các bước này trong Controller, Controller trở nên quá tải (Fat Controller) và rất khó kiểm thử độc lập.

**Tại sao chọn Command?** Command Pattern cho phép đóng gói toàn bộ một use case (đăng ký) vào một class duy nhất. Controller chỉ việc tạo DTO và gọi `execute()`. Mọi dependency được inject qua constructor, dễ dàng Mock để viết unit test.

**Tác dụng đối với chức năng:**
- Controller cực kỳ mỏng (Skinny Controller), chỉ xử lý HTTP request/response.
- Command có thể được tái sử dụng: từ API, Console Command (CLI) hoặc Jobs.
- Dễ viết unit test nhờ Dependency Injection.

**Triển khai code:**

```php
// RegisterUserCommand — Concrete Command
final class RegisterUserCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,          // Receiver
        private readonly RegistrationValidationChain $validationChain, // Chain of Responsibility
        private readonly OtpService $otpService,                   // Adapter
    ) {}

    public function execute(RegisterUserDto $dto): void
    {
        $this->validationChain->validate($dto);                       // 1. Validate
        $user = $this->userRepository->create([...]);                 // 2. Lưu DB
        $this->otpService->generate($user, OtpContext::REGISTER);     // 3. Sinh OTP
        UserRegistered::dispatch($user);                               // 4. Phát Event
    }
}

// Controller (Invoker) rất mỏng:
public function register(RegisterRequest $request): JsonResponse
{
    $dto = RegisterUserDto::fromRequest($request);
    $this->registerUserCommand->execute($dto);
    return ApiResponse::success(message: 'Đăng ký thành công, vui lòng kiểm tra email.', statusCode: 202);
}
```

---

### 1B. Chain of Responsibility Pattern

**Vấn đề đặt ra:** Dữ liệu đăng ký phải được kiểm tra tuần tự và nghiêm ngặt: Email hợp lệ → Mật khẩu đủ mạnh → Email chưa tồn tại. Nếu viết các kiểm tra này bằng `if-else` lồng nhau, code sẽ tạo ra "mũi tên thụt lề" khó đọc và khó mở rộng.

**Tại sao chọn Chain of Responsibility?** Pattern này cho phép thực thi tuần tự các luật kiểm tra, nếu bất kỳ luật nào thất bại, chuỗi sẽ dừng ngay và ném ra Exception, không chạy các luật phía sau.

**Tác dụng đối với chức năng:**
- Không còn `if-else` lồng nhau, code đọc từ trên xuống dưới rất trực quan.
- Dễ dàng thêm luật kiểm tra mới (VD: rate limiting, blacklist email) chỉ bằng cách thêm một phương thức mới.

**Triển khai code:**

```php
final class RegistrationValidationChain
{
    public function validate(RegisterUserDto $dto): void
    {
        $this->checkEmailFormat($dto->email);         // Bước 1: Định dạng email
        $this->checkPasswordStrength($dto->password); // Bước 2: Mật khẩu mạnh
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

### 1C. Observer Pattern (Sự kiện đăng ký thành công)

**Vấn đề đặt ra:** Khi người dùng đăng ký xong, hệ thống cần gửi email chào mừng. Nếu viết trực tiếp trong Command, mỗi lần muốn thêm hành động phụ (VD: xóa cache, log audit) sẽ phải sửa Command.

**Tại sao chọn Observer?** Observer (Event/Listener trong Laravel) cho phép các hành vi phụ chạy tách biệt, không ảnh hưởng đến luồng chính và có thể chạy bất đồng bộ (Queue).

**Tác dụng đối với chức năng:**
- Luồng chính không bị chậm do gửi email.
- Lỗi gửi email không làm crash luồng đăng ký.
- Dễ thêm listener mới mà không sửa code cũ.

**Triển khai code:**

```php
// Event
class UserRegistered
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly User $user,
    ) {}
}

// Listener — gửi email chào mừng
class SendWelcomeNotification
{
    public function __construct(
        private readonly NotificationService $notificationService
    ) {}

    public function handle(UserRegistered $event): void
    {
        $this->notificationService->send(
            user: $event->user,
            type: MailType::WELCOME,
            channels: [NotificationChannelType::EMAIL],
        );
    }
}
```

---

## CHỨC NĂNG 2: ĐĂNG NHẬP

### 2A. Strategy Pattern

**Vấn đề đặt ra:** Hệ thống hỗ trợ đăng nhập qua nhiều phương thức: Email/Mật khẩu truyền thống và Google OAuth. Nếu dùng `switch-case` hoặc `if-else` trong Controller, mỗi lần thêm một phương thức đăng nhập mới (Facebook, Apple ID) sẽ phải sửa Controller và vi phạm nguyên tắc Open/Closed (OCP).

**Tại sao chọn Strategy?** Strategy Pattern cho phép đóng gói từng thuật toán đăng nhập vào các class riêng biệt, có thể thay thế và mở rộng linh hoạt.

**Tác dụng đối với chức năng:**
- Tuân thủ OCP — thêm phương thức đăng nhập mới chỉ cần thêm một class mới.
- `EmailPasswordAuthStrategy` và `GoogleOAuthAuthStrategy` chỉ làm nhiệm vụ xác thực của riêng mình.

**Triển khai code:**

```php
// Interface chung
interface AuthStrategy {
    public function method(): AuthMethod;
    public function authenticate(AuthPayload $payload): AuthResultDto;
}

// Strategy A: Email/Password
final class EmailPasswordAuthStrategy implements AuthStrategy
{
    public function authenticate(AuthPayload $payload): AuthResultDto
    {
        $this->loginValidationChain->validate($payload->email, $payload->password);
        $token = $this->tokenIssuer->issueForUser($user);
        return new AuthResultDto($user, $token);
    }
}

// Strategy B: Google OAuth
final class GoogleOAuthAuthStrategy implements AuthStrategy { ... }
```

---

### 2B. Factory Method Pattern (AuthStrategyResolver)

**Vấn đề đặt ra:** Controller và Service không thể biết trước người dùng sẽ chọn phương thức đăng nhập nào. Cần một lớp trung gian chọn và khởi tạo Strategy phù hợp dựa trên `AuthMethod` từ client.

**Tác dụng:** Controller chỉ việc gọi `$resolver->resolve(AuthMethod::EmailPassword)->authenticate($payload)`. Tất cả nằm trong Factory.

**Triển khai code:**

```php
final class AuthStrategyResolver
{
    public function resolve(AuthMethod $method): AuthStrategy
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->method() === $method) return $strategy;
        }
        throw new LogicException("No auth strategy for method [{$method->value}].");
    }
}
```

---

### 2C. Adapter Pattern (GoogleSocialiteAdapter)

**Vấn đề đặt ra:** SDK Google Socialite trả về đối tượng User với cấu trúc đặc thù. Domain của hệ thống không thể phụ thuộc trực tiếp vào SDK này.

**Tác dụng:** Khi SDK thay đổi, chỉ cần sửa duy nhất `GoogleSocialiteAdapter`, không ảnh hưởng đến phần còn lại.

**Triển khai code:**

```php
final class GoogleSocialiteAdapter implements SocialUserAdapter
{
    public function __construct(private readonly SocialiteUser $adaptee) {}

    public function getEmail(): string { return $this->adaptee->getEmail(); }
    public function getName(): string  { return $this->adaptee->getName(); }
}
```

---

## CHỨC NĂNG 3: NÂNG CẤP TIN ĐĂNG

### 3A. Command Pattern

**Vấn đề đặt ra:** Quy trình nâng cấp gồm 2 giai đoạn riêng biệt:
1. Tạo giao dịch PENDING + sinh link thanh toán (VNPAY).
2. Nhận callback thành công → cập nhật gói tin.

Mỗi giai đoạn cần được đóng gói trong một Command độc lập.

**Triển khai code:**

```php
// Giai đoạn 1: Tạo giao dịch
final class CreateUpgradePaymentCommand
{
    public function execute(...): string
    {
        $transaction = Transaction::create([...]);          // Tạo giao dịch PENDING
        ExpirePendingTransactionJob::dispatch($transaction->id)->delay(now()->addMinutes(15));
        return $this->paymentProviderFactory->for('VNPAY')->createPaymentUrl($transaction, $clientIp);
    }
}

// Giai đoạn 2: Kích hoạt nâng cấp
final class UpgradeListingCommand
{
    public function execute(...): Listing
    {
        $this->policy->assertEligible($context);
        return DB::transaction(function () use (...) {
            $transaction->update(['status' => 'SUCCESS']);
            $listing->update(['package_id' => $newPackage->id, 'package_expires_at' => $expiresAt]);
        });
    }
}
```

---

### 3B. Strategy Pattern (Tính hạn dùng)

**Vấn đề đặt ra:** Mua gói mới → hạn tính từ hôm nay. Gia hạn gói cũ → hạn cộng dồn vào ngày cũ. Hai công thức khác nhau cần được đóng gói riêng.

**Triển khai code:**

```php
interface ExpiryCalculationStrategy {
    public function calculate(UpgradeContext $context): CarbonInterface;
}

final class FreshPurchaseExpiryStrategy implements ExpiryCalculationStrategy {
    public function calculate(UpgradeContext $context): CarbonInterface {
        return $context->now->copy()->addDays($context->durationDays);
    }
}

final class RenewalExpiryStrategy implements ExpiryCalculationStrategy {
    public function calculate(UpgradeContext $context): CarbonInterface {
        $base = $context->listing->package_expires_at ?? $context->now;
        return $base->lessThanOrEqualTo($context->now)
            ? $context->now->addDays($context->durationDays)
            : $base->addDays($context->durationDays);
    }
}
```

---

### 3C. Specification Pattern (Kiểm định nâng cấp)

**Vấn đề đặt ra:** Quy tắc nâng cấp rất phức tạp: tin phải ACTIVE, người nâng cấp là chủ tin, gói mới phải active, và hoặc nâng cấp gói cao hơn hoặc gia hạn cùng gói.

**Triển khai code:**

```php
final class CanUpgradeSpecification {
    public function isSatisfiedBy(UpgradeContext $context): bool {
        return !$context->isRenewal() && $context->newPackage->priority > ($context->currentPackage?->priority ?? 0);
    }
}

final class CanRenewSpecification {
    public function isSatisfiedBy(UpgradeContext $context): bool {
        return $context->isRenewal();
    }
}
```

---

### 3D. Adapter Pattern (VNPAY PaymentGateway)

**Vấn đề đặt ra:** Cần chuẩn hóa giao tiếp với cổng thanh toán VNPAY. Nếu đổi sang MOMO sau này, chỉ cần viết thêm Adapter mới.

**Triển khai code:**

```php
interface PaymentGateway {
    public function method(): string;
    public function createPaymentUrl(Transaction $transaction, string $clientIp): string;
    public function verifyCallback(Request $request): CallbackResult;
}

final class VnpayGateway implements PaymentGateway {
    public function verifyCallback(Request $request): CallbackResult {
        return new CallbackResult(
            isValidSignature: $this->vnpayService->isValidReturn($request),
            responseCode: $request->query('vnp_ResponseCode'),
            reference: (string) $request->query('vnp_TxnRef', ''),
        );
    }
}
```

---

## CHỨC NĂNG 4: LỌC & SẮP XẾP TIN ĐĂNG (Filter Sorting)

### 4A. Strategy Pattern (Sắp xếp hiển thị)

**Vấn đề đặt ra:** Có 7+ cách sắp xếp tin đăng: mặc định (theo công thức suy giảm thời gian), giá tăng/giảm, diện tích tăng/giảm, mới nhất, cũ nhất. Nếu viết 7 nhánh `orderBy` trong Repository, code rất khối, mỗi lần thêm cách sắp xếp mới phải sửa Repository.

**Triển khai code:**

```php
interface ListingSortingStrategy {
    public function apply(Builder $query): Builder;
}

// Chiến lược sắp xếp mặc định — áp dụng công thức tính điểm ưu tiên phức tạp
final class DefaultPackageScoreSortingStrategy implements ListingSortingStrategy
{
    public function apply(Builder $query): Builder
    {
        $hoursSince = "TIMESTAMPDIFF(HOUR, COALESCE(listings.published_at, listings.created_at), NOW())";
        $formula = "(COALESCE(listings.score,0) * COALESCE(packages.multiplier,1.0) * (1.0/(1.0+{$hoursSince}/24.0)) * EXP(-COALESCE(packages.decay_rate,0.05) * {$hoursSince}))";

        return $query->leftJoin('packages', 'listings.package_id', '=', 'packages.id')
            ->orderByDesc('pkg_priority')
            ->orderByDesc('final_score');
    }
}

// Strategy giá tăng dần
final class PriceLowToHighSortingStrategy implements ListingSortingStrategy
{
    public function apply(Builder $query): Builder
    {
        return $query->orderBy('listings.price', 'asc');
    }
}
```

---

### 4B. Strategy Pattern (Lọc tìm kiếm)

**Vấn đề đặt ra:** Người dùng có thể tìm kiếm theo: tiêu đề, tên chủ tin, hoặc địa chỉ. Mỗi trường có logic `LIKE` khác nhau.

**Triển khai code:**

```php
interface SearchFieldStrategy {
    public function apply(Builder $query, string $normalizedKeyword): void;
}

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

### 4C. Factory Method Pattern (ListingSortingStrategyFactory & SearchFieldStrategyFactory)

**Vấn đề đặt ra:** Client (HTTP Request) gửi tham số `sort_by=price_asc` hoặc `search_field=owner`, Service cần Strategy tương ứng mà không cần biết cách khởi tạo class cụ thể.

**Triển khai code:**

```php
final class ListingSortingStrategyFactory {
    public static function make(?string $sortBy): ListingSortingStrategy {
        return match ($sortBy) {
            'price_asc' => new PriceLowToHighSortingStrategy,
            'price_desc' => new PriceHighToLowSortingStrategy,
            default => new DefaultPackageScoreSortingStrategy,
        };
    }
}
```

---

## CHỨC NĂNG 5: CHAT

### 5A. Facade Pattern

**Vấn đề đặt ra:** Phân hệ Chat có nhiều thực thể phức tạp: Conversation, Message, Group, Member, Participant. Controller không thể trực tiếp phối hợp tất cả các thực thể này; cần một interface thống nhất để che giấu sự phức tạp.

**Tác dụng đối với chức năng:** Controller chỉ cần gọi `$chatService->sendMessage(dto)` thay vì xử lý kiểm tra quyền participant, lưu message, cập nhật last_seen, broadcast event.

**Triển khai code:**

```php
// Facade Interface
interface ChatService {
    public function sendMessage(SendMessageDto $dto): Message;
    public function getOrCreateConversation(GetOrCreateConversationDto $dto): Conversation;
}

// Facade Implementation — che giấu sự phức tạp của ChatRepository, Conversation, Message, GroupMember
final class ChatServiceImpl implements ChatService
{
    public function sendMessage(SendMessageDto $dto): Message
    {
        $this->assertParticipant($dto->conversationId, $dto->senderId); // Kiểm tra quyền
        $message = $this->chatRepository->createMessage([...]);         // Lưu DB
        MessageSent::dispatch($message);                                // Broadcast realtime
        return $message->loadMissing('sender:id,full_name,avatar_url');
    }
}
```

---

### 5B. Observer Pattern (Broadcast tin nhắn qua WebSocket)

**Vấn đề đặt ra:** Khi tin nhắn được gửi, cần thông báo realtime đến người nhận qua WebSocket. Nếu gửi synchronous trong luồng chính, API bị chậm; nếu WebSocket lỗi, việc ghi tin nhắn bị crash theo.

**Tác dụng:** Event `MessageSent` phát ra sau khi lưu DB thành công. Laravel Queue & Broadcasting đảm nhận việc gửi WebSocket không đồng bộ. Nếu lỗi broadcast, DB vẫn được ghi.

**Triển khai code:**

```php
class MessageSent implements ShouldBroadcast {
    public function __construct(public readonly Message $message) {}
    public function broadcastOn(): array {
        return [new PrivateChannel('chat.'.$this->message->conversation_id)];
    }
}
```

---

## TỔNG KẾT VỀ CÁC DESIGN PATTERN ĐÃ DÙNG

| # | Design Pattern | Chức năng áp dụng | Mục đích chính |
|---|---|---|---|
| 1 | **Command** | Đăng ký, Nâng cấp tin | Đóng gói use case, tách Controller, dễ test |
| 2 | **Chain of Responsibility** | Đăng ký, Đăng nhập | Validate tuần tự, tránh if-else lồng nhau |
| 3 | **Strategy** | Đăng nhập, Sắp xếp, Lọc, Nâng cấp | Hoán đổi thuật toán linh hoạt, tuân thủ OCP |
| 4 | **Factory Method** | Đăng nhập, Sắp xếp, Lọc | Tập trung khởi tạo Strategy, giảm dependency |
| 5 | **Adapter** | Đăng nhập Google, Thanh toán VNPAY | Cách ly SDK ngoài, dễ đổi nhà cung cấp |
| 6 | **Observer** | Đăng ký, Chat, Nâng cấp | Decoupling event-driven, async, fail-safe |
| 7 | **Facade** | Chat | Che giấu độ phức tạp hệ thống con chat |
| 8 | **Specification** | Nâng cấp tin đăng | Đóng gói business rule, tái sử dụng |

---

> **Cảm ơn Thầy/Cô đã lắng nghe!**
> Mọi đóng góp và phản biện của hội đồng sẽ giúp chúng em hoàn thiện đồ án tốt hơn.
