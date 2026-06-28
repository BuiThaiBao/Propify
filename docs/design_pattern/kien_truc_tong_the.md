# Kiến trúc Tổng thể Hệ thống Propify

Tài liệu này phân tích kiến trúc phần mềm (Software Architecture) tổng thể của dự án Propify, xác định rõ **Mô hình kiến trúc PM** (Pattern-based Architecture Model) được áp dụng, lý do lựa chọn và cách các thành phần tương tác.

---

## 1. Kiến trúc PM đã sử dụng

### 1.1. Ba mô hình kết hợp (Layered + Clean + Hexagonal)

Dự án Propify áp dụng **Kiến trúc kết hợp 3 mô hình**:

| # | Mô hình Kiến trúc | Vai trò trong dự án |
|---|---|---|
| **1** | **3-Layer Architecture** (Kiến trúc 3 lớp) | Tổ chức mã nguồn theo lớp dọc: Presentation → Application → Data. |
| **2** | **Clean Architecture** (Kiến trúc sạch - Robert C. Martin) | Quy tắc phụ thuộc chiều: các layer ngoài phụ thuộc vào layer trong. Layer trong (Domain) không biết gì về layer ngoài. |
| **3** | **Hexagonal Architecture** (Cổng và Bộ điều hợp - Alistair Cockburn) | Sử dụng Port (Interface) và Adapter để giao tiếp với các hệ thống bên ngoài (Database, Cache, Payment Gateway, CDN). |

### 1.2. Lý do lựa chọn kiến trúc này

- **Yêu cầu nghiệp vụ phức tạp**: Hệ thống gồm nhiều chức năng đăng tin, chat, thanh toán, nâng cấp gói, lọc, sắp xếp với các quy tắc nghiệp vụ đan xen; đòi hỏi tách biệt rõ ràng.
- **Dễ kiểm thử (Testability)**: Domain/business logic thuần khiết trong các Service/Command không phụ thuộc Laravel Facade, cho phép Unit Test dễ dàng.
- **Dễ thay đổi hạ tầng**: Database có thể chuyển từ MySQL sang PostgreSQL, Cache từ Redis sang Memcached, Storage từ R2 sang S3 mà không cần sửa logic nghiệp vụ nhờ các Port/Adapter.
- **Bảo trì lâu dài**: Kiến trúc sạch giúp các lập trình viên mới nhanh chóng hiểu cách tổ chức code.

---

## 2. Sơ đồ Kiến trúc Tổng thể (PlantUML)

```plantuml
@startuml
title Kiến trúc Tổng thể Propify — Clean + Hexagonal Architecture

skinparam backgroundColor #FEFEFE
skinparam nodeFontSize 11
skinparam packageStyle rectangle
skinparam linetype ortho

'========== TẦNG PRESENTATION ==========
package "Presentation Layer (Interface Adapters)" as PRES {
  [HTTP Controller] as Controller
  [FormRequest] as Request
  [ApiResponse] as Response
}

'========== TẦNG APPLICATION ==========
package "Application Layer (Use Cases)" as APP {
  [DTOs] as DTO
  [Commands] as Command
  [Services] as Service
  [Events] as Event
}

'========== TẦNG DOMAIN ==========
package "Domain Layer (Entities & Rules)" as DOM {
  [Business Rules] as BR
  [State] as State
  [Specifications] as Spec
  [Strategies] as Strat
  [Event Handlers] as Handler
}

'========== TẦNG INFRASTRUCTURE ==========
package "Infrastructure Layer (Adapters & Repos)" as INFRA {
  [Eloquent Repositories] as Repo
  [PaymentGateway \n(VNPAY Adapter)] as PayGW
  [FileStorage \n(R2 Adapter)] as FStorage
  [UploadSignature \n(Cloudinary Adapter)] as CSignature
  [SocialiteAdapter \n(Google Login)] as Social
  [Broadcast \n(Reverb Adapter)] as Broadcast
}

'========== HỆ THỐNG NGOÀI ==========
package "External Systems" as EXT {
  database "MySQL" as DB
  database "Redis" as Cache
  cloud "Cloudflare R2" as R2
  cloud "Cloudinary" as CDN
  cloud "VNPAY Gateway" as VNPay
  cloud "Google OAuth" as Google
  cloud "Laravel Reverb\n(WebSocket)" as WS
}

'========== QUAN HỆ ==========

' --- Client -> Controller ---
Client --> Controller : "HTTP Request"

' --- Presentation -> Application ---
Controller --> DTO : "chuyển request → DTO"
Controller --> Command : "gọi use case"
Controller --> Service : "hoặc gọi service"
Controller ..> Response : "trả về JSON"

' --- Application -> Domain (gọi business rule) ---
Command --> Service : "điều phối"
Command --> BR : "gọi business rule"
Command --> State : "gọi state pattern"
Command --> Spec : "gọi specification"
Service --> Strat : "gọi strategy"

' --- Application -> Infrastructure (qua Port/Interface) ---
Command --> Repo : "Port (Interface)"
Service --> Repo : "Port (Interface)"
Command --> PayGW : "Port (PaymentGateway)"
Service --> FStorage : "Port (FileStorageAdapter)"
Service --> CSignature : "Port (UploadSignatureAdapter)"
Service --> Social : "Port (SocialUserAdapter)"
Service --> Broadcast : "Port (ShouldBroadcast)"

' --- Infrastructure -> External ---
Repo --> DB : "Eloquent ORM"
Repo --> Cache : "Cache"
PayGW --> VNPay : "HMAC Signature"
FStorage --> R2 : "S3 API"
CSignature --> CDN : "Upload Signature"
Social --> Google : "OAuth 2.0"
Broadcast --> WS : "WebSocket"

' --- Event Handling ---
Command ..> Event : "phát Domain Event"
Event --> Handler : "lan toả"
Handler ---> Repo : "xử lý async"

@enduml
```

---

## 3. Giải thích các Layer

### 3.1. Presentation Layer (Tầng Giao diện)

| Thành phần | Trách nhiệm |
|---|---|
| **HTTP Controller** | Tiếp nhận HTTP Request từ client, trích xuất tham số, gọi use case và trả về JSON Response. **Không chứa logic nghiệp vụ.** |
| **FormRequest** | Validate dữ liệu đầu vào (định dạng, kiểu dữ liệu) trước khi đến Controller. |
| **ApiResponse** | Helper chuẩn hóa định dạng JSON trả về (status, message, data, errors). |

**Ví dụ:** `AuthController::register(RegisterRequest $request)` — chỉ nhận request, tạo dto và gọi `RegisterUserCommand`.

### 3.2. Application Layer (Tầng Ứng dụng)

| Thành phần | Trách nhiệm |
|---|---|
| **DTO** | Giữ dữ liệu giao tiếp giữa Controller và Service/Command. |
| **Commands** | Đóng gói 1 Use Case cụ thể — **Command Pattern**. Điều phối Business Rules, Strategy, Adapter và Repository. |
| **Services** | Logic nghiệp vụ tầm trung — điều phối giữa các Repository (Facade). |
| **Events** | Domain Event phát ra khi có thay đổi quan trọng (UserRegistered, MessageSent, ListingPackageUpgraded). |

### 3.3. Domain Layer (Tầng Nghiệp vụ cốt lõi)

| Thành phần | Trách nhiệm |
|---|---|
| **Business Rules** | Các luật không đổi của hệ thống (VD: Booking 2h rule, CanUpgradeSpecification). |
| **State** | Quản lý vòng đời đối tượng (BookingState, ListingStatusState). |
| **Strategies** | Thuật toán thay đổi theo ngữ cảnh (Sorting, Search, Deadline). |
| **Specifications** | Kiểm định quy tắc nghiệp vụ (CanUpgrade, CanRenew). |

### 3.4. Infrastructure Layer (Tầng Hạ tầng)

| Thành phần | Trách nhiệm |
|---|---|
| **Eloquent Repositories** | Triển khai interface Repository (Port) — là Adapter cho Database. |
| **PaymentGateway (Adapter)** | Bọc VNPAY API thành interface `PaymentGateway` chung. |
| **FileStorage (Adapter)** | Bọc Cloudflare R2 thành interface `FileStorageAdapter`. |
| **UploadSignature (Adapter)** | Bọc Cloudinary thành interface `UploadSignatureAdapter`. |
| **SocialiteAdapter (Adapter)** | Bọc Google OAuth SDK thành interface `SocialUserAdapter`. |
| **Broadcast (Adapter)** | Chuyển đổi Event PHP thành WebSocket Realtime. |

---

## 4. Luồng Dữ Liệu (Data Flow) — Ví dụ

### 4.1. Luồng Đăng ký tài khoản

```plantuml
@startuml
title Data Flow: Đăng ký tài khoản

actor "Client" as C
participant "RegisterRequest" as Req
participant "AuthController" as Ctrl
participant "RegisterUserCommand" as Cmd
participant "RegistrationValidationChain" as Chain
participant "UserRepository" as Repo
participant "OtpService" as Otp
database "MySQL" as DB

C -> Req: POST /api/auth/register
Req -> Ctrl: validated data
Ctrl -> Cmd: execute(RegisterUserDto)
Cmd -> Chain: validate(dto)
Chain --> Cmd: OK
Cmd -> Repo: create(userData)
Repo -> DB: INSERT INTO users
DB --> Repo: User model
Cmd -> Otp: generate(user, context)
Otp --> DB: INSERT INTO otps
Cmd -> C: UserRegistered event
@enduml
```

### 4.2. Luồng Nâng cấp tin đăng (Luồng xuyên suốt các tầng)

```plantuml
@startuml
title Data Flow: Nâng cấp tin đăng (Payment & Activation)

actor "Client" as C
participant "API Controller" as Ctrl
participant "CreateUpgradePaymentCommand" as PayCmd
participant "PaymentProviderFactory" as Factory
participant "VnpayGateway" as GW
actor "VNPAY Gateway" as VNPay
participant "UpgradeListingCommand" as UpgradeCmd
participant "UpgradeEligibilityPolicy" as Policy
participant "ExpiryCalculationStrategyFactory" as ExpF
participant "PackageBenefitStrategyFactory" as BenF
database "MySQL" as DB
database "Redis/Cache" as Cache

C -> Ctrl: POST /api/listings/upgrade
Ctrl -> PayCmd: execute(user, listing, package)
PayCmd --> DB: create Transaction PENDING
PayCmd -> Factory: for('VNPAY')
Factory --> PayCmd: VnpayGateway
PayCmd -> GW: createPaymentUrl(txn)
GW -> VNPay: build signed URL
VNPay --> GW: paymentUrl
GW --> PayCmd: paymentUrl
Ctrl --> C: { paymentUrl }

note right of C: Người dùng chuyển sang VNPAY\nvà thanh toán. Sau đó VNPAY\ncallbacks về hệ thống.

Ctrl -> UpgradeCmd: execute(transaction, listing, package, context)
UpgradeCmd -> Policy: assertEligible(context)
Policy --> UpgradeCmd: OK
UpgradeCmd -> ExpF: make(context)
UpgradeCmd -> BenF: make(package)
UpgradeCmd --> DB: transaction update SUCCESS
UpgradeCmd --> DB: listing update package + expiry
UpgradeCmd -> Cache: invalidate listing cache
@enduml
```

---

## 5. Chiến lược Kiến trúc theo Layer và Design Pattern

| Tầng | Design Pattern liên quan | Mục đích |
|---|---|---|
| **Presentation** | *Không dùng pattern cụ thể* | Giữ Controller mỏng (Skinny Controller). |
| **Application** | **Command**, **Facade**, **Observer**, **DTO** | Đóng gói use case, che giấu độ phức tạp, phát sự kiện domain. |
| **Domain** | **State**, **Strategy**, **Specification** | Đóng gói quy tắc kinh doanh, cho phép thay đổi linh hoạt. |
| **Infrastructure (Ports)** | *Interface (Port)* | Định nghĩa giao diện chuẩn cho mỗi dịch vụ ngoài. |
| **Infrastructure (Adapters)** | **Adapter**, **Repository**, **Factory Method** | Triển khai interface, thích ứng với từng SDK ngoài. |

---

## 6. Ưu điểm và Nhược điểm

### ✅ Ưu điểm

- **Tuân thủ Dependency Inversion Principle (DIP)**: Các lớp Application/Service chỉ phụ thuộc vào Interface (Port), không phụ thuộc vào triển khai cụ thể. Đảo ngược chiều phụ thuộc: Layer ngoài phụ thuộc vào Layer trong.
- **Khả năng kiểm thử cao**: Domain Layers và Application Layers có thể kiểm thử độc lập với Database, Cache, và các External Services nhờ khả năng Mock các Port/Interface.
- **Tái sử dụng Business Rule**: Các Rules (State, Strategy, Specification) là các class độc lập, có thể tái sử dụng giữa các use case, Controller, và cả Frontend (qua API check).
- **Dễ dàng mở rộng (Scalability)**: Thêm cổng thanh toán mới, thêm CDN mới, thêm phương thức login mới chỉ là việc viết Adapter mới. Thêm luật nghiệp vụ mới chỉ là việc thêm lớp State/Strategy/Specification mới.

### ⚠️ Nhược điểm & Giải pháp

| Nhược điểm | Giải pháp |
|---|---|
| Số lượng class nhiều (khoảng ~200+ files cho tất cả Adapter, Command, State, Strategy, Event...) | Tổ chức thư mục theo chức năng (Auth/Appointment/Payment/Listing/...) thay vì theo pattern. |
| Phức tạp cho lập trình viên mới | Tài liệu hóa kiến trúc (chính là tài liệu này) + pair programming giai đoạn đầu. |
| Over-engineering với chức năng đơn giản | Các chức năng đơn giản có thể bỏ qua pattern (VD: CRUD thuần dùng Eloquent trực tiếp). |

---

## 7. Tổng kết

Hệ thống Propify áp dụng mô hình kiến trúc **Clean Architecture + Hexagonal Architecture trên nền tảng 3-Layer**, tận dụng sức mạnh của 10 Design Pattern khác nhau để tổ chức code một cách khoa học, linh hoạt và dễ bảo trì.

```plantuml
@startuml
title Mối quan hệ Kiến trúc PM - Design Pattern - Chức năng

package "Clean + Hexagonal Architecture" as ARCH {
    package "Presentation Layer" as PRE {
        [Controllers + Requests]
    }
    package "Application Layer" as APP {
        [Commands]
        [Services / Facades]
        [DTOs]
        [Events]
    }
    package "Domain Layer" as DOM {
        [Entities + Business Rules]
        [States]
        [Strategies]
        [Specifications]
    }
    package "Infrastructure Layer" as INF {
        [Repositories]
        [Adapters (VNPAY, R2, Cloudinary, Socialite, Reverb)]
    }
}

PRE --> APP : "gọi Use Case"
APP --> DOM : "áp dụng Business Rule"
APP --> INF : "qua Port Interface"
INF --> "MySQL / Redis / VNPAY / R2 / Cloudinary / Reverb" : "gọi SDK ngoài"

@enduml
```

> **Kiến trúc sạch + Hexagonal = Hệ thống linh hoạt, dễ bảo trì và sẵn sàng cho sự thay đổi.**
