# KỊCH BẢN THUYẾT TRÌNH BẢO VỆ DESIGN PATTERN — DỰ ÁN PROPIFY

Tài liệu này cung cấp kịch bản thuyết trình bằng lời (hướng dẫn nói từng bước) giúp bạn tự tin giải thích chi tiết cho thầy giáo về các Design Pattern đã áp dụng trong dự án Propify.

---

## PHẦN 1: MỞ ĐẦU — GIỚI THIỆU TỔNG QUAN KIẾN TRÚC (Nói trong 1-2 phút)

**[Nội dung nói mẫu]**:
> *"Thưa thầy, hệ thống Propify được nhóm tụi em xây dựng trên nền tảng **Laravel 12 (PHP)** phối hợp với **Vue 3** ở Frontend. Để đảm bảo hệ thống có khả năng bảo trì cao, dễ viết kiểm thử tự động (Unit Test) và dễ mở rộng khi có tính năng mới, tụi em đã áp dụng cấu trúc **Clean Architecture** phân chia rõ ràng thành 4 tầng độc lập:
> 
> 1. **Interface Layer**: Nơi chứa Controller tiếp nhận Request và chuyển đổi dữ liệu thô thành DTO.
> 2. **Application Layer**: Chứa các lớp Service nghiệp vụ và các Command thực thi các Use Case cụ thể.
> 3. **Domain Layer**: Chứa các Entity (Model) và sự kiện nghiệp vụ lõi (Domain Events).
> 4. **Infrastructure Layer**: Chứa Eloquent Repository thao tác cơ sở dữ liệu và các Adapter tích hợp dịch vụ ngoài như VNPAY hay WebSockets.
> 
> Đặc biệt, để giải quyết các bài toán nghiệp vụ phức tạp, tụi em đã áp dụng các **Design Pattern chuẩn GoF** vào từng module chức năng cụ thể nhằm tuân thủ nghiêm ngặt các nguyên lý **SOLID**, đặc biệt là nguyên lý Đơn nhiệm (SRP), Đóng/Mở (OCP) và Nghịch đảo phụ thuộc (DIP). Sau đây, em xin phép trình bày chi tiết cách áp dụng và lý do lựa chọn các Pattern này theo từng chức năng ạ."*

---

## PHẦN 2: KỊCH BẢN CHI TIẾT TỪNG CHỨC NĂNG (Thuyết trình chính)

---

### 1. Chức năng Đăng ký tài khoản (Áp dụng: Command, Chain of Responsibility, Adapter, Observer)

**[Nội dung nói mẫu]**:
> *"Thưa thầy, ở chức năng Đăng ký tài khoản, ban đầu luồng xử lý rất phức tạp vì phải vừa xác thực dữ liệu, lưu database, sinh OTP gửi mail và thông báo hệ thống. Em đã tách biệt luồng xử lý này như sau:
> 
> - **Đầu tiên là Command Pattern**: Em đóng gói toàn bộ logic đăng ký vào lớp `RegisterUserCommand`. Controller `AuthController` chỉ đóng vai trò **Invoker** kích hoạt câu lệnh thông qua hàm `execute()`. Điều này giải quyết bài toán 'Fat Controller', giúp Controller cực kỳ mỏng và em có thể dễ dàng viết Unit Test độc lập cho nghiệp vụ đăng ký mà không cần giả lập môi trường Request của Controller.
> 
> - **Tiếp theo là Chain of Responsibility**: Trước khi tạo user, em gọi lớp `RegistrationValidationChain` để kiểm tra dữ liệu tuần tự: Check định dạng email → Check độ mạnh mật khẩu → Check trùng email trong CSDL. Bài toán giải quyết ở đây là loại bỏ các khối điều kiện `if-else` lồng nhau phức tạp. Nếu sau này cần thêm luật kiểm tra mới (ví dụ: chặn IP spam), em chỉ cần viết thêm một kiểm tra mới ghép vào chuỗi mà không cần sửa code cũ, tuân thủ nguyên lý **OCP**.
> 
> - **Về gửi OTP (Adapter Pattern)**: Nghiệp vụ đăng ký gọi interface `OtpService`. Lớp cụ thể triển khai là `MailOtpService` hoạt động như một **Adapter** để gọi hệ thống Mail của Laravel. Nó giúp hệ thống độc lập với hạ tầng truyền thông ngoài. Nếu sau này đổi sang gửi OTP qua SMS (như Twilio), em chỉ cần viết adapter mới mà không phải sửa code xử lý đăng ký lõi (tuân thủ **DIP**).
> 
> - **Cuối cùng là Observer Pattern**: Sau khi lưu DB thành công, Command phát đi sự kiện `UserRegistered`. Lớp lắng nghe `SendWelcomeNotification` đóng vai trò **Observer** tự động gửi mail chào mừng. Em giải quyết được việc giải khớp (decouple) hoàn toàn. Command đăng ký không cần biết việc gửi email chào mừng chạy ra sao, thậm chí tác vụ gửi email này được đẩy vào Queue chạy nền để người dùng nhận kết quả phản hồi đăng ký ngay lập tức."*

---

### 2. Chức năng Đăng nhập (Áp dụng: Strategy, Factory Method, Adapter, Chain of Responsibility, Observer)

**[Nội dung nói mẫu]**:
> *"Trong chức năng Đăng nhập, hệ thống của tụi em phải hỗ trợ đồng thời hai phương thức xác thực: đăng nhập bằng tài khoản/mật khẩu truyền thống và đăng nhập qua tài khoản liên kết Google OAuth.
> 
> - **Strategy Pattern**: Em đóng gói 2 thuật toán xác thực khác nhau vào 2 lớp chiến lược cụ thể là `EmailPasswordAuthStrategy` và `GoogleOAuthAuthStrategy` cùng implement interface `AuthStrategy`. Việc này giải quyết bài toán vi phạm nguyên lý Đóng/Mở (OCP). Mỗi khi tích hợp thêm cổng đăng nhập mới (như Facebook, Apple ID), em chỉ cần viết một lớp Strategy mới mà không cần chạm vào code đăng nhập chung.
> 
> - **Factory Method Pattern**: Để quyết định Strategy nào được khởi tạo tại thời điểm chạy (runtime), em áp dụng lớp `AuthStrategyResolver`. Lớp này hoạt động như một Factory, nhận diện phương thức đăng nhập (`AuthMethod`) từ request của người dùng để sinh ra Strategy xử lý tương ứng.
> 
> - **Adapter Pattern**: Đối với đăng nhập Google, dữ liệu trả về từ SDK ngoài (Laravel Socialite) có định dạng rất khác với hệ thống. Em thiết kế lớp `GoogleSocialiteAdapter` triển khai interface `SocialUserAdapter` để chuyển đổi cấu trúc dữ liệu người dùng của Google thành dạng tiêu chuẩn của hệ thống, bảo vệ mã nguồn lõi trước những biến động cập nhật API từ phía Google.
> 
> - **Chain of Responsibility & Observer**: Lớp đăng nhập truyền thống gọi `LoginValidationChain` để xác thực tuần tự (tìm user → check hoạt động → check hash mật khẩu). Khi đăng nhập thành công, sự kiện `UserLoggedIn` được phát ra để hệ thống tự động ghi nhật ký truy cập (Activity Log) ở chế độ chạy nền mà không làm chậm tốc độ phản hồi phiên đăng nhập."*

---

### 3. Chức năng Quên mật khẩu (Áp dụng: Chain of Responsibility, Command, Adapter, Observer)

**[Nội dung nói mẫu]**:
> *"Thưa thầy, quy trình Quên mật khẩu là ví dụ mẫu mực nhất về **Chain of Responsibility** trong đồ án của em.
> 
> - **Quy trình xử lý tuần tự (CoR)**: Tiến trình phục hồi mật khẩu gồm 3 bước logic: Xác minh tài khoản tồn tại → Tạo & gửi mã OTP → Ghi log nỗ lực phục hồi. Em tạo ra interface `ForgotPasswordHandler` và lớp cơ sở trừu tượng `AbstractForgotPasswordHandler`. Ba bước xử lý trên được tách thành 3 concrete handler nối với nhau dạng Linked List (`FindResetUserHandler` → `SendResetOtpHandler` → `LogResetAttemptHandler`).
> 
>   *Vấn đề giải quyết*: Nếu viết chung vào một hàm Service thông thường, code sẽ rất dài và vi phạm SRP. Với CoR, mỗi class chỉ chịu trách nhiệm đúng 1 việc. Nếu `FindResetUserHandler` không tìm thấy user, nó sẽ lập tức ngắt chuỗi và trả lỗi về, ngăn chặn việc sinh OTP vô nghĩa. Thầy có thể thấy luồng thiết lập chuỗi này được config trực tiếp trong Service Provider thông qua cơ chế Dependency Injection của Laravel.
> 
> - **Về thực thi đặt lại mật khẩu (Command & Observer)**: Khi người dùng điền mật khẩu mới và mã OTP xác nhận gửi lên, lớp `ResetPasswordCommand` sẽ đóng vai trò thực thi xác thực mã OTP và lưu mật khẩu mới. Đồng thời, sự kiện `PasswordReset` phát ra để hủy bỏ (revoke) toàn bộ token phiên đăng nhập cũ trên các thiết bị khác vì lý do an toàn bảo mật."*

---

### 4. Chức năng Xác thực tin đăng (Áp dụng: Strategy, Command, State)

**[Nội dung nói mẫu]**:
> *"Tin đăng bất động sản cần được kiểm định để tránh thông tin giả.
> 
> - **Strategy Pattern**: Em đóng gói thuật toán kiểm định vào interface `VerificationStrategy`. Hiện tại dự án đang áp dụng kiểm duyệt thủ công bởi Admin qua lớp `ManualVerificationStrategy`. Tuy nhiên, với thiết kế này, trong tương lai khi tích hợp tính năng duyệt tự động bằng AI (kiểm tra sổ đỏ tự động bằng OCR), em chỉ cần viết thêm `AIVerificationStrategy` mà không làm ảnh hưởng đến mã nguồn duyệt tin hiện có.
> 
> - **Command Pattern**: Yêu cầu xác minh của người dùng được đóng gói thông qua lớp `SubmitListingVerificationCommand` xử lý việc tiếp nhận tài liệu pháp lý và gọi chiến lược xác thực tương ứng.
> 
> - **State Pattern**: Lớp thực thể tin đăng `Listing` duy trì một trạng thái nội tại (`status`). Tin đăng có vòng đời trạng thái rõ ràng từ Nháp (`DRAFT`) → Chờ duyệt (`PENDING`) → Đã duyệt (`ACTIVE`) hoặc Bị từ chối (`REJECTED`). Logic chuyển đổi trạng thái được kiểm soát chặt chẽ, ngăn chặn việc tin đăng hiển thị công khai khi chưa vượt qua quy trình kiểm duyệt."*

---

### 5. Chức năng Tin đăng yêu thích (Áp dụng: Facade)

**[Nội dung nói mẫu]**:
> *"Chức năng Tin đăng yêu thích đòi hỏi sự tương tác phức tạp giữa hai thực thể độc lập là Người dùng (`User`) và Tin đăng (`Listing`).
> 
> - **Facade Pattern**: Để đơn giản hóa giao tiếp, em thiết kế interface `FavoriteService` (và lớp triển khai `FavoriteServiceImpl`) đóng vai trò lớp **Facade**.
> 
>   *Bài toán giải quyết*: Thay vì Controller phải tự liên kết và gọi đồng thời cả `UserRepository` để kiểm tra tài khoản, `ListingRepository` để kiểm tra tin đăng tồn tại và cập nhật bảng liên kết trung gian; Controller chỉ cần gọi đúng một phương thức duy nhất là `FavoriteService->toggle()`. Toàn bộ sự phức tạp của hệ thống con (subsystem) cơ sở dữ liệu đã được ẩn đi một cách sạch sẽ."*

---

### 6. Chức năng Nâng cấp gói tin (Áp dụng: Command, Strategy, Factory Method, Specification, Adapter, Observer)

**[Nội dung nói mẫu]**:
> *"Thưa thầy, Nâng cấp gói tin (đăng tin VIP) là **chức năng phức tạp nhất** trong hệ thống và là nơi tích hợp nhiều Pattern nhất để xử lý an toàn luồng giao dịch tài chính.
> 
> - **Command Pattern**: Em đóng gói luồng nghiệp vụ thành hai lệnh: `CreateUpgradePaymentCommand` xử lý tạo giao dịch tài chính chờ duyệt (`PENDING`) cùng liên kết thanh toán VNPAY, và `UpgradeListingCommand` thực hiện cập nhật quyền lợi, trạng thái hiển thị của tin đăng sau khi thanh toán thành công.
> 
> - **Strategy Pattern (Nhân đôi chiến lược)**: Em tách biệt hai logic tính toán phức tạp nhất:
>   1. Tính toán ngày hết hạn (`ExpiryCalculationStrategy`): Nếu là gia hạn cùng gói tin thì cộng dồn ngày (`RenewalExpiryStrategy`), nếu là nâng cấp lên gói cao hơn thì reset tính từ hôm nay (`FreshPurchaseExpiryStrategy`).
>   2. Áp dụng quyền lợi hiển thị (`PackageBenefitStrategy`): Xác định các hệ số ưu tiên hiển thị trên trang chủ tương ứng theo từng gói VIP.
> 
> - **Factory Method Pattern**: Lớp Factory `ExpiryCalculationStrategyFactory` và `PackageBenefitStrategyFactory` chịu trách nhiệm khởi tạo động các chiến lược tính ngày và quyền lợi dựa trên thông số gói VIP đích, giúp mã nguồn nghiệp vụ chính độc lập với cách khởi tạo đối tượng cụ thể.
> 
> - **Specification Pattern**: Trước khi thanh toán, hệ thống phải kiểm tra xem tin đăng có đủ điều kiện nâng cấp không. Em đóng gói luật này thành các Spec: `CanUpgradeSpecification` (gói mới phải cao cấp hơn gói cũ) và `CanRenewSpecification`. Việc này giúp tách logic kiểm định ra khỏi logic xử lý thanh toán tài chính, dễ dàng thay đổi điều kiện kinh doanh của doanh nghiệp.
> 
> - **Adapter & Observer Pattern**: Tích hợp VNPAY qua `VnpayService` (Adapter) và phát sự kiện `ListingPackageUpgraded` để hệ thống tự động xóa bộ nhớ đệm (Cache) danh sách tin công cộng nhằm cập nhật thứ tự tin VIP mới lập tức (Observer)."*

---

### 7. Thuật toán sắp xếp hiển thị (Áp dụng: Strategy, Factory Method)

**[Nội dung nói mẫu]**:
> *"Thứ tự hiển thị tin đăng trên trang tìm kiếm quyết định trải nghiệm người dùng.
> 
> - **Strategy Pattern**: Em đóng gói thuật toán sắp xếp vào các lớp chiến lược cụ thể như: `DefaultPackageScoreSortingStrategy` (sắp xếp mặc định có nhân hệ số VIP và trừ điểm suy giảm theo thời gian đăng), `PriceLowToHighSortingStrategy` (sắp xếp giá tăng dần). Tất cả implement interface `ListingSortingStrategy`.
> 
>   *Vấn đề giải quyết*: Lớp `EloquentListingRepository` (Context) nhận chiến lược sắp xếp và áp dụng trực tiếp lên Eloquent Query Builder của Laravel. Thiết kế này giúp câu lệnh truy vấn SQL không bị ràng buộc cứng bởi các điều kiện lọc. Thêm kiểu sắp xếp hiển thị mới chỉ cần tạo Strategy mới mà không sửa một dòng code nào trong Repository.
> 
> - **Factory Method**: Lớp Factory `ListingSortingStrategyFactory` nhận dạng tham số `sortBy` truyền lên từ URL của người dùng để trả về Strategy tương ứng."*

---

### 8. Chức năng Chat (Áp dụng: Facade, Observer, Adapter)

**[Nội dung nói mẫu]**:
> *"Hệ thống chat realtime đòi hỏi sự phối hợp đồng bộ giữa lưu trữ tin nhắn và đẩy tin tức thời đến người nhận.
> 
> - **Facade Pattern**: Em thiết kế `ChatService` để cung cấp interface duy nhất cho phân hệ chat, che giấu sự liên kết phức tạp giữa các bảng hội thoại (`Conversation`), tin nhắn (`Message`), quản lý block thành viên.
> 
> - **Observer & Adapter Pattern**: Khi tin nhắn được gửi thành công, sự kiện `MessageSent` (Subject) được phát ra. Lớp phát sóng WebSocket của Laravel Reverb hoạt động như một **Adapter** bọc giao thức mạng socket để đẩy trực tiếp tin nhắn realtime đến thiết bị người nhận (Observer). Việc này giúp luồng gửi tin nhắn chính được giải phóng ngay lập tức sau khi lưu DB, còn truyền phát mạng được đẩy vào Queue xử lý chạy nền."*

---

### 9. Lịch sử giao dịch (Áp dụng: Adapter, State)

**[Nội dung nói mẫu]**:
> *"Đây là module tiếp nhận dữ liệu callback tài chính từ VNPAY.
> 
> - **Adapter Pattern**: Lớp `VnpayService` đóng vai trò Adapter chuyển đổi dữ liệu thô và tiến hành giải mã, xác thực chữ ký bảo mật từ VNPAY gửi về để kiểm tra tính hợp pháp của giao dịch trước khi cập nhật lịch sử.
> 
> - **State Pattern**: Đối tượng lịch sử giao dịch `Transaction` quản lý một trạng thái nội tại (`status`). Trạng thái giao dịch có quy trình chuyển dịch nghiêm ngặt: Chờ xử lý (`PENDING`) → Thành công (`SUCCESS`) hoặc Thất bại (`FAILED`). Việc áp dụng State giúp chặn đứng các lỗi nghiệp vụ nghiêm trọng khi callback của VNPAY bị trễ hoặc bị tấn công giả mạo làm thay đổi ngược trạng thái tài chính của giao dịch đã hoàn thành."*

---

## PHẦN 3: BỘ CÂU HỎI PHẢN BIỆN THƯỜNG GẶP CỦA THẦY CÔ & CÁCH TRẢ LỜI

Khi bảo vệ đồ án/môn học Phân tích thiết kế hệ thống, các thầy giáo thường xoay quanh các nguyên lý thiết kế và so sánh. Dưới đây là các câu hỏi kinh điển và cách bạn trả lời để ghi điểm tuyệt đối:

### Câu 1: Tại sao em lại dùng Command Pattern ở chức năng Đăng ký/Nâng cấp mà không viết trực tiếp vào Service thông thường như cách viết Laravel truyền thống?

*   **Cách trả lời**:
    > *"Thưa thầy, cách viết Laravel truyền thống là nhét tất cả code vào Controller hoặc một lớp Service lớn. Việc này vi phạm nguyên lý đơn nhiệm **SRP**.
    > Khi một chức năng có quá nhiều bước xử lý phối hợp (xác thực, database, OTP, gửi email, tích hợp bên thứ ba), viết chung sẽ biến Service đó thành một 'God Class' rất khó bảo trì và không thể viết Unit Test độc lập cho từng phần.
    > Áp dụng **Command Pattern** giúp em đóng gói mỗi hành động nghiệp vụ (Use Case) thành một class duy nhất chỉ có một phương thức thực thi `execute()`. Nó giúp giải khớp hoàn toàn, dễ dàng kiểm thử và dễ mở rộng (như bọc thêm tính năng log, cache qua Decorator) mà không cần chỉnh sửa trực tiếp vào mã nguồn nghiệp vụ chính."*

### Câu 2: Em hãy phân biệt sự khác nhau giữa Strategy Pattern và State Pattern trong dự án của em? (Câu hỏi cực kỳ phổ biến)

*   **Cách trả lời**:
    > *"Thưa thầy, cả hai Pattern này đều giải quyết bài toán loại bỏ các khối điều kiện rẽ nhánh phức tạp (`if-else` / `switch-case`) bằng cách ủy thác hành vi cho các đối tượng con. Tuy nhiên, chúng khác nhau về mục đích thiết kế:
    > - **Strategy Pattern** đại diện cho **Họ thuật toán có thể thay thế lẫn nhau linh hoạt**. Client là người chủ động quyết định lựa chọn chiến lược nào (ví dụ: chọn phương thức đăng nhập bằng Google hay Mật khẩu, chọn sắp xếp theo giá hay theo điểm gói). Các chiến lược này thường không biết đến sự tồn tại của nhau.
    > - **State Pattern** đại diện cho **Hành vi thay đổi theo trạng thái nội tại của đối tượng**. Các trạng thái (`DRAFT`, `PENDING`, `ACTIVE`) tự kiểm soát và quyết định luồng chuyển dịch sang trạng thái tiếp theo dựa trên hành động được yêu cầu. Đối tượng Client (Context) không tự ý đổi thuật toán mà hành vi của nó tự động thay đổi khi thuộc tính trạng thái bên trong của nó thay đổi."*

### Câu 3: Adapter Pattern giúp em giải quyết vấn đề gì khi tích hợp API VNPAY hay Laravel Socialite SDK?

*   **Cách trả lời**:
    > *"Thưa thầy, khi tích hợp thư viện hoặc dịch vụ bên thứ ba, cấu trúc dữ liệu của họ luôn nằm ngoài tầm kiểm soát của dự án chúng ta. Nếu họ thay đổi API hoặc cập nhật phiên bản mới làm thay đổi tên trường, toàn bộ ứng dụng của ta sẽ bị lỗi nếu ta gọi trực tiếp SDK của họ ở tầng Service nghiệp vụ.
    > Áp dụng **Adapter Pattern** giúp tạo ra một 'lớp đệm' bảo vệ. Nghiệp vụ của em chỉ giao tiếp với một Interface tiêu chuẩn do em tự định nghĩa (Target). Lớp Adapter sẽ bọc lấy SDK của bên thứ ba (Adaptee) và chuyển đổi dữ liệu của họ thành dạng phù hợp với Interface của em. Nếu bên thứ ba thay đổi API, em chỉ cần sửa duy nhất tại lớp Adapter mà không phải sửa bất kỳ dòng code nghiệp vụ nào."*

### Câu 4: Em hiểu thế nào là nguyên lý Nghịch đảo phụ thuộc (Dependency Inversion - DIP) và nó được thể hiện như thế nào trong dự án của em?

*   **Cách trả lời**:
    > *"Thưa thầy, nguyên lý **DIP** quy định: 'Module cấp cao không được phụ thuộc vào module cấp thấp. Cả hai phải phụ thuộc vào trừu tượng (Interface)'.
    > Trong dự án Propify, ở tầng Application (Command/Service), em hoàn toàn không gọi trực tiếp các lớp Eloquent Repository cụ thể hay các SDK gửi mail cụ thể. Thay vào đó, em chỉ phụ thuộc vào các Interface như `UserRepository` hay `OtpService`.
    > Các implementation cụ thể (như `EloquentUserRepository` hay `MailOtpService`) ở tầng Infrastructure sẽ được cấu hình tự động liên kết (binding) thông qua Container của Laravel trong `AppServiceProvider`. Điều này giúp em có thể dễ dàng thay đổi nhà cung cấp CSDL hoặc dịch vụ gửi mail/OTP mà không cần biên dịch lại tầng nghiệp vụ lõi."*
