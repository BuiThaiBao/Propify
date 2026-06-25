# Sơ Đồ Activity - Đăng Ký Tài Khoản

---

## Activity Diagram (User - System Interaction)

```plantuml
@startuml

'=== Cài đặt styling ===
skinparam defaultTextAlignment center
skinparam activityBorderColor #000000
skinparam activityBackgroundColor #F0F0F0
skinparam activityStartColor #90EE90
skinparam activityEndColor #FFB6C6
skinparam activityDiamondBackgroundColor #FFFFAA
skinparam shadowing true

title Quy Trình Đăng Ký Tài Khoản

|User|
start
:Truy cập trang đăng ký;

:Nhập thông tin:
- Họ tên
- Email
- Mật khẩu;

:Nhấn "Đăng ký";

|System|
:Kiểm tra thông tin;

if (Email đã tồn tại?) then (có)
  if (Tài khoản chưa kích hoạt?) then (có)
    :Cho phép đăng ký lại;
  else (đã kích hoạt)
    |User|
    :Nhận lỗi:
    **Email đã được sử dụng**;
    stop
  endif
else (chưa)
  :Tạo tài khoản mới;
endif

:Tạo mã OTP (6 số);

:Gửi OTP qua email;

|User|
:Nhận email chứa OTP;

:Nhập mã OTP;

:Nhấn "Xác thực";

|System|
:Kiểm tra OTP;

if (OTP đúng?) then (không)
  |User|
  :Nhận lỗi:
  **OTP không chính xác**;
  stop
else (đúng)
  :Kích hoạt tài khoản;
  
  :Tạo token đăng nhập;
  
  |User|
  :Nhận thông báo:
  **Đăng ký thành công**;
  
  :Tự động đăng nhập;
  
  stop
endif

@enduml
```

## Giải Thích

**Quy trình đăng ký gồm 2 bước:**

1. **User nhập thông tin** → System tạo tài khoản và gửi OTP qua email
2. **User nhập OTP** → System kích hoạt tài khoản và tự động đăng nhập

**Lưu ý:** Nếu email đã tồn tại nhưng chưa kích hoạt OTP, user có thể đăng ký lại (cập nhật thông tin và gửi OTP mới).

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
