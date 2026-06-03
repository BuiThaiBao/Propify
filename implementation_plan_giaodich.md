# Kế hoạch triển khai: Lịch sử giao dịch Admin & Nghiệp vụ kế toán (Nâng cấp)

## Bối cảnh & Nghiệp vụ kế toán chuyên sâu

Hệ thống được thiết kế và siết chặt để đáp ứng các tiêu chuẩn nghiệp vụ kế toán thực tế:
1. **Bảo toàn dữ liệu (Immutability)**:
   - Nghiêm cấm các thao tác xóa hoặc sửa đổi thông tin tài chính (số tiền, gói tin, trạng thái, ngày thanh toán).
   - Enforce ở lớp API: Chỉ cung cấp route `GET` (danh sách, chi tiết) và `POST` (tạo ghi chú). Không khai báo route `PUT`, `DELETE` hay `PATCH` làm thay đổi thông tin giao dịch.
   - Enforce ở lớp Model: Cấu hình `$guarded` nghiêm ngặt trên model `Transaction` để tránh lỗ hổng Mass Assignment.
2. **Audit Trail Note chuẩn chỉnh**:
   - Thay vì ghi đè trực tiếp lên cột `note` của bảng `transactions` (làm mất lịch sử và không rõ ai sửa lúc nào), ta sẽ xây dựng bảng riêng `transaction_notes` để lưu lịch sử ghi chú chi tiết: ai tạo, vào lúc nào, nội dung gì.
3. **Thống kê Doanh thu & Đối soát**:
   - Tính toán doanh thu động dựa trên bộ lọc:
     - **Tổng Doanh Thu Thành Công**: Tổng số tiền của các giao dịch `SUCCESS` thỏa mãn bộ lọc hiện tại (bỏ qua điều kiện lọc `status` của bộ lọc để kế toán luôn biết được doanh thu thực tế thu về của nhóm đối tượng đang được lọc).
     - **Số giao dịch**: Thống kê động số lượng SUCCESS, PENDING, FAILED theo bộ lọc.
4. **Hiệu năng cơ sở dữ liệu**:
   - Thêm index cho các trường thường dùng để lọc và tìm kiếm nhằm tối ưu hóa các câu truy vấn join lớn.
5. **Xuất báo cáo (Export CSV)**:
   - Hỗ trợ xuất **toàn bộ dữ liệu thỏa mãn bộ lọc**, không chỉ các dòng hiển thị trên trang hiện tại.

---

## Thiết kế Cơ sở Dữ liệu

### [NEW] Bảng `transaction_notes`
Bảng lưu vết ghi chú kế toán nội bộ:
- `id`: Primary key
- `transaction_id`: Khóa ngoại liên kết `transactions.id` (on delete cascade)
- `admin_id`: Khóa ngoại liên kết `users.id` (người thực hiện ghi chú)
- `note`: Nội dung ghi chú (text)
- `created_at`: Thời điểm ghi chú

### [NEW] Migration tạo bảng & bổ sung index
Tạo file migration để xây dựng bảng `transaction_notes` và tạo indexes cho bảng `transactions`.

---

## Proposed Changes

### 1. Backend (PropifyBackend)

#### [NEW] [2026_06_03_230000_create_transaction_notes_and_add_indexes.php](file:///d:/PROJECT/Meyland/PropifyBackend/database/migrations/2026_06_03_230000_create_transaction_notes_and_add_indexes.php)
Migration xây dựng cấu trúc DB mới:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tạo bảng transaction_notes
        Schema::create('transaction_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained('transactions')->onDelete('cascade');
            $table->foreignId('admin_id')->constrained('users');
            $table->text('note');
            $table->timestamp('created_at')->useCurrent();
        });

        // 2. Thêm indexes tối ưu hiệu năng cho transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->index('status');
            $table->index('payment_method');
            $table->index('package_id');
            $table->index('transaction_date');
            $table->index('amount');
            $table->index('vnp_txn_ref');
            $table->index('vnp_transaction_no');
            $table->index(['status', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_notes');

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['payment_method']);
            $table->dropIndex(['package_id']);
            $table->dropIndex(['transaction_date']);
            $table->dropIndex(['amount']);
            $table->dropIndex(['vnp_txn_ref']);
            $table->dropIndex(['vnp_transaction_no']);
            $table->dropIndex(['status', 'transaction_date']);
        });
    }
};
```

#### [NEW] [AdminMiddleware.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Middleware/AdminMiddleware.php)
Middleware xác thực quyền Admin tập trung:
```php
<?php

namespace App\Http\Middleware;

use App\Enums\ErrorCode;
use App\Enums\UserRole;
use App\Exceptions\BusinessException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class AdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user() || $request->user()->role !== UserRole::Admin) {
            throw new BusinessException(ErrorCode::AuthForbidden);
        }

        return $next($request);
    }
}
```

#### [MODIFY] [app.php](file:///d:/PROJECT/Meyland/PropifyBackend/bootstrap/app.php)
Đăng ký alias `admin` middleware:
```diff
     ->withMiddleware(function (Middleware $middleware): void {
-        //
+        $middleware->alias([
+            'admin' => \App\Http\Middleware\AdminMiddleware::class,
+        ]);
     })
```

#### [NEW] [TransactionNote.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Models/TransactionNote.php)
Model quản lý ghi chú giao dịch:
- `belongsTo(User::class, 'admin_id')`
- `belongsTo(Transaction::class, 'transaction_id')`

#### [MODIFY] [Transaction.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Models/Transaction.php)
Siết chặt Mass Assignment và thiết lập liên kết `hasMany(TransactionNote::class)`:
```diff
-    protected $fillable = [
-        'user_id',
-        'listing_id',
-        'package_id',
-        'amount',
-        'duration_days',
-        'payment_method',
-        'status',
-        'transaction_date',
-        'expires_at',
-        'vnp_txn_ref',
-        'vnp_transaction_no',
-        'vnp_bank_code',
-        'vnp_response_code',
-        'vnp_pay_date',
-    ];
+    // Siết chặt immutability, chỉ cho phép gán ban đầu, hạn chế cập nhật
+    protected $guarded = [
+        'id',
+        'amount',
+        'package_id',
+        'user_id',
+        'listing_id',
+        'status',
+        'transaction_date',
+    ];

+    /** Lịch sử các ghi chú của giao dịch */
+    public function notes(): \Illuminate\Database\Eloquent\Relations\HasMany
+    {
+        return $this->hasMany(TransactionNote::class, 'transaction_id')->latest();
+    }
```

#### [NEW] [TransactionResource.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Resources/TransactionResource.php)
Resource định dạng bảo mật thông tin nhạy cảm:
- `user`: compact `{ id, full_name, email, phone }`
- `listing`: compact `{ id, title }`
- `package`: compact `{ id, name }`
- `notes`: Collection của `TransactionNote` (chứa id, note, created_at, admin name)

#### [NEW] [AdminTransactionController.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Controllers/Api/V1/Admin/AdminTransactionController.php)
- **`index(Request $request)`**:
  - Validate filter params.
  - Sử dụng base query và `clone` query an toàn để tính thống kê và phân trang.
  - Thống kê `total_revenue`: Luôn tính tổng tiền của các giao dịch `SUCCESS` thỏa mãn bộ lọc (bỏ qua điều kiện lọc `status` của bộ lọc).
- **`show($id)`**: Trả về chi tiết giao dịch cùng lịch sử notes.
- **`storeNote(Request $request, $id)`**: Thêm ghi chú mới vào bảng `transaction_notes` lưu vết admin_id.
- **`export(Request $request)`**: Trả về stream file CSV chứa toàn bộ kết quả lọc (không phân trang).

#### [MODIFY] [api.php](file:///d:/PROJECT/Meyland/PropifyBackend/routes/api.php)
Khai báo route được bảo vệ bởi middleware `admin` và dùng `whereNumber('id')`:
```php
Route::prefix('v1/admin')->as('admin.')->middleware(['auth:api', 'admin'])->group(function () {
    // ... các route admin cũ ...
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/export', [AdminTransactionController::class, 'export'])->name('transactions.export');
    Route::get('/transactions/{id}', [AdminTransactionController::class, 'show'])->whereNumber('id')->name('transactions.show');
    Route::post('/transactions/{id}/notes', [AdminTransactionController::class, 'storeNote'])->whereNumber('id')->name('transactions.store-note');
});
```

---

### 2. Frontend (PropifyAdmin)

#### [NEW] [useTransactionApi.js](file:///d:/PROJECT/Meyland/PropifyAdmin/src/composables/useTransactionApi.js)
Composable tương tác API giao dịch:
- `fetchTransactions(params)`
- `fetchTransaction(id)`
- `storeNote(id, note)`
- `exportUrl(params)`: Trả về link API export CSV với query params.

#### [NEW] [Page.vue](file:///d:/PROJECT/Meyland/PropifyAdmin/src/pages/Transactions/Page.vue)
Trang quản lý giao dịch của Admin:
- **Tổng quan Thống kê**: Doanh thu thành công, Giao dịch thành công, Giao dịch đang chờ/lỗi.
- **Bộ lọc & Tìm kiếm nâng cao**:
  - Tìm kiếm Debounced (300-500ms).
  - Lọc Trạng thái, Gói tin, Khoảng ngày, Khoảng số tiền.
  - Reset bộ lọc và đưa trang về page 1 khi thay đổi bộ lọc.
  - Nút "Xuất báo cáo" (xuất toàn bộ kết quả lọc qua file CSV).
- **Bảng dữ liệu giao dịch**:
  - Mã GD VNPay & Nút Copy nhanh mã.
  - Khách hàng (Tên + Email/SĐT), Gói tin + Số ngày, Số tiền (format VND), Phương thức, Ngày GD, Trạng thái (Badge).
  - Hành động: Xem chi tiết mở Modal.
- **Modal chi tiết giao dịch**:
  - Hiển thị đầy đủ thông tin đối soát cổng thanh toán VNPay.
  - Hiển thị danh sách lịch sử các ghi chú (ai ghi, nội dung gì, thời điểm nào) dạng timeline.
  - Textarea để thêm ghi chú mới. Có cảnh báo confirm trước khi lưu note.

#### [MODIFY] [index.js](file:///d:/PROJECT/Meyland/PropifyAdmin/src/router/index.js)
Đăng ký route `/transactions` liên kết tới `Transactions/Page.vue`.

#### [MODIFY] [AppSidebar.vue](file:///d:/PROJECT/Meyland/PropifyAdmin/src/components/layout/AppSidebar.vue)
Thêm mục menu "Lịch sử giao dịch" vào sidebar:
- URL: `/transactions`
- Tiêu đề: `Lịch sử giao dịch`
- Icon: `Receipt`

---

## Kế hoạch xác thực (Verification Plan)

### Automated Tests (Laravel Backend)
Tạo file test `tests/Feature/AdminTransactionTest.php` bao gồm các test case:
- [x] Người dùng chưa đăng nhập bị chặn (401 Unauthorized).
- [x] Người dùng thường (role = USER) bị chặn (403 Forbidden).
- [x] Lấy danh sách giao dịch phân trang thành công cho admin.
- [x] Validation hoạt động chính xác (per_page, dates, amounts).
- [x] Tìm kiếm giao dịch theo ID, tên, email, sđt, mã VNPay.
- [x] Lọc giao dịch theo trạng thái, gói tin, số tiền, ngày tháng.
- [x] Thống kê doanh thu (`total_revenue`) và số lượng trạng thái chính xác (dùng query clone).
- [x] Thêm ghi chú mới thành công, lưu vết đúng `admin_id` và không ghi đè ghi chú cũ.
- [x] Nghiêm cấm các API thay đổi dữ liệu tài chính (PUT/DELETE/PATCH thông tin transaction).
- [x] API Export CSV hoạt động, trả về đúng định dạng CSV và đúng dữ liệu theo bộ lọc.

### Manual Verification (Frontend)
- Khởi chạy Admin app (`npm run dev`) để kiểm tra:
  - Menu sidebar xuất hiện mục "Lịch sử giao dịch" với icon `Receipt`.
  - Bộ lọc hoạt động tốt, debounce tìm kiếm, tự động reset về page 1 khi thay đổi filter.
  - Click vào nút Copy hoạt động chính xác và thông báo thành công.
  - Thêm ghi chú mới trong modal hiển thị thông báo confirm, sau khi lưu thì cập nhật tức thì vào timeline ghi chú bên trên.
  - Nút "Xuất báo cáo" tải xuống đúng file CSV chứa tất cả các dòng giao dịch khớp bộ lọc.
