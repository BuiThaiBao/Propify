# Sơ đồ Lớp Hệ thống (Class Diagrams)

Tài liệu này cung cấp **Sơ đồ lớp (Class Diagram) tổng thể** cho toàn bộ dự án Propify và các liên kết dẫn đến sơ đồ lớp chi tiết của từng phân hệ nghiệp vụ cụ thể.

---

## 1. Danh sách Sơ đồ Lớp theo Phân hệ

Để dễ quan sát cấu trúc chi tiết, các sơ đồ lớp đã được tách biệt theo từng phân hệ nghiệp vụ:

1. **[🔐 Phân hệ Xác thực (Authentication)](class_diagram_auth.md)** — Đăng ký, đăng nhập truyền thống, và Google Login.
2. **[📅 Phân hệ Đặt lịch hẹn (Appointment Booking)](class_diagram_appointment.md)** — Quản lý lịch hẹn, xác nhận/hủy lịch với State & Command.
3. **[📝 Phân hệ Tin đăng & Kiểm duyệt (Listing & Moderation)](class_diagram_listing.md)** — Tạo tin, sửa tin, duyệt tin theo Template Method.
4. **[🔍 Phân hệ Lọc & Sắp xếp tin](class_diagram_filter_sorting.md)** — Các thuật toán lọc, tìm kiếm, sắp xếp theo Strategy.
5. **[💳 Phân hệ Thanh toán & Nâng cấp](class_diagram_payment.md)** — Tích hợp VNPAY, nâng cấp gói tin và xử lý giao dịch.
6. **[📁 Phân hệ Quản lý Media (Storage)](class_diagram_media.md)** — Kết nối Cloudflare R2 Cloud Storage và Cloudinary.
7. **[💬 Phân hệ Tin nhắn Realtime (Chat)](class_diagram_chat.md)** — Trò chuyện trực tiếp và WebSocket Broadcasting qua Reverb.

---

## 2. Sơ đồ Quan hệ Giữa các Phân hệ (Subsystem Relationships)

Dưới đây là sơ đồ lớp mức cao (High-Level Class Diagram) mô tả cách các phân hệ tương tác và liên kết với nhau thông qua cơ sở dữ liệu và các Port/Interface:

```plantuml
@startuml
title Quan hệ giữa các Phân hệ trong Propify

skinparam linetype ortho
skinparam packageStyle rectangle
skinparam backgroundColor #FEFEFE

package "Authentication" as AUTH {
  class User {
    +int id
    +string email
  }
}

package "Listing Subsystem" as LIST {
  class Listing {
    +int id
    +int owner_id
    +int package_id
    +string status
  }
}

package "Appointment" as APPT {
  class AppointmentBooking {
    +int id
    +int slot_id
    +int viewer_id
  }
}

package "Payment" as PAY {
  class Transaction {
    +int id
    +int user_id
    +int listing_id
    +string status
  }
}

package "Chat" as CHAT {
  class Conversation {
    +int id
    +int listing_id
    +int participant_a_id
    +int participant_b_id
  }
}

package "Media" as MEDIA {
  interface FileStorageAdapter {
    +upload()
  }
}

' --- Mối quan hệ tương tác ---
User "1" *-- "0..*" Listing : "sở hữu >"
User "1" *-- "0..*" Transaction : "thực hiện >"
User "1" *-- "0..*" AppointmentBooking : "đặt cuộc hẹn >"

Listing "1" *-- "0..*" AppointmentBooking : "có lịch xem nhà tại slot >"
Listing "1" *-- "0..*" Transaction : "được nâng cấp qua >"
Listing "1" *-- "0..*" Conversation : "là chủ đề bàn luận >"

Transaction "1" *-- "1" Listing : "nâng cấp gói tin >"
Conversation "1" *-- "2" User : "trò chuyện giữa >"

Listing ..> FileStorageAdapter : "upload hình ảnh/video"

@enduml
```

### Giải thích Tương tác chéo:
- **User** là thực thể trung tâm liên kết với tất cả các hoạt động nghiệp vụ khác như đăng tin (`Listing`), đặt lịch (`AppointmentBooking`), và thanh toán (`Transaction`).
- **Listing** là lõi của hệ thống bất động sản, được tham chiếu bởi lịch hẹn xem nhà, giao dịch thanh toán nâng cấp gói VIP, cuộc trò chuyện chat trực tiếp và các adapter lưu trữ media tĩnh.
