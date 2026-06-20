# HƯỚNG DẪN GIẢI THÍCH CHI TIẾT DESIGN PATTERN BẰNG LỜI
## BẢO VỆ ĐỒ ÁN / BÁO CÁO MÔN HỌC — DỰ ÁN PROPIFY

Tài liệu này tổng hợp toàn bộ **nội dung thuyết trình bằng lời** (văn nói) và **cách trả lời câu hỏi** để bạn trình bày trực tiếp với thầy giáo. Mỗi mẫu câu đều được thiết kế theo văn phong học thuật, thực tế và trực diện vào mã nguồn dự án Propify (Laravel 12 + Vue 3).

---

## MỤC LỤC
1. [MỞ ĐẦU: Giới thiệu Kiến trúc Clean Architecture & SOLID](#1-mo-dau-gioi-thieu-kien-truc-clean-architecture--solid)
2. [CÁCH GIẢI THÍCH 9 DESIGN PATTERN ĐÃ ÁP DỤNG (Bằng Lời)](#2-cach-giai-thich-9-design-pattern-da-ap-dung-bang-loi)
   - [2.1. Command Pattern (Đóng gói nghiệp vụ Use Case)](#21-command-pattern-dong-goi-nghiep-vu-use-case)
   - [2.2. Chain of Responsibility (Chuỗi xác thực tuần tự)](#22-chain-of-responsibility-chuoi-xac-thuc-tuan-tu)
   - [2.3. Strategy Pattern (Họ thuật toán linh hoạt)](#23-strategy-pattern-ho-thuat-toan-linh-hoat)
   - [2.4. Factory Method Pattern (Khởi tạo đối tượng động)](#24-factory-method-pattern-khoi-tao-doi-tuong-dong)
   - [2.5. Adapter Pattern (Thích ứng giao diện & Bảo vệ Domain)](#25-adapter-pattern-thich-ung-giao-dien--bao-ve-domain)
   - [2.6. Observer Pattern (Lắng nghe & Decouple tác vụ phụ)](#26-observer-pattern-lang-nghe--decouple-tac-vu-phu)
   - [2.7. State Pattern (Quản lý vòng đời trạng thái)](#27-state-pattern-quan-ly-vong-doi-trang-thai)
   - [2.8. Facade Pattern (Đơn giản hóa giao diện hệ thống con)](#28-facade-pattern-don-gian-hoa-giao-dien-he-thong-con)
   - [2.9. Specification Pattern (Đóng gói quy tắc nghiệp vụ)](#29-specification-pattern-dong-goi-quy-tac-nghiep-vu)
3. [BỘ CÂU HỎI PHẢN BIỆN "HÓC BÚA" CỦA THẦY & CÁCH ĐÁP TRẢ](#3-bo-cau-hoi-phan-bien-hoc-bua-cua-thay--cach-dap-tra)

---

## 1. MỞ ĐẦU: Giới thiệu Kiến trúc Clean Architecture & SOLID
*(Thời gian: 1-2 phút. Hãy nói phần này đầu tiên để định hình tư duy thiết kế chuyên nghiệp của bạn)*

**[Lời nói mẫu]**:
> *"Thưa thầy, trước khi đi vào chi tiết từng Design Pattern, em xin phép giới thiệu nhanh về cấu trúc kiến trúc của hệ thống **Propify**. Để tránh lỗi 'Fat Controller' và 'Spaghetti Code' (code rối như mì ống) thường gặp ở các dự án Laravel truyền thống, tụi em đã áp dụng cấu trúc **Clean Architecture** chia làm 4 tầng riêng biệt:
> 
> 1. **Interface Layer**: Chứa `Controller` và `FormRequest` để validate cấu trúc request đầu vào.
> 2. **Application Layer**: Nơi chứa các lớp `Service` nghiệp vụ và đặc biệt là các `Command` thực thi từng Use Case cụ thể của hệ thống.
> 3. **Domain Layer**: Nơi định nghĩa các `Model` (Entities), `Interface` (Ports) và các sự kiện nghiệp vụ (`Events`).
> 4. **Infrastructure Layer**: Chứa các lớp `Repository` thao tác cơ sở dữ liệu và các `Adapter` bọc dịch vụ bên ngoài như VNPAY, Reverb WebSocket.
> 
> Nhờ cấu trúc này kết hợp với việc áp dụng các **Design Pattern chuẩn GoF**, mã nguồn của tụi em tuân thủ rất chặt chẽ nguyên lý **SOLID**, đặc biệt là **SRP** (Đơn nhiệm) giúp code dễ viết Unit Test và **DIP** (Nghịch đảo phụ thuộc) giúp hệ thống độc lập hoàn toàn với các thư viện hoặc công nghệ bên thứ ba."*

---

## 2. CÁCH GIẢI THÍCH 9 DESIGN PATTERN ĐÃ ÁP DỤNG (Bằng Lời)

### 2.1. Command Pattern (Đóng gói nghiệp vụ Use Case)
*   **Giải thích ngắn gọn**: Biến một yêu cầu/hành động thành một Object độc lập, chứa đầy đủ thông tin để thực thi.
*   **Nghiệp vụ áp dụng**: Đăng ký tài khoản, Quên mật khẩu, Xác thực tin đăng, Nâng cấp gói tin.
*   **Các class đại diện**: `RegisterUserCommand`, `ResetPasswordCommand`, `SubmitListingVerificationCommand`, `UpgradeListingCommand`.

**[Lời nói mẫu]**:
> *"Thưa thầy, em sử dụng **Command Pattern** để đóng gói toàn bộ luồng nghiệp vụ thay vì viết trực tiếp vào Controller hay một class Service khổng lồ. 
> 
> Ví dụ, trong chức năng đăng ký, em tạo ra lớp `RegisterUserCommand`. `AuthController` của em đóng vai trò **Invoker**, chỉ nhận dữ liệu từ request, bọc vào DTO và gọi phương thức `$command->execute($dto)`.
> 
> Lớp `RegisterUserCommand` đóng vai trò **ConcreteCommand**, nó tự điều phối việc gọi chuỗi validate dữ liệu, gọi Repository (`EloquentUserRepository` đóng vai trò **Receiver**) để lưu cơ sở dữ liệu và phát sự kiện.
> 
> **Lợi ích thực tế**: Controller cực kỳ mỏng, đúng vai trò tiếp nhận và phản hồi HTTP. Logic đăng ký được đóng gói riêng biệt, giúp em viết Unit Test cho luồng đăng ký cực kỳ dễ dàng bằng cách mock (giả lập) các repository, mà không cần khởi tạo request HTTP giả."*

---

### 2.2. Chain of Responsibility (Chuỗi xác thực tuần tự)
*   **Giải thích ngắn gọn**: Cho phép truyền các yêu cầu dọc theo một chuỗi các handler xử lý. Mỗi handler tự quyết định xem có xử lý yêu cầu đó hay không và có chuyển tiếp sang handler tiếp theo hay không.
*   **Nghiệp vụ áp dụng**: Validate đăng ký tài khoản, Validate đăng nhập, Quên mật khẩu.
*   **Các class đại diện**: 
    - `RegistrationValidationChain` (điều phối chuỗi đăng ký)
    - `LoginValidationChain` (điều phối chuỗi đăng nhập)
    - Chuỗi quên mật khẩu: `FindResetUserHandler` $\rightarrow$ `SendResetOtpHandler` $\rightarrow$ `LogResetAttemptHandler`.

**[Lời nói mẫu]**:
> *"Thưa thầy, để tránh việc lồng các khối `if-else` phức tạp khi xác thực dữ liệu hoặc thực hiện luồng nghiệp vụ nhiều bước, em đã áp dụng **Chain of Responsibility**.
> 
> Minh chứng rõ nhất là ở nghiệp vụ **Quên mật khẩu**. Tiến trình này gồm 3 bước: Tìm tài khoản $\rightarrow$ Tạo & gửi OTP $\rightarrow$ Ghi log. 
> Em định nghĩa interface `ForgotPasswordHandler` và lớp cơ sở `AbstractForgotPasswordHandler` chứa con trỏ liên kết `nextHandler`. Ba bước trên được em tách thành 3 class handler cụ thể: `FindResetUserHandler`, `SendResetOtpHandler`, và `LogResetAttemptHandler`.
> 
> Khi chạy, `ForgotPasswordChain` sẽ khởi chạy handler đầu tiên. Nếu `FindResetUserHandler` không tìm thấy email trong DB, nó sẽ chủ động throw Exception để dừng chuỗi ngay lập tức, ngăn không cho gửi OTP bừa bãi. Nếu hợp lệ, nó gọi `$this->next($context)` để chuyển tiếp sang bước tiếp theo.
> 
> **Lợi ích thực tế**: Tuân thủ nguyên lý Đóng/Mở (OCP). Nếu sau này em muốn chặn IP spam yêu cầu OTP (Rate Limiting), em chỉ cần viết thêm một class `RateLimitForgotPasswordHandler` và chèn vào đầu chuỗi trong cấu hình Dependency Injection của Laravel (`AppServiceProvider`) mà không cần sửa bất kỳ dòng code nào của các handler cũ."*

---

### 2.3. Strategy Pattern (Họ thuật toán linh hoạt)
*   **Giải thích ngắn gọn**: Định nghĩa một tập hợp các thuật toán, đóng gói từng thuật toán lại và giúp chúng có thể thay thế lẫn nhau linh hoạt tại runtime.
*   **Nghiệp vụ áp dụng**: Đăng nhập (Mật khẩu vs Google OAuth), Xác thực tin đăng (Thủ công), Nâng cấp gói tin (Tính hạn dùng & Áp quyền hiển thị), Sắp xếp hiển thị tin đăng.
*   **Các class đại diện**:
    - `AuthStrategy` (Interface) $\rightarrow$ `EmailPasswordAuthStrategy`, `GoogleOAuthAuthStrategy`.
    - `VerificationStrategy` (Interface) $\rightarrow$ `ManualVerificationStrategy`.
    - `ExpiryCalculationStrategy` (Interface) $\rightarrow$ `FreshPurchaseExpiryStrategy` (mua mới), `RenewalExpiryStrategy` (gia hạn cộng dồn).
    - `ListingSortingStrategy` (Interface) $\rightarrow$ `DefaultPackageScoreSortingStrategy` (sắp xếp theo điểm VIP), `PriceLowToHighSortingStrategy` (giá thấp đến cao).

**[Lời nói mẫu]**:
> *"Thưa thầy, hệ thống Propify có rất nhiều nghiệp vụ đòi hỏi sự thay đổi linh hoạt về mặt thuật toán xử lý. Em đã áp dụng **Strategy Pattern** để giải quyết triệt để vấn đề này.
> 
> Ví dụ tiêu biểu là ở **Thuật toán sắp xếp hiển thị tin đăng**. Người dùng có thể chọn sắp xếp theo giá tăng dần, giá giảm dần hoặc sắp xếp mặc định ưu tiên tin VIP. Em tạo interface `ListingSortingStrategy` có phương thức `apply(Builder $query)`. 
> Mỗi kiểu sắp xếp là một class strategy cụ thể can thiệp trực tiếp vào Eloquent Query Builder của Laravel (như `DefaultPackageScoreSortingStrategy`, `PriceLowToHighSortingStrategy`). 
> Class `EloquentListingRepository` đóng vai trò **Context**, nhận vào chiến lược sắp xếp và gọi phương thức thực thi mà không cần biết thuật toán chi tiết bên trong.
> 
> **Lợi ích thực tế**: Nếu sau này hệ thống cần thêm kiểu sắp xếp mới, ví dụ như: Sắp xếp theo khoảng cách địa lý (GPS) hay Sắp xếp theo mức độ gợi ý của AI, em chỉ việc tạo thêm class strategy mới triển khai `ListingSortingStrategy` và cấu hình mà không làm ảnh hưởng đến mã nguồn của Repository cũ."*

---

### 2.4. Factory Method Pattern (Khởi tạo đối tượng động)
*   **Giải thích ngắn gọn**: Định nghĩa một interface hoặc phương thức để khởi tạo đối tượng, nhưng để các lớp con hoặc logic runtime quyết định lớp nào sẽ được khởi tạo.
*   **Nghiệp vụ áp dụng**: Phân giải phương thức đăng nhập, Phân giải thuật toán tính ngày hết hạn gói tin, Phân giải chiến lược sắp xếp hiển thị.
*   **Các class đại diện**: `AuthStrategyResolver`, `ExpiryCalculationStrategyFactory`, `ListingSortingStrategyFactory`.

**[Lời nói mẫu]**:
> *"Thưa thầy, đi đôi với **Strategy Pattern**, em áp dụng **Factory Method Pattern** để chịu trách nhiệm khởi tạo động các đối tượng chiến lược tại runtime dựa trên yêu cầu từ client.
> 
> Ví dụ, trong nghiệp vụ Đăng nhập, người dùng gửi request lên chứa phương thức xác thực (`AuthMethod`). Lớp `AuthStrategyResolver` hoạt động như một Factory. Nó chứa phương thức `resolve()` nhận diện xem request yêu cầu đăng nhập bằng Email thường hay Google OAuth để tự động sinh ra và trả về đúng đối tượng `EmailPasswordAuthStrategy` hoặc `GoogleOAuthAuthStrategy`.
> 
> **Lợi ích thực tế**: Che giấu hoàn toàn logic khởi tạo đối tượng phức tạp khỏi Controller hay Service chính. Client chỉ cần gọi Factory, đưa tham số đầu vào và nhận lại interface trừu tượng để sử dụng, giảm thiểu sự phụ thuộc trực tiếp vào các class cụ thể."*

---

### 2.5. Adapter Pattern (Thích ứng giao diện & Bảo vệ Domain)
*   **Giải thích ngắn gọn**: Hoạt động như một cầu nối giữa hai giao diện không tương thích. Giúp tích hợp thư viện, SDK bên ngoài vào hệ thống một cách an toàn.
*   **Nghiệp vụ áp dụng**: Gửi OTP (Email/SMS), Xác thực Google OAuth, Thanh toán VNPAY, Chat Realtime qua WebSockets.
*   **Các class đại diện**:
    - `OtpService` (Interface) $\leftarrow$ `MailOtpService` (Adapter bọc SMTP Mailer).
    - `SocialUserAdapter` (Interface) $\leftarrow$ `GoogleSocialiteAdapter` (Adapter bọc Laravel Socialite SDK).
    - `VnpayService` (Adapter bọc API thanh toán VNPAY).
    - `LaravelReverb` (Adapter bọc WebSocket server).

**[Lời nói mẫu]**:
> *"Thưa thầy, trong dự án Propify có rất nhiều tính năng cần tương tác với bên thứ ba như gửi mail OTP, đăng nhập Google, hay thanh toán qua VNPAY. Em đã áp dụng **Adapter Pattern** để tạo ra các lớp đệm bảo vệ hệ thống.
> 
> Ví dụ, khi đăng nhập qua Google, em sử dụng thư viện Socialite của Laravel. Cấu trúc dữ liệu của Google trả về nằm ngoài tầm kiểm soát của hệ thống. Em tạo interface `SocialUserAdapter` làm chuẩn trong Domain của mình, và lớp `GoogleSocialiteAdapter` bọc lấy đối tượng trả về từ SDK Google để dịch các trường dữ liệu như `getEmail()` hay `getName()` về đúng chuẩn hệ thống.
> 
> **Lợi ích thực tế**: Nếu tương lai Google cập nhật API thay đổi cấu trúc trường, hoặc dự án của em quyết định đổi sang nhà cung cấp xác thực khác (như Facebook hay Apple ID), em chỉ việc viết một lớp Adapter mới triển khai `SocialUserAdapter` để dịch dữ liệu của họ, hoàn toàn không cần chạm vào và sửa đổi logic đăng nhập cốt lõi của hệ thống. Đây là minh chứng rõ ràng cho nguyên lý **Nghịch đảo phụ thuộc (DIP)**."*

---

### 2.6. Observer Pattern (Lắng nghe & Decouple tác vụ phụ)
*   **Giải thích ngắn gọn**: Định nghĩa mối quan hệ một-nhiều giữa các đối tượng. Khi một đối tượng thay đổi trạng thái, tất cả các đối tượng phụ thuộc (đăng ký lắng nghe) sẽ tự động nhận thông báo và cập nhật.
*   **Nghiệp vụ áp dụng**: Đăng ký thành công (Gửi mail chào mừng), Đăng nhập thành công (Ghi log bảo mật), Đổi/Quên mật khẩu (Thu hồi token cũ), Nâng cấp tin thành công (Xóa cache), Gửi tin nhắn chat (Phát socket realtime).
*   **Các class đại diện**:
    - Subject (Event): `UserRegistered`, `UserLoggedIn`, `PasswordReset`, `ListingPackageUpgraded`, `MessageSent`.
    - Observer (Listener): `SendWelcomeNotification`, `LogSuccessfulLogin`, `SendPasswordChangeAlert`, `BroadcastMessage` (Reverb).

**[Lời nói mẫu]**:
> *"Thưa thầy, để tách rời các tác vụ phụ trợ (side-effects) ra khỏi tiến trình nghiệp vụ chính, em đã áp dụng **Observer Pattern** dưới dạng hệ thống **Event - Listener** của Laravel.
> 
> Ví dụ, khi người dùng mua gói tin VIP thành công, luồng xử lý chính trong `UpgradeListingCommand` chỉ tập trung cập nhật dữ liệu tài chính và hạn dùng của tin. Sau đó, nó phát đi sự kiện `ListingPackageUpgraded` (đóng vai trò **Subject**). 
> Hệ thống có các Listener (đóng vai trò **Observer**) tự động lắng nghe sự kiện này để làm việc riêng của chúng: Một listener tiến hành xóa bộ nhớ đệm (cache) danh sách tin đăng công cộng để cập nhật thứ tự hiển thị VIP mới, một listener khác gửi mail hóa đơn cho khách hàng.
> 
> **Lợi ích thực tế**: Các lớp nghiệp vụ chính hoàn toàn giải khớp (decouple). Chúng không cần biết có bao nhiêu hành động phụ xảy ra sau đó. Đặc biệt, em cấu hình các Listener này chạy dưới dạng **Queue Job** chạy nền (asynchronous), giúp phản hồi giao dịch trả về cho người dùng ngay lập tức mà không phải chờ đợi các tác vụ gửi mail hay xóa cache tốn thời gian."*

---

### 2.7. State Pattern (Quản lý vòng đời trạng thái)
*   **Giải thích ngắn gọn**: Cho phép một đối tượng thay đổi hành vi của nó khi trạng thái nội tại của nó thay đổi. Đối tượng sẽ giống như thay đổi lớp của nó.
*   **Nghiệp vụ áp dụng**: Quản lý trạng thái Tin đăng (`Listing`), Quản lý trạng thái Lịch sử giao dịch (`Transaction`).
*   **Các class đại diện/Trạng thái quản lý**:
    - Trạng thái tin đăng: `DRAFT` (Nháp) $\rightarrow$ `PENDING` (Chờ duyệt) $\rightarrow$ `ACTIVE` (Hiển thị) hoặc `REJECTED` (Từ chối).
    - Trạng thái giao dịch: `PENDING` $\rightarrow$ `SUCCESS` hoặc `FAILED` hoặc `EXPIRED`.

**[Lời nói mẫu]**:
> *"Thưa thầy, các thực thể chính trong dự án như Tin đăng (`Listing`) hay Giao dịch (`Transaction`) có vòng đời trạng thái rất phức tạp và cần kiểm soát nghiêm ngặt. Em đã áp dụng **State Pattern** để quản lý tiến trình này.
> 
> Ví dụ, đối với thực thể `Transaction` (Lịch sử giao dịch), trạng thái khởi tạo luôn là `PENDING`. Khi nhận callback từ VNPAY, trạng thái sẽ được chuyển sang `SUCCESS` hoặc `FAILED`. 
> Lớp `Transaction` đóng vai trò **Context**, quản lý hàm chuyển đổi trạng thái `transitionTo()`. Logic chuyển đổi này kiểm soát chặt chẽ: Một giao dịch đã ở trạng thái `SUCCESS` (Thành công) thì không bao giờ được phép chuyển đổi ngược lại thành `FAILED` hay quay về `PENDING` để tránh các lỗi logic nghiệp vụ hoặc hacker lợi dụng tấn công giả mạo callback làm thay đổi dòng tiền.
> 
> **Lợi ích thực tế**: Gom toàn bộ logic chuyển đổi và ràng buộc trạng thái vào trong thực thể kiểm soát, loại bỏ hoàn toàn việc viết điều kiện kiểm tra thủ công rải rác ở khắp các file Service hay Controller, đảm bảo tính nhất quán của dữ liệu."*

---

### 2.8. Facade Pattern (Đơn giản hóa giao diện hệ thống con)
*   **Giải thích ngắn gọn**: Cung cấp một giao diện thống nhất, đơn giản hóa để giao tiếp với một hệ thống con (subsystem) phức tạp gồm nhiều lớp bên trong.
*   **Nghiệp vụ áp dụng**: Tin đăng yêu thích, Phân hệ Chat.
*   **Các class đại diện**:
    - `FavoriteService` / `FavoriteServiceImpl` (Facade kết nối giữa `UserRepository` và `ListingRepository`).
    - `ChatService` / `ChatServiceImpl` (Facade gom các nghiệp vụ tạo Conversation, gửi Message, check block member).

**[Lời nói mẫu]**:
> *"Thưa thầy, trong phân hệ Chat và Tin đăng yêu thích, nghiệp vụ đòi hỏi sự phối hợp của rất nhiều bảng dữ liệu và Repository khác nhau. Để đơn giản hóa cho phía Controller, em đã thiết kế **Facade Pattern**.
> 
> Lấy ví dụ ở chức năng **Tin đăng yêu thích**. Để thêm một tin vào danh sách yêu thích của người dùng, Controller phải kiểm tra user tồn tại thông qua `UserRepository`, kiểm tra tin đăng tồn tại qua `ListingRepository`, rồi cập nhật vào bảng trung gian. 
> Thay vì để Controller gọi trực tiếp cả 2 Repository và tự xử lý logic liên kết, em tạo ra lớp Facade `FavoriteServiceImpl`. Controller chỉ cần gọi đúng một phương thức đơn giản: `FavoriteService->toggle($userId, $listingId)`. Toàn bộ sự phức tạp của hệ thống con bên dưới đã được ẩn đi.
> 
> **Lợi ích thực tế**: Giảm sự phụ thuộc (coupling) giữa tầng Controller và các tầng lưu trữ dữ liệu chi tiết. Controller cực kỳ sạch sẽ và dễ hiểu."*

---

### 2.9. Specification Pattern (Đóng gói quy tắc nghiệp vụ)
*   **Giải thích ngắn gọn**: Đóng gói các quy tắc, điều kiện nghiệp vụ (Business Rules) thành các đối tượng kiểm định độc lập, có thể kết hợp với nhau bằng các phép toán logic (AND, OR, NOT).
*   **Nghiệp vụ áp dụng**: Điều kiện kiểm định tin đăng có được phép nâng cấp VIP hay gia hạn.
*   **Các class đại diện**: 
    - `CanUpgradeSpecification` (Quy tắc kiểm tra tin đủ điều kiện nâng cấp gói VIP cao hơn).
    - `CanRenewSpecification` (Quy tắc kiểm tra tin đủ điều kiện gia hạn gói hiện tại).
    - `UpgradeEligibilityPolicy` (Lớp gom chính sách kiểm tra).

**[Lời nói mẫu]**:
> *"Thưa thầy, đối với nghiệp vụ **Nâng cấp gói tin**, hệ thống cần kiểm tra rất nhiều điều kiện kinh doanh phức tạp: Tin đăng phải đang hoạt động (`ACTIVE`), gói tin đăng ký mới phải có cấp độ VIP cao hơn gói hiện tại thì mới được coi là 'Nâng cấp', còn nếu bằng gói cũ thì chỉ được coi là 'Gia hạn'.
> 
> Để không làm bẩn logic thanh toán chính, em áp dụng **Specification Pattern**. Em tách các luật kiểm định này thành các lớp Spec riêng biệt: `CanUpgradeSpecification` và `CanRenewSpecification`. Các class này chứa duy nhất phương thức kiểm tra `isSatisfiedBy($context)`.
> 
> **Lợi ích thực tế**: Tách biệt hoàn toàn logic kiểm tra luật kinh doanh (Business Rules) khỏi logic thực thi thanh toán tài chính. Khi doanh nghiệp thay đổi chính sách nâng cấp gói tin, em chỉ cần vào sửa đúng file Spec tương ứng mà không sợ làm hỏng code thanh toán VNPAY cốt lõi."*

---

## 3. BỘ CÂU HỎI PHẢN BIỆN "HÓC BÚA" CỦA THẦY & CÁCH ĐÁP TRẢ
*(Các thầy giáo thường hỏi xoáy vào bản chất lý thuyết và so sánh giữa các pattern. Hãy chuẩn bị kỹ các câu trả lời dưới đây)*

### Câu hỏi 1: Tại sao em lại dùng Command Pattern ở chức năng Đăng ký/Nâng cấp mà không viết trực tiếp vào Service thông thường như cách làm Laravel mặc định?
*   **Điểm thầy muốn nghe**: Sự hiểu biết về nguyên lý đơn nhiệm (SRP) và khả năng viết Unit Test độc lập.
*   **Cách trả lời**:
    > *"Thưa thầy, cách viết truyền thống của Laravel là nhét toàn bộ logic vào Controller hoặc một lớp Service khổng lồ. Cách làm này tạo ra các 'God Class' (lớp vạn năng) ôm quá nhiều việc. Khi dự án lớn lên, việc sửa đổi code cực kỳ nguy hiểm vì các thành phần liên kết chặt chẽ với nhau.
    > Khi áp dụng **Command Pattern**, em chia nhỏ từng hành động của hệ thống thành một class duy nhất (Use Case). Class này chỉ làm đúng một việc và có một hàm thực thi `execute()`. Nó giúp em cô lập hoàn toàn logic nghiệp vụ đăng ký. Em có thể dễ dàng viết Unit Test độc lập cho Command đó bằng cách giả lập (mock) các Repository/Service phụ thuộc mà không cần quan tâm đến môi trường HTTP Request của Controller."*

---

### Câu hỏi 2: Sự khác nhau bản chất giữa Strategy Pattern và State Pattern trong dự án của em là gì?
*   **Điểm thầy muốn nghe**: Cả hai đều dùng đa hình thay cho `if-else`/`switch-case`, nhưng ý đồ thiết kế (intent) hoàn toàn khác nhau.
*   **Cách trả lời**:
    > *"Thưa thầy, hai pattern này đều hướng đến việc loại bỏ các câu lệnh rẽ nhánh phức tạp bằng cách ủy thác hành vi cho các đối tượng con. Tuy nhiên, chúng khác nhau ở hai điểm cốt lõi:
    > 
    > 1. **Về ý đồ thiết kế**: 
    >    - **Strategy Pattern** đại diện cho **họ thuật toán có thể thay thế linh hoạt cho nhau**. Việc lựa chọn Strategy nào là do **Client chủ động quyết định** ở thời điểm chạy (ví dụ: người dùng chọn đăng nhập bằng Google hay chọn sắp xếp theo giá). Các Strategy này thường độc lập và không biết gì về nhau.
    >    - **State Pattern** đại diện cho **hành vi thay đổi theo trạng thái nội tại của đối tượng**. Các State con tự kiểm soát luồng và tự động quyết định chuyển dịch sang trạng thái tiếp theo (ví dụ: Tin đăng từ Nháp $\rightarrow$ Chờ duyệt $\rightarrow$ Hoạt động). Client không can thiệp trực tiếp mà đối tượng tự thay đổi hành vi của mình khi thuộc tính trạng thái bên trong thay đổi.
    > 
    > 2. **Sự liên kết**: Các Concrete State thường có liên kết chặt chẽ và biết rõ về các State khác để thực hiện chuyển đổi trạng thái. Trong khi các Concrete Strategy thì hoàn toàn cô lập, không biết đến sự tồn tại của nhau."*

---

### Câu hỏi 3: Adapter Pattern giúp em giải quyết vấn đề gì khi tích hợp API VNPAY hay Laravel Socialite SDK?
*   **Điểm thầy muốn nghe**: Sự hiểu biết về việc cô lập Domain Core khỏi sự phụ thuộc vào các thư viện ngoài không ổn định (Dependency Inversion).
*   **Cách trả lời**:
    > *"Thưa thầy, khi tích hợp các thư viện hoặc API của bên thứ ba như VNPAY hay Google SDK, chúng ta không có quyền kiểm soát cấu trúc code và dữ liệu của họ. Nếu họ cập nhật phiên bản mới làm thay đổi tên các trường dữ liệu hoặc cấu trúc hàm, toàn bộ dự án của ta sẽ bị lỗi nếu ta gọi trực tiếp SDK của họ ở tầng nghiệp vụ.
    > Áp dụng **Adapter Pattern** giúp em tạo ra một interface chuẩn hóa của riêng hệ thống. Lớp Adapter sẽ đứng ra nhận dữ liệu từ SDK ngoài, dịch nó về chuẩn interface của hệ thống rồi mới truyền vào Domain. Nếu VNPAY hay Google thay đổi API, em chỉ việc cập nhật duy nhất lớp Adapter tương ứng. Tầng nghiệp vụ lõi (Domain và Application) hoàn toàn được bảo vệ an toàn và không bị thay đổi một dòng code nào."*

---

### Câu hỏi 4: Em hãy chỉ ra nguyên lý Dependency Inversion (DIP) được thể hiện như thế nào trong mã nguồn dự án?
*   **Điểm thầy muốn nghe**: Code cấp cao phụ thuộc vào Interface (abstraction), không phụ thuộc trực tiếp vào Code triển khai thực tế (concrete class).
*   **Cách trả lời**:
    > *"Thưa thầy, nguyên lý Nghịch đảo phụ thuộc quy định: 'Các module cấp cao không nên phụ thuộc vào các module cấp thấp, cả hai nên phụ thuộc vào sự trừu tượng'.
    > Trong dự án Propify, ở tầng Application (ví dụ như trong `RegisterUserCommand` hay `UpgradeListingCommand`), em hoàn toàn không gọi trực tiếp lớp Database `EloquentUserRepository` hay dịch vụ gửi OTP cụ thể. Thay vào đó, em khai báo phụ thuộc vào interface `UserRepository` và interface `OtpService`.
    > Các lớp triển khai thực tế như `EloquentUserRepository` hay `MailOtpService` được đặt ở tầng Infrastructure. Em sử dụng cơ chế **Dependency Injection** của Laravel Container để cấu hình liên kết (binding) các Interface với các Implementation tương ứng trong file `AppServiceProvider.php`. 
    > Nhờ vậy, nếu sau này em muốn đổi CSDL từ MySQL sang MongoDB, em chỉ cần tạo một `MongoUserRepository` triển khai interface `UserRepository` rồi đổi cấu hình bind trong Service Provider, tầng nghiệp vụ chính của em hoàn toàn không phải sửa một dòng code nào."*

---

### Câu hỏi 5: Tại sao em lại dùng Facade Pattern cho chức năng Chat và Tin đăng yêu thích mà không phải là các chức năng khác?
*   **Điểm thầy muốn nghe**: Hiểu được khi nào hệ thống con đủ phức tạp để cần một Facade đơn giản hóa giao tiếp.
*   **Cách trả lời**:
    > *"Thưa thầy, các chức năng khác thường có luồng nghiệp vụ đơn giản và độc lập. Tuy nhiên, phân hệ **Chat** và **Tin đăng yêu thích** là các hệ thống con có độ phức tạp cao, đòi hỏi sự tương tác đồng thời của nhiều thực thể và Repository khác nhau.
    > Ví dụ, ở phân hệ Chat, để gửi một tin nhắn, hệ thống phải thực hiện: Tìm/Tạo hội thoại (`ConversationRepository`), lưu tin nhắn mới (`MessageRepository`), cập nhật thời gian cập nhật của hội thoại, kiểm tra danh sách block thành viên. 
    > Nếu em để Controller trực tiếp gọi tất cả các Repository này, Controller sẽ rất phức tạp và bị liên kết chặt chẽ (tightly coupled) với cấu trúc chi tiết của phân hệ Chat. Em tạo ra Facade `ChatServiceImpl` cung cấp một interface duy nhất `sendMessage()` để gom toàn bộ các tương tác này. Controller chỉ cần gọi đúng một hàm này, giúp giảm thiểu tối đa sự phụ thuộc và giúp hệ thống dễ dàng bảo trì hơn."*
