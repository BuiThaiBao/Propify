# Sơ Đồ Activity - Chỉnh Sửa Tin Đăng

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

title Quy Trình Chỉnh Sửa Tin Đăng

|User|
start
:Truy cập trang "Tin đăng của tôi";

:Chọn tin đăng cần chỉnh sửa;

:Nhấn "Chỉnh sửa";

|System|
:Kiểm tra quyền sở hữu;

if (User là chủ tin đăng?) then (không)
  |User|
  :Nhận lỗi:
  **Không có quyền chỉnh sửa**;
  stop
endif

:Hiển thị form với dữ liệu hiện tại;

|User|
:Chỉnh sửa thông tin:
- Tiêu đề, mô tả
- Giá, diện tích
- Hình ảnh, video
- Thông tin liên hệ;

if (Chọn hành động?) then (Lưu nháp)
  :Nhấn "Lưu nháp";
  
  |System|
  :Cập nhật tin đăng
  (status = DRAFT);
  
  |User|
  :Nhận thông báo:
  **Lưu nháp thành công**;
  
  stop
  
else (Gửi cập nhật)
  :Nhấn "Cập nhật tin đăng";
  
  |System|
  :Kiểm tra thông tin đầy đủ;
  
  if (Thông tin hợp lệ?) then (không)
    |User|
    :Nhận lỗi validation;
    stop
  endif
  
  :Cập nhật property và listing;
  
  :Thay thế images/videos cũ;
  
  :Chuyển status = PENDING
  (chờ duyệt lại);
  
  :Xóa cache tin đăng công khai;
  
  |User|
  :Nhận thông báo:
  **Cập nhật thành công.
  Tin đăng đang chờ duyệt lại.**;
  
  stop
endif

@enduml
```

## Giải Thích

**Quy trình chỉnh sửa tin đăng:**

1. **User chọn tin đăng** → System kiểm tra quyền sở hữu
2. **User chỉnh sửa thông tin** → Có thể lưu nháp hoặc gửi cập nhật
3. **Lưu nháp**: Cập nhật với status DRAFT (không cần duyệt lại)
4. **Gửi cập nhật**: Cập nhật và chuyển về status PENDING để admin duyệt lại

**Lưu ý:** Khi cập nhật tin đã ACTIVE, tin sẽ quay về trạng thái PENDING để admin kiểm duyệt lại nội dung mới.

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
