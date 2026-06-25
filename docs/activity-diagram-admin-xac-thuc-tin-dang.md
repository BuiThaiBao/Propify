# Sơ Đồ Activity - Admin Xác Thực Tin Đăng

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

title Quy Trình Admin Xác Thực Tin Đăng

|Admin|
start
:Truy cập trang quản lý tin đăng;

:Lọc tin có yêu cầu xác thực:
**is_verified = PENDING_VERIFICATION**;

:Chọn tin đăng để xem xét;

|System|
:Hiển thị thông tin tin đăng
và giấy tờ đã upload:
- CCCD mặt trước
- CCCD mặt sau
- Giấy tờ pháp lý (1-5 file);

|Admin|
:Xem xét giấy tờ:
- CCCD rõ ràng?
- Họ tên khớp với contact_name?
- Giấy tờ pháp lý hợp lệ?
- Địa chỉ khớp với BĐS?;

if (Quyết định?) then (Phê duyệt)
  :Nhấn "Phê duyệt xác thực";
  
  |System|
  :Kiểm tra trạng thái;
  
  if (is_verified = PENDING_VERIFICATION?) then (không)
    |Admin|
    :Nhận lỗi:
    **Yêu cầu đã được xử lý**;
    stop
  endif
  
  :Cập nhật listing:
  - is_verified = VERIFIED
  - verified_at = now()
  - verified_by = admin_id;
  
  :Gắn badge "Đã xác thực"
  trên tin đăng;
  
  :Tăng độ tin cậy
  của tin đăng (score boost);
  
  :Gửi thông báo cho chủ tin:
  **Tin đăng đã được xác thực**;
  
  :Xóa cache tin đăng;
  
  |Admin|
  :Nhận thông báo:
  **Đã phê duyệt xác thực**;
  
  stop
  
else (Từ chối)
  :Nhấn "Từ chối xác thực";
  
  |System|
  :Hiển thị form lý do;
  
  |Admin|
  :Nhập lý do từ chối:
  - CCCD không rõ/giả mạo
  - Thông tin không khớp
  - Giấy tờ pháp lý không hợp lệ
  - Thiếu giấy tờ bắt buộc;
  
  :Xác nhận từ chối;
  
  |System|
  :Kiểm tra trạng thái;
  
  if (is_verified = PENDING_VERIFICATION?) then (không)
    |Admin|
    :Nhận lỗi:
    **Yêu cầu đã được xử lý**;
    stop
  endif
  
  :Cập nhật listing:
  - is_verified = REJECTED
  - verification_rejection_reason = lý do
  - verification_rejected_at = now()
  - verification_rejected_by = admin_id;
  
  :Gửi thông báo cho chủ tin:
  **Yêu cầu xác thực bị từ chối**
  + Lý do
  + Hướng dẫn sửa;
  
  |Admin|
  :Nhận thông báo:
  **Đã từ chối xác thực**;
  
  note right
    User có thể upload lại
    giấy tờ đúng và gửi
    yêu cầu xác thực mới
  end note
  
  stop
endif

@enduml
```

## Giải Thích

**Quy trình admin xác thực tin đăng:**

1. **Admin lọc tin cần xác thực** → Danh sách tin có status `PENDING_VERIFICATION`
2. **Admin xem giấy tờ** → CCCD 2 mặt + Giấy tờ pháp lý
3. **Admin kiểm tra**:
   - CCCD rõ ràng, không giả mạo
   - Họ tên trên CCCD khớp với thông tin liên hệ
   - Giấy tờ pháp lý hợp lệ (sổ đỏ, hợp đồng, etc.)
   - Địa chỉ trên giấy tờ khớp với địa chỉ BĐS
4. **Phê duyệt**: Tin được gắn badge "Đã xác thực", tăng độ tin cậy
5. **Từ chối**: Gửi lý do chi tiết, user có thể gửi lại

**Lợi ích của xác thực:**
- Tin đăng được gắn badge "Đã xác thực" (trustworthy)
- Tăng score → hiển thị ưu tiên hơn
- Người mua/thuê tin tưởng hơn

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
