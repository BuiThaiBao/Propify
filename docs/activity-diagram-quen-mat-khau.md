# Sơ Đồ Activity - Quên Mật Khẩu

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

title Quy Trình Quên Mật Khẩu

|User|
start
:Nhấn "Quên mật khẩu?";

:Nhập email;

:Nhấn "Gửi mã OTP";

|System|
:Tìm tài khoản;

if (Tài khoản hợp lệ?) then (có)
  :Tạo mã OTP reset (6 số);
  
  :Gửi OTP qua email;
else (không)
  note right
    Không tiết lộ tài khoản
    có tồn tại hay không
    (bảo mật)
  end note
endif

|User|
:Nhận thông báo:
**Mã OTP đã được gửi**;

:Mở email và lấy OTP;

:Nhập email + OTP;

:Nhấn "Xác thực";

|System|
:Kiểm tra OTP;

if (OTP hợp lệ?) then (không)
  |User|
  :Nhận lỗi:
  **OTP không đúng
  hoặc đã hết hạn**;
  stop
endif

|User|
:Nhận thông báo:
**OTP hợp lệ**;

:Nhập mật khẩu mới;

:Nhấn "Đặt lại mật khẩu";

|System|
:Kiểm tra OTP lần nữa
(đảm bảo chưa hết hạn);

if (OTP vẫn hợp lệ?) then (không)
  |User|
  :Nhận lỗi:
  **OTP đã hết hạn**;
  stop
endif

:Cập nhật mật khẩu mới;

:Đăng xuất tất cả thiết bị;

|User|
:Nhận thông báo:
**Đặt lại mật khẩu thành công**;

:Đăng nhập lại với mật khẩu mới;

stop

@enduml
```

## Giải Thích

**Quy trình reset mật khẩu gồm 3 bước:**

1. **User nhập email** → System gửi OTP qua email
2. **User nhập OTP** → System xác thực và cho phép đặt mật khẩu mới
3. **User nhập password mới** → System cập nhật và đăng xuất tất cả thiết bị

**Bảo mật:** System không tiết lộ email có tồn tại hay không. Sau khi reset thành công, tất cả session cũ bị đăng xuất để bảo mật.

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
