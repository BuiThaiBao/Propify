# Sơ Đồ Activity - Admin Khóa/Mở Khóa Tài Khoản

---

## Activity Diagram (Admin - System Interaction)

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

title Quy Trình Admin Khóa/Mở Khóa Tài Khoản

|Admin|
start
:Truy cập trang quản lý user;

:Tìm kiếm user theo:
- Email
- Tên
- ID;

:Chọn user cần xử lý;

|System|
:Hiển thị thông tin user:
- Họ tên, email
- Trạng thái hiện tại
- Ngày đăng ký
- Số tin đăng
- Lịch sử vi phạm (nếu có);

|Admin|
:Xem xét thông tin;

if (Quyết định?) then (Khóa tài khoản)
  :Nhấn "Khóa tài khoản";
  
  |System|
  :Kiểm tra trạng thái;
  
  if (User đã bị khóa?) then (có)
    |Admin|
    :Nhận thông báo:
    **Tài khoản đã bị khóa**;
    stop
  endif
  
  :Hiển thị form lý do;
  
  |Admin|
  :Nhập lý do khóa:
  - Spam
  - Lừa đảo
  - Vi phạm chính sách
  - Báo cáo nhiều lần;
  
  :Xác nhận khóa;
  
  |System|
  :Cập nhật user:
  - status = BLOCKED
  - block_reason = lý do
  - blocked_at = now()
  - blocked_by = admin_id;
  
  :Vô hiệu hóa tất cả tin đăng
  của user (status = LOCKED);
  
  :Xóa tất cả refresh tokens
  (force logout khỏi mọi thiết bị);
  
  :Gửi email thông báo cho user:
  **Tài khoản bị khóa**
  + Lý do
  + Cách khiếu nại;
  
  |Admin|
  :Nhận thông báo:
  **Đã khóa tài khoản**;
  
  stop
  
else (Mở khóa tài khoản)
  :Nhấn "Mở khóa tài khoản";
  
  |System|
  :Kiểm tra trạng thái;
  
  if (User đang bị khóa?) then (không)
    |Admin|
    :Nhận thông báo:
    **Tài khoản không bị khóa**;
    stop
  endif
  
  :Hiển thị form ghi chú;
  
  |Admin|
  :Nhập ghi chú (tùy chọn):
  - Khiếu nại hợp lệ
  - Đã xác minh lại
  - Vi phạm nhẹ;
  
  :Xác nhận mở khóa;
  
  |System|
  :Cập nhật user:
  - status = ACTIVE
  - unblocked_at = now()
  - unblocked_by = admin_id
  - unblock_note = ghi chú;
  
  :Khôi phục tin đăng
  (chuyển từ LOCKED về ACTIVE
  nếu trước đó là ACTIVE);
  
  :Gửi email thông báo cho user:
  **Tài khoản đã được mở khóa**;
  
  |Admin|
  :Nhận thông báo:
  **Đã mở khóa tài khoản**;
  
  stop
endif

@enduml
```

## Giải Thích

**Quy trình admin khóa/mở khóa tài khoản:**

**Khóa tài khoản:**
1. **Admin tìm user** → Xem thông tin và lịch sử
2. **Admin nhập lý do** → System cập nhật status = BLOCKED
3. **System xử lý**: Vô hiệu hóa tin đăng, force logout, gửi email thông báo

**Mở khóa tài khoản:**
1. **Admin chọn user bị khóa** → Nhập ghi chú (tùy chọn)
2. **System cập nhật** status = ACTIVE
3. **System khôi phục**: Tin đăng active trở lại, gửi email thông báo

**Lưu ý:** Khi khóa tài khoản, tất cả tin đăng của user cũng bị khóa và user bị đăng xuất khỏi mọi thiết bị.

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
