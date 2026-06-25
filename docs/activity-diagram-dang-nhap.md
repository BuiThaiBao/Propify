# Sơ Đồ Activity - Đăng Nhập

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

title Quy Trình Đăng Nhập

|User|
start

if (Chọn phương thức?) then (Email + Password)
  :Nhập email và password;
  
  :Nhấn "Đăng nhập";
  
  |System|
  :Tìm tài khoản theo email;
  
  if (Tài khoản tồn tại?) then (không)
    |User|
    :Nhận lỗi:
    **Email hoặc mật khẩu sai**;
    stop
  endif
  
  :Kiểm tra password;
  
  if (Password đúng?) then (không)
    |User|
    :Nhận lỗi:
    **Email hoặc mật khẩu sai**;
    stop
  endif
  
  :Kiểm tra trạng thái tài khoản;
  
  if (Tài khoản ACTIVE?) then (không)
    |User|
    :Nhận lỗi:
    **Tài khoản chưa kích hoạt
    hoặc đã bị khóa**;
    stop
  endif
  
else (Google OAuth)
  :Nhấn "Đăng nhập Google";
  
  |System|
  :Chuyển đến Google;
  
  |User|
  :Chọn tài khoản Google;
  
  :Cho phép quyền truy cập;
  
  |System|
  :Nhận thông tin từ Google;
  
  if (Tài khoản đã tồn tại?) then (không)
    :Tạo tài khoản mới
    (tự động ACTIVE);
  else (có)
    :Liên kết với tài khoản hiện có;
  endif
endif

:Kiểm tra quyền truy cập
(Admin vs User);

if (Quyền hợp lệ?) then (không)
  |User|
  :Nhận lỗi:
  **Không có quyền truy cập**;
  stop
endif

:Tạo token đăng nhập;

|User|
:Nhận thông báo:
**Đăng nhập thành công**;

:Chuyển đến trang chủ;

stop

@enduml
```

## Giải Thích

**Hệ thống hỗ trợ 2 phương thức đăng nhập:**

1. **Email + Password**: System kiểm tra tài khoản, password, và trạng thái → Tạo token nếu hợp lệ
2. **Google OAuth**: User đăng nhập Google → System tạo/liên kết tài khoản → Tạo token

**Lưu ý:** Hệ thống phân quyền Admin/User - mỗi client (web/admin) chỉ cho phép role tương ứng đăng nhập.

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
