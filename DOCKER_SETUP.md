# Hướng dẫn Setup dự án Propify bằng Docker

> Hướng dẫn này dành cho việc chạy toàn bộ hệ thống (Backend PHP, MySQL, Redis, Web Server, Queue, WebSocket) thông qua Docker mà không cần cài đặt thủ công PHP, Composer, Node... trên máy host.

---

## I. Yêu cầu hệ thống

- Docker Desktop (Windows/Mac) hoặc Docker Engine + Docker Compose (Linux)
- Tối thiểu 8GB RAM, khuyến nghị 16GB RAM
- Ổ cứng còn ít nhất 10GB cho images + volumes
- **Git** đã được cài đặt

---

## II. Tải mã nguồn từ Git (Clone & Checkout)

Mở terminal và di chuyển đến thư mục làm việc của bạn (ví dụ: `D:\PROJECT`), sau đó chạy các lệnh sau:

### Bước 1: Clone dự án từ Git

```bash
git clone <URL_DỰ_ÁN_GIT> Meyland
cd Meyland
```

### Bước 2: Chuyển sang nhánh phát triển phù hợp (ví dụ nhánh nâng cấp gói tin)

```bash
git checkout feature/nangcapgoitin
```

---

## III. Cấu trúc thư mục

```
Meyland/
├── docker-compose.yml           # MySQL + Redis (dịch vụ chung)
├── PropifyBackend/
│   ├── docker-compose.yml       # App PHP + Nginx + Queue + Reverb
│   ├── Dockerfile
│   ├── docker-entrypoint.sh
│   └── .env                     # Config backend
├── PropifyAdmin/                # Admin FE (Vue 3) — chạy node
├── PropifyFrontend/             # Client FE (Vue 3) — chạy node
└── DOCKER_SETUP.md              # File này
```

---

## III. 1. Khởi chạy cơ sở dữ liệu & Cache (MySQL + Redis)

Mở terminal và chạy:

```bash
cd D:\PROJECT\Meyland
docker-compose up -d
```

Sau khi chạy, kiểm tra các container đã chạy thành công:

```bash
docker ps
```

Kết quả mong đợi:

| Container | Port | Dịch vụ |
|---|---|---|
| `project_mysql` | 3307 (host) → 3306 | MySQL 8.0 |
| `redis` | 6379 (host) → 6379 | Redis 7 |

---

## IV. 2. Khởi chạy Backend (Laravel PHP + Nginx + Queue)

```bash
cd D:\PROJECT\Meyland\PropifyBackend
```

### Bước 2.1: Tạo file .env (nếu chưa có)

```bash
copy .env.example .env
```

Chỉnh sửa file `.env` với các thông số phù hợp:

```dotenv
DB_CONNECTION=mysql
DB_HOST=host.docker.internal    # Trên Windows/Mac dùng host.docker.internal
                                # Trên Linux dùng IP của docker0 hoặc 172.17.0.1
DB_PORT=3307                    # Port MySQL expose ra từ container project_mysql
DB_DATABASE=propify
DB_USERNAME=root
DB_PASSWORD=rootpassword

REDIS_HOST=host.docker.internal # Trỏ tới container redis
REDIS_PASSWORD=redispassword
REDIS_PORT=6379
```

Lưu ý: Đảm bảo các thông số DB_HOST, DB_PORT khớp với thông tin container MySQL `project_mysql`.

### Bước 2.2: Dựng các container Backend

```bash
docker-compose up -d --build
```

Danh sách container sau khi chạy (thêm vào `docker ps`):

| Container | Port | Dịch vụ |
|---|---|---|
| `propify_backend_app` | — (nội bộ) | PHP-FPM + Composer |
| `propify_backend_nginx` | 8000 (host) → 80 | Nginx (web server) |
| `propify_backend_queue` | — (nội bộ) | Laravel Queue Worker |
| `propify_backend_reverb` | 8080 (host) → 8080 | Laravel Reverb WebSocket |

### Bước 2.3: Chạy migration & seed dữ liệu

```bash
docker exec -it propify_backend_app php artisan key:generate
docker exec -it propify_backend_app php artisan migrate --seed
```

### Bước 2.4: Cài đặt thư viện DomPDF (bắt buộc cho chức năng xuất PDF)

```bash
docker exec -it propify_backend_app composer require barryvdh/laravel-dompdf
docker exec -it propify_backend_app php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
```

### Bước 2.5: Tạo Symbolic Link cho Storage

```bash
docker exec -it propify_backend_app php artisan storage:link
```

### Bước 2.6: Kiểm tra Backend

Mở trình duyệt, truy cập: [http://localhost:8000](http://localhost:8000)

Kết quả mong đợi: Trang Laravel welcome xuất hiện (hoặc JSON response từ API).

---

## V. 3. Khởi chạy Frontend & Admin UI (Node trên máy host)

> Do dự án Frontend sử dụng Vite (Hot Module Replacement), ta chạy trực tiếp trên máy host (không dùng Docker).

### Yêu cầu: Node.js >= 20.x, npm hoặc yarn.

### Bước 3.1: Cài đặt & chạy PropifyFrontend (Client)

```bash
cd D:\PROJECT\Meyland\PropifyFrontend
npm install
npm run dev
```

Mở trình duyệt tại: [http://localhost:5173](http://localhost:5173)

### Bước 3.2: Cài đặt & chạy PropifyAdmin (Admin Panel)

```bash
cd D:\PROJECT\Meyland\PropifyAdmin
npm install
npm run dev
```

Mở trình duyệt tại: [http://localhost:5174](http://localhost:5174)

---

## VI. Một số lệnh hữu ích

### Dừng tất cả container

```bash
cd D:\PROJECT\Meyland
docker-compose down
cd D:\PROJECT\Meyland\PropifyBackend
docker-compose down
```

### Xem logs của Backend

```bash
docker logs -f propify_backend_app
docker logs -f propify_backend_nginx
```

### Vào shell của container PHP

```bash
docker exec -it propify_backend_app bash
```

### Chạy artisan command bên trong container

```bash
docker exec -it propify_backend_app php artisan <command>
```

### Xóa toàn bộ dữ liệu MySQL và Redis (reset toàn bộ)

```bash
docker-compose down -v
```

Sau đó khởi chạy lại các bước ở mục **III**, **IV**.

---

## VII. Xử lý sự cố thường gặp (Troubleshooting)

| Vấn đề | Nguyên nhân | Cách fix |
|---|---|---|
| `Connection refused` khi kết nối MySQL | Container `project_mysql` chưa sẵn sàng, hoặc sai DB_HOST. | Chạy `docker-compose up -d` lại. Kiểm tra `docker logs project_mysql`. Đảm bảo DB_HOST trỏ đúng (`host.docker.internal`). |
| `SQLSTATE[HY000] [2002] Connection refused` | Artisan chạy quá sớm trước khi MySQL sẵn sàng. | Chờ 10-15s rồi chạy lại lệnh `migrate`. |
| `Class "Barryvdh\DomPDF\Facade\Pdf" not found` | Chưa cài đặt thư viện DomPDF. | Chạy `docker exec -it propify_backend_app composer require barryvdh/laravel-dompdf`. |
| Port 8000 hoặc 3307 đã được sử dụng | Có ứng dụng khác đang dùng port. | Đổi port trong file `docker-compose.yml` tương ứng. |
| `npm ERR!` khi chạy Frontend | Phiên bản Node không phù hợp. | Yêu cầu Node >= 20.x. Chạy `node -v` để kiểm tra. |
| `vite` không tìm thấy lệnh | Thiếu `node_modules`. | Chạy `npm install` trước khi `npm run dev`. |
