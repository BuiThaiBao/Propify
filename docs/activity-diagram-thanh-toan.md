# Sơ Đồ Activity - Thanh Toán

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

title Quy Trình Thanh Toán (VNPay)

|User|
start
:Chọn gói tin/dịch vụ cần thanh toán;

:Nhấn "Thanh toán";

|System|
:Tính toán số tiền;

:Tạo transaction record
(status = PENDING);

:Tạo VNPay payment URL;

:Chuyển hướng đến VNPay;

|User|
:Trang thanh toán VNPay;

:Chọn ngân hàng;

:Nhập thông tin thẻ/tài khoản;

:Xác nhận OTP (từ ngân hàng);

|System (VNPay)|
:Xử lý thanh toán;

if (Thanh toán thành công?) then (có)
  :Gửi callback success
  đến hệ thống;
else (không)
  :Gửi callback failed
  đến hệ thống;
endif

|System|
:Nhận callback từ VNPay;

:Verify chữ ký digital;

if (Chữ ký hợp lệ?) then (không)
  :Log security warning;
  stop
endif

if (Thanh toán thành công?) then (có)
  :Cập nhật transaction
  (status = SUCCESS);
  
  :Kích hoạt dịch vụ đã mua:
  - Nâng cấp gói tin
  - Gia hạn tin đăng
  - etc.;
  
  :Gửi email hóa đơn;
  
  |User|
  :Chuyển về trang success;
  
  :Nhận thông báo:
  **Thanh toán thành công!**;
  
  :Dịch vụ được kích hoạt;
  
  stop
  
else (không)
  :Cập nhật transaction
  (status = FAILED);
  
  :Ghi log lỗi;
  
  |User|
  :Chuyển về trang failed;
  
  :Nhận thông báo:
  **Thanh toán thất bại**
  + Mã lỗi;
  
  :Có thể thử lại;
  
  stop
endif

@enduml
```

## Giải Thích

**Quy trình thanh toán qua VNPay:**

1. **User chọn dịch vụ** → System tạo transaction và payment URL
2. **User thanh toán trên VNPay** → Nhập thông tin thẻ, xác nhận OTP
3. **VNPay xử lý** → Gửi callback về hệ thống (success/failed)
4. **System nhận callback** → Verify chữ ký, cập nhật transaction, kích hoạt dịch vụ

**Bảo mật:** System verify chữ ký digital từ VNPay để đảm bảo callback hợp lệ. Không verify → Từ chối xử lý.

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
