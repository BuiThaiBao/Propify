export const meta = {
  name: 'simplify-diagrams',
  description: 'Simplify all diagram files to concise versions',
  phases: [
    { title: 'Sequence Diagrams' },
    { title: 'Activity Diagrams' },
  ],
}

const docsDir = 'D:/PROJECT/Meyland/docs'

const SEQUENCE = {
  'sequence-diagram-dang-ky-tai-khoan.md': `# Sequence Diagram - Đăng Ký Tài Khoản

\`\`\`plantuml
@startuml
title Đăng Ký Tài Khoản

actor "User" as User
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

User -> FE: Nhập thông tin
FE -> API: POST /auth/register
API -> API: Validate
alt Hợp lệ
  API -> DB: Tạo user (PENDING)
  API -> API: Gửi OTP
  API --> FE: 201 OK
  FE -> API: POST /auth/verify-otp
  alt OTP đúng
    API -> DB: Kích hoạt user
    API --> FE: 200 OK
  else OTP sai
    API --> FE: Lỗi
  end
else Không hợp lệ
  API --> FE: 422
end
@enduml
\`\`\`

**Luồng:** Nhập → Validate → OTP → Kích hoạt.
`,

  'sequence-diagram-dang-nhap.md': `# Sequence Diagram - Đăng Nhập

\`\`\`plantuml
@startuml
title Đăng Nhập

actor "User" as User
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

alt Email + Password
  User -> FE: Nhập email, password
  FE -> API: POST /auth/login
  API -> DB: Tìm user
  alt Đúng
    API -> API: Tạo JWT
    API --> FE: 200 + tokens
  else Sai
    API --> FE: 401
  end
else Google OAuth
  User -> FE: Nhấn "Đăng nhập Google"
  FE -> API: GET /auth/google
  API --> User: Redirect Google
  User -> Google: Xác thực
  Google --> API: Callback
  API -> API: Tạo JWT
  API --> FE: 200 + tokens
end
@enduml
\`\`\`

**Luồng:** 2 phương thức (Email/Google) → JWT tokens.
`,

  'sequence-diagram-quen-mat-khau.md': `# Sequence Diagram - Quên Mật Khẩu

\`\`\`plantuml
@startuml
title Quên Mật Khẩu

actor "User" as User
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

User -> FE: Nhập email
FE -> API: POST /auth/forgot-password
alt Email tồn tại
  API -> API: Gửi OTP
  API --> FE: 200
  User -> FE: OTP + mật khẩu mới
  FE -> API: POST /auth/reset-password
  alt OTP đúng
    API -> DB: Cập nhật password
    API --> FE: 200
  else Sai
    API --> FE: Lỗi
  end
else Không tồn tại
  API --> FE: 404
end
@enduml
\`\`\`

**Luồng:** Email → OTP → Reset password.
`,

  'sequence-diagram-tao-tin-dang.md': `# Sequence Diagram - Tạo Tin Đăng

\`\`\`plantuml
@startuml
title Tạo Tin Đăng

actor "Owner" as Owner
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Owner -> FE: Nhập thông tin + ảnh
Owner -> FE: Bấm Đăng / Lưu nháp
FE -> API: POST /listings
API -> API: Validate
alt Hợp lệ
  API -> DB: Tạo property + listing
  API -> DB: Tạo ảnh, video, giấy tờ
  API --> FE: 201 Created
else Không hợp lệ
  API --> FE: 422
end
@enduml
\`\`\`

**Luồng:** Nhập → Validate → Tạo DB → 201.
`,

  'sequence-diagram-chinh-sua-tin-dang.md': `# Sequence Diagram - Chỉnh Sửa Tin Đăng

\`\`\`plantuml
@startuml
title Chỉnh Sửa Tin Đăng

actor "Owner" as Owner
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Owner -> FE: Mở chỉnh sửa
FE -> API: GET /listings/{id}
API --> FE: Listing
Owner -> FE: Sửa + upload ảnh mới
FE -> API: PUT /listings/{id}
alt Hợp lệ
  API -> DB: Cập nhật listing
  API --> FE: 200
else Lỗi
  API --> FE: 422
end
@enduml
\`\`\`

**Luồng:** GET → Sửa → PUT → Cập nhật DB.
`,

  'sequence-diagram-dat-lich-hen.md': `# Sequence Diagram - Đặt Lịch Hẹn

\`\`\`plantuml
@startuml
title Đặt Lịch Hẹn

actor "Viewer" as Viewer
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Viewer -> FE: Xem chi tiết tin
Viewer -> FE: Ấn "Đặt lịch"
FE -> API: GET /slots
API --> FE: Slot trống
Viewer -> FE: Chọn slot
FE -> API: POST /appointments
API -> DB: Tạo appointment
API --> FE: 201
@enduml
\`\`\`

**Luồng:** Xem slot → Chọn → Đặt.
`,

  'sequence-diagram-xu-ly-lich-hen.md': `# Sequence Diagram - Xử Lý Lịch Hẹn

\`\`\`plantuml
@startuml
title Xử Lý Lịch Hẹn

actor "Owner" as Owner
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Owner -> FE: Xem lịch hẹn
FE -> API: GET /appointments
API --> FE: Danh sách
Owner -> FE: Xác nhận / Từ chối
FE -> API: PUT /appointments/{id}
API -> DB: Cập nhật
API --> FE: 200
@enduml
\`\`\`

**Luồng:** Xem → Xác nhận/Từ chối.
`,

  'sequence-diagram-thanh-toan.md': `# Sequence Diagram - Thanh Toán

\`\`\`plantuml
@startuml
title Thanh Toán

actor "User" as User
participant "Frontend" as FE
participant "API" as API
participant "Gateway" as GW
database "Database" as DB

User -> FE: Chọn gói
FE -> API: POST /payments/create
API -> DB: Tạo payment
API --> FE: URL gateway
FE -> GW: Redirect
GW --> API: Webhook
alt Thành công
  API -> DB: Cập nhật
  API --> FE: OK
else Thất bại
  API --> FE: Lỗi
end
@enduml
\`\`\`

**Luồng:** Tạo payment → Gateway → Webhook.
`,

  'sequence-diagram-xac-thuc-tin-dang-user.md': `# Sequence Diagram - Xác Thực Tin Đăng (User)

\`\`\`plantuml
@startuml
title Xác Thực (User)

actor "Owner" as Owner
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Owner -> FE: Upload CCCD + giấy tờ
FE -> API: POST /listings/{id}/verify
API -> DB: Lưu documents
API -> DB: Cập nhật trạng thái
API --> FE: 200
@enduml
\`\`\`

**Luồng:** Upload giấy tờ → Gửi → Chờ duyệt.
`,

  'sequence-diagram-nang-cap-goi-tin.md': `# Sequence Diagram - Nâng Cấp Gói Tin

\`\`\`plantuml
@startuml
title Nâng Cấp Gói Tin

actor "Owner" as Owner
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Owner -> FE: Chọn gói
FE -> API: POST /listings/{id}/upgrade
API -> API: Xử lý thanh toán
alt Thành công
  API -> DB: Cập nhật package
  API --> FE: 200
else Thất bại
  API --> FE: Lỗi
end
@enduml
\`\`\`

**Luồng:** Chọn gói → Thanh toán → Cập nhật.
`,

  'sequence-diagram-admin-duyet-tin-dang.md': `# Sequence Diagram - Admin Duyệt Tin

\`\`\`plantuml
@startuml
title Admin Duyệt Tin

actor "Admin" as Admin
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Admin -> FE: Xem danh sách chờ
FE -> API: GET /admin/listings?status=PENDING
API --> FE: Danh sách
Admin -> FE: Duyệt / Từ chối
FE -> API: PUT /admin/listings/{id}/approve
API -> DB: Cập nhật status
API --> FE: 200
@enduml
\`\`\`

**Luồng:** Xem → Duyệt → Cập nhật.
`,

  'sequence-diagram-admin-khoa-tai-khoan.md': `# Sequence Diagram - Admin Khóa User

\`\`\`plantuml
@startuml
title Admin Khóa User

actor "Admin" as Admin
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Admin -> FE: Tìm user
FE -> API: GET /admin/users
API --> FE: Kết quả
Admin -> FE: Khóa user
FE -> API: PUT /admin/users/{id}/block
API -> DB: status = BANNED
API --> FE: 200
@enduml
\`\`\`

**Luồng:** Tìm → Khóa → Cập nhật.
`,

  'sequence-diagram-admin-xac-thuc-tin-dang.md': `# Sequence Diagram - Admin Xác Thực

\`\`\`plantuml
@startuml
title Admin Xác Thực

actor "Admin" as Admin
participant "Frontend" as FE
participant "API" as API
database "Database" as DB

Admin -> FE: Danh sách chờ xác thực
FE -> API: GET /admin/verifications
API --> FE: Danh sách
Admin -> FE: Xem giấy tờ
Admin -> FE: Xác thực / Từ chối
FE -> API: PUT /admin/listings/{id}/verify
API -> DB: Cập nhật
API --> FE: 200
@enduml
\`\`\`

**Luồng:** Xem giấy tờ → Xác thực/Từ chối.
`,
}

const ACTIVITY = {
  'activity-diagram-them-moi-tin-dang.md': `# Activity Diagram - Thêm Mới Tin Đăng

\`\`\`plantuml
@startuml
title Thêm Mới Tin Đăng

start
:Nhập thông tin BĐS;
:Upload ảnh;
:Chọn "Đăng" / "Lưu nháp";
if (Lưu nháp?) then (Có)
  :Lưu DRAFT;
else (Không)
  :Validate;
  if (Hợp lệ?) then (Có)
    :Tạo Property + Listing;
    :Upload media;
    :Thông báo thành công;
  else (Không)
    :Hiển thị lỗi;
  endif
endif
stop
@enduml
\`\`\`

**Luồng:** Nhập → Upload → Validate → Lưu.
`,

  'activity-diagram-dang-ky-tai-khoan.md': `# Activity Diagram - Đăng Ký

\`\`\`plantuml
@startuml
title Đăng Ký

start
:Nhập thông tin;
:Validate;
if (Hợp lệ?) then (Có)
  :Tạo user (PENDING);
  :Gửi OTP;
  :Nhập OTP;
  if (Đúng?) then (Có)
    :Kích hoạt;
    :Thành công;
  else (Sai)
    :Báo lỗi;
  endif
else (Không)
  :Báo lỗi;
endif
stop
@enduml
\`\`\`

**Luồng:** Nhập → OTP → Kích hoạt.
`,

  'activity-diagram-dang-nhap.md': `# Activity Diagram - Đăng Nhập

\`\`\`plantuml
@startuml
title Đăng Nhập

start
:Chọn phương thức;
if (Email/Password?) then (Có)
  :Nhập email, password;
  if (Đúng?) then (Có)
    :Tạo JWT;
    :OK;
  else (Không)
    :Báo lỗi;
  endif
else (Google)
  :Redirect Google;
  :Callback;
  :Tạo JWT;
  :OK;
endif
stop
@enduml
\`\`\`

**Luồng:** 2 phương thức → JWT.
`,

  'activity-diagram-quen-mat-khau.md': `# Activity Diagram - Quên Mật Khẩu

\`\`\`plantuml
@startuml
title Quên Mật Khẩu

start
:Nhập email;
if (Tồn tại?) then (Có)
  :Gửi OTP;
  :Nhập OTP + mật khẩu mới;
  if (OTP đúng?) then (Có)
    :Cập nhật;
    :OK;
  else (Sai)
    :Báo lỗi;
  endif
else (Không)
  :Báo lỗi;
endif
stop
@enduml
\`\`\`

**Luồng:** Email → OTP → Reset.
`,

  'activity-diagram-chinh-sua-tin-dang.md': `# Activity Diagram - Chỉnh Sửa

\`\`\`plantuml
@startuml
title Chỉnh Sửa Tin Đăng

start
:Mở trang sửa;
:Lấy tin;
:Sửa thông tin;
if (Hợp lệ?) then (Có)
  :Cập nhật DB;
  :OK;
else (Không)
  :Báo lỗi;
endif
stop
@enduml
\`\`\`

**Luồng:** Sửa → Validate → Cập nhật.
`,

  'activity-diagram-nang-cap-goi-tin.md': `# Activity Diagram - Nâng Cấp Gói

\`\`\`plantuml
@startuml
title Nâng Cấp Gói Tin

start
:Chọn gói;
:Thanh toán;
if (Thành công?) then (Có)
  :Cập nhật package;
  :OK;
else (Không)
  :Báo lỗi;
endif
stop
@enduml
\`\`\`

**Luồng:** Chọn → Thanh toán → Cập nhật.
`,

  'activity-diagram-dat-lich-hen.md': `# Activity Diagram - Đặt Lịch Hẹn

\`\`\`plantuml
@startuml
title Đặt Lịch Hẹn

start
:Xem tin;
:Chọn slot;
:Nhập thông tin;
if (Đã đăng nhập?) then (Có)
  :Tạo appointment;
  :Thông báo;
else (Chưa)
  :Yêu cầu login;
endif
stop
@enduml
\`\`\`

**Luồng:** Chọn slot → Đặt → Thông báo.
`,

  'activity-diagram-xu-ly-lich-hen.md': `# Activity Diagram - Xử Lý Lịch

\`\`\`plantuml
@startuml
title Xử Lý Lịch Hẹn

start
:Xem danh sách;
:Chọn lịch;
if (Xác nhận?) then (Có)
  :APPROVED;
else (Từ chối)
  :CANCELLED;
endif
:Thông báo;
stop
@enduml
\`\`\`

**Luồng:** Xác nhận/Từ chối → Thông báo.
`,

  'activity-diagram-thanh-toan.md': `# Activity Diagram - Thanh Toán

\`\`\`plantuml
@startuml
title Thanh Toán

start
:Chọn gói;
:Tạo payment;
:Redirect gateway;
if (Thành công?) then (Có)
  :Kích hoạt gói;
else (Không)
  :Báo lỗi;
endif
stop
@enduml
\`\`\`

**Luồng:** Gateway → Webhook → Kích hoạt.
`,

  'activity-diagram-xac-thuc-tin-dang-user.md': `# Activity Diagram - Xác Thực (User)

\`\`\`plantuml
@startuml
title Xác Thực (User)

start
:Vào quản lý tin;
:Chọn "Xác thực";
:Upload CCCD + giấy tờ;
:Gửi;
if (Hợp lệ?) then (Có)
  :Chờ duyệt;
else (Không)
  :Báo lỗi;
endif
stop
@enduml
\`\`\`

**Luồng:** Upload → Gửi → Chờ duyệt.
`,

  'activity-diagram-admin-duyet-tin-dang.md': `# Activity Diagram - Duyệt Tin

\`\`\`plantuml
@startuml
title Duyệt Tin

start
:Danh sách chờ;
:Chọn tin;
if (Duyệt?) then (Có)
  :APPROVED;
else (Không)
  :REJECTED;
endif
:Thông báo user;
stop
@enduml
\`\`\`

**Luồng:** Duyệt/Từ chối → Cập nhật.
`,

  'activity-diagram-admin-khoa-tai-khoan.md': `# Activity Diagram - Khóa User

\`\`\`plantuml
@startuml
title Khóa User

start
:Tìm user;
:Chọn "Khóa";
:Nhập lý do;
:Xác nhận;
:status = BANNED;
:Thông báo;
stop
@enduml
\`\`\`

**Luồng:** Tìm → Khóa → Thông báo.
`,

  'activity-diagram-admin-xac-thuc-tin-dang.md': `# Activity Diagram - Xác Thực (Admin)

\`\`\`plantuml
@startuml
title Xác Thực (Admin)

start
:Danh sách chờ;
:Chọn tin;
:Xem giấy tờ;
if (Hợp lệ?) then (Có)
  :VERIFIED;
else (Không)
  :REJECTED;
endif
:Thông báo;
stop
@enduml
\`\`\`

**Luồng:** Xem → Xác thực/Từ chối.
`,
}

phase('Sequence Diagrams')
for (const [file, content] of Object.entries(SEQUENCE)) {
  writeFileSync(`${docsDir}/${file}`, content, 'utf-8')
  log(`✅ ${file}`)
}

phase('Activity Diagrams')
for (const [file, content] of Object.entries(ACTIVITY)) {
  writeFileSync(`${docsDir}/${file}`, content, 'utf-8')
  log(`✅ ${file}`)
}

return { updated: [...Object.keys(SEQUENCE), ...Object.keys(ACTIVITY)] }
