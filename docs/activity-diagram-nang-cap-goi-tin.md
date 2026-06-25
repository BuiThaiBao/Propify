# Sơ Đồ Activity - Nâng Cấp Gói Tin

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

title Quy Trình Nâng Cấp Gói Tin

|User|
start
:Truy cập tin đăng của mình;

:Nhấn "Nâng cấp gói";

|System|
:Hiển thị danh sách gói:
- Gói Tiêu chuẩn
- Gói Nổi bật
- Gói VIP;

|User|
:Chọn gói và thời hạn
(7 ngày, 15 ngày, 30 ngày);

:Nhấn "Thanh toán";

|System|
:Kiểm tra tin đăng;

if (Tin đăng ACTIVE?) then (không)
  |User|
  :Nhận lỗi:
  **Chỉ tin đăng đang hoạt động
  mới được nâng cấp**;
  stop
endif

:Kiểm tra điều kiện nâng cấp;

if (Có thể nâng cấp?) then (không)
  |User|
  :Nhận lỗi:
  **Không thể hạ cấp
  hoặc đã có gói cao hơn**;
  stop
endif

:Tính toán giá:
- Giá gói x thời hạn
- Trừ thời gian còn lại (nếu có);

:Tạo payment URL (VNPay);

|User|
:Chuyển đến trang thanh toán VNPay;

:Nhập thông tin thẻ;

:Xác nhận thanh toán;

|System|
:Nhận callback từ VNPay;

if (Thanh toán thành công?) then (không)
  |User|
  :Nhận thông báo:
  **Thanh toán thất bại**;
  stop
endif

:Cập nhật tin đăng:
- package_id = gói mới
- package_expires_at = now() + duration;

if (Đang có gói chưa hết hạn?) then (có)
  :Cộng thêm thời gian còn lại
  vào thời hạn mới;
endif

:Tạo transaction record;

:Gửi email xác nhận;

|User|
:Nhận thông báo:
**Nâng cấp gói thành công!**;

:Tin đăng được ưu tiên hiển thị;

stop

@enduml
```

## Giải Thích

**Quy trình nâng cấp gói tin:**

1. **User chọn gói và thời hạn** → System tính giá và tạo payment URL
2. **User thanh toán qua VNPay** → System nhận callback
3. **Nếu thành công**: Cập nhật gói tin và thời hạn, gửi email xác nhận

**Tính năng đặc biệt:**
- **Gia hạn**: Nếu đang có gói chưa hết hạn và mua lại cùng gói → Thời hạn mới = thời hạn hiện tại + thời gian mua thêm
- **Nâng cấp**: Chỉ được nâng lên gói cao hơn, không được hạ cấp

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
