---
status: active
phase: verification
implementation-approved: true
last-updated: 2026-11-13
---

# SePay Test Mode cho nâng cấp gói

## Checkout Sandbox

- Đã được yêu cầu chuyển đơn mới sang checkout Sandbox có IPN.
- Ký form trên Laravel, chuyển trình duyệt bằng POST và hiển thị trạng thái đơn khi quay lại.
- IPN xác thực bằng khóa riêng, khớp mã đơn và số tiền rồi dùng lại dịch vụ kích hoạt gói.
- Giữ webhook Test Mode để xử lý các đơn mô phỏng đã tạo.
- Trong Cổng thanh toán Sandbox, lấy Merchant ID và Secret Key của đơn vị. Ở tab IPN chọn `SECRET_KEY`, đặt khóa riêng và URL HTTPS của ngrok có đuôi `/api/v1/payments/sepay/ipn`.
- Điền `SEPAY_SANDBOX_MERCHANT_ID`, `SEPAY_SANDBOX_MERCHANT_SECRET` và `SEPAY_SANDBOX_IPN_SECRET` vào backend. Chạy migration và `php artisan config:clear`.
- Đã build frontend, chạy migration MySQL local và kiểm tra checkout/IPN bằng dữ liệu giả trong transaction rollback. Còn kiểm tra với bộ khóa Sandbox thật và IPN từ SePay.

- Dùng Test Mode Webhooks đã thiết lập, hiển thị mã đơn và số tiền để mô phỏng.
- Dùng SePay cho mọi đơn nâng cấp mới. Gỡ tích hợp VNPAY và giữ dữ liệu lịch sử giao dịch.
- Xác minh HMAC, khớp tiền vào, tài khoản, VA, mã đơn và hạn. Hoàn tất qua dịch vụ hiện có với khóa hàng và ID SePay duy nhất.
- Kiểm tra build frontend, PHP và thử từ Dashboard sau khi triển khai.
- Bắt đầu từ 519db06. PropifyAdmin/src/pages/Packages/EditPage.vue có thay đổi từ trước.

## Triển khai và kiểm thử

### Kiểm tra auto-deploy sau khi push

Trong GitHub, mở **Settings → Webhooks**, chọn webhook triển khai và kiểm tra **Active** cùng sự kiện **Just the push event**. Sau khi push commit mới lên `main`, mở **Recent Deliveries** để xem sự kiện `push` và mã HTTP trả về.

Trên server, đối chiếu commit đang chạy với commit đã push và xem kết quả build, migration trong log:

```bash
git -C /var/www/Propify rev-parse --short HEAD
tail -n 50 /var/www/Propify/deploy.log
```

### Chạy local với Docker

Mở Docker Desktop. Chạy từ thư mục gốc:

```powershell
rtk proxy docker compose up -d --build
rtk proxy docker compose exec app php artisan config:clear
rtk proxy docker compose exec app php artisan migrate
```

Frontend ở `http://localhost:5173`, API ở `http://localhost:8000/api`. Compose kết nối Laravel với MySQL và Redis nội bộ, lưu dữ liệu trong Docker volume. Nếu cơ sở dữ liệu local mới và chưa có gói tin, chạy `rtk proxy docker compose exec app php artisan db:seed --class=PackageSeeder`.

Sau khi điền cấu hình SePay vào `PropifyBackend/.env`, chạy lại `rtk proxy docker compose exec app php artisan config:clear`. Mở `ngrok http 8000` rồi đăng ký URL HTTPS của ngrok với đuôi `/api/v1/payments/sepay/webhook` trong SePay Test Mode.

### Cấu hình SePay

Sau khi triển khai backend và frontend, chạy migration. Cấu hình backend:

```dotenv
SEPAY_TEST_ACCOUNT="0000000001"
SEPAY_TEST_VA="số VA Test Mode bắt đầu bằng SBSEPAY"
SEPAY_WEBHOOK_SECRET="Secret Key từ cấu hình webhook SePay"
```

Chạy `php artisan migrate --force` và `php artisan config:cache` từ thư mục backend.

Trong SePay Test Mode, tạo webhook JSON cho sự kiện tiền vào và đúng tài khoản/VA. Đặt URL thành `https://propifyy.duckdns.org/api/v1/payments/sepay/webhook` và chọn HMAC-SHA256. Tắt bộ lọc tiền tố và yêu cầu có mã thanh toán. Backend khớp toàn bộ nội dung chuyển khoản với mã đơn hiển thị trên Meyland.

Các cấu hình Webhook Test Mode ở trên chỉ áp dụng cho các đơn mô phỏng cũ. Đơn mới dùng Checkout Sandbox theo phần đầu tài liệu.

- [Xác thực HMAC của SePay](https://developer.sepay.vn/en/sepay-webhooks/xac-thuc)
- [Cấu hình webhook Test Mode](https://developer.sepay.vn/en/tien-ich-khac/test-mode/tao-webhook)

## Kết quả kiểm tra

- Build frontend thành công.
- PHPUnit chạy 6 test SePay/VNPAY với 30 assertion. Khi chạy trên SQLite, tạm bỏ qua câu lệnh `ALTER TABLE ... MODIFY` dành cho MySQL trong migration cũ của bảng messages. Đã khôi phục file migration sau khi chạy.
- Còn triển khai và thử webhook thực từ dashboard SePay Test Mode.
