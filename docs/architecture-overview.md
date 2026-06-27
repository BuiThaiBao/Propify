# Sơ đồ Kiến trúc 3-Layer - Propify

## 1. Sơ đồ Kiến trúc (PlantUML)

```plantuml
@startuml
title Sơ đồ kiến trúc 3-Layer

skinparam monochrome true
skinparam componentStyle uml2
skinparam linetype ortho

' Khai báo thành phần
component "Client" as Client
component "Controllers" as Controller
component "DTOs" as DTO
component "Services" as Service
component "Repository Interfaces" as RepoIface
component "Repository Implementations" as RepoImpl
component "Models" as Model
database "MySQL" as DB

' Các dịch vụ phụ trợ ở 2 bên để không đè đường kẻ
database "Redis (Cache)" as Redis
cloud "Cloudinary" as Cloudinary
cloud "Cloudflare R2" as R2

' Luồng chính thẳng đứng từ trên xuống
Client -down-> Controller
Controller -down-> DTO
Controller -down-> Service
Service -down-> RepoIface
RepoImpl -up-> RepoIface : Implements
RepoImpl -down-> Model
RepoImpl -down-> DB

' Kết nối ngoại vi (rẽ nhánh sang 2 bên)
Service -left-> Redis
Service -right-> Cloudinary
Service -right-> R2
@enduml
```

## 2. Mô tả các thành phần

*   **Client**: Giao diện người dùng gửi yêu cầu.
*   **Controllers**: Tiếp nhận, validate và định tuyến request.
*   **DTOs**: Cấu trúc hóa dữ liệu truyền nhận giữa Controller và Service.
*   **Services**: Xử lý logic nghiệp vụ chính của hệ thống.
*   **Repository Interfaces**: Định nghĩa các cổng giao tiếp dữ liệu (Interface).
*   **Repository Implementations**: Nơi hiện thực hóa truy vấn thực tế.
*   **Models**: Các thực thể dữ liệu (Eloquent Entities).
*   **MySQL & Redis**: Lưu trữ dữ liệu chính và cache/hàng đợi.
*   **Cloudinary & Cloudflare R2**: Lưu trữ media tĩnh và tệp đính kèm.
