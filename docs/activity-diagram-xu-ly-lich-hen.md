# Sơ Đồ Activity - Xác Nhận/Từ Chối Lịch Hẹn

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

title Quy Trình Xác Nhận/Từ Chối Lịch Hẹn

|User (Chủ nhà)|
start
:Nhận thông báo:
**Có yêu cầu lịch hẹn mới**;

:Truy cập trang quản lý lịch hẹn;

:Xem chi tiết yêu cầu:
- Tên khách
- Số điện thoại
- Thời gian muốn xem
- Ghi chú;

if (Quyết định?) then (Xác nhận)
  :Nhấn "Xác nhận lịch hẹn";
  
  |System|
  :Kiểm tra lịch hẹn;
  
  if (Lịch vẫn PENDING?) then (không)
    |User (Chủ nhà)|
    :Nhận lỗi:
    **Lịch đã được xử lý**;
    stop
  endif
  
  :Cập nhật appointment
  (status = CONFIRMED);
  
  :Gửi email cho khách:
  **Lịch hẹn đã được xác nhận**;
  
  :Gửi thông báo cho khách (app);
  
  |User (Chủ nhà)|
  :Nhận thông báo:
  **Đã xác nhận lịch hẹn**;
  
  stop
  
else (Từ chối)
  :Nhấn "Từ chối";
  
  |System|
  :Hiển thị form lý do;
  
  |User (Chủ nhà)|
  :Nhập lý do từ chối
  (tùy chọn);
  
  :Xác nhận từ chối;
  
  |System|
  :Kiểm tra lịch hẹn;
  
  if (Lịch vẫn PENDING?) then (không)
    |User (Chủ nhà)|
    :Nhận lỗi:
    **Lịch đã được xử lý**;
    stop
  endif
  
  :Cập nhật appointment
  (status = REJECTED);
  
  :Gửi email cho khách:
  **Lịch hẹn bị từ chối**
  + Lý do (nếu có);
  
  :Gửi thông báo cho khách (app);
  
  |User (Chủ nhà)|
  :Nhận thông báo:
  **Đã từ chối lịch hẹn**;
  
  stop
endif

@enduml
```

## Giải Thích

**Quy trình xử lý lịch hẹn (từ phía chủ nhà):**

1. **Chủ nhà nhận thông báo** → Có yêu cầu lịch hẹn mới (status PENDING)
2. **Chủ nhà xem chi tiết** → Xem thông tin người đặt và thời gian
3. **Xác nhận**: Cập nhật status = CONFIRMED, gửi thông báo cho khách
4. **Từ chối**: Cập nhật status = REJECTED, gửi thông báo + lý do cho khách

**Lưu ý:** Mỗi lịch hẹn chỉ có thể xử lý 1 lần. Sau khi xác nhận hoặc từ chối, không thể thay đổi.

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
