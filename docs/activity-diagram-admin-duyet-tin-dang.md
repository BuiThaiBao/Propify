# Sơ Đồ Activity - Admin Duyệt/Từ Chối Tin Đăng

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

title Quy Trình Admin Duyệt/Từ Chối Tin Đăng

|Admin|
start
:Truy cập trang quản lý tin đăng;

:Lọc tin đăng theo status:
**PENDING** (chờ duyệt);

:Chọn tin đăng để xem xét;

|System|
:Hiển thị đầy đủ thông tin:
- Tiêu đề, mô tả
- Hình ảnh, video
- Thông tin BĐS
- Thông tin liên hệ
- Giấy tờ xác thực (nếu có);

|Admin|
:Xem xét nội dung tin đăng;

if (Quyết định?) then (Phê duyệt)
  :Nhấn "Phê duyệt";
  
  |System|
  :Kiểm tra trạng thái hiện tại;
  
  if (Status = PENDING?) then (không)
    |Admin|
    :Nhận lỗi:
    **Tin đăng đã được xử lý**;
    stop
  endif
  
  :Cập nhật listing:
  - status = ACTIVE
  - approved_at = now()
  - approved_by = admin_id;
  
  :Tạo status history record;
  
  :Gửi thông báo cho chủ tin:
  **Tin đăng đã được duyệt**;
  
  :Xóa cache tin đăng công khai;
  
  :Tin đăng xuất hiện trên trang chủ;
  
  |Admin|
  :Nhận thông báo:
  **Đã phê duyệt tin đăng**;
  
  stop
  
else (Từ chối)
  :Nhấn "Từ chối";
  
  |System|
  :Hiển thị form lý do;
  
  |Admin|
  :Nhập lý do từ chối:
  - Vi phạm nội dung
  - Hình ảnh không phù hợp
  - Thông tin không chính xác
  - Spam;
  
  :Xác nhận từ chối;
  
  |System|
  :Kiểm tra trạng thái hiện tại;
  
  if (Status = PENDING?) then (không)
    |Admin|
    :Nhận lỗi:
    **Tin đăng đã được xử lý**;
    stop
  endif
  
  :Cập nhật listing:
  - status = REJECTED
  - rejection_reason = lý do
  - rejected_at = now()
  - rejected_by = admin_id;
  
  :Tạo status history record;
  
  :Gửi thông báo cho chủ tin:
  **Tin đăng bị từ chối**
  + Lý do;
  
  |Admin|
  :Nhận thông báo:
  **Đã từ chối tin đăng**;
  
  stop
  
else (Khóa)
  :Nhấn "Khóa tin đăng";
  
  |System|
  :Hiển thị form lý do;
  
  |Admin|
  :Nhập lý do khóa:
  - Vi phạm nghiêm trọng
  - Lừa đảo
  - Báo cáo nhiều lần;
  
  :Xác nhận khóa;
  
  |System|
  :Cập nhật listing:
  - status = LOCKED
  - lock_reason = lý do
  - locked_at = now()
  - locked_by = admin_id;
  
  :Tạo status history record;
  
  :Gửi thông báo cho chủ tin:
  **Tin đăng bị khóa**
  + Lý do;
  
  :Xóa khỏi trang công khai;
  
  |Admin|
  :Nhận thông báo:
  **Đã khóa tin đăng**;
  
  stop
endif

@enduml
```

## Giải Thích

**Quy trình admin duyệt tin đăng:**

1. **Admin lọc tin PENDING** → Xem danh sách tin chờ duyệt
2. **Admin xem chi tiết** → Đọc nội dung, xem hình ảnh, kiểm tra thông tin
3. **Admin quyết định**:
   - **Phê duyệt**: Tin chuyển sang ACTIVE, hiển thị công khai
   - **Từ chối**: Tin chuyển sang REJECTED, gửi lý do cho user
   - **Khóa**: Tin chuyển sang LOCKED (vi phạm nghiêm trọng)

**Lưu ý:** Mỗi thay đổi trạng thái đều được ghi lại trong `listing_status_histories` để audit trail.

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
