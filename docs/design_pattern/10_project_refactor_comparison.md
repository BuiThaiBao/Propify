# Bảng so sánh trước và sau refactor - Toàn bộ dự án Propify

Tài liệu này tổng hợp phần **giải thích, ưu điểm và bảng so sánh trước/sau refactor** cho các Design Pattern được áp dụng trong toàn bộ dự án Propify. Nội dung dựa trên code và các tài liệu tổng hợp trong `docs/design_pattern/tong_hop_design_pattern.md`, `docs/design-patterns-analysis.md` và các file phân tích theo chức năng.

Các pattern được tổng hợp:

```text
Strategy Pattern
Command Pattern
Chain of Responsibility Pattern
State Pattern
Observer Pattern
Adapter Pattern
Factory Pattern / Factory Method Pattern
Facade Pattern
Template Method Pattern
Specification Pattern
Builder Pattern
```

---

## 1. Strategy Pattern

### Khái niệm và cách áp dụng trong code

Strategy Pattern đóng gói nhiều thuật toán hoặc cách xử lý khác nhau thành các class riêng có cùng interface. Client chỉ gọi interface chung, còn thuật toán cụ thể được chọn thông qua factory/resolver hoặc service điều phối.

Trong Propify, Strategy được dùng ở nhiều phân hệ:

```text
AuthStrategy: EmailPasswordAuthStrategy, GoogleOAuthAuthStrategy
ListingSortingStrategy: DefaultPackageScore, Newest, Oldest, PriceAsc, PriceDesc, AreaAsc, AreaDesc
SearchFieldStrategy: TitleSearchStrategy, OwnerSearchStrategy, AddressSearchStrategy
ExpiryCalculationStrategy: FreshPurchaseExpiryStrategy, RenewalExpiryStrategy
PackageBenefitStrategy: DataDrivenBenefitStrategy
DeadlineStrategy: DefaultDeadlineStrategy, UrgentDeadlineStrategy
```

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Linh hoạt thuật toán | Có thể thay đổi cách đăng nhập, sắp xếp, tìm kiếm, tính hạn dùng mà không sửa client. |
| Giảm if/else | Không cần gom nhiều nhánh xử lý trong một service hoặc repository. |
| Dễ mở rộng | Thêm thuật toán mới bằng cách tạo strategy mới. |
| Dễ kiểm thử | Có thể test từng strategy độc lập. |
| Tuân thủ OCP | Mở rộng hành vi mới mà ít sửa logic cũ. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Cách chọn thuật toán | Dùng `if/else`, `switch`, hoặc logic cứng trong service/repository | Dùng interface strategy và concrete strategy riêng |
| Đăng nhập | Logic email/password và Google OAuth dễ trộn chung | `EmailPasswordAuthStrategy`, `GoogleOAuthAuthStrategy` tách riêng |
| Sắp xếp tin | `ORDER BY` dễ bị viết cứng trong repository | Mỗi kiểu sắp xếp là một `ListingSortingStrategy` |
| Tìm kiếm tin | Tìm theo tiêu đề/chủ tin/địa chỉ dễ gom trong một hàm lớn | Mỗi trường tìm kiếm là một `SearchFieldStrategy` |
| Nâng cấp gói | Tính hạn dùng, quyền lợi gói dễ lẫn trong service | Tách thành `ExpiryCalculationStrategy`, `PackageBenefitStrategy` |
| Khi thêm cách xử lý mới | Phải sửa code cũ, dễ ảnh hưởng nhánh khác | Thêm strategy mới và cập nhật factory/resolver |
| Kiểm thử | Khó test riêng từng thuật toán | Dễ test từng strategy độc lập |

---

## 2. Command Pattern

### Khái niệm và cách áp dụng trong code

Command Pattern đóng gói một request/hành động nghiệp vụ thành một object riêng. Thay vì để controller hoặc service xử lý toàn bộ use case, mỗi hành động được đưa vào một command class.

Trong Propify, Command được dùng ở nhiều nghiệp vụ:

```text
Auth: RegisterUserCommand, RequestPasswordResetCommand, ResetPasswordCommand
Listing: CreateListingCommand, UpdateListingCommand, SaveDraftListingCommand, UnlistListingCommand
Listing Verification: SubmitListingVerificationCommand
Moderation: ApproveListingCommand, RejectListingCommand, LockListingCommand
Appointment: ConfirmBookingCommand, RejectBookingCommand, CancelBookingCommand
Package/Upgrade: CreateUpgradePaymentCommand, UpgradeListingCommand
```

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Mỗi use case một class | Đăng ký, tạo tin, nâng cấp, duyệt tin, xác nhận lịch hẹn không bị trộn chung. |
| Controller/service gọn hơn | Lớp điều phối chỉ gọi command thay vì chứa toàn bộ nghiệp vụ. |
| Dễ mở rộng | Thêm hành động mới bằng command mới. |
| Dễ kiểm thử | Có thể test từng command theo từng use case. |
| Tăng tính đọc hiểu | Tên command thể hiện rõ nghiệp vụ đang xử lý. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Vị trí xử lý nghiệp vụ | Controller/service dễ chứa nhiều logic lớn | Mỗi nghiệp vụ được đóng gói trong command |
| Đăng ký tài khoản | Validate, hash password, lưu user, phát event dễ nằm chung controller/service | `RegisterUserCommand` xử lý toàn bộ luồng đăng ký |
| Tạo/cập nhật tin | Service dễ phình to vì xử lý property, listing, media, giấy tờ | `CreateListingCommand`, `UpdateListingCommand` xử lý riêng |
| Đặt lịch hẹn | Xác nhận/từ chối/hủy dễ dùng nhiều nhánh trong service | `ConfirmBookingCommand`, `RejectBookingCommand`, `CancelBookingCommand` |
| Admin duyệt tin | Logic duyệt/từ chối/khóa dễ bị lặp | Các moderation command xử lý riêng |
| Thêm hành động mới | Phải sửa service cũ | Tạo command mới |
| Kiểm thử | Khó cô lập từng hành động | Test từng command độc lập |

---

## 3. Chain of Responsibility Pattern

### Khái niệm và cách áp dụng trong code

Chain of Responsibility tách một chuỗi kiểm tra/xử lý thành nhiều handler. Mỗi handler xử lý một phần việc, sau đó chuyển request cho handler tiếp theo.

Trong Propify, pattern này được dùng cho các luồng validation/kiểm tra tuần tự:

```text
RegistrationValidationChain: kiểm tra email, password, email tồn tại
LoginValidationChain: kiểm tra email tồn tại, user active, mật khẩu đúng
ListingSubmissionValidationPipeline: UserPhoneVerifiedHandler, VerificationDocumentsHandler
Forgot Password: FindResetUserHandler, SendResetOtpHandler, LogResetAttemptHandler
Listing Reports: PreventDuplicateListingReportHandler, EnsureListingCanBeReportedHandler
```

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Mỗi rule một handler | Rule không bị dồn vào một hàm validate lớn. |
| Dễ thêm/bớt rule | Thêm handler mới và nối vào chain. |
| Dừng sớm khi lỗi | Handler có thể throw exception và dừng luồng. |
| Dễ đọc thứ tự xử lý | Nhìn pipeline/chain biết request đi qua những bước nào. |
| Dễ test | Có thể test từng handler riêng. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Cách validate | Nhiều rule nằm trong một hàm lớn | Mỗi rule là một handler |
| Đăng ký | Email/password/email tồn tại dễ trộn chung | Tách thành chain kiểm tra tuần tự |
| Đăng nhập | Kiểm tra user, trạng thái, password dễ nằm trong một method | Tách thành handler theo từng bước |
| Đăng tin | Rule số điện thoại và giấy tờ xác thực dễ nằm trong command | `UserPhoneVerifiedHandler`, `VerificationDocumentsHandler` |
| Quên mật khẩu | Tìm user, gửi OTP, ghi log dễ trộn trong service | Tách thành các forgot password handler |
| Thêm rule mới | Phải sửa hàm validate cũ | Thêm handler mới |
| Bảo trì | Khó theo dõi khi rule tăng | Chain rõ ràng, dễ thay đổi thứ tự |

---

## 4. State Pattern

### Khái niệm và cách áp dụng trong code

State Pattern cho phép một đối tượng thay đổi hành vi theo trạng thái nội bộ. Thay vì dùng nhiều `if/else` theo status, mỗi trạng thái được tách thành một class.

Trong Propify, State được dùng ở:

```text
ListingStatusState: Draft, Pending, Active, Rejected, Locked, Unlisted
BookingState: PendingState, ApprovedState, TerminalState
TransactionState: PendingTransactionState, SuccessTransactionState, SettledTransactionState
UserState: ActiveUserState, BannedUserState
```

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Quản lý trạng thái rõ ràng | Mỗi state tự biết hành vi và transition hợp lệ. |
| Giảm if/else | Không rải điều kiện status ở nhiều nơi. |
| Giảm lỗi chuyển trạng thái | Transition được kiểm soát tập trung. |
| Dễ mở rộng workflow | Thêm state mới bằng class mới. |
| Dễ test | Test từng state độc lập. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Quản lý status | So sánh chuỗi/số trạng thái rải rác | State class đại diện từng trạng thái |
| Tin đăng | DRAFT/PENDING/ACTIVE/LOCKED/UNLISTED dễ set tùy ý | `ListingStatusStateFactory` kiểm soát transition |
| Lịch hẹn | PENDING/APPROVED/CANCELLED dễ dùng if/else | `BookingState` quản lý hành vi theo trạng thái |
| Giao dịch | PENDING/SUCCESS/SETTLED dễ bị xử lý phân tán | `TransactionState` kiểm soát vòng đời giao dịch |
| Thêm trạng thái mới | Phải sửa nhiều điều kiện | Thêm state class và cập nhật factory |
| Nguy cơ sai nghiệp vụ | Cao hơn | Thấp hơn do transition rõ ràng |

---

## 5. Observer Pattern

### Khái niệm và cách áp dụng trong code

Observer Pattern cho phép một event/subject thông báo cho nhiều listener/observer khi có sự kiện xảy ra. Publisher không cần biết observer cụ thể nào đang xử lý.

Trong Propify, Observer được dùng qua Laravel Event/Listener và custom observer:

```text
ListingSaved -> ClearPublicListingCache, LogListingSaved, CreateListingNotification
UserRegistered -> SendWelcomeMailListener
UserLoggedIn -> LogSuccessfulLoginListener
MessageSent -> Broadcast realtime
ListingPackageUpgraded -> CacheInvalidationListener
UserSubject -> NotificationObserver, AuditLogObserver
```

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Giảm coupling | Publisher không biết listener cụ thể. |
| Tách side effect | Gửi mail, log, cache, notification nằm ngoài nghiệp vụ chính. |
| Dễ mở rộng | Thêm listener mới mà ít sửa code cũ. |
| Hỗ trợ async | Listener có thể chuyển sang queue. |
| Tuân thủ OCP | Thêm phản ứng mới sau event mà không sửa publisher. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Xử lý sau nghiệp vụ | Controller/service gọi trực tiếp mail, log, cache | Dispatch event, listener tự xử lý |
| Đăng ký user | Gửi welcome mail có thể nằm trong command/controller | `UserRegistered` phát event, listener gửi mail |
| Tạo/cập nhật tin | Xóa cache, log, notification dễ nằm trong command | `ListingSaved` kích hoạt các listener |
| Chat | Gửi tin nhắn và broadcast dễ gắn chặt | `MessageSent` được broadcast qua listener/event |
| Thêm side effect mới | Phải sửa publisher | Thêm listener mới |
| Coupling | Cao | Thấp |

---

## 6. Adapter Pattern

### Khái niệm và cách áp dụng trong code

Adapter Pattern chuyển đổi interface của một service/library có sẵn thành interface mà hệ thống mong muốn. Client làm việc với interface chung thay vì phụ thuộc trực tiếp vào SDK/provider.

Trong Propify, Adapter được dùng ở:

```text
UploadSignatureAdapter / CloudinaryUploadSignatureAdapter
FileStorageAdapter / R2FileStorageAdapter
PaymentGateway / VnpayGateway
GoogleSocialiteAdapter / SocialUserAdapter
Laravel Broadcasting / Reverb cho chat realtime
```

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Giảm phụ thuộc provider | Code nghiệp vụ không phụ thuộc trực tiếp Cloudinary, R2, VNPAY, Google SDK. |
| Dễ thay thế dịch vụ ngoài | Có thể đổi provider bằng adapter mới. |
| Interface ổn định | Client gọi interface chung. |
| Dễ mock khi test | Mock adapter thay vì mock SDK phức tạp. |
| Tuân thủ DIP | Phụ thuộc abstraction thay vì concrete provider. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Upload Cloudinary | Controller/service phụ thuộc trực tiếp Cloudinary | Dùng `UploadSignatureAdapter` |
| Lưu trữ R2 | Code dễ phụ thuộc S3/R2 client cụ thể | Dùng `FileStorageAdapter` |
| Thanh toán VNPAY | Logic nghiệp vụ dễ gắn với `VnpayService` | Dùng `PaymentGateway` và `VnpayGateway` |
| Google login | Domain dễ phụ thuộc object Socialite | `GoogleSocialiteAdapter` chuẩn hóa dữ liệu user |
| Chat realtime | Domain event dễ phụ thuộc cơ chế broadcast cụ thể | Reverb/Broadcast đóng vai trò adapter realtime |
| Đổi provider | Phải sửa nhiều nơi | Tạo adapter mới |

---

## 7. Factory Pattern / Factory Method Pattern

### Khái niệm và cách áp dụng trong code

Factory Pattern / Factory Method Pattern gom logic khởi tạo object vào một factory. Client không cần biết concrete class nào được tạo, chỉ yêu cầu factory trả về object phù hợp theo tham số đầu vào. Trong project này, nhiều chỗ dùng theo hướng factory/resolver thực tế của Laravel: nhận tham số nghiệp vụ rồi trả về Strategy, State hoặc Gateway tương ứng.

Trong Propify, Factory được dùng ở:

```text
AuthStrategyResolver
ListingSortingStrategyFactory
SearchFieldStrategyFactory
ExpiryCalculationStrategyFactory
PackageBenefitStrategyFactory
PaymentProviderFactory
ListingStatusStateFactory
AuthCookieFactory
```

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Ẩn logic khởi tạo | Client không tự `new` concrete class. |
| Dễ mở rộng loại mới | Thêm concrete class và cập nhật factory. |
| Giảm coupling | Service/controller phụ thuộc interface hoặc factory. |
| Chuẩn hóa lựa chọn object | Các tham số như `sortBy`, `search_field`, `payment_method` được xử lý tập trung. |
| Dễ kiểm soát fallback | Factory quyết định default strategy/gateway/state. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Khởi tạo strategy | Service/repository tự chọn class cụ thể | Factory trả về strategy phù hợp |
| Sắp xếp tin | `sortBy` dễ xử lý bằng if/else ở service | `ListingSortingStrategyFactory` xử lý |
| Tìm kiếm | `search_field` dễ map thủ công | `SearchFieldStrategyFactory` xử lý |
| Thanh toán | Payment method dễ map thủ công | `PaymentProviderFactory` chọn gateway |
| Tính hạn gói | Logic mua mới/gia hạn dễ trộn trong service | `ExpiryCalculationStrategyFactory` chọn strategy |
| Thêm loại mới | Sửa nhiều nơi gọi | Cập nhật factory |

---

## 8. Facade Pattern

### Khái niệm và cách áp dụng trong code

Facade Pattern cung cấp một interface đơn giản cho một hệ thống con phức tạp. Client gọi facade thay vì biết toàn bộ các service/repository/strategy phía sau.

Trong Propify, Facade được dùng ở:

```text
ChatService / ChatServiceImpl
AccountFacade
useListingMediaUpload.js
```

`ChatServiceImpl` che giấu các chi tiết như conversation, participant, message, group member. `AccountFacade` gom dữ liệu tài khoản. `useListingMediaUpload.js` che giấu chi tiết upload media ở form đăng tin.

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Client đơn giản | Client chỉ gọi một interface cấp cao. |
| Che giấu subsystem | Không lộ chi tiết repository/service con. |
| Giảm coupling | UI/controller không phụ thuộc nhiều service nhỏ. |
| Dễ thay đổi nội bộ | Có thể đổi cách xử lý phía sau facade mà ít ảnh hưởng client. |
| Dễ đọc luồng nghiệp vụ | Facade thể hiện một hành động cấp cao. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Chat | Controller/UI phải biết conversation, message, participant | Gọi `ChatService`/`ChatServiceImpl` |
| Account overview | Client phải tự gom nhiều dữ liệu tài khoản | `AccountFacade` gom dữ liệu |
| Upload media đăng tin | `PostForm.vue` biết nhiều chi tiết upload | `useListingMediaUpload.js` che giấu upload |
| Thay đổi subsystem | Dễ ảnh hưởng client | Chủ yếu sửa trong facade |
| Coupling | Cao | Thấp hơn |

---

## 9. Template Method Pattern

### Khái niệm và cách áp dụng trong code

Template Method Pattern định nghĩa khung xử lý cố định trong abstract class, còn các lớp con chỉ override các bước cụ thể. Pattern này phù hợp khi nhiều nghiệp vụ có cùng quy trình nhưng khác một vài bước.

Trong Propify, Template Method được dùng rõ ở:

```text
AbstractListingModerationCommand
ApproveListingCommand
RejectListingCommand
LockListingCommand
AbstractListingSubmissionValidationHandler
```

`AbstractListingModerationCommand` định nghĩa khung xử lý kiểm duyệt: validate, lock/transaction, kiểm tra state, mutate dữ liệu, lưu lịch sử, dispatch event. Các command con chỉ thay đổi `targetStatus()` hoặc `mutate()`.

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Tránh lặp quy trình | Các command duyệt/từ chối/khóa dùng chung khung xử lý. |
| Kiểm soát thứ tự bước | Template method đảm bảo bước nào chạy trước/sau. |
| Lớp con gọn | Lớp con chỉ override phần khác biệt. |
| Dễ bảo trì | Sửa quy trình chung ở abstract class. |
| Tăng nhất quán nghiệp vụ | Duyệt, từ chối, khóa đều qua cùng một pipeline xử lý. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Admin duyệt tin | Approve/reject/lock dễ lặp transaction, state check, log | `AbstractListingModerationCommand` định nghĩa khung chung |
| Thứ tự xử lý | Dễ lệch giữa các command | Template cố định thứ tự |
| Lớp con | Có thể chứa nhiều code lặp | Chỉ khai báo status đích và mutate riêng |
| Ghi lịch sử/event | Dễ thiếu ở một vài nhánh | Khung chung đảm bảo luôn ghi history/dispatch event |
| Bảo trì | Sửa nhiều command | Sửa abstract template khi thay đổi quy trình chung |

---

## 10. Specification Pattern

### Khái niệm và cách áp dụng trong code

Specification Pattern đóng gói các luật kiểm định nghiệp vụ thành các object độc lập, có thể tái sử dụng và kết hợp. Thay vì viết điều kiện trực tiếp trong service, service gọi specification để hỏi nghiệp vụ có hợp lệ không.

Trong Propify, Specification được dùng ở phần nâng cấp tin đăng:

```text
UpgradeEligibilityPolicy
CanUpgradeSpecification
CanRenewSpecification
```

Các specification kiểm tra người dùng có được nâng cấp lên gói cao hơn không, hoặc có được gia hạn gói hiện tại không.

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Tách luật nghiệp vụ | Điều kiện nâng cấp/gia hạn không nằm rải rác trong service. |
| Dễ tái sử dụng | Cùng specification có thể dùng cho API, UI check, admin. |
| Dễ test | Test từng rule độc lập. |
| Dễ mở rộng rule | Thêm specification mới khi có chính sách mới. |
| Code tự mô tả | Tên class như `CanUpgradeSpecification` nói rõ rule đang kiểm tra. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Kiểm tra nâng cấp gói | Điều kiện có thể nằm trực tiếp trong service | `CanUpgradeSpecification` xử lý |
| Kiểm tra gia hạn | Dễ trộn với rule nâng cấp | `CanRenewSpecification` xử lý |
| Khi thêm rule mới | Sửa service hiện có | Tạo specification mới |
| Test rule | Khó tách rule khỏi service | Test từng specification độc lập |
| Bảo trì chính sách | Điều kiện phân tán | Rule nằm trong object riêng |

---

## 11. Builder Pattern

### Khái niệm và cách áp dụng trong code

Builder Pattern tách quá trình dựng object phức tạp ra khỏi nơi sử dụng object. Trong Propify, Builder được dùng rõ ở chức năng đăng tin để dựng payload API và dữ liệu preview.

```text
PropifyFrontend/src/services/listingPayloadBuilder.js
PropifyFrontend/src/services/listingPreviewBuilder.js
PropifyFrontend/src/pages/Listings/PostForm.vue
```

`PostForm.vue` truyền dữ liệu form cho builder. `listingPayloadBuilder.js` tạo payload gửi API, còn `listingPreviewBuilder.js` tạo object preview dùng lại giao diện chi tiết tin đăng.

### Ưu điểm cốt lõi

| Ưu điểm | Giải thích |
|---|---|
| Giảm lặp mapping | Field đăng tin không bị map thủ công ở nhiều nơi. |
| Tách UI khỏi build object | Component form không phải dựng payload dài. |
| Dễ thêm field | Cập nhật builder thay vì sửa nhiều component/service. |
| Giảm lỗi payload | Tên field và default value được chuẩn hóa. |
| Tái sử dụng | Dùng cho create, update, draft và preview. |

### Bảng so sánh trước và sau refactoring

| Tiêu chí | Trước Refactoring | Sau Refactoring |
|---|---|---|
| Dựng payload đăng tin | `PostForm.vue` hoặc service tự map field | `buildListingPayload()` xử lý |
| Dựng preview | Component tự dựng object preview | `buildListingPreview()` xử lý |
| Lặp field | Dễ lặp giữa create/update/draft | Mapping tập trung |
| Thêm field | Sửa nhiều nơi | Sửa builder |
| Độ phức tạp component | Cao | Thấp hơn |

---

## 12. Tổng hợp nhanh

| Pattern | Vấn đề trước refactor | Kết quả sau refactor |
|---|---|---|
| **Strategy** | Thuật toán đăng nhập/sắp xếp/tìm kiếm/tính hạn dùng dễ viết bằng if/else | Mỗi thuật toán là một strategy riêng |
| **Command** | Use case lớn nằm trong controller/service | Mỗi hành động nghiệp vụ là một command |
| **Chain of Responsibility** | Validation nhiều rule nằm trong một hàm lớn | Mỗi rule là một handler trong chain |
| **State** | Status xử lý rải rác bằng if/else | Mỗi trạng thái là một state class |
| **Observer** | Side effect như mail/log/cache nằm trong nghiệp vụ chính | Event dispatch, listener tự xử lý |
| **Adapter** | Code phụ thuộc trực tiếp provider ngoài | Dùng interface adapter/gateway |
| **Factory Pattern / Factory Method** | Client tự chọn concrete class | Factory/resolver tạo object phù hợp |
| **Facade** | Client biết quá nhiều subsystem | Client gọi interface cấp cao |
| **Template Method** | Các quy trình tương tự bị lặp | Abstract class giữ khung xử lý chung |
| **Specification** | Rule nghiệp vụ nằm rải rác trong service | Rule được đóng gói thành specification |
| **Builder** | Object phức tạp dựng thủ công nhiều nơi | Builder dựng object tập trung |
