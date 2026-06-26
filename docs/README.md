# Tài Liệu Sơ Đồ Hệ Thống - Propify

Thư mục này chứa các sơ đồ Activity Diagram và Sequence Diagram cho các chức năng chính của hệ thống Propify.

## 📋 Tổng Quan

- **Activity Diagrams**: Tập trung vào tương tác luồng nghiệp vụ giữa **User** và **System**.
- **Sequence Diagrams**: Tập trung vào tương tác chi tiết giữa các thành phần kỹ thuật theo đúng mô hình phân lớp: **User/Admin → Frontend → Controller/API → Request/Validation → Service/Command → Entity → Repository → Database**.

**Cách xem sơ đồ:**
1. Mở file `.md` bất kỳ dưới đây.
2. Copy nội dung PlantUML (trong block ```plantuml ... ```).
3. Paste vào trang online: https://www.plantuml.com/plantuml/uml/
4. Hoặc sử dụng extension **PlantUML** trong VS Code (`Alt+D` để preview).

---

## 🔐 Chức Năng Authentication

| Chức năng | Activity Diagram | Sequence Diagram | Mô tả |
| :--- | :--- | :--- | :--- |
| **Đăng ký tài khoản** | [Activity](activity-diagram-dang-ky-tai-khoan.md) | [Sequence](sequence-diagram-dang-ky-tai-khoan.md) | Nhập thông tin → Nhận OTP email → Xác thực → Kích hoạt |
| **Đăng nhập** | [Activity](activity-diagram-dang-nhap.md) | [Sequence](sequence-diagram-dang-nhap.md) | Email + Password hoặc Google OAuth |
| **Quên mật khẩu** | [Activity](activity-diagram-quen-mat-khau.md) | [Sequence](sequence-diagram-quen-mat-khau.md) | Reset password qua mã OTP gửi về email |

---

## 🏠 Chức Năng Quản Lý Tin Đăng

| Chức năng | Activity Diagram | Sequence Diagram | Mô tả |
| :--- | :--- | :--- | :--- |
| **Tạo tin đăng** | [Activity](activity-diagram-them-moi-tin-dang.md) | [Sequence](sequence-diagram-tao-tin-dang.md) | Đăng tin mới (lưu nháp hoặc gửi phê duyệt) |
| **Chỉnh sửa tin đăng** | [Activity](activity-diagram-chinh-sua-tin-dang.md) | [Sequence](sequence-diagram-chinh-sua-tin-dang.md) | Cập nhật thông tin tin đăng hiện có |
| **Nâng cấp gói tin** | [Activity](activity-diagram-nang-cap-goi-tin.md) | [Sequence](sequence-diagram-nang-cap-goi-tin.md) | Mua/Nâng cấp gói VIP/Nổi bật |
| **Xác thực tin đăng (User)** | [Activity](activity-diagram-xac-thuc-tin-dang-user.md) | [Sequence](sequence-diagram-xac-thuc-tin-dang-user.md) | Gửi CCCD + Giấy tờ pháp lý để lấy badge xác thực |

---

## 📅 Chức Năng Lịch Hẹn

| Chức năng | Activity Diagram | Sequence Diagram | Mô tả |
| :--- | :--- | :--- | :--- |
| **Đặt lịch hẹn** | [Activity](activity-diagram-dat-lich-hen.md) | [Sequence](sequence-diagram-dat-lich-hen.md) | Khách đặt lịch đi xem bất động sản |
| **Xác nhận/Từ chối lịch** | [Activity](activity-diagram-xu-ly-lich-hen.md) | [Sequence](sequence-diagram-xu-ly-lich-hen.md) | Chủ tin đăng phê duyệt hoặc từ chối lịch hẹn |

---

## 💳 Chức Năng Thanh Toán

| Chức năng | Activity Diagram | Sequence Diagram | Mô tả |
| :--- | :--- | :--- | :--- |
| **Thanh toán** | [Activity](activity-diagram-thanh-toan.md) | [Sequence](sequence-diagram-thanh-toan.md) | Quy trình tích hợp thanh toán cổng VNPay |

---

## 👨‍💼 Chức Năng Admin

| Chức năng | Activity Diagram | Sequence Diagram | Mô tả |
| :--- | :--- | :--- | :--- |
| **Duyệt/Từ chối tin** | [Activity](activity-diagram-admin-duyet-tin-dang.md) | [Sequence](sequence-diagram-admin-duyet-tin-dang.md) | Admin kiểm duyệt tin đăng chờ duyệt |
| **Xác thực tin đăng** | [Activity](activity-diagram-admin-xac-thuc-tin-dang.md) | [Sequence](sequence-diagram-admin-xac-thuc-tin-dang.md) | Admin kiểm duyệt giấy tờ xác thực của BĐS |
| **Khóa/Mở khóa tài khoản** | [Activity](activity-diagram-admin-khoa-tai-khoan.md) | [Sequence](sequence-diagram-admin-khoa-tai-khoan.md) | Admin quản lý trạng thái hoạt động của User |

---

## 📊 Thống Kê Tài Liệu

- **Tổng số file sơ đồ**: 26 file (13 Activity + 13 Sequence)
- **Cấu trúc thư mục**:
  - `activity-diagram-*.md`: Chứa mã PlantUML Activity và giải thích.
  - `sequence-diagram-*.md`: Chứa mã PlantUML Sequence (phân lớp từ UI đến Database) và giải thích.
- **Last Updated**: 2026-06-26

---
