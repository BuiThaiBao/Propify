# Đề xuất Công nghệ — Propify Real Estate Platform

> **Ngày tạo:** 2026-04-09  
> **Stack hiện tại:** Laravel 12 · Vue 3 · MySQL · Redis · JWT · Tailwind CSS  
> **Mục đích:** Mapping công nghệ cụ thể vào từng nhóm chức năng trong Product Backlog

---

## Mục lục

1. [Stack tổng quan](#1-stack-tổng-quan)
2. [Backend — Laravel](#2-backend--laravel)
3. [Frontend — Vue 3 (Customer)](#3-frontend--vue-3-customer)
4. [Frontend — Vue 3 (Admin)](#4-frontend--vue-3-admin)
5. [Infrastructure & DevOps](#5-infrastructure--devops)
6. [Bảng ánh xạ chức năng → công nghệ](#6-bảng-ánh-xạ-chức-năng--công-nghệ)
7. [Lộ trình cài đặt gói](#7-lộ-trình-cài-đặt-gói)

---

## 1. Stack tổng quan

```
┌──────────────────────────────────────────────────────────────────────┐
│                          CLIENT APPS                                  │
│                                                                       │
│  PropifyFrontend (Vue 3 + Vite + TailwindCSS v4)                     │
│  PropifyAdmin    (Vue 3 + Vite + Element Plus / PrimeVue)             │
└────────────────────────┬─────────────────────────────────────────────┘
                         │ HTTPS · WebSocket (Pusher/Soketi)
┌────────────────────────▼─────────────────────────────────────────────┐
│                     API GATEWAY (Nginx)                               │
│  Rate Limiting · SSL Termination · Static Asset Serving              │
└────────────────────────┬─────────────────────────────────────────────┘
                         │
┌────────────────────────▼─────────────────────────────────────────────┐
│                   LARAVEL 12 APPLICATION                              │
│                                                                       │
│  Auth          JWT (tymon/jwt-auth) · Socialite (Google/Facebook)    │
│  API Docs      L5-Swagger (darkaonline/l5-swagger)                   │
│  Queue         Laravel Queue + Redis driver                           │
│  Real-time     Laravel Broadcasting + Pusher/Soketi                  │
│  Mail          Laravel Mail + Mailtrap (dev) / SES (prod)            │
│  Storage       Laravel Filesystem (local / S3-compatible)            │
│  Payment       VNPay SDK / Momo API                                  │
│  Search        Laravel Scout + Meilisearch / MySQL FULLTEXT          │
│  Excel Export  Maatwebsite/Laravel-Excel (PhpSpreadsheet)            │
│  PDF Export    barryvdh/laravel-dompdf                               │
│  Testing       PHPUnit · Mockery · Laravel HTTP Tests                │
└──────────┬───────────────────────────────┬───────────────────────────┘
           │                               │
┌──────────▼──────────┐       ┌────────────▼────────────┐
│   MySQL 8.x         │       │   Redis 7.x              │
│                     │       │                          │
│   users             │       │  OTP (TTL 3 phút)        │
│   listings          │       │  JWT Blacklist           │
│   packages          │       │  Cache (Listings, etc.)  │
│   appointments      │       │  Queue Jobs              │
│   payments          │       │  Session                 │
│   messages          │       │  Rate Limiting           │
│   ...               │       └──────────────────────────┘
└─────────────────────┘
```

---

## 2. Backend — Laravel

### 2.1 Authentication & Authorization

| Chức năng | Công nghệ | Package | Ghi chú |
|-----------|-----------|---------|---------|
| Đăng ký tài khoản (email) | Laravel + bcrypt | *(built-in)* | ✅ Đang dùng |
| Đăng nhập email/password | JWT Auth | `tymon/jwt-auth` | ✅ Đang dùng |
| Đăng nhập Google | Laravel Socialite | `laravel/socialite` | ✅ Đang dùng |
| Đăng nhập Facebook | Laravel Socialite | `laravel/socialite` | Thêm driver |
| Quên / Reset mật khẩu | OTP + Redis | `predis/predis` | ✅ Đang dùng |
| Xác thực OTP đăng ký | Redis TTL | `predis/predis` | ✅ Đang dùng |
| Token Blacklist (Logout) | Redis Cache | `predis/predis` | ✅ Đang dùng |
| Phân quyền Role (User/Admin) | Laravel Gates + Policies | *(built-in)* | Cần bổ sung Policy |
| Rate Limiting (chống brute force) | Laravel RateLimiter | *(built-in)* | Thêm vào `RouteServiceProvider` |

```bash
# Đã cài
composer require tymon/jwt-auth laravel/socialite predis/predis
```

---

### 2.2 Tin đăng (Listing)

| Chức năng | Công nghệ | Package | Ghi chú |
|-----------|-----------|---------|---------|
| Tạo / Sửa / Xóa tin đăng | Laravel Eloquent | *(built-in)* | Dùng cùng Repository Pattern |
| Upload ảnh tin đăng | Laravel Filesystem | *(built-in)* | Local → có thể migrate S3 |
| Upload ảnh lên cloud | Amazon S3 / MinIO | `league/flysystem-aws-s3-v3` | Khuyến nghị dùng S3 |
| Resize / Optimize ảnh | Intervention Image | `intervention/image` | 🆕 Cần cài |
| Tìm kiếm full-text | Laravel Scout | `laravel/scout` | 🆕 Cần cài |
| Full-text engine | Meilisearch hoặc MySQL FULLTEXT | `meilisearch/meilisearch-php` | Meilisearch cho kết quả tốt hơn |
| Lọc tin đăng (Strategy) | Eloquent Builder | *(built-in)* | ListingQueryBuilder tự viết |
| Nhân đôi tin đăng | Eloquent replicate() | *(built-in)* | `$listing->replicate()->save()` |
| Phân trang | Laravel Paginator | *(built-in)* | Cursor pagination cho performance |
| Trạng thái tin (State Machine) | State Pattern tự implement | *(tự viết)* | Xem `system-design-patterns.md` |
| Geo-location / Bản đồ | Google Maps API | *(external API)* | Frontend embed |
| Slug URL (SEO) | Laravel Eloquent | `spatie/laravel-sluggable` | 🆕 Cần cài |

```bash
# Cần cài thêm
composer require intervention/image laravel/scout
composer require spatie/laravel-sluggable
composer require league/flysystem-aws-s3-v3
# Hoặc chạy Meilisearch bằng Docker
docker run -d -p 7700:7700 getmeili/meilisearch
```

---

### 2.3 Gói tin & Thanh toán (Package & Payment)

| Chức năng | Công nghệ | Package / API | Ghi chú |
|-----------|-----------|---------------|---------|
| Quản lý gói tin (CRUD Admin) | Eloquent + Repository | *(built-in)* | Theo pattern đã định |
| Thanh toán VNPay | VNPay SDK | `vinacms/vnpay` hoặc tự gọi API | 🆕 Cần cài |
| Thanh toán Momo | Momo Payment API | *(gọi HTTP trực tiếp)* | 🆕 REST API |
| Thanh toán ZaloPay | ZaloPay API | *(gọi HTTP trực tiếp)* | 🆕 REST API (tương lai) |
| Webhook xử lý callback | Laravel Route | *(built-in)* | Xác thực HMAC signature |
| Kích hoạt gói sau thanh toán | Laravel Queue + Event | *(built-in)* | `PaymentSucceeded` event |
| Lịch sử giao dịch | Eloquent + Repository | *(built-in)* | `payments` table |
| Hủy thanh toán (timeout) | Laravel Scheduler | *(built-in)* | `php artisan schedule:run` |
| Xuất hóa đơn PDF | DomPDF | `barryvdh/laravel-dompdf` | 🆕 Cần cài |
| Ghi doanh thu | Eloquent Events | *(built-in)* | Observer pattern |

```bash
# Cần cài thêm
composer require barryvdh/laravel-dompdf

# VNPay: dùng repo community hoặc tự implement theo docs chính thức
# https://sandbox.vnpayment.vn/apis/docs/thanh-toan-pay/api.html
```

> **Lưu ý VNPay:** Không có package chính thức, nên tự implement `VnPayGateway` theo `PaymentGateway` interface. Xem `system-design-patterns.md` §4.2 Strategy Pattern.

---

### 2.4 Lịch hẹn (Appointment)

| Chức năng | Công nghệ | Package | Ghi chú |
|-----------|-----------|---------|---------|
| Đặt / Sửa / Hủy lịch hẹn | Eloquent + State Pattern | *(built-in)* | State Machine tự viết |
| Xác nhận / Từ chối lịch | State Pattern | *(built-in)* | `AppointmentState` interface |
| Kiểm tra conflict lịch hẹn | Eloquent query | *(built-in)* | `whereBetween` thời gian |
| Nhắc lịch hẹn tự động | Laravel Scheduler + Notification | *(built-in)* | Cron job 24h trước |
| iCal / Calendar invite | ICS format | `eluceo/ical` | 🆕 Tùy chọn |
| Timezone xử lý | Carbon + Laravel Timezone | *(built-in)* | `Carbon::parse()->setTimezone()` |

```bash
# Tùy chọn - calendar invite
composer require eluceo/ical
```

---

### 2.5 Chat & Real-time

| Chức năng | Công nghệ | Package | Ghi chú |
|-----------|-----------|---------|---------|
| Chat real-time giữa User | Laravel Broadcasting | *(built-in)* | 🆕 Cần cài Pusher/Soketi |
| WebSocket server (dev) | Soketi (self-hosted) | `soketi/soketi` | Thay thế Pusher miễn phí |
| WebSocket server (prod) | Pusher hoặc Soketi | `pusher/pusher-php-server` | 🆕 Cần cài |
| Lưu lịch sử chat | MySQL `messages` table | Eloquent | `messages` table cần tạo |
| Online presence (User online/offline) | Redis + Echo | *(built-in + Pusher)* | Presence Channel |
| Đánh dấu đã đọc | Eloquent + Broadcasting | *(built-in)* | `read_at` column |

```bash
# Cài Pusher PHP SDK
composer require pusher/pusher-php-server

# Hoặc chạy Soketi local (Docker)
docker run -d -p 6001:6001 quay.io/soketi/soketi

# Frontend: cài Laravel Echo + Pusher JS
npm install laravel-echo pusher-js
```

---

### 2.6 Thông báo (Notification)

| Chức năng | Công nghệ | Package | Ghi chú |
|-----------|-----------|---------|---------|
| Gửi Email | Laravel Mail + SMTP | *(built-in)* | ✅ Đang dùng |
| Email template | Blade + Markdown | *(built-in)* | ✅ Đang dùng |
| Testing email (dev) | Mailtrap | *(external SMTP)* | ✅ Đang dùng |
| Production email | SendGrid / AWS SES | *(external SMTP)* | Khuyến nghị SES |
| Gửi SMS | Twilio hoặc ESMS (VN) | `twilio/sdk` hoặc HTTP API | 🆕 Tương lai |
| Gửi Zalo OA | Zalo OA API | *(HTTP API)* | 🆕 Tương lai |
| Push Notification (mobile) | Firebase FCM | `laravel-notification-channels/fcm` | 🆕 Tương lai |
| Queue mail (async) | Laravel Queue + Redis | *(built-in)* | ✅ Đang dùng (`queue()`) |
| Retry failed jobs | Laravel Queue | *(built-in)* | `--tries=3 --backoff=5` |

---

### 2.7 Admin — Doanh thu & Báo cáo

| Chức năng | Công nghệ | Package | Ghi chú |
|-----------|-----------|---------|---------|
| Dashboard thống kê | Eloquent aggregates | *(built-in)* | `sum()`, `count()`, `groupBy()` |
| Biểu đồ dữ liệu | Trả về JSON → Chart.js | *(built-in)* | Frontend render |
| Xuất Excel | Laravel Excel | `maatwebsite/excel` | 🆕 Cần cài |
| Xuất PDF | DomPDF | `barryvdh/laravel-dompdf` | 🆕 Cần cài |
| Xuất CSV | `fputcsv` PHP native | *(built-in)* | Streamed response |
| Lịch sử giao dịch filter | Eloquent + Query Builder | *(built-in)* | Nhiều filter phức tạp |
| Cache Dashboard data | Redis Cache | `predis/predis` | ✅ Đang có Redis |

```bash
composer require maatwebsite/excel
composer require barryvdh/laravel-dompdf
```

---

### 2.8 Storage & Media

| Chức năng | Công nghệ | Package | Ghi chú |
|-----------|-----------|---------|---------|
| Lưu ảnh local (dev) | Laravel Filesystem | *(built-in)* | `storage/app/public` |
| Lưu ảnh production | AWS S3 / MinIO | `league/flysystem-aws-s3-v3` | Khuyến nghị |
| Resize ảnh thumbnail | Intervention Image | `intervention/image` | Tự động khi upload |
| Xóa ảnh cũ | Storage::delete() | *(built-in)* | Gọi khi update/delete listing |
| CDN cho ảnh | Cloudflare R2 (S3-compatible) | *(S3 driver)* | Rẻ hơn AWS S3 |

---

### 2.9 API docs & Testing

| Chức năng | Công nghệ | Package | Ghi chú |
|-----------|-----------|---------|---------|
| API Documentation | Swagger/OpenAPI | `darkaonline/l5-swagger` | ✅ Đang dùng |
| Unit Testing | PHPUnit | `phpunit/phpunit` | ✅ Đang dùng |
| Mocking | Mockery | `mockery/mockery` | ✅ Đang dùng |
| Feature Testing | Laravel HTTP Tests | *(built-in)* | `$this->postJson()` |
| Fake Queue/Mail trong test | Laravel Test Helpers | *(built-in)* | `Queue::fake()`, `Mail::fake()` |
| Test Database | SQLite in-memory | *(built-in)* | `RefreshDatabase` trait |
| API Client (dev) | Postman / Bruno | *(external tool)* | Export từ Swagger |

---

## 3. Frontend — Vue 3 (Customer)

### 3.1 Core Framework

| Chức năng | Công nghệ | Package | Trạng thái |
|-----------|-----------|---------|-----------|
| UI Framework | Vue 3 (Composition API) | `vue@^3.5` | ✅ Đang dùng |
| Build tool | Vite | `vite@^8` | ✅ Đang dùng |
| CSS Framework | Tailwind CSS v4 | `tailwindcss@^4` | ✅ Đang dùng |
| State Management | Pinia | `pinia@^3` | ✅ Đang dùng |
| Routing | Vue Router | `vue-router@^5` | ✅ Đang dùng |
| HTTP client | Axios | `axios@^1.13` | ✅ Đang dùng |
| Icons | Lucide Vue | `lucide-vue-next` | ✅ Đang dùng |
| Date handling | Day.js | `dayjs@^1` | ✅ Đang dùng |
| Composables | VueUse | `@vueuse/core@^14` | ✅ Đang dùng |
| JWT Decode | jwt-decode | `jwt-decode@^4` | ✅ Đang dùng |

### 3.2 Chức năng cần thêm package

| Chức năng | Package đề xuất | Lý do |
|-----------|-----------------|-------|
| **Bản đồ & Vị trí** | `@vue-leaflet/vue-leaflet` hoặc Google Maps | Hiển thị vị trí tin đăng trên map |
| **Carousel ảnh tin đăng** | `swiper` (Swiper.js) | Trình chiếu ảnh listing |
| **Form validation** | `vee-validate` + `yup` | Validate form tạo/sửa tin đăng phức tạp |
| **Rich text editor** | `@tiptap/vue-3` | Mô tả tin đăng có format |
| **Infinite scroll** | `@vueuse/core` (đã có) | `useInfiniteScroll` từ VueUse |
| **Toast notification** | `vue-sonner` hoặc `vue-toastification` | Thông báo UX |
| **Real-time Chat** | `laravel-echo` + `pusher-js` | WebSocket cho chat |
| **File upload preview** | `@vueuse/core` (đã có) | `useObjectUrl` cho preview ảnh |
| **Lazy load ảnh** | `@vueuse/core` (đã có) | `useIntersectionObserver` |
| **Date picker lịch hẹn** | `@vuepic/vue-datepicker` | Chọn ngày giờ hẹn |
| **QR Code thanh toán** | `qrcode.vue` | Hiển thị QR cho Momo/ZaloPay |
| **Loading skeleton** | CSS thuần hoặc `vue-content-loader` | UX khi đang load data |
| **SEO / Meta tags** | `@vueuse/head` | Meta tag động theo trang |
| **Chart Dashboard** | `chart.js` + `vue-chartjs` | (Nếu có chart cho user) |

```bash
# Cài các package cần thiết
npm install swiper @tiptap/vue-3 @tiptap/pm @tiptap/starter-kit
npm install vee-validate yup
npm install vue-sonner
npm install laravel-echo pusher-js
npm install @vuepic/vue-datepicker
npm install @vueuse/head
npm install qrcode.vue
```

### 3.3 Mapping chức năng → Package (Frontend Customer)

| Nhóm chức năng | Package chính |
|----------------|---------------|
| **Auth** (Login/Register/OTP) | Axios · Pinia (`auth.js` store) · vue-router guards |
| **Google OAuth redirect** | `window.location` redirect → Laravel Socialite |
| **Danh sách tin đăng** | Axios · Pinia · `useInfiniteScroll` (VueUse) |
| **Bộ lọc tin đăng** | Vue Router query params · Pinia |
| **Tìm kiếm** | Axios debounce · `useDebounceFn` (VueUse) |
| **Chi tiết tin đăng** | Swiper (ảnh) · `@vueuse/head` (SEO) · Leaflet (map) |
| **Tạo tin đăng** | Vee-validate · Tiptap (editor) · File upload preview |
| **Thanh toán VNPay/Momo** | Redirect URL · QR code (`qrcode.vue`) |
| **Chat** | Laravel Echo · Pusher JS · Real-time updates |
| **Lịch hẹn** | Vue Datepicker · Axios · Pinia |
| **Tin yêu thích** | Pinia (local state) · Axios (persist) |
| **Thông báo Toast** | `vue-sonner` |
| **Profile / Sửa thông tin** | Vee-validate · Axios · File upload |

---

## 4. Frontend — Vue 3 (Admin)

### 4.1 Thêm UI Component Library cho Admin

Khuyến nghị dùng **PrimeVue** hoặc **Element Plus** cho Admin panel vì:
- Có sẵn DataTable, Dialog, Charts, Form components
- Phù hợp với dashboard dữ liệu phức tạp hơn

```bash
# Trong PropifyAdmin
npm install primevue primeicons  # Lựa chọn 1
# HOẶC
npm install element-plus @element-plus/icons-vue  # Lựa chọn 2
```

### 4.2 Mapping chức năng Admin → Package

| Chức năng Admin | Package đề xuất | Ghi chú |
|-----------------|-----------------|---------|
| **Dashboard thống kê** | `chart.js` + `vue-chartjs` | ✅ Xem `RevenueBarChart.vue` đang dùng |
| **Bảng dữ liệu listing/user** | PrimeVue DataTable hoặc `tanstack/vue-table` | Sort, filter, paginate |
| **Duyệt / Từ chối tin đăng** | Axios · Dialog component · Toast |
| **Quản lý gói tin** | Form validation · PrimeVue InputNumber |
| **Xuất Excel/PDF** | *(Backend xử lý)* · `axios responseType: 'blob'` | Download file |
| **Biểu đồ doanh thu** | `chart.js` + `vue-chartjs` | Bar, Line, Pie charts |
| **Quản lý tài khoản** | PrimeVue DataTable · Badge status |
| **Quản lý lịch hẹn** | `@fullcalendar/vue3` (tùy chọn) | Calendar view |
| **Real-time notifications** | Laravel Echo · Pusher JS | Admin nhận alert mới |

```bash
# Admin packages
npm install vue-chartjs chart.js
npm install primevue primeicons
npm install @tanstack/vue-table
npm install laravel-echo pusher-js
npm install @fullcalendar/vue3 @fullcalendar/daygrid  # tùy chọn
```

---

## 5. Infrastructure & DevOps

### 5.1 Server & Deployment

| Thành phần | Công nghệ | Ghi chú |
|------------|-----------|---------|
| **Web server** | Nginx | ✅ Đang dùng |
| **PHP runtime** | PHP 8.2 FPM | ✅ Đang dùng |
| **Database** | MySQL 8.x | ✅ Đang dùng |
| **Cache / Queue / OTP** | Redis 7.x | ✅ Đang dùng |
| **Queue worker** | Laravel Queue (Redis driver) | ✅ Đang dùng (`queue:listen`) |
| **Scheduler** | Laravel Scheduler + Cron | Cần setup `crontab` trên server |
| **Process manager** | Supervisor | Giữ queue worker chạy liên tục |
| **SSL** | Let's Encrypt / Certbot | ✅ Đang dùng (duckdns.org) |
| **CI/CD** | GitHub Actions hoặc Webhook | ✅ Đang dùng (deploy.sh) |
| **WebSocket server** | Soketi (Docker) hoặc Pusher | Khi thêm Chat / Real-time |

### 5.2 Supervisor config cho Queue Worker

```ini
; /etc/supervisor/conf.d/propify-worker.conf
[program:propify-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/Propify/PropifyBackend/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/propify-worker.log
stopwaitsecs=3600
```

### 5.3 Laravel Scheduler (Cron)

```bash
# Thêm vào crontab của server
* * * * * cd /var/www/Propify/PropifyBackend && php artisan schedule:run >> /dev/null 2>&1
```

```php
// app/Console/Kernel.php — các job scheduled
Schedule::command('listings:expire-packages')       // Hết hạn gói tin mỗi ngày
         ->dailyAt('01:00');

Schedule::command('appointments:send-reminders')    // Nhắc lịch hẹn
         ->hourly();

Schedule::command('payments:cancel-pending')        // Hủy thanh toán timeout
         ->everyFifteenMinutes();
```

### 5.4 Biến môi trường cần thêm

```dotenv
# .env — cần bổ sung khi tích hợp thêm công nghệ

# VNPay
VNPAY_TMN_CODE=
VNPAY_HASH_SECRET=
VNPAY_URL=https://sandbox.vnpayment.vn/paymentv2/vpcpay.html
VNPAY_RETURN_URL="${APP_URL}/api/payment/vnpay/callback"

# Momo
MOMO_PARTNER_CODE=
MOMO_ACCESS_KEY=
MOMO_SECRET_KEY=
MOMO_ENDPOINT=https://test-payment.momo.vn/v2/gateway/api/create

# Pusher / Soketi (Real-time Chat)
BROADCAST_DRIVER=pusher
PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_HOST=127.0.0.1   # Soketi self-hosted
PUSHER_PORT=6001
PUSHER_SCHEME=http

# AWS S3 / Cloudflare R2 (File Storage)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=ap-southeast-1
AWS_BUCKET=propify-assets
AWS_ENDPOINT=           # Cloudflare R2 endpoint nếu dùng R2

# Meilisearch (Full-text search)
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=http://localhost:7700
MEILISEARCH_KEY=

# Mail Production
MAIL_MAILER=ses         # Hoặc smtp
MAIL_FROM_ADDRESS=noreply@propify.vn

# Twilio (SMS - tương lai)
TWILIO_SID=
TWILIO_TOKEN=
TWILIO_FROM=
```

---

## 6. Bảng ánh xạ chức năng → công nghệ

### 6.1 Khách hàng

| # | Chức năng | Backend | Frontend | Infrastructure |
|---|-----------|---------|----------|----------------|
| 1 | **Đăng ký tài khoản** | Laravel Auth · bcrypt · OTP Redis | Vee-validate · Pinia auth store | Redis TTL |
| 2 | **Đăng nhập (Email)** | JWT + tymon/jwt-auth | Axios · jwt-decode · Pinia | Redis Blacklist |
| 3 | **Đăng nhập Google** | Socialite · GoogleSocialiteAdapter | Redirect thường (không SPA oauth) | — |
| 4 | **Đăng nhập Facebook** | Socialite (thêm driver) | Redirect thường | — |
| 5 | **Xem thông tin tài khoản** | `GET /api/user/me` | Pinia auth store · Axios | — |
| 6 | **Chỉnh sửa thông tin cá nhân** | FormRequest · UserRepository | Vee-validate · File upload (avatar) | Storage::put() |
| 7 | **Đổi mật khẩu** | bcrypt · Hash::check() | Vee-validate | — |
| 8 | **Quên mật khẩu** | OTP Redis · NotificationService | Form + OTP input | Redis TTL 3 phút |
| 9 | **Tạo tin đăng** | ListingRepository · StorageAdapter | Tiptap editor · Swiper preview · Vee-validate | S3 / Local upload |
| 10 | **Xem chi tiết tin đăng** | ListingRepository · Cache | `@vueuse/head` (SEO) · Swiper · Leaflet (map) | Redis cache 5 phút |
| 11 | **Xem danh sách tin đăng** | Scout search · Paginator | InfiniteScroll (VueUse) · Skeleton loader | Meilisearch |
| 12 | **Chỉnh sửa tin đăng** | ListingRepository · StorageAdapter | Tiptap · Vee-validate · Image re-upload | S3 |
| 13 | **Nhân đôi tin đăng** | `$listing->replicate()` | 1-click button · Toast confirm | — |
| 14 | **Bộ lọc tin đăng** | ListingQueryBuilder · Strategy filters | Vue Router query · Reactive filters UI | — |
| 15 | **Tìm kiếm tin đăng** | Laravel Scout · Meilisearch | `useDebounceFn` · Search bar | Meilisearch |
| 16 | **Xóa tin đăng** | SoftDelete · ListingRepository | Confirm dialog · Toast | — |
| 17 | **Nâng cấp gói tin** | PackageRepository · PaymentGateway | Package selection UI | — |
| 18 | **Thanh toán** | VnPayGateway / MomoGateway | Redirect / QR code (`qrcode.vue`) | HTTPS required |
| 19 | **Xác thực tin đăng** | State Pattern (Pending→Approved) | Status badge · Toast | — |
| 20 | **Tin đăng yêu thích** | `favorites` pivot table | Pinia favorites store · Heart toggle | — |
| 21 | **Chat với người đăng** | Laravel Broadcasting · `messages` table | Laravel Echo · Pusher JS · Chat UI | Soketi / Pusher |
| 22 | **Đặt lịch hẹn** | AppointmentRepository · State | Vue Datepicker · Time slot selector | — |
| 23 | **Xem thông tin lịch hẹn** | AppointmentRepository | Calendar view · Status badge | — |
| 24 | **Sửa lịch hẹn** | State: PENDING only | Vue Datepicker | — |
| 25 | **Hủy lịch hẹn** | State: cancel() · Event fired | Confirm dialog | Queue notify |
| 26 | **Xác nhận lịch hẹn** | State: confirm() · Event fired | Button + Toast | Queue notify |
| 27 | **Từ chối lịch hẹn** | State: decline() · reason | Modal + text input | Queue notify |
| 28 | **Tìm kiếm lịch sử giao dịch** | PaymentRepository · query | Search input · filter | — |
| 29 | **Lọc lịch sử giao dịch** | PaymentRepository · filter | Date range picker · Status filter | — |
| 30 | **Xem chi tiết lịch sử** | PaymentRepository::findById() | Transaction detail modal | — |
| 31 | **Hủy thanh toán** | Payment State · Scheduler timeout | Cancel button (trong TTL) | Queue job |

### 6.2 Admin

| # | Chức năng | Backend | Frontend | Infrastructure |
|---|-----------|---------|----------|----------------|
| 1 | **Dashboard** | Eloquent aggregates · Redis cache | Chart.js + vue-chartjs · Stats cards | Redis cache 5 phút |
| 2 | **Tìm kiếm tin đăng** | Scout · MySQL LIKE | Search input | Meilisearch |
| 3 | **Lọc tin đăng** | ListingQueryBuilder | DataTable filters (PrimeVue) | — |
| 4 | **Xem danh sách tin đăng** | ListingRepository · paginate | PrimeVue DataTable | — |
| 5 | **Xem chi tiết tin đăng** | ListingRepository::findById() | Detail panel / modal | — |
| 6 | **Duyệt tin đăng** | State: approve() · Event | Approve button · Toast | Queue: notify poster |
| 7 | **Từ chối tin đăng** | State: reject() + reason · Event | Modal + reason input | Queue: notify poster |
| 8 | **Khóa tin đăng** | State: lock() · Event | Lock button · Confirm dialog | — |
| 9 | **Xác thực tin đăng** | State: verify() | Verify badge | — |
| 10 | **Thêm gói tin** | PackageRepository::create() | Form (PrimeVue) · Vee-validate | — |
| 11 | **Khóa/Kích hoạt gói tin** | PackageRepository::toggleStatus() | Toggle switch | — |
| 12 | **Chỉnh sửa gói tin** | PackageRepository::update() | Inline edit / Form modal | — |
| 13 | **Tìm kiếm gói tin** | Package query | Search input | — |
| 14 | **Lọc gói tin** | Package filter | Filter chips | — |
| 15 | **Xem thông tin gói tin** | PackageRepository | DataTable | — |
| 16 | **Xem chi tiết gói tin** | PackageRepository::findById() | Detail modal | — |
| 17 | **Tìm kiếm tài khoản** | UserRepository · query | Search | — |
| 18 | **Lọc tài khoản** | UserRepository · filter | Filter by role/status | — |
| 19 | **Xem thông tin tài khoản** | UserRepository | DataTable | — |
| 20 | **Xem chi tiết tài khoản** | UserRepository::findById() | Detail panel | — |
| 21 | **Khóa tài khoản** | UserRepository · status update | Toggle · Confirm | — |
| 22 | **Mở khóa tài khoản** | UserRepository · status update | Toggle | — |
| 23 | **Thêm tiện ích** | AmenityRepository::create() | Form | — |
| 24 | **Sửa tiện ích** | AmenityRepository::update() | Inline edit | — |
| 25 | **Cài đặt hiển thị tiện ích** | `amenities.is_visible` flag | Toggle switch | — |
| 26 | **Xem doanh thu** | Revenue query · groupBy tháng | vue-chartjs · date range filter | Redis cache |
| 27 | **Xuất báo cáo** | Laravel Excel / DomPDF | Download button · `responseType: blob` | Queue (Excel lớn) |

---

## 7. Lộ trình cài đặt gói

### Phase 1 — Ngay bây giờ (Core improvements)

```bash
# Backend
composer require intervention/image         # Resize ảnh
composer require spatie/laravel-sluggable   # SEO slug cho listing
composer require barryvdh/laravel-dompdf    # Export PDF

# Frontend (Customer)
npm install swiper                          # Carousel ảnh
npm install vee-validate yup                # Form validation
npm install vue-sonner                      # Toast notifications
npm install @vueuse/head                    # SEO meta tags

# Admin
npm install vue-chartjs chart.js            # Charts (dashboard)
```

### Phase 2 — Listing Module

```bash
# Backend
composer require laravel/scout              # Full-text search
# Chạy Meilisearch bằng Docker
docker run -d -p 7700:7700 getmeili/meilisearch:latest

# Frontend
npm install @tiptap/vue-3 @tiptap/pm @tiptap/starter-kit  # Rich text
npm install @vuepic/vue-datepicker          # Date picker lịch hẹn
```

### Phase 3 — Payment Module

```bash
# Backend (VNPay tự implement theo interface)
# Tạo VnPayGateway theo PaymentGateway interface
# Docs: https://sandbox.vnpayment.vn/apis

# Frontend
npm install qrcode.vue                      # QR code Momo
```

### Phase 4 — Chat & Real-time

```bash
# Backend
composer require pusher/pusher-php-server   # Pusher SDK

# Docker: Soketi (Pusher-compatible, self-hosted)
docker run -d \
  -p 6001:6001 \
  -e SOKETI_DEFAULT_APP_ID=propify \
  -e SOKETI_DEFAULT_APP_KEY=propify-key \
  -e SOKETI_DEFAULT_APP_SECRET=propify-secret \
  quay.io/soketi/soketi:latest-16-alpine

# Frontend (cả Customer và Admin)
npm install laravel-echo pusher-js
```

### Phase 5 — Production Storage & Export

```bash
# Backend
composer require league/flysystem-aws-s3-v3  # S3 / Cloudflare R2
composer require maatwebsite/excel            # Export Excel lớn
```

---

## Phụ lục: So sánh lựa chọn công nghệ

### Full-text Search

| Option | Ưu điểm | Nhược điểm | Khuyến nghị |
|--------|---------|-----------|-------------|
| **MySQL FULLTEXT** | Không cần cài thêm gì | Tiếng Việt không có stemming | Dev/Small scale |
| **Meilisearch** | Hỗ trợ tiếng Việt, typo-tolerant, nhanh | Cần Docker riêng | **Production** ✅ |
| **Algolia** | Managed, mạnh | Có phí | Khi scale lớn |

### WebSocket

| Option | Ưu điểm | Nhược điểm | Khuyến nghị |
|--------|---------|-----------|-------------|
| **Soketi** | Miễn phí, self-hosted, Pusher-compatible | Tự quản lý | **Dev + Small Prod** ✅ |
| **Pusher** | Managed, reliable | Có phí (free 200 connections) | Prod khi scale |
| **Laravel Reverb** | Built-in Laravel, miễn phí | Mới, ít tài liệu | Alternative tốt |

### File Storage

| Option | Ưu điểm | Nhược điểm | Khuyến nghị |
|--------|---------|-----------|-------------|
| **Local Storage** | Đơn giản | Không scale, mất khi redeploy | Dev only |
| **Cloudflare R2** | Rẻ (miễn phí 10GB), S3-compatible | Cần config | **Production** ✅ |
| **AWS S3** | Chuẩn industry | Đắt hơn R2 | Khi cần ecosystem AWS |

### Payment Gateway (Việt Nam)

| Option | Ưu điểm | Nhược điểm | Khuyến nghị |
|--------|---------|-----------|-------------|
| **VNPay** | Phổ biến nhất VN, có sandbox | API docs tiếng Việt khó đọc | **Phase 1** ✅ |
| **Momo** | QR code phổ biến | Cần tài khoản doanh nghiệp | **Phase 1** ✅ |
| **ZaloPay** | Phổ biến TP.HCM | Ít phổ biến hơn | Phase 2 |

---

*Tài liệu này được tạo dựa trên phân tích Product Backlog (BẤT ĐỘNG SẢN.xlsx) và stack kỹ thuật thực tế của dự án Propify.*  
*Tham chiếu: `system-design-patterns.md` · `adapter-pattern-analysis.md`*
