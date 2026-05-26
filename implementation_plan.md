# Kế hoạch Refactor theo Design Pattern — Clean Architecture & SOLID (v2)

## Bối cảnh

Dự án Propify hiện tại đã có nền tảng kiến trúc khá tốt: interface/impl cho Service và Repository, Strategy pattern cho Auth, Chain of Responsibility cho Forgot Password, Command pattern cho User actions. Tuy nhiên, nhiều chức năng vẫn chưa áp dụng đầy đủ design pattern theo tài liệu [design-pattern.md](file:///d:/PROJECT/Meyland/design-pattern.md).

Kế hoạch này refactor **11 chức năng** theo design pattern, đảm bảo:
- **Clean Architecture**: Domain → Application → Interface Adapter → Infrastructure
- **SOLID**: Module cấp cao không phụ thuộc module cấp thấp (DIP), mở rộng không sửa code cũ (OCP)
- **Backward compatible**: Không phá vỡ API contract hiện tại
- **Pragmatic**: Chỉ áp dụng pattern khi có giá trị rõ ràng, tránh áp dụng máy móc

> [!NOTE]
> Các pattern được đề xuất có thể áp dụng tùy mức độ phức tạp của nghiệp vụ. Trong triển khai thực tế, ưu tiên các pattern giải quyết vấn đề rõ ràng, tránh áp dụng máy móc. Trong phạm vi Laravel, các Domain Events được triển khai bằng Laravel Events/Listeners để đơn giản hóa tích hợp. Tuy nhiên, UseCase vẫn không phụ thuộc trực tiếp vào hạ tầng như Email, Database, VNPAY.

---

## Phân tích hiện trạng & Gap Analysis

| Chức năng | Hiện trạng | Pattern cần bổ sung |
|---|---|---|
| Đăng ký | Logic trực tiếp trong `AuthServiceImpl`, thiếu validation chain | Command, Chain of Responsibility, Observer |
| Đăng nhập | ✅ Đã có Strategy + Adapter + Resolver. Thiếu CoR kiểm tra trạng thái, thiếu Facade, thiếu Observer | Chain of Responsibility, Facade, Observer |
| Xem thông tin tài khoản | Logic mỏng trong `UserServiceImpl.getProfile()`, thiếu phân quyền hiển thị | Facade, Strategy (Visibility), Policy (Proxy) |
| Chỉnh sửa thông tin cá nhân | ✅ Đã có Command + AuditLog. Thiếu validation chain, thiếu Observer event | Chain of Responsibility, Observer. AuditLog.changes đóng vai trò Memento |
| Đổi mật khẩu | ✅ Đã có Command. Thiếu validation chain, thiếu Observer event | Chain of Responsibility, Observer |
| Quên mật khẩu | ✅ Đã có Chain of Responsibility. Thiếu Command wrap | Command, Observer |
| Xác thực tin đăng | Logic inline trong `ListingServiceImpl`, thiếu Strategy xác thực | Strategy, Chain of Responsibility, Observer |
| Tin đăng yêu thích | Logic trực tiếp trong Controller (FAT controller!), thiếu mọi pattern | Command, Observer, Service layer |
| Nâng cấp gói tin | ✅ Đã có Strategy + Specification + Factory. Thiếu Command wrap | Command, Observer (đã có event). Decorator chỉ cần nếu bọc thêm quyền lợi hiển thị |
| Thêm gói tin | Logic inline trong `PackageServiceImpl`, thiếu Factory Method, thiếu Command | Factory Method, Command |
| Khóa/Kích hoạt gói tin | Logic inline trong `PackageServiceImpl.delete()`, chỉ set `is_active = false` | Command, Observer. State Pattern chỉ cần nếu mở rộng thêm nhiều trạng thái |

---

## Phạm vi & Giới hạn

> [!IMPORTANT]
> **Phạm vi refactor chỉ Backend (PropifyBackend)**. Frontend (Vue) sẽ không bị ảnh hưởng vì API contract giữ nguyên.

> [!WARNING]
> **[ListingServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php) hiện 814 dòng** — đây là file lớn nhất cần refactor. Kế hoạch sẽ tách thành nhiều service nhỏ hơn (VerificationService, FavoriteService, UpgradeCommand) nhưng vẫn giữ `ListingService` interface không thay đổi.

> [!CAUTION]
> **[FavoriteController.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Controllers/Api/V1/Listing/FavoriteController.php) hiện là FAT controller** — logic nghiệp vụ nằm hoàn toàn trong controller, truy vấn trực tiếp Model. Cần tách ra Service + Repository.

---

## Quyết định thiết kế (đã giải quyết)

### 1. Memento cho Profile/Listing

**Đề xuất:** Giai đoạn hiện tại dùng `AuditLog.changes` làm Memento cho Profile để tránh tạo thêm bảng. Với Listing, nếu có yêu cầu khôi phục phiên bản cũ thì tạo bảng `listing_snapshots`. **Không áp dụng Memento cho đổi mật khẩu** vì lý do bảo mật — không được lưu mật khẩu cũ, kể cả hash.

### 2. Observer events

**Đề xuất:** Nên phát Laravel Events cho các hành động quan trọng: đăng nhập, đổi mật khẩu, cập nhật profile, xác thực tin, nâng cấp gói. Listener có thể chỉ ghi log trước, sau này mở rộng gửi email/notification.

### 3. Khóa/Kích hoạt gói tin

**Đề xuất:** Trước mắt giữ `is_active` boolean để backward compatible. Nếu cần lưu lý do khóa, thêm cột `locked_reason`, `locked_at`, `locked_by`. Khi trạng thái phức tạp hơn (Draft, Active, Locked, Archived, Expired) mới chuyển sang `PackageStatus` enum và State Machine.

### 4. Proxy Pattern trong Laravel

**Đề xuất:** Proxy Pattern được triển khai thông qua **Laravel Policy/Middleware** để kiểm soát quyền truy cập trước khi gọi Service thật. Không tạo class Proxy riêng mà tận dụng cơ chế framework sẵn có (`Gate`, `Policy`, `FormRequest::authorize()`).

### 5. Decorator trong Nâng cấp gói tin

**Đề xuất:** Benefit strategies hiện tại (`PackageBenefitStrategy`, `DataDrivenBenefitStrategy`) là **Strategy Pattern**, không phải Decorator. Decorator chỉ áp dụng nếu hệ thống bọc thêm quyền lợi hiển thị cho tin đăng (VIP badge, nổi bật, ghim đầu, xác thực) theo dạng:
```php
$listing = new VipListingDecorator($listing);
$listing = new HighlightListingDecorator($listing);
```
Ở giai đoạn hiện tại, Decorator chưa cần triển khai — chỉ là đề xuất mở rộng.

---

## Proposed Changes — Vertical Slice

Triển khai theo **từng chức năng hoàn chỉnh** (vertical slice): mỗi phase refactor xong là hệ thống vẫn hoạt động được và dễ test.

---

### Phase 1: Refactor Tin đăng yêu thích

**Ưu tiên cao nhất** vì đang vi phạm Clean Architecture nghiêm trọng (FAT controller).

**Pattern: Command + Observer + Policy (Proxy)**

#### [NEW] `app/Repositories/FavoriteRepository.php`
Interface repository cho favorites:
```php
interface FavoriteRepository {
    public function findByUser(int $userId, string $type = 'FAVORITE'): Collection;
    public function findIdsByUser(int $userId, string $type = 'FAVORITE'): Collection;
    public function findByUserAndListing(int $userId, int $listingId, string $type): ?UserFavorite;
    public function create(array $attributes): UserFavorite;
    public function delete(int $id): void;
}
```

#### [NEW] `app/Repositories/Eloquent/EloquentFavoriteRepository.php`
Eloquent implementation.

#### [NEW] `app/Services/Listing/Favorite/FavoriteService.php`
Interface:
```php
interface FavoriteService {
    public function getUserFavorites(int $userId): Collection;
    public function getUserFavoriteIds(int $userId): Collection;
    public function toggle(int $userId, int $listingId): bool;
}
```

#### [NEW] `app/Services/Listing/Favorite/Impl/FavoriteServiceImpl.php`
Implementation sử dụng `FavoriteRepository`. Phát `FavoriteToggled` event.

#### [NEW] `app/Events/Listing/FavoriteToggled.php`
Event cho thống kê yêu thích — Observer pattern.

#### [MODIFY] [FavoriteController.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Controllers/Api/V1/Listing/FavoriteController.php)
- Inject `FavoriteService` thay vì dùng Model trực tiếp
- Controller chỉ còn nhận request → gọi service → trả response
- Kiểm tra đăng nhập qua middleware (Proxy via Middleware)

#### [MODIFY] [AppServiceProvider.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Providers/AppServiceProvider.php)
- Thêm bindings: `FavoriteRepository`, `FavoriteService`
- Thêm Event → Listener mapping cho `FavoriteToggled`

**Test cases:**
- User có thể thêm tin yêu thích
- User có thể bỏ yêu thích
- Không thể yêu thích tin không tồn tại
- Không thể tạo duplicate favorite (unique index `user_id, listing_id`)
- User chưa đăng nhập không toggle được

---

### Phase 2: Refactor Nâng cấp gói tin

**Pattern: Command + Strategy (✅ đã có) + Factory (✅ đã có) + Observer (✅ đã có event)**

#### [NEW] `app/Services/Listing/Upgrade/UpgradeListingCommand.php`
Command wrap logic nâng cấp đang inline trong `ListingServiceImpl`:
```php
final class UpgradeListingCommand {
    public function __construct(
        private readonly UpgradeEligibilityPolicy $policy,
        private readonly ExpiryCalculationStrategyFactory $expiryFactory,
        private readonly PackageBenefitStrategyFactory $benefitFactory,
    ) {}
    public function execute(UpgradeContext $context): Listing;
}
```

#### [NEW] `app/Services/Listing/Upgrade/CreateUpgradePaymentCommand.php`
Command wrap logic tạo payment URL, inject `VnpayService` qua interface.

#### [MODIFY] [ListingServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php)
- `upgradeListing()`: delegate sang `UpgradeListingCommand`
- `createUpgradePayment()`: delegate sang `CreateUpgradePaymentCommand`
- `completePaidUpgrade()`: delegate sang `UpgradeListingCommand`
- Xóa `applyCompletedUpgrade()` và `prepareUpgrade()` private methods
- Giảm ~120 dòng trong file này

#### [MODIFY] [AppServiceProvider.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Providers/AppServiceProvider.php)
- Autowire Commands (Laravel tự resolve constructor dependencies)

**Test cases:**
- User nâng cấp gói thành công, listing cập nhật `package_id` và `package_expires_at`
- Không cho nâng cấp tin không phải của mình
- Không cho nâng cấp bằng gói đã bị khóa (`is_active = false`)
- Không cho nâng cấp tin không ở trạng thái ACTIVE
- VNPAY payment URL được tạo đúng format
- Gia hạn (renew cùng gói) cộng thêm thời hạn

---

### Phase 3: Refactor Xác thực tin đăng

**Pattern: Strategy + Chain of Responsibility + Observer**

#### [NEW] `app/Services/Listing/Verification/VerificationStrategy.php`
Interface cho các phương thức xác thực:
```php
interface VerificationStrategy {
    public function supports(string $method): bool;
    public function verify(Listing $listing, array $documents): VerificationResult;
}
```

#### [NEW] `app/Services/Listing/Verification/ManualVerificationStrategy.php`
Xác thực thủ công bởi admin (flow hiện tại).

#### [NEW] `app/Services/Listing/Verification/ListingVerificationService.php`
Service riêng tách từ `ListingServiceImpl`:
```php
interface ListingVerificationService {
    public function requestVerification(User $user, int $listingId, array $documents): Listing;
    public function approveVerification(int $listingId, int $adminUserId): Listing;
    public function rejectVerification(int $listingId, int $adminUserId, string $reason): Listing;
}
```

#### [NEW] `app/Services/Listing/Verification/Impl/ListingVerificationServiceImpl.php`
Implementation tách logic `updateVerification()` và `updateVerificationForAdmin()` từ `ListingServiceImpl`.

#### [NEW] `app/Events/Listing/ListingVerificationRequested.php`
Event phát khi user yêu cầu xác thực tin.

#### [MODIFY] [ListingServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php)
- `updateVerification()`: delegate sang `ListingVerificationService`
- `updateVerificationForAdmin()`: delegate sang `ListingVerificationService`
- Giảm ~100 dòng

#### [MODIFY] [AppServiceProvider.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Providers/AppServiceProvider.php)
- Thêm binding `ListingVerificationService`
- Thêm Event → Listener cho `ListingVerificationRequested`

**Test cases:**
- User gửi yêu cầu xác thực tin thành công
- Admin duyệt xác thực tin → `is_verified = true`
- Admin từ chối xác thực → cần nhập lý do
- Không xác thực tin đã bị khóa/gỡ
- `ListingStatusHistory` được ghi log đúng

---

### Phase 4: Refactor Đăng ký / Đăng nhập / Quên mật khẩu

#### 4.1 Đăng ký tài khoản

**Pattern: Command + Chain of Responsibility + Observer**

> [!NOTE]
> Builder không cần thiết cho đăng ký vì User chỉ có vài trường cơ bản (name, email, password, phone, role). `RegisterUserDto` đã đủ vai trò. Builder phù hợp hơn với tạo tin đăng bất động sản hoặc bộ lọc tìm kiếm — nơi object có rất nhiều trường optional.

##### [NEW] `app/Services/Auth/Registration/RegisterUserCommand.php`
Command đóng gói toàn bộ logic đăng ký, tách khỏi `AuthServiceImpl`:
```php
final class RegisterUserCommand {
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RegistrationValidationChain $validationChain,
        private readonly OtpService $otpService,
    ) {}
    public function execute(RegisterUserDto $dto): void {
        $this->validationChain->validate($dto);
        // Create/update user
        // Generate OTP
    }
}
```

##### [NEW] `app/Services/Auth/Registration/RegistrationValidationChain.php`
Chain of Responsibility kiểm tra: email format → password strength → duplicate check.

##### [NEW] `app/Services/Auth/Registration/Handlers/`
- `EmailFormatHandler.php`
- `PasswordStrengthHandler.php`
- `DuplicateEmailHandler.php`

##### [MODIFY] [AuthServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Auth/Impl/AuthServiceImpl.php)
- Method `register()`: delegate sang `RegisterUserCommand`

---

#### 4.2 Đăng nhập tài khoản

**Pattern: Strategy (✅ đã có) + Chain of Responsibility + Facade + Observer**

##### [NEW] `app/Services/Auth/Login/LoginValidationChain.php`
Chain kiểm tra tuần tự: account exists → account not locked → credential valid.

##### [NEW] `app/Services/Auth/Login/Handlers/`
- `AccountExistsHandler.php`
- `AccountStatusHandler.php` — kiểm tra Locked/Banned

##### [NEW] `app/Services/Auth/AuthFacade.php`
Facade đơn giản hóa giao diện cho controller:
```php
final class AuthFacade {
    public function login(LoginCredentialsDto $dto): AuthResultDto;
    public function loginWithGoogle(string $authCode): AuthResultDto;
    public function logout(?string $refreshToken): void;
}
```

##### [NEW] `app/Events/Auth/UserLoggedIn.php`
Event phát sau đăng nhập thành công.

##### [NEW] `app/Listeners/Auth/LogSuccessfulLogin.php`
Listener ghi `last_login_at`, audit log.

##### [MODIFY] [EmailPasswordAuthStrategy.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Auth/Strategies/EmailPasswordAuthStrategy.php)
- Tách logic kiểm tra `status !== Active` ra `LoginValidationChain`
- Phát `UserLoggedIn` event sau khi đăng nhập thành công

---

#### 4.3 Quên mật khẩu

**Pattern: Chain of Responsibility (✅ đã có) + Command + Observer**

##### [NEW] `app/Services/Auth/ForgotPassword/RequestPasswordResetCommand.php`
Command wrap logic gọi `ForgotPasswordChain`.

##### [NEW] `app/Services/Auth/ForgotPassword/ResetPasswordCommand.php`
Command đóng gói verify OTP + update password.

##### [NEW] `app/Events/Auth/PasswordReset.php`
Event phát sau reset thành công.

##### [MODIFY] [AuthServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Auth/Impl/AuthServiceImpl.php)
- `forgotPassword()`: delegate sang `RequestPasswordResetCommand`
- `resetPassword()`: delegate sang `ResetPasswordCommand`, phát `PasswordReset` event

**Test cases (Auth nhóm chung):**
- Đăng ký user mới → status = Pending, OTP được gửi
- Đăng ký email đã tồn tại (Active) → lỗi
- Đăng ký email đang Pending → cập nhật lại thông tin
- Login email/password thành công → trả token
- Login tài khoản bị khóa → lỗi `AuthNotVerified`
- Login sai mật khẩu → lỗi `AuthLoginFailed`
- Quên mật khẩu → OTP gửi qua chain FindUser → SendOtp → Log
- Reset mật khẩu đúng OTP → password updated
- Reset mật khẩu sai OTP → lỗi

---

### Phase 5: Refactor Xem TK / Chỉnh sửa Profile / Đổi mật khẩu

#### 5.1 Xem thông tin tài khoản

**Pattern: Facade + Strategy (Visibility) + Policy (Proxy)**

##### [NEW] `app/Services/User/AccountFacade.php`
Gom logic lấy profile + thống kê tin đăng + lịch sử gói:
```php
final class AccountFacade {
    public function getAccountOverview(User $viewer, int $targetUserId): AccountOverviewDto;
}
```

##### [NEW] `app/Services/User/Visibility/VisibilityStrategy.php`
Interface quyết định trường nào được hiển thị:
```php
interface VisibilityStrategy {
    public function filter(User $target, array $data): array;
}
```

##### [NEW] `app/Services/User/Visibility/OwnerVisibilityStrategy.php`
Hiện toàn bộ thông tin cho chủ tài khoản.

##### [NEW] `app/Services/User/Visibility/PublicVisibilityStrategy.php`
Ẩn email, phone, thông tin nhạy cảm cho user khác.

##### Proxy triển khai qua Policy
Kiểm tra quyền xem tài khoản bằng `UserPolicy::view()` thay vì tạo class `AccountAccessProxy` riêng.

##### [MODIFY] [UserServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/User/Impl/UserServiceImpl.php)
- Inject `AccountFacade` cho logic tổng hợp thông tin

---

#### 5.2 Chỉnh sửa thông tin cá nhân

**Pattern: Command (✅ đã có) + Chain of Responsibility + Observer + Memento (via AuditLog)**

##### [NEW] `app/Services/User/Validation/ProfileValidationChain.php`
Chain kiểm tra: phone format → required fields.

##### [NEW] `app/Services/User/Validation/Handlers/`
- `PhoneFormatHandler.php`
- `RequiredFieldsHandler.php`

##### [NEW] `app/Events/User/ProfileUpdated.php`
Event phát sau cập nhật profile.

##### [MODIFY] [UpdateUserProfileCommand.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Commands/User/UpdateUserProfileCommand.php)
- Inject `ProfileValidationChain` trước khi update
- Phát `ProfileUpdated` event sau khi update (Observer)
- `AuditLog.changes` field đã đóng vai trò **Memento** — lưu snapshot thay đổi (old/new values), giữ nguyên, bổ sung comment giải thích pattern

---

#### 5.3 Đổi mật khẩu

**Pattern: Chain of Responsibility + Command (✅ đã có) + Observer**

> [!WARNING]
> **Không áp dụng Memento cho đổi mật khẩu.** Không được lưu mật khẩu cũ (kể cả hash) vì lý do bảo mật. Chỉ audit metadata: `user_id`, `action = password_changed`, `changed_at`, `ip_address`.

##### [NEW] `app/Services/User/Validation/PasswordValidationChain.php`
Chain kiểm tra: current password correct → new password strength.

##### [NEW] `app/Services/User/Validation/Handlers/`
- `CurrentPasswordHandler.php` — tách `Hash::check` từ Command
- `PasswordStrengthHandler.php`

##### [NEW] `app/Events/Auth/PasswordChanged.php`
Event phát sau đổi mật khẩu.

##### [NEW] `app/Listeners/Auth/SendPasswordChangeAlert.php`
Gửi email cảnh báo khi mật khẩu bị thay đổi.

##### [MODIFY] [ChangeUserPasswordCommand.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Commands/User/ChangeUserPasswordCommand.php)
- Inject `PasswordValidationChain`
- Tách logic `Hash::check` ra handler trong chain
- Phát `PasswordChanged` event
- AuditLog chỉ ghi metadata, **không lưu password hash**

**Test cases (User nhóm chung):**
- Xem profile chính mình → trả đầy đủ thông tin
- Xem profile user khác → ẩn email/phone
- Cập nhật profile → AuditLog ghi changes (old/new)
- Cập nhật phone chỉ được 1 lần (khi phone đang trống)
- Đổi mật khẩu đúng → password updated, event phát
- Đổi mật khẩu sai current → lỗi `AuthPasswordIncorrect`
- AuditLog đổi mật khẩu **không chứa** password hash

---

### Phase 6: Refactor Thêm gói tin + Khóa/Kích hoạt gói tin

#### 6.1 Thêm gói tin (Admin)

**Pattern: Factory Method + Command**

##### [NEW] `app/Services/Packages/PackageFactory.php`
Factory tạo gói, đóng gói logic tạo Package + sync pricing:
```php
final class PackageFactory {
    public function create(CreatePackageDto $dto): Package;
}
```

##### [NEW] `app/Services/Packages/CreatePackageCommand.php`
Command đóng gói logic tạo gói + validate trùng tên + sync pricing:
```php
final class CreatePackageCommand {
    public function __construct(
        private readonly PackageFactory $factory,
        private readonly PackageRepository $repository,
    ) {}
    public function execute(CreatePackageDto $dto): Package;
}
```

##### [NEW] `app/Events/Package/PackageCreated.php`
Event phát khi tạo gói mới.

##### [MODIFY] [PackageServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Packages/Impl/PackageServiceImpl.php)
- `create()`: delegate sang `CreatePackageCommand`
- Phát `PackageCreated` event

---

#### 6.2 Khóa/Kích hoạt gói tin (Admin)

**Pattern: Command + Observer + Policy (Proxy)**

> [!NOTE]
> Ở mức hiện tại giữ `is_active` boolean. Nếu hệ thống mở rộng thêm nhiều trạng thái (Draft, Active, Locked, Archived, Expired) thì mới chuyển sang `PackageStatus` enum và State Machine. Nếu cần lưu lý do khóa, thêm cột `locked_reason`, `locked_at`, `locked_by` vào bảng `packages`.

##### [NEW] `app/Services/Packages/Commands/ActivatePackageCommand.php`
```php
final class ActivatePackageCommand {
    public function execute(int $packageId, int $adminUserId): Package {
        // Policy: check admin permission (via Laravel Gate/Policy)
        // Validate: package đang bị khóa mới được kích hoạt
        // Update: is_active = true
        // Observer: dispatch PackageStatusChanged
    }
}
```

##### [NEW] `app/Services/Packages/Commands/LockPackageCommand.php`
```php
final class LockPackageCommand {
    public function execute(int $packageId, int $adminUserId, ?string $reason): Package {
        // Policy: check admin permission
        // Validate: package đang active mới được khóa
        // Update: is_active = false, locked_reason, locked_at, locked_by
        // Observer: dispatch PackageStatusChanged
    }
}
```

##### [NEW] `app/Events/Package/PackageStatusChanged.php`
Event phát khi gói bị khóa/kích hoạt.

##### [NEW] `app/Listeners/Package/NotifyPackageStatusChange.php`
Gửi thông báo cho user đang dùng gói khi gói bị khóa.

##### [MODIFY] [PackageService.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Packages/PackageService.php)
- Thêm methods: `activate(int $id): Package` và `lock(int $id, ?string $reason): Package`
- Đổi tên `delete()` thành `lock()` (semantic chính xác hơn)

##### [MODIFY] [PackageServiceImpl.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Services/Packages/Impl/PackageServiceImpl.php)
- Delegate sang Command classes

##### [MODIFY] [AppServiceProvider.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Providers/AppServiceProvider.php)
- Thêm bindings mới cho Phase 6
- Thêm Event → Listener mapping

**Test cases (Package nhóm chung):**
- Admin tạo gói thành công → pricings được sync
- Tạo gói trùng tên → lỗi `PackageAlreadyExists`
- Admin khóa gói → `is_active = false`
- Admin kích hoạt gói → `is_active = true`
- User không thể nâng cấp bằng gói đã bị khóa
- Không cho khóa gói đang bị khóa (idempotent check)

---

## Tổng kết Design Pattern áp dụng

> [!NOTE]
> Không phải tất cả pattern đều được áp dụng đồng thời trong mọi chức năng. Hệ thống ưu tiên các pattern có giá trị rõ ràng:

| Pattern | Loại | Chức năng áp dụng | Ghi chú |
|---|---|---|---|
| **Command** | [B] | Đăng ký, Chỉnh sửa profile, Đổi mật khẩu, Quên mật khẩu, Yêu thích, Nâng cấp gói, Thêm gói, Khóa gói | **Áp dụng thực tế** — mọi hành động thay đổi dữ liệu |
| **Strategy** | [B] | Đăng nhập (Auth), Xem TK (Visibility), Xác thực tin, Nâng cấp gói (Pricing, Benefit, Expiry) | **Áp dụng thực tế** — thay đổi thuật toán mà không sửa code gọi |
| **Chain of Responsibility** | [B] | Đăng ký, Đăng nhập, Chỉnh sửa profile, Đổi mật khẩu, Quên mật khẩu, Xác thực tin | **Áp dụng thực tế** — validate nhiều bước |
| **Observer** | [B] | Đăng ký, Đăng nhập, Chỉnh sửa profile, Đổi mật khẩu, Quên mật khẩu, Xác thực tin, Yêu thích, Nâng cấp, Khóa gói | **Áp dụng thực tế** — qua Laravel Events/Listeners |
| **Factory Method** | [C] | Đăng nhập (AuthProvider), Thêm gói (PackageFactory), Nâng cấp (BenefitFactory, ExpiryFactory) | **Áp dụng thực tế** — tạo đối tượng theo loại |
| **Facade** | [S] | Đăng nhập (AuthFacade), Xem TK (AccountFacade) | **Áp dụng thực tế** — gom logic phức tạp |
| **Adapter** | [S] | Đăng nhập Google (✅ đã có), VNPAY (✅ đã có) | **Đã có** — tích hợp bên thứ ba |
| **Specification** | [B] | Nâng cấp gói (✅ đã có: CanRenew, CanUpgrade) | **Đã có** — kiểm tra điều kiện nâng cấp |
| **Memento** | [B] | Chỉnh sửa profile (qua AuditLog.changes) | **Áp dụng thực tế** — snapshot thay đổi |
| **Proxy** | [S] | Xem TK, Yêu thích, Khóa gói, Xác thực tin | **Triển khai qua Laravel Policy/Middleware** |
| **State** | [B] | Listing (DRAFT→PENDING→ACTIVE→LOCKED), Payment (PENDING→SUCCESS→FAILED) | **Đã có implicit** — đề xuất mở rộng khi cần |
| **Decorator** | [S] | Nâng cấp gói — bọc thêm quyền lợi hiển thị VIP/Highlight | **Đề xuất mở rộng** — chưa cần triển khai ngay |
| **Builder** | [C] | Tạo tin đăng (ListingBuilder), Bộ lọc tìm kiếm | **Đề xuất mở rộng** — phù hợp object nhiều trường optional |

---

## Cấu trúc thư mục sau refactor

```
app/
├── Commands/User/
│   ├── ChangeUserPasswordCommand.php    [MODIFY] +CoR +Observer
│   └── UpdateUserProfileCommand.php     [MODIFY] +CoR +Observer +Memento(AuditLog)
├── Events/
│   ├── Auth/
│   │   ├── UserRegistered.php           [existing]
│   │   ├── UserLoggedIn.php             [NEW]
│   │   ├── PasswordChanged.php          [NEW]
│   │   └── PasswordReset.php            [NEW]
│   ├── Listing/
│   │   ├── ListingPackageUpgraded.php   [existing]
│   │   ├── ListingVerificationRequested.php [NEW]
│   │   └── FavoriteToggled.php          [NEW]
│   ├── Package/
│   │   ├── PackageCreated.php           [NEW]
│   │   └── PackageStatusChanged.php     [NEW]
│   └── User/
│       └── ProfileUpdated.php           [NEW]
├── Listeners/
│   ├── Auth/
│   │   ├── SendWelcomeNotification.php  [existing]
│   │   ├── LogSuccessfulLogin.php       [NEW]
│   │   └── SendPasswordChangeAlert.php  [NEW]
│   ├── Listing/
│   │   └── ClearPublicListingCache.php  [existing]
│   ├── Package/
│   │   └── NotifyPackageStatusChange.php [NEW]
│   └── User/
│       └── NotifyProfileChange.php      [NEW]
├── Repositories/
│   ├── FavoriteRepository.php           [NEW]
│   └── Eloquent/
│       └── EloquentFavoriteRepository.php [NEW]
├── Services/
│   ├── Auth/
│   │   ├── AuthFacade.php               [NEW] Facade
│   │   ├── Login/
│   │   │   ├── LoginValidationChain.php [NEW] CoR
│   │   │   └── Handlers/               [NEW]
│   │   ├── Registration/
│   │   │   ├── RegisterUserCommand.php  [NEW] Command
│   │   │   ├── RegistrationValidationChain.php [NEW] CoR
│   │   │   └── Handlers/               [NEW]
│   │   └── ForgotPassword/
│   │       ├── RequestPasswordResetCommand.php [NEW] Command
│   │       └── ResetPasswordCommand.php [NEW] Command
│   ├── Listing/
│   │   ├── Favorite/
│   │   │   ├── FavoriteService.php      [NEW]
│   │   │   └── Impl/FavoriteServiceImpl.php [NEW]
│   │   ├── Verification/
│   │   │   ├── ListingVerificationService.php [NEW]
│   │   │   ├── Impl/ListingVerificationServiceImpl.php [NEW]
│   │   │   ├── VerificationStrategy.php [NEW] Strategy
│   │   │   └── ManualVerificationStrategy.php [NEW]
│   │   └── Upgrade/
│   │       ├── UpgradeListingCommand.php [NEW] Command
│   │       └── CreateUpgradePaymentCommand.php [NEW] Command
│   ├── Packages/
│   │   ├── PackageFactory.php           [NEW] Factory
│   │   ├── CreatePackageCommand.php     [NEW] Command
│   │   └── Commands/
│   │       ├── ActivatePackageCommand.php [NEW] Command
│   │       └── LockPackageCommand.php   [NEW] Command
│   └── User/
│       ├── AccountFacade.php            [NEW] Facade
│       ├── Visibility/
│       │   ├── VisibilityStrategy.php   [NEW] Strategy
│       │   ├── OwnerVisibilityStrategy.php [NEW]
│       │   └── PublicVisibilityStrategy.php [NEW]
│       └── Validation/
│           ├── ProfileValidationChain.php [NEW] CoR
│           ├── PasswordValidationChain.php [NEW] CoR
│           └── Handlers/               [NEW]
└── Providers/
    └── AppServiceProvider.php           [MODIFY] DI wiring
```

---

## Nguyên tắc SOLID đảm bảo

| Nguyên tắc | Cách áp dụng |
|---|---|
| **S** — Single Responsibility | Mỗi Command/Handler chỉ làm 1 việc. `ListingServiceImpl` được tách thành Verification + Favorite + Upgrade |
| **O** — Open/Closed | Thêm strategy mới (vd: FacebookAuthStrategy) không sửa code cũ. Thêm validation handler chỉ cần append vào chain |
| **L** — Liskov Substitution | Tất cả Strategy/Handler implement interface chung, có thể swap thoải mái |
| **I** — Interface Segregation | `FavoriteService` tách riêng khỏi `ListingService`. `ListingVerificationService` tách riêng |
| **D** — Dependency Inversion | Service phụ thuộc interface (Repository, Strategy), không phụ thuộc Eloquent/Redis/VNPAY trực tiếp |

---

## Rủi ro kỹ thuật

| Rủi ro | Ảnh hưởng | Cách giảm rủi ro |
|---|---|---|
| Tách quá nhiều class | Code khó hiểu hơn | Chỉ áp dụng pattern khi có nghiệp vụ rõ ràng |
| Event listener lỗi | Chức năng chính bị ảnh hưởng | Dùng `ShouldQueue` hoặc `afterCommit` cho listener |
| Favorite toggle bị race condition | User bấm nhanh gây duplicate | Thêm unique index `(user_id, listing_id)` và DB transaction |
| Refactor `ListingServiceImpl` quá lớn | Dễ regression | Viết test trước khi tách service, refactor từng method |
| DI container wiring sai | Service inject lỗi | Test từng binding sau khi sửa `AppServiceProvider` |
| Tách Chain of Responsibility quá nhỏ | Overhead không đáng | Chỉ tách khi handler có logic đáng kể (>10 dòng) |

---

## Verification Plan

### Automated Tests
```bash
cd PropifyBackend
composer run test
```
- Chạy full test suite sau mỗi phase
- Viết test mới cho từng chức năng refactor (xem test cases ở mỗi phase)

### Manual Verification
- Kiểm tra API endpoints vẫn hoạt động qua `npm run dev` trên Frontend
- Verify DI bindings bằng cách gọi từng endpoint
- Kiểm tra Events + Listeners hoạt động qua Laravel logs

### Code Quality
```bash
cd PropifyBackend
vendor/bin/pint
```
- Format code sau mỗi phase
- Kiểm tra không có circular dependency
- Review `AppServiceProvider` sau mỗi phase
