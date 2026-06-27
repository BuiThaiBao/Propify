# Phân tích Design Pattern trong Dự Án Propify

## 1. Strategy Pattern

**Định nghĩa GOF**: Định nghĩa một họ các thuật toán, đóng gói chúng và làm cho chúng có thể hoán đổi cho nhau.

### Áp dụng trong dự án:

| Module | Interface | Các Strategy | Mục đích |
|---|---|---|---|
| **Auth** | `AuthStrategy` | `EmailPasswordAuthStrategy`, `GoogleOAuthAuthStrategy` | Cho phép thêm phương thức đăng nhập mới mà không sửa code xác thực |
| **Sorting** | `ListingSortingStrategy` | `NewestListingSortingStrategy`, `PriceLowToHighSortingStrategy`, `AreaHighToLowSortingStrategy` ... | Người dùng chọn tiêu chí sắp xếp, factory trả về strategy tương ứng |
| **Verification** | `VerificationStrategy` | `ManualVerificationStrategy`, `AutomaticVerificationStrategy` | Xác thực tin đăng theo nhiều cách |
| **Search Field** | `SearchFieldStrategy` | `TitleSearchStrategy`, `AddressSearchStrategy`, `OwnerSearchStrategy` | Tìm kiếm theo các trường khác nhau |
| **User Search** | `UserSearchStrategy` | `SearchKeywordStrategy`, `RoleFilterStrategy`, `AuthTypeFilterStrategy` | Tìm kiếm user với nhiều bộ lọc |
| **Payment** | `PaymentGateway` | `VnpayGateway` | Dễ dàng thêm cổng thanh toán mới (Momo, VNPay...) |

**Nhận xét**: Strategy Pattern được sử dụng rộng rãi nhất trong project. Các strategy được inject qua `AuthStrategyResolver`, `ListingSortingStrategyFactory`, `SearchFieldStrategyFactory` — đúng chuẩn GOF.

## 2. Chain of Responsibility (CoR) Pattern

**Định nghĩa GOF**: Cho phép nhiều đối tượng có cơ hội xử lý request bằng cách tạo thành một chuỗi.

### Áp dụng trong dự án:

| Module | Chain | Các Handler |
|---|---|---|
| **Listing Validation** | `ListingSubmissionValidationPipeline` | `UserPhoneVerifiedHandler`, `VerificationDocumentsHandler` |
| **Listing Reports** | Chain trong `ReportListingCommand` | `PreventDuplicateListingReportHandler`, `EnsureListingCanBeReportedHandler` |
| **Forgot Password** | `AbstractForgotPasswordHandler` | `FindResetUserHandler`, `SendResetOtpHandler`, `LogResetAttemptHandler` |

**Ví dụ** (`AbstractListingSubmissionValidationHandler`):
```php
abstract class AbstractListingSubmissionValidationHandler {
    private ?ListingSubmissionValidationHandler $next = null;
    
    public function handle(ListingSubmissionValidationContext $context): void {
        $this->validate($context);
        $this->next?->handle($context);  // Gọi handler tiếp theo
    }
}
```

**Nhận xét**: Đúng chuẩn GOF — mỗi handler có một nhiệm vụ kiểm tra duy nhất, pipeline nối chúng lại với nhau.

## 3. State Pattern

**Định nghĩa GOF**: Cho phép đối tượng thay đổi hành vi khi trạng thái nội tại của nó thay đổi.

### Áp dụng trong dự án:

| Module | Interface | Các State | Mục đích |
|---|---|---|---|
| **Listing Status** | `ListingStatusState` | `DraftListingState`, `PendingListingState`, `ActiveListingState`, `RejectedListingState`, `LockedListingState`, `UnlistedListingState` | Quản lý vòng đời tin đăng |
| **Payment Status** | `TransactionState` | `PendingTransactionState`, `SuccessTransactionState`, `SettledTransactionState` | Quản lý trạng thái giao dịch |
| **Appointment Status** | `BookingState` | `PendingState`, `ApprovedState`, `TerminalState` | Quản lý lịch hẹn |
| **User Status** | `UserState` | `ActiveUserState`, `BannedUserState` | Quản lý trạng thái user |

**Ví dụ** (`ActiveListingState`):
```php
final class ActiveListingState extends AbstractListingStatusState {
    public function value(): string { return 'ACTIVE'; }
    protected function allowedTransitions(): array {
        return ['LOCKED', 'REJECTED', 'UNLISTED'];
    }
}
```

**Nhận xét**: Chuẩn GOF — mỗi state biết nó có thể chuyển sang state nào, factory tạo state từ string.

## 4. Command Pattern

**Định nghĩa GOF**: Đóng gói một request thành một đối tượng, cho phép tham số hóa client.

### Áp dụng trong dự án:

| Module | Command | Mục đích |
|---|---|---|
| **Listing** | `CreateListingCommand`, `UpdateListingCommand`, `SaveDraftListingCommand`, `UnlistListingCommand`, `SubmitListingVerificationCommand` | Mỗi thao tác trên tin đăng là một command riêng biệt |
| **Moderation** | `ApproveListingCommand`, `RejectListingCommand`, `LockListingCommand` | Kiểm duyệt tin đăng |
| **Appointment** | `ConfirmBookingCommand`, `CancelBookingCommand`, `RejectBookingCommand` | Xử lý lịch hẹn |
| **Auth** | `RegisterUserCommand`, `ResetPasswordCommand`, `RequestPasswordResetCommand` | Xác thực người dùng |
| **Package** | `CreatePackageCommand`, `ActivatePackageCommand`, `LockPackageCommand` | Quản lý gói tin |

**Ví dụ** (`CreateListingCommand`): Nhận DTO, thực hiện cả transaction (tạo Property → Listing → Images → Videos → Documents), dispatch event sau khi thành công.

**Nhận xét**: Đúng chuẩn GOF — mỗi command gói gọn một use case, dễ test, dễ maintain.

## 5. Template Method Pattern

**Định nghĩa GOF**: Định nghĩa khung (skeleton) của thuật toán trong một method, để các lớp con implements các bước chi tiết.

### Áp dụng trong dự án:

| Module | Abstract Class | Template Method | Các bước con |
|---|---|---|---|
| **Moderation** | `AbstractListingModerationCommand` | `execute()` | `validate()`, `mutate()`, `targetStatus()` |
| **Validation Handler** | `AbstractListingSubmissionValidationHandler` | `handle()` | `validate()` |

**Ví dụ** (`AbstractListingModerationCommand::execute()`):
```php
final public function execute(int $listingId, ModerationContext $ctx): Listing {
    $this->validate($ctx);               // Hook 1: kiểm tra tiền điều kiện
    DB::transaction(function () {
        $this->statusStateFactory->assertCanTransition(...);  // State check
        $this->mutate($listing, $ctx);   // Hook 2: thay đổi listing
        $listing->save();
        ListingStatusHistory::create(...); // Ghi log
    });
    ListingSaved::dispatch($loaded, ...); // Post-processing
    return $loaded;
}
```

**Nhận xét**: Chuẩn GOF — khung xử lý cố định viết một lần, lớp con chỉ điền các bước khác nhau.

## 6. Repository Pattern

**Định nghĩa**: Trừu tượng hóa tầng dữ liệu, tách biệt logic truy vấn khỏi business logic.

### Áp dụng trong dự án:

| Interface | Implementation | Module |
|---|---|---|
| `ListingRepository` | `EloquentListingRepository` | Tin đăng |
| `UserRepository` | `EloquentUserRepository` | Người dùng |
| `ChatRepository` | `EloquentChatRepository` | Chat |
| `AppointmentSlotRepository` | `EloquentAppointmentSlotRepository` | Lịch hẹn |
| `PackageRepository` | `EloquentPackageRepository` | Gói tin |
| `AmenityRepository` | `EloquentAmenityRepository` | Tiện ích |

**Nhận xét**: Toàn bộ tầng Service không biết đến Eloquent — chỉ gọi qua Interface. Đây là Port & Adapter (Hexagonal Architecture) mở rộng từ Repository Pattern.

## 7. Adapter Pattern

**Định nghĩa GOF**: Chuyển đổi interface của một lớp thành interface khác mà client mong đợi.

### Áp dụng trong dự án:

| Interface | Adapter | Vai trò |
|---|---|---|
| `UploadSignatureAdapter` | `CloudinaryUploadSignatureAdapter` | Chuyển đổi từ CloudinaryService sang interface đồng nhất để tạo upload signature |

**Nhận xét**: Cho phép dễ dàng thay thế Cloudinary bằng service khác (R2, S3...) mà không sửa code gọi.

## 8. Observer Pattern

**Định nghĩa GOF**: Định nghĩa quan hệ một-nhiều giữa các đối tượng để khi một đối tượng thay đổi trạng thái, tất cả các phụ thuộc của nó được thông báo.

### Áp dụng trong dự án:

**Event → Listener (Laravel built-in)**:

| Event | Listeners |
|---|---|
| `ListingSaved` | `ClearPublicListingCache`, `LogListingSaved`, `CreateListingNotification` |
| `AppointmentBooked` | `SendAppointmentBookedNotification` |
| `AppointmentBookingStatusUpdated` | `SendAppointmentBookingStatusNotification` |
| `MessageSent` | Pusher broadcast (qua queue) |
| `FavoriteToggled` | `LogFavoriteAction` |

**Custom Observer (UserSubject)**:
- `UserSubject` → `NotificationObserver`, `AuditLogObserver`

**Nhận xét**: Project sử dụng cả Event của Laravel và tự xây Observer pattern riêng cho User.

## 9. Factory Pattern

**Định nghĩa GOF**: Định nghĩa interface để tạo đối tượng, để lớp con quyết định khởi tạo lớp nào.

### Áp dụng trong dự án:

| Factory | Tạo ra | Mục đích |
|---|---|---|
| `ListingStatusStateFactory` | `ListingStatusState` (Active, Pending, Draft...) | Tạo state object từ string status |
| `ExpiryCalculationStrategyFactory` | `ExpiryCalculationStrategy` | Tính ngày hết hạn theo loại giao dịch (mua mới/gia hạn) |
| `PackageBenefitStrategyFactory` | `PackageBenefitStrategy` | Tính quyền lợi gói tin |
| `PaymentProviderFactory` | `PaymentGateway` | Chọn cổng thanh toán |
| `ListingSortingStrategyFactory` | `ListingSortingStrategy` | Tạo strategy sắp xếp |
| `SearchFieldStrategyFactory` | `SearchFieldStrategy` | Tạo strategy tìm kiếm |
| `AuthCookieFactory` | Cookie config | Tạo cấu hình cookie auth |

**Nhận xét**: Dùng đúng chuẩn GOF (Factory Method / Simple Factory).

## 10. Facade Pattern

**Định nghĩa GOF**: Cung cấp một interface đơn giản cho một hệ thống con phức tạp.

### Áp dụng trong dự án:

**`AccountFacade`**: Gom UserRepository + Visibility strategies vào một method `getAccountOverview()` duy nhất — client chỉ cần gọi facade, không cần biết bên trong có những strategy nào.

## 11. Singleton Pattern

**Định nghĩa GOF**: Đảm bảo một lớp chỉ có một instance duy nhất.

### Áp dụng trong dự án:

Laravel App Service Container hoạt động như Singleton container — tất cả service, repository, controller đều là singleton trong một request lifecycle.

## 12. Composite Pattern

**Định nghĩa GOF**: Gom các đối tượng thành cấu trúc cây để biểu diễn quan hệ whole-part.

### Áp dụng trong dự án:

**Eloquent ORM**: `Listing → Property → Attributes` — các model Eloquent có sẵn quan hệ composition (hasMany, belongsTo) tạo thành cấu trúc cây tự nhiên.

---

## Tổng kết

| Pattern | Mức độ sử dụng | Ghi chú |
|---|---|---|
| **Strategy** | ⭐⭐⭐⭐⭐ | Rất nhiều (Auth, Sorting, Verification, Search...) |
| **Command** | ⭐⭐⭐⭐⭐ | Mỗi use case là một Command riêng |
| **Repository** | ⭐⭐⭐⭐⭐ | Interface + Implementation xuyên suốt |
| **State** | ⭐⭐⭐⭐ | Listing, Payment, Appointment, User |
| **Template Method** | ⭐⭐⭐⭐ | AbstractListingModerationCommand + Validation Handler |
| **Chain of Responsibility** | ⭐⭐⭐ | Listing Validation Pipeline, Report Chain |
| **Factory** | ⭐⭐⭐ | StateFactory, StrategyFactory, PaymentFactory |
| **Observer** | ⭐⭐⭐ | Event/Listener của Laravel + UserSubject riêng |
| **Adapter** | ⭐⭐ | UploadSignatureAdapter (Cloudinary) |
| **Facade** | ⭐⭐ | AccountFacade |
| **Composite** | ⭐ | Quan hệ Model Eloquent |
| **Singleton** | ⭐ | Laravel Service Container |
