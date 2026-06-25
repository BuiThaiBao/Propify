# Sơ Đồ Activity - Đặt Lịch Hẹn

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

title Quy Trình Đặt Lịch Hẹn Xem BĐS

|User|
start
:Tìm tin đăng quan tâm;

:Nhấn "Đặt lịch hẹn xem";

|System|
:Hiển thị form đặt lịch;

|User|
:Nhập thông tin:
- Họ tên
- Số điện thoại
- Ngày/giờ muốn xem
- Ghi chú (tùy chọn);

:Nhấn "Gửi yêu cầu";

|System|
:Kiểm tra thông tin;

if (Thông tin hợp lệ?) then (không)
  |User|
  :Nhận lỗi validation;
  stop
endif

:Kiểm tra trùng lịch;

if (Đã có lịch chưa xử lý?) then (có)
  |User|
  :Nhận thông báo:
  **Bạn đã có lịch hẹn
  đang chờ xử lý**;
  stop
endif

:Tạo appointment
(status = PENDING);

:Gửi thông báo cho chủ nhà;

:Gửi email xác nhận cho khách;

|User|
:Nhận thông báo:
**Gửi yêu cầu thành công.
Chờ chủ nhà xác nhận.**;

:Chờ phản hồi từ chủ nhà;

|System|
note right
  Chủ nhà sẽ nhận được
  thông báo và có thể:
  • Xác nhận lịch hẹn
  • Từ chối và gửi lý do
end note

stop

@enduml
```

## Giải Thích

**Quy trình đặt lịch hẹn xem BĐS:**

1. **User chọn tin đăng** → Nhấn "Đặt lịch hẹn"
2. **User nhập thông tin** → System tạo appointment với status PENDING
3. **System gửi thông báo** → Cho cả chủ nhà (app) và khách (email)
4. **User chờ xác nhận** → Chủ nhà sẽ xác nhận hoặc từ chối

**Lưu ý:** Mỗi user chỉ được có 1 lịch hẹn PENDING cho cùng 1 tin đăng. Nếu muốn đặt lại, phải hủy lịch cũ trước.

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
