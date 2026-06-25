# Tài Liệu Activity Diagrams - Propify

Thư mục này chứa các sơ đồ activity diagram cho các chức năng chính của hệ thống Propify.

## 📋 Tổng Quan

Tất cả các sơ đồ được tạo theo format PlantUML, tập trung vào **tương tác giữa User và System**, không đi sâu vào chi tiết code implementation.

**Cách xem sơ đồ:**
1. Mở file `.md` bất kỳ
2. Copy nội dung PlantUML (trong block ```plantuml)
3. Paste vào: https://www.plantuml.com/plantuml/uml/
4. Hoặc dùng VS Code extension: `jebbs.plantuml`

---

## 🔐 Chức Năng Authentication (3 sơ đồ)

| Sơ đồ | File | Mô tả |
|-------|------|-------|
| Đăng ký tài khoản | [activity-diagram-dang-ky-tai-khoan.md](activity-diagram-dang-ky-tai-khoan.md) | User đăng ký → Nhận OTP → Xác thực → Kích hoạt tài khoản |
| Đăng nhập | [activity-diagram-dang-nhap.md](activity-diagram-dang-nhap.md) | Email/Password hoặc Google OAuth |
| Quên mật khẩu | [activity-diagram-quen-mat-khau.md](activity-diagram-quen-mat-khau.md) | Reset password với OTP qua email |

---

## 🏠 Chức Năng Quản Lý Tin Đăng (4 sơ đồ)

| Sơ đồ | File | Mô tả |
|-------|------|-------|
| Tạo tin đăng | [activity-diagram-them-moi-tin-dang.md](activity-diagram-them-moi-tin-dang.md) | Đăng tin mới (lưu nháp hoặc gửi duyệt) |
| Chỉnh sửa tin đăng | [activity-diagram-chinh-sua-tin-dang.md](activity-diagram-chinh-sua-tin-dang.md) | Cập nhật tin đã tạo |
| Nâng cấp gói tin | [activity-diagram-nang-cap-goi-tin.md](activity-diagram-nang-cap-goi-tin.md) | Mua gói VIP/Nổi bật với thanh toán VNPay |
| Xác thực tin đăng | [activity-diagram-xac-thuc-tin-dang-user.md](activity-diagram-xac-thuc-tin-dang-user.md) | User upload giấy tờ để xác thực BĐS |

---

## 📅 Chức Năng Lịch Hẹn (2 sơ đồ)

| Sơ đồ | File | Mô tả |
|-------|------|-------|
| Đặt lịch hẹn | [activity-diagram-dat-lich-hen.md](activity-diagram-dat-lich-hen.md) | Khách đặt lịch xem BĐS |
| Xử lý lịch hẹn | [activity-diagram-xu-ly-lich-hen.md](activity-diagram-xu-ly-lich-hen.md) | Chủ nhà xác nhận/từ chối lịch hẹn |

---

## 💳 Chức Năng Thanh Toán (1 sơ đồ)

| Sơ đồ | File | Mô tả |
|-------|------|-------|
| Thanh toán | [activity-diagram-thanh-toan.md](activity-diagram-thanh-toan.md) | Quy trình thanh toán qua VNPay |

---

## 👨‍💼 Chức Năng Admin (3 sơ đồ)

| Sơ đồ | File | Mô tả |
|-------|------|-------|
| Duyệt/Từ chối tin đăng | [activity-diagram-admin-duyet-tin-dang.md](activity-diagram-admin-duyet-tin-dang.md) | Admin kiểm duyệt tin PENDING → ACTIVE/REJECTED/LOCKED |
| Xác thực tin đăng | [activity-diagram-admin-xac-thuc-tin-dang.md](activity-diagram-admin-xac-thuc-tin-dang.md) | Admin duyệt giấy tờ xác thực BĐS |
| Khóa/Mở khóa tài khoản | [activity-diagram-admin-khoa-tai-khoan.md](activity-diagram-admin-khoa-tai-khoan.md) | Admin khóa/mở khóa user vi phạm |

---

## 📊 Thống Kê

- **Tổng số sơ đồ:** 13
- **Chức năng User:** 10
- **Chức năng Admin:** 3
- **Format:** PlantUML Activity Diagram
- **Focus:** User-System Interaction (không có code chi tiết)

---

## 🎨 Cấu Trúc Sơ Đồ

Mỗi sơ đồ có cấu trúc chuẩn:

```
# Tiêu Đề

---

## Activity Diagram (User - System Interaction)

```plantuml
@startuml
... PlantUML code ...
@enduml
```

## Giải Thích

Brief explanation of the flow...

---

**Cách xem sơ đồ**: Copy nội dung PlantUML vào https://www.plantuml.com/plantuml/uml/
```

### Quy Ước Đặt Tên File

- `activity-diagram-<ten-chuc-nang>.md`
- Tên file dạng kebab-case (lowercase, dấu gạch ngang)
- Prefix `admin-` cho các chức năng admin

---

## 🚀 Cách Sử Dụng

1. **Xem nhanh**: Mở file `.md` trực tiếp trên GitHub/GitLab
2. **Render diagram**: 
   - Online: Copy PlantUML code → Paste vào https://www.plantuml.com/plantuml/uml/
   - VS Code: Cài extension `PlantUML` → Mở file → Press `Alt+D`
   - IntelliJ/PhpStorm: Cài plugin `PlantUML Integration`

---

## 📝 Lưu Ý

- Các sơ đồ tập trung vào **luồng tương tác** giữa User và System
- **Không bao gồm**: Chi tiết code implementation, API endpoints cụ thể
- **Phù hợp cho**: Product Owner, BA, QA, Developer mới join dự án

---

**Last Updated:** 2026-06-25  
**Version:** 1.0  
**Author:** Claude AI
