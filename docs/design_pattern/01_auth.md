# Phân tích Design Pattern Chức năng: Đăng ký tài khoản / Đăng nhập

Tài liệu này phân tích chi tiết các Design Pattern được áp dụng trong phân hệ Xác thực (Authentication) của dự án Propify, bao gồm hai luồng chính là **Đăng ký tài khoản** và **Đăng nhập**.

---

## 1. Command Pattern (Đóng gói nghiệp vụ Đăng ký tài khoản)

### 1.1. Ánh xạ thành phần (Mapping)

| Thành phần chuẩn UML GoF | Lớp cụ thể trong dự án | Ghi chú |
|---|---|---|
| **Invoker** | `App\Http\Controllers\Api\V1\Auth\AuthController` | Nhận request, tạo DTO và gọi thực thi Command. |
| **Command (Interface)** | *(Không dùng do tận dụng DI & Class-based Command)* | Laravel Services/Commands thường viết trực tiếp dạng Concrete class. |
| **ConcreteCommand** | `App\Services\Auth\Registration\RegisterUserCommand` | Chứa logic điều phối và thực thi toàn bộ luồng đăng ký. |
| **Receiver** | `App\Repositories\UserRepository` | Thực hiện lưu trữ tài khoản xuống database. |

### 1.2. Giải thích Trách nhiệm (Responsibility)

- **`AuthController`**: Tiếp nhận HTTP Request từ client, thực hiện lọc dữ liệu đầu vào qua `FormRequest`, đóng gói vào `RegisterUserDto` và gọi phương thức `execute()` của `RegisterUserCommand`.
- **`RegisterUserCommand`**: Đóng gói quy trình tạo tài khoản mới. Có nhiệm vụ gọi Validator để kiểm tra điều kiện nghiệp vụ, tạo mật khẩu hash, gọi repository lưu dữ liệu, sinh mã OTP thông qua `OtpService` và phát đi event `UserRegistered`.
- **`UserRepository` (Eloquent implementation)**: Thực hiện hành động chèn bản ghi user mới vào cơ sở dữ liệu.

### 1.3. Sơ đồ lớp (Class Diagram) bằng PlantUML

```plantuml
@startuml
interface UserRepository {
    +create(array attributes): User
}

class EloquentUserRepository {
    +create(array attributes): User
}

UserRepository <|.. EloquentUserRepository

class AuthController {
    -RegisterUserCommand registerCommand
    +register(RegisterRequest request): JsonResponse
}

class RegisterUserCommand {
    -UserRepository userRepository
    -RegistrationValidationChain validationChain
    -OtpService otpService
    +execute(RegisterUserDto dto): void
}

class RegisterUserDto {
    +string email
    +string password
    +string fullName
    +string phone
}

AuthController --> RegisterUserCommand : "invokes"
RegisterUserCommand --> UserRepository : "uses"
RegisterUserCommand ..> RegisterUserDto : "receives"
@enduml
```

### 1.4. Đánh giá ưu điểm

- **Tách biệt mối quan tâm (SoC)**: Đóng gói toàn bộ logic nghiệp vụ đăng ký vào một Command duy nhất, giúp `AuthController` cực kỳ mỏng nhẹ (Skinny Controller), chỉ lo việc giao tiếp HTTP.
- **Dễ kiểm thử độc lập (Testability)**: Do `RegisterUserCommand` nhận các dependency qua Constructor Injection (như `UserRepository`, `OtpService`), chúng ta có thể Mock toàn bộ các dependencies này để viết unit test cho Command một cách dễ dàng mà không cần truy cập Database hoặc SMTP Server thật.
- **Tái sử dụng cao**: Command có thể được gọi từ API Controller, Console Command (CLI) hay Jobs hàng đợi mà không cần viết lại logic.

---

## 2. Chain of Responsibility Pattern (Xác thực dữ liệu đầu vào tuần tự)

### 2.1. Ánh xạ thành phần (Mapping)

| Thành phần chuẩn UML GoF | Lớp cụ thể trong dự án | Ghi chú |
|---|---|---|
| **Handler (Interface)** | *(Không định nghĩa class cha do tích hợp Laravel Validation)* | Chuỗi được tổ chức thông qua một lớp Chain duy nhất thực thi các validator con tuần tự. |
| **Handler Chain** | `App\Services\Auth\Registration\RegistrationValidationChain`<br/>`App\Services\Auth\Login\LoginValidationChain` | Lớp đóng vai trò thiết lập và duyệt qua các quy tắc kiểm tra nghiệp vụ đầu vào. |

### 2.2. Giải thích Trách nhiệm (Responsibility)

- **`RegistrationValidationChain`**: Thiết lập một chuỗi các luật kiểm duyệt dữ liệu đăng ký (Ví dụ: Định dạng Email hợp lệ -> Kiểm tra Password mạnh -> Email chưa từng tồn tại). Nếu bất kỳ luật nào thất bại, nó sẽ ném ra `BusinessException` lập tức để ngắt chuỗi xử lý.
- **`LoginValidationChain`**: Đảm nhận nhiệm vụ validate dữ liệu đăng nhập tuần tự (Email tồn tại -> Trạng thái User là ACTIVE -> Đúng mật khẩu hash).

### 2.3. Sơ đồ lớp (Class Diagram) bằng PlantUML

```plantuml
@startuml
class RegistrationValidationChain {
    -UserRepository userRepository
    +validate(RegisterUserDto dto): void
    -checkEmailFormat(string email): void
    -checkPasswordStrength(string password): void
    -checkEmailUnique(string email): void
}

class RegisterUserCommand {
    -RegistrationValidationChain validationChain
    +execute(RegisterUserDto dto): void
}

RegisterUserCommand --> RegistrationValidationChain : "delegates validation"
@enduml
```

### 2.4. Đánh giá ưu điểm

- **Dễ dàng bảo trì và bổ sung luật**: Nếu hệ thống cần thêm một bước kiểm tra mới (ví dụ: Blacklist email rác, hay chặn IP spam đăng ký), ta chỉ cần viết thêm một hàm kiểm duyệt và chèn vào chuỗi xử lý của Chain mà không phá vỡ logic cũ.
- **Cắt giảm If-Else lồng nhau**: Loại bỏ hoàn toàn mô hình chống mẫu "Arrow Anti-pattern" (các khối if-else lồng nhau sâu hoắm), làm cho mã nguồn trực quan, dễ đọc từ trên xuống dưới.

---

## 3. Strategy Pattern (Chiến lược đăng nhập linh hoạt tại Runtime)

### 3.1. Ánh xạ thành phần (Mapping)

| Thành phần chuẩn UML GoF | Lớp cụ thể trong dự án | Ghi chú |
|---|---|---|
| **Context** | `App\Services\Auth\AuthStrategyResolver` | Duy trì tham chiếu đến các Strategy và phân giải Strategy phù hợp dựa vào request. |
| **Strategy (Interface)** | `App\Services\Auth\AuthStrategy` | Định nghĩa giao diện chung cho mọi chiến lược đăng nhập của hệ thống. |
| **ConcreteStrategy A** | `App\Services\Auth\Strategies\EmailPasswordAuthStrategy` | Triển khai đăng nhập bằng cặp tài khoản email/mật khẩu truyền thống. |
| **ConcreteStrategy B** | `App\Services\Auth\Strategies\GoogleOAuthAuthStrategy` | Triển khai đăng nhập thông qua bên thứ ba (Google OAuth). |

### 3.2. Giải thích Trách nhiệm (Responsibility)

- **`AuthStrategy`**: Định nghĩa phương thức `authenticate(AuthPayload $payload)` chung để mọi cơ chế đăng nhập bắt buộc phải tuân theo.
- **`EmailPasswordAuthStrategy`**: Chịu trách nhiệm xác thực thông tin đăng nhập truyền thống, gọi `LoginValidationChain` và sinh JWT Token.
- **`GoogleOAuthAuthStrategy`**: Tiếp nhận access token hoặc auth code của Google, kiểm tra thông tin qua Google SDK, lưu hoặc cập nhật thông tin user và cấp JWT Token tương ứng.
- **`AuthStrategyResolver`**: Quản lý danh sách các Strategy, nhận diện loại hình đăng nhập từ Client để cung cấp đối tượng Strategy xử lý tương thích.

### 3.3. Sơ đồ lớp (Class Diagram) bằng PlantUML

```plantuml
@startuml
interface AuthStrategy {
    +method(): AuthMethod
    +authenticate(AuthPayload payload): AuthResultDto
}

class EmailPasswordAuthStrategy {
    -LoginValidationChain loginValidationChain
    +authenticate(AuthPayload payload): AuthResultDto
}

class GoogleOAuthAuthStrategy {
    -SocialUserAdapter socialUserAdapter
    +authenticate(AuthPayload payload): AuthResultDto
}

class AuthStrategyResolver {
    -AuthStrategy[] strategies
    +resolve(AuthMethod method): AuthStrategy
}

AuthStrategy <|.. EmailPasswordAuthStrategy
AuthStrategy <|.. GoogleOAuthAuthStrategy
AuthStrategyResolver --> AuthStrategy : "resolves"
@enduml
```

### 3.4. Đánh giá ưu điểm

- **Tuân thủ nguyên tắc Open/Closed (OCP)**: Khi hệ thống muốn tích hợp thêm phương thức đăng nhập bằng Facebook, Apple ID hay Github, lập trình viên chỉ cần tạo thêm class ConcreteStrategy mới thực thi `AuthStrategy` mà không cần sửa đổi bất kỳ dòng code nào trong `AuthStrategyResolver` hay `AuthController`.
- **Loại bỏ rẽ nhánh phức tạp**: Thay thế câu lệnh switch-case hoặc chuỗi if-else dài dòng trong Controller bằng cơ chế gọi động đa hình.

---

## 4. Factory Method Pattern (Khởi tạo Strategy đăng nhập)

### 4.1. Ánh xạ thành phần (Mapping)

| Thành phần chuẩn UML GoF | Lớp cụ thể trong dự án | Ghi chú |
|---|---|---|
| **Creator (Factory)** | `App\Services\Auth\AuthStrategyResolver` | Vừa đóng vai trò Context cho Strategy, vừa trực tiếp hoạt động như một Factory sinh Strategy. |
| **Product** | `App\Services\Auth\AuthStrategy` | Sản phẩm được khởi tạo động bởi Factory. |

### 4.2. Giải thích Trách nhiệm (Responsibility)

- **`AuthStrategyResolver`**: Nhận vào danh sách các Strategy đã được đăng ký thông qua Container, triển khai hàm `resolve(AuthMethod $method)` để tìm kiếm Strategy có type tương khớp và trả về đối tượng đó cho Controller sử dụng.

### 4.3. Sơ đồ lớp (Class Diagram) bằng PlantUML

```plantuml
@startuml
class AuthStrategyResolver {
    -strategies: AuthStrategy[]
    +resolve(AuthMethod method): AuthStrategy
}

interface AuthStrategy {
    +method(): AuthMethod
}

AuthStrategyResolver ..> AuthStrategy : "creates/resolves"
@enduml
```

### 4.4. Đánh giá ưu điểm

- **Che giấu chi tiết khởi tạo**: Client (Controller) không cần biết cách khởi tạo `GoogleOAuthAuthStrategy` phức tạp như thế nào (cần nạp adapter nào, config ra sao). Nhiệm vụ này hoàn toàn do DI Container và Resolver đảm nhận.

---

## 5. Adapter Pattern (Thích ứng cấu trúc dữ liệu người dùng Google)

### 5.1. Ánh xạ thành phần (Mapping)

| Thành phần chuẩn UML GoF | Lớp cụ thể trong dự án | Ghi chú |
|---|---|---|
| **Target Interface** | `App\Services\Auth\SocialUserAdapter` | Giao diện chuẩn mực mà Domain của hệ thống yêu cầu đối với các tài khoản mạng xã hội. |
| **Adapter** | `App\Services\Auth\Adapters\GoogleSocialiteAdapter` | Lớp bọc bên ngoài lớp của SDK Google để chuyển đổi interface. |
| **Adaptee** | `Laravel\Socialite\Two\User` *(Từ SDK Socialite ngoài)* | Đối tượng người dùng trả về trực tiếp từ SDK bên thứ ba. |

### 5.2. Giải thích Trách nhiệm (Responsibility)

- **`SocialUserAdapter`**: Định nghĩa các phương thức chuẩn như `getEmail()`, `getName()`, `getAvatar()` để hệ thống sử dụng thống nhất.
- **`GoogleSocialiteAdapter`**: Triển khai interface `SocialUserAdapter`, chứa đối tượng người dùng thô của Google Socialite, gọi các hàm tương ứng của đối tượng này và định dạng lại đầu ra cho khớp với chuẩn Domain.

### 5.3. Sơ đồ lớp (Class Diagram) bằng PlantUML

```plantuml
@startuml
interface SocialUserAdapter {
    +getEmail(): string
    +getName(): string
}

class GoogleSocialiteAdapter {
    -SocialiteUser adaptee
    +getEmail(): string
    +getName(): string
}

class SocialiteUser {
    <<External SDK Class>>
    +getEmail(): string
    +getName(): string
}

SocialUserAdapter <|.. GoogleSocialiteAdapter
GoogleSocialiteAdapter --> SocialiteUser : "delegates to"
@enduml
```

### 5.4. Đánh giá ưu điểm

- **Cách ly thư viện bên thứ ba**: Nếu trong tương lai thư viện Socialite cập nhật và đổi tên hàm của đối tượng trả về (ví dụ từ `getEmail()` thành `getMailAddress()`), ta chỉ cần sửa duy nhất tại lớp `GoogleSocialiteAdapter` thay vì đi lục tìm và sửa hàng chục file nghiệp vụ khác.

---

## 6. Observer Pattern (Sự kiện đăng ký & đăng nhập thành công)

### 6.1. Ánh xạ thành phần (Mapping)

| Thành phần chuẩn UML GoF | Lớp cụ thể trong dự án | Ghi chú |
|---|---|---|
| **Subject (Event)** | `App\Events\Auth\UserRegistered`<br/>`App\Events\Auth\UserLoggedIn` | Các Domain Event phát tín hiệu khi có sự thay đổi. |
| **Observer (Listener)** | `App\Listeners\Auth\SendWelcomeMailListener`<br/>`App\Listeners\Auth\LogSuccessfulLoginListener` | Lắng nghe sự kiện để kích hoạt các nghiệp vụ phụ tương ứng. |

### 6.2. Giải thích Trách nhiệm (Responsibility)

- **`UserRegistered`**: Được phát đi từ `RegisterUserCommand` sau khi tài khoản được lưu.
- **`SendWelcomeMailListener`**: Lắng nghe `UserRegistered`, nhận thông tin email người dùng để đưa tác vụ gửi email chào mừng vào hàng đợi (Queue Job).
- **`UserLoggedIn`**: Phát ra khi đăng nhập thành công từ Strategy.
- **`LogSuccessfulLoginListener`**: Ghi log địa chỉ IP, User-Agent và thời gian đăng nhập vào cơ sở dữ liệu phục vụ audit bảo mật.

### 6.3. Sơ đồ lớp (Class Diagram) bằng PlantUML

```plantuml
@startuml
class UserRegistered {
    +int userId
}

class SendWelcomeMailListener {
    +handle(UserRegistered event): void
}

class UserLoggedIn {
    +int userId
}

class LogSuccessfulLoginListener {
    +handle(UserLoggedIn event): void
}

UserRegistered <.. SendWelcomeMailListener : "listens"
UserLoggedIn <.. LogSuccessfulLoginListener : "listens"
@enduml
```

### 6.4. Đánh giá ưu điểm

- **Khử ghép nối (Decoupling) tối đa**: Việc gửi email chào mừng hay ghi log đăng nhập hoàn toàn không làm ảnh hưởng đến thời gian phản hồi của luồng chính (đăng ký/đăng nhập), vì các Listener có thể chạy bất đồng bộ dưới hàng đợi.
