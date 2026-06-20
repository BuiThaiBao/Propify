# Propify Backend API

Hệ thống Backend API cho dự án Propify, được viết bằng Laravel 12 và cấu hình sẵn Docker phục vụ môi trường phát triển local (Development Environment).

---

## 🛠️ Yêu cầu hệ thống

- **Docker** & **Docker Compose**
- Database MySQL ngoài (hoặc có thể kết nối thông qua file cấu hình `.env`)

---

## 🚀 Khởi chạy dự án bằng Docker

Thực hiện các bước sau tại thư mục `PropifyBackend`:

### 1. Chuẩn bị file `.env`
Đảm bảo đã tạo file cấu hình `.env` từ `.env.example`:
```bash
cp .env.example .env
```
*Lưu ý: Thiết lập cấu hình cơ sở dữ liệu (`DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`) và Redis trong `.env` cho phù hợp. Nếu Redis của bạn được chạy ở container khác, hãy điều chỉnh `REDIS_HOST` (ví dụ thành tên container Redis hoặc `host.docker.internal` nếu kết nối ra máy host) thay vì dùng `127.0.0.1`.*

### 2. Khởi tạo container
Build và khởi chạy các dịch vụ ở chế độ chạy ngầm (detached mode):
```bash
docker compose build
docker compose up -d
```
Container entrypoint sẽ tự động chạy `composer install` khi khởi động nếu thư mục `vendor` chưa tồn tại hoặc cần cập nhật.

---

## 🏗️ Danh sách các Service trong Compose

Dự án được phân tách thành 4 service chính hoạt động độc lập và tự động nhận diện code thay đổi (hot-reloading):

| Service | Chức năng | Port (Host:Container) | Chi tiết lệnh chạy |
| :--- | :--- | :--- | :--- |
| **`web`** (Nginx) | Web Server nhận request chính | `8000:80` | Cấu hình trong `./docker/nginx/default.conf` |
| **`app`** (PHP) | Xử lý logic PHP (FPM) | - (Internal 9000) | PHP 8.2.12-fpm |
| **`queue`** | Xử lý hàng đợi Laravel | Chạy ngầm | `php artisan queue:work` |
| **`reverb`** | WebSocket Realtime | `8080:8080` | `php artisan reverb:start` |

---

## 💻 Các câu lệnh hữu ích khi làm việc

Tất cả các lệnh cần chạy bên trong môi trường PHP/Laravel cần được thực thi thông qua container `app`:

### Vào terminal của container
```bash
docker compose exec app bash
```

### Chạy Migration & Seeder
```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

### Xóa Cache
```bash
docker compose exec app php artisan cache:clear
docker compose exec app php artisan config:clear
docker compose exec app php artisan route:clear
```

### Theo dõi Log của hệ thống
Xem log thời gian thực của toàn bộ container:
```bash
docker compose logs -f
```
Xem log riêng của service cụ thể (Ví dụ: queue worker):
```bash
docker compose logs -f queue
```

### Tắt các container
```bash
docker compose down
```
