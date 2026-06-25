# Sơ Đồ Activity - Xác Thực Tin Đăng (User)

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

title Quy Trình Gửi Yêu Cầu Xác Thực Tin Đăng

|User|
start
:Truy cập tin đăng của mình;

:Nhấn "Yêu cầu xác thực";

|System|
:Kiểm tra điều kiện;

if (Tin đăng loại CHO THUÊ?) then (có)
  |User|
  :Nhận thông báo:
  **Tin cho thuê không cần
  xác thực giấy tờ**;
  stop
endif

if (Đã có yêu cầu đang xử lý?) then (có)
  |User|
  :Nhận thông báo:
  **Đã có yêu cầu xác thực
  đang chờ duyệt**;
  stop
endif

:Hiển thị form upload giấy tờ;

|User|
:Upload giấy tờ:
- CCCD mặt trước
- CCCD mặt sau
- Giấy tờ pháp lý (1-5 file);

:Xác nhận đồng ý
công khai thông tin;

:Nhấn "Gửi yêu cầu";

|System|
:Kiểm tra tính hợp lệ;

if (Đủ giấy tờ bắt buộc?) then (không)
  |User|
  :Nhận lỗi:
  **Vui lòng upload đầy đủ
  CCCD 2 mặt + giấy tờ pháp lý**;
  stop
endif

:Lưu verification documents;

:Cập nhật tin đăng:
- request_verification = true
- is_verified = PENDING_VERIFICATION;

:Gửi thông báo cho Admin;

|User|
:Nhận thông báo:
**Đã gửi yêu cầu xác thực.
Admin sẽ xem xét trong 24-48h.**;

:Chờ Admin duyệt;

|System|
note right
  Admin sẽ xem xét và:
  • Phê duyệt → is_verified = VERIFIED
  • Từ chối → is_verified = REJECTED
end note

stop

@enduml
```

## Giải Thích

**Quy trình gửi yêu cầu xác thực tin đăng:**

1. **User chọn tin đăng** → Nhấn "Yêu cầu xác thực"
2. **System kiểm tra điều kiện** → Chỉ tin BÁN/MUA được xác thực
3. **User upload giấy tờ** → CCCD 2 mặt + Giấy tờ pháp lý
4. **System lưu documents** → Gửi thông báo cho Admin để duyệt

**Lưu ý:** 
- Chỉ tin đăng loại BÁN/MUA mới được xác thực (CHO THUÊ không cần)
- Phải upload đầy đủ CCCD 2 mặt + ít nhất 1 giấy tờ pháp lý

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
