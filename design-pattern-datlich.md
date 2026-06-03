# Phân tích Design Pattern cho chức năng Đặt lịch hẹn (Backend)

> Tài liệu này phân tích **code thật** của module Đặt lịch hẹn (branch `feature/datlich`, Laravel)
> và đề xuất **hướng refactor theo Design Pattern**. Mục tiêu kép:
>
> 1. Cho dev / AI agent một lộ trình refactor sạch hơn, dễ mở rộng (thêm trạng thái, thêm rule, thêm thông báo).
> 2. Cho nhóm tài liệu để **trình bày trong báo cáo Phân tích & Thiết kế hệ thống**.
>
> **Đọc theo thứ tự:** nếu chưa quen các pattern, đọc **Mục 2 (Kiến thức nền)** trước — mục này giải
> thích từng pattern *là gì, ví dụ đời thường, và dùng trong module đặt lịch để làm gì* — rồi mới đọc
> phần phân tích từng API.

**Ký hiệu nhóm pattern:** `[C]` Creational · `[S]` Structural · `[B]` Behavioral.

---

## 1. Bối cảnh & mục tiêu

Module đặt lịch hẹn gồm **5 API**:

| # | Chức năng | Endpoint | Hàm Service hiện tại |
|---|-----------|----------|----------------------|
| 1 | Đặt lịch hẹn | `POST /v1/appointment-bookings` | `createBooking()` |
| 2 | Xem thông tin lịch hẹn | `GET /` (viewer), `GET /received` (poster) | `getViewerBookings()`, `getPosterBookings()` |
| 3 | Hủy lịch hẹn | `POST /cancel` | `cancelBooking()` |
| 4 | Xác nhận lịch hẹn | `POST /update-status` (status=`APPROVED`) | `updateBookingStatus()` |
| 5 | Từ chối lịch hẹn | `POST /update-status` (status=`CANCELLED_BY_POSTER`) | `updateBookingStatus()` |

Code hiện chạy đúng, nhưng gần như toàn bộ nghiệp vụ nằm trong một service dưới dạng các khối `if`
kiểm tra tuần tự, và việc chuyển trạng thái được xử lý rải rác bằng so sánh chuỗi. Đây là cơ hội tốt
để áp dụng pattern nhằm tách trách nhiệm, giảm trùng lặp và dễ kiểm thử.

> ⚠️ **Ràng buộc quan trọng:** mọi refactor dưới đây là **thay đổi bên trong (internal)**. Các endpoint,
> tham số request và cấu trúc response **giữ nguyên** — Frontend tiếp tục gọi API cũ và nhận response cũ,
> **không phải sửa gì**. (Chi tiết ở Mục 5.4 và Mục 7.)

---

## 2. Kiến thức nền: mỗi Design Pattern là gì và dùng ở đâu?

Phần này giải thích ngắn gọn 8 pattern xuất hiện trong tài liệu. Mỗi pattern gồm 3 ý:
**(a) Là gì** → **(b) Ví dụ đời thường** → **(c) Dùng trong module đặt lịch để làm gì**.

### 2.1. `[B]` Chain of Responsibility (Chuỗi trách nhiệm)

- **Là gì:** xếp nhiều bước xử lý/kiểm tra thành một **dây chuyền**. Yêu cầu đi qua từng mắt xích; mắt
  xích nào xử lý được thì xử lý, không thì chuyển tiếp. Mỗi mắt xích là **một lớp độc lập**.
- **Ví dụ đời thường:** xét duyệt hồ sơ vay: nhân viên → trưởng phòng → giám đốc. Hồ sơ đi lần lượt,
  ai đủ thẩm quyền thì duyệt/từ chối.
- **Trong đặt lịch:** hàm `createBooking()` đang có 11 bước kiểm tra (`if`) nối nhau. Mỗi bước tách thành
  một "Rule" riêng: `SlotActiveRule`, `NotSelfBookingRule`, `MinLeadTimeRule`… Booking đi qua chuỗi rule;
  sai ở đâu dừng ở đó. **Thêm rule mới = thêm 1 lớp**, không phải sửa hàm dài.

### 2.2. `[B]` Strategy (Chiến lược)

- **Là gì:** đóng gói nhiều **cách làm khác nhau cho cùng một việc** vào các lớp cùng interface, rồi
  *chọn* lớp phù hợp lúc chạy — thay vì viết `if/else` để rẽ nhánh cách tính.
- **Ví dụ đời thường:** app gọi xe có nhiều cách tính cước (giờ thường / giờ cao điểm / ngày lễ). Cùng
  hành động "tính cước" nhưng đổi chiến lược tuỳ tình huống.
- **Trong đặt lịch:** việc tính `confirm_deadline` có 2 cách — đặt gấp (<6h → hạn = giờ hẹn − 1h) và
  thường (hạn = bây giờ + 6h). Tách thành `UrgentDeadlineStrategy` và `DefaultDeadlineStrategy`, chọn
  đúng chiến lược thay cho `if ($isUrgent)`.

### 2.3. `[C]` Builder (Bộ dựng)

- **Là gì:** tách việc **lắp ráp một đối tượng nhiều thuộc tính** ra khỏi nơi dùng nó, dựng từng bước
  cho dễ đọc thay vì gọi constructor với cả chục tham số.
- **Ví dụ đời thường:** đặt một chiếc burger: chọn bánh → thịt → topping → sốt, từng bước, rồi mới "ra món".
- **Trong đặt lịch:** một `AppointmentBooking` gồm nhiều trường tính toán (`meet_time`, `confirm_deadline`,
  `is_urgent`, status `PENDING`…). `BookingBuilder` lo phần lắp ráp này, service chỉ cần `->buildPending()`.

### 2.4. `[B]` State (Trạng thái)

- **Là gì:** mỗi **trạng thái của đối tượng là một lớp riêng**, và lớp đó tự biết *từ đây được làm gì,
  chuyển sang đâu*. Thay cho việc rải `if (status == ...)` khắp nơi.
- **Ví dụ đời thường:** đèn giao thông. Đèn Đỏ chỉ cho phép chuyển sang Xanh; đèn Xanh sang Vàng… Mỗi
  màu "biết" bước tiếp theo hợp lệ là gì.
- **Trong đặt lịch:** booking có các trạng thái `PENDING, APPROVED, CANCELLED_*, EXPIRED`. Hiện luật
  chuyển trạng thái đang nằm rải rác (`if ($booking->status !== PENDING)`…). State pattern gom lại:
  `PendingState` cho phép `confirm/reject/cancel`; `ApprovedState` chỉ cho `cancel`; các trạng thái kết
  thúc thì cấm hết. (Chi tiết Mục 6.)

### 2.5. `[B]` Command (Lệnh)

- **Là gì:** **đóng gói một thao tác thành một đối tượng** (gồm dữ liệu cần để chạy thao tác đó). Nhờ vậy
  dễ ghi log, dễ kiểm thử, dễ xếp hàng/đợi, và mỗi thao tác tách bạch nhau.
- **Ví dụ đời thường:** phiếu gọi món trong nhà hàng. Tờ phiếu (lệnh) ghi rõ "1 phở, ít hành" được chuyển
  xuống bếp; bếp cứ theo phiếu mà làm, không cần biết ai gọi.
- **Trong đặt lịch:** `updateBookingStatus()` đang gánh **cả xác nhận lẫn từ chối**. Tách thành
  `ConfirmBookingCommand` và `RejectBookingCommand` (và `CancelBookingCommand`) để mỗi thao tác rõ ràng,
  dễ gắn log/thông báo riêng. **Việc tách này nằm sau controller — endpoint và response không đổi.**

### 2.6. `[B]` Observer (Quan sát / Phát sự kiện)

- **Là gì:** khi một việc xảy ra, đối tượng chỉ **"phát một thông báo (event)"** rồi *quên đi*. Những ai
  quan tâm (listener) tự đăng ký lắng nghe và phản ứng. Bên phát **không cần biết** có bao nhiêu bên nghe.
- **Ví dụ đời thường:** kênh YouTube đăng video mới (phát sự kiện); tất cả người đã đăng ký (observer) nhận
  thông báo. Kênh không phải gọi điện cho từng người.
- **Trong đặt lịch:** khi đặt/xác nhận/từ chối/hủy lịch, service chỉ cần `BookingApproved::dispatch(...)`.
  Các listener tự lo: gửi thông báo cho bên còn lại, gửi email, ghi audit log… Sau này muốn thêm "gửi SMS"
  thì **chỉ thêm 1 listener**, không đụng vào service. (Hiện code mới chỉ có `AutoCancelExpiredBookingJob`
  — đúng tinh thần này nhưng dùng cho mỗi việc tự hủy quá hạn.)

### 2.7. `[S]` Proxy (Người gác cổng / Lớp đại diện)

- **Là gì:** một lớp **đứng trước** đối tượng thật để **kiểm soát truy cập** (kiểm tra quyền, ghi log,
  cache…) trước khi cho gọi vào trong. Người gọi tưởng đang dùng đối tượng thật, nhưng thực ra qua "người
  gác cổng".
- **Ví dụ đời thường:** bảo vệ toà nhà kiểm tra thẻ nhân viên trước khi cho vào. Không có thẻ hợp lệ → chặn.
- **Trong đặt lịch:** API xem chi tiết một booking cần đảm bảo **chỉ người đặt (viewer), chủ tin (poster)
  hoặc admin** mới xem được. Trong Laravel, **Policy/Gate** chính là hiện thực sẵn có của Proxy:
  `AppointmentBookingPolicy::view()` kiểm tra quyền trước khi trả dữ liệu.

### 2.8. `[S]` Facade (Mặt tiền / Cổng gom)

- **Là gì:** một lớp **cung cấp một hàm đơn giản** che giấu nhiều bước phức tạp bên trong (gọi nhiều
  service/repository, ghép dữ liệu…). Người dùng chỉ gọi 1 hàm, không cần biết bên trong làm gì.
- **Ví dụ đời thường:** một nút "Khởi động" trên xe. Bấm một nút, bên trong hàng loạt bộ phận phối hợp; tài
  xế không cần biết chi tiết.
- **Trong đặt lịch:** để hiển thị một lịch hẹn cần gộp dữ liệu từ nhiều nơi (booking + slot + listing +
  người tham gia). `AppointmentFacade::detail($id)` gom hết lại thành một khối gọn cho Controller/Resource,
  giúp controller mỏng và dễ tái sử dụng.

> **Tóm tắt nhanh:** Chain = chuỗi kiểm tra · Strategy = đổi cách tính · Builder = lắp ráp object ·
> State = trạng thái tự biết luật · Command = đóng gói thao tác · Observer = phát sự kiện cho ai quan tâm ·
> Proxy = gác cổng kiểm quyền · Facade = một nút gom nhiều bước.

---

## 3. Kiến trúc & code hiện tại

### 3.1. Luồng tổng quát

```
HTTP Request
   │
   ▼
FormRequest (validate cú pháp)         ← CreateBookingRequest / UpdateBookingStatusRequest / CancelBookingRequest
   │  toDto()
   ▼
Controller (AppointmentBookingController)
   │  gọi interface
   ▼
Service (AppointmentBookingService ← AppointmentBookingServiceImpl)   ← TOÀN BỘ nghiệp vụ ở đây
   │
   ▼
Repository (AppointmentBookingRepository ← EloquentAppointmentBookingRepository)
   │
   ▼
Model (AppointmentBooking / AppointmentSlot)  +  Job (AutoCancelExpiredBookingJob)
```

### 3.2. Các file chính

| Vai trò | Đường dẫn |
|---------|-----------|
| Controller | `PropifyBackend/app/Http/Controllers/Api/V1/Appointment/AppointmentBookingController.php` |
| Service (interface) | `PropifyBackend/app/Services/Appointment/AppointmentBookingService.php` |
| Service (impl) | `PropifyBackend/app/Services/Appointment/Impl/AppointmentBookingServiceImpl.php` |
| Enum trạng thái | `PropifyBackend/app/Enums/BookingStatus.php` |
| DTO | `PropifyBackend/app/DTOs/Appointment/CreateBookingDto.php` |
| Repository | `PropifyBackend/app/Repositories/AppointmentBookingRepository.php` (+ `Eloquent/...`) |
| FormRequest | `PropifyBackend/app/Http/Requests/Appointment/*.php` |
| Resource | `PropifyBackend/app/Http/Resources/{AppointmentBooking,ViewerBooking}Resource.php` |
| Job auto-hủy | `PropifyBackend/app/Jobs/AutoCancelExpiredBookingJob.php` |

### 3.3. Pattern **đã có sẵn** trong code (ghi nhận, không cần đề xuất lại)

| Pattern | Hiện thực trong dự án | Lợi ích đang có |
|---------|----------------------|-----------------|
| `[S]` **Repository** | `AppointmentBookingRepository` (interface) ↔ `EloquentAppointmentBookingRepository` | Tách truy cập dữ liệu khỏi nghiệp vụ; dễ đổi nguồn dữ liệu, dễ mock khi test. |
| **Service Layer + Dependency Injection** | `AppointmentBookingService` (interface) ↔ `Impl`, bind ở `AppServiceProvider` | Controller mỏng; tuân thủ Dependency Inversion (phụ thuộc interface). |
| `[C]` **DTO** (gần với Builder/Value Object) | `CreateBookingDto` (readonly), tạo từ `CreateBookingRequest::toDto()` | Truyền dữ liệu type-safe giữa các tầng. |
| **FormRequest** (gần với Chain of Responsibility cấp validate cú pháp) | `CreateBookingRequest`, `UpdateBookingStatusRequest`, `CancelBookingRequest` | Validate định dạng tách khỏi controller. |
| Delayed **Job** (mầm mống của Observer) | `AutoCancelExpiredBookingJob::dispatch()->delay($confirmDeadline)` | Tự động chuyển `PENDING → EXPIRED` khi quá hạn xác nhận. |

> **Nhận xét:** nền tảng kiến trúc đã tốt. Phần còn thiếu là **pattern nghiệp vụ**: quản lý trạng thái,
> đóng gói thao tác, và chuỗi kiểm tra nghiệp vụ — tất cả đang bị dồn vào service.

---

## 4. Máy trạng thái của Booking (nền tảng cho State pattern)

Enum thật: `PENDING, APPROVED, CANCELLED_BY_VIEWER, CANCELLED_BY_POSTER, EXPIRED`.

Các chuyển trạng thái **hợp lệ** hiện đang được code mô tả bằng các khối `if` rải rác:

```
                  poster xác nhận
   ┌──────────────────────────────────► APPROVED
   │                                        │
   │ poster từ chối                         │ viewer/poster hủy (≥ 2h trước hẹn)
   │                                        ▼
PENDING ───────────────────────────► CANCELLED_BY_POSTER
   │                                        ▲
   │ viewer/poster hủy (≥ 2h)               │
   ├────────────────────────────────► CANCELLED_BY_VIEWER
   │
   │ quá confirm_deadline (Job tự chạy)
   └────────────────────────────────► EXPIRED
```

> Lưu ý nghiệp vụ: **"poster từ chối"** và **"poster hủy"** dùng chung trạng thái `CANCELLED_BY_POSTER`.
> Hệ thống **không cần thống kê tỷ lệ từ chối** nên **giữ nguyên** cách này — không tách trạng thái mới.
> Khác biệt giữa hai thao tác được ghi trong `note` (`[Chủ nhà từ chối] …` vs `[Chủ nhà hủy] …`).

| Trạng thái | Cho `confirm()` | Cho `reject()` | Cho `cancel()` | Loại |
|------------|:---:|:---:|:---:|------|
| `PENDING` | ✅ → APPROVED | ✅ → CANCELLED_BY_POSTER | ✅ → CANCELLED_BY_{VIEWER,POSTER} | trung gian |
| `APPROVED` | ❌ | ❌ | ✅ (≥ 2h trước hẹn) → CANCELLED_BY_{VIEWER,POSTER} | trung gian |
| `CANCELLED_BY_VIEWER` / `CANCELLED_BY_POSTER` / `EXPIRED` | ❌ | ❌ | ❌ | kết thúc |

Bảng này chính là "luật" mà **State pattern** (Mục 6) sẽ đóng gói, thay cho các `if ($booking->status !== ...)` hiện nằm ở `updateBookingStatus()` và `cancelBooking()`.

---

## 5. Phân tích từng API + pattern đề xuất

### 5.1. Đặt lịch hẹn — `createBooking()`

**Code hiện tại làm gì:** một hàm ~100 dòng thực hiện 11 bước nối tiếp: kiểm tra slot active → listing
ACTIVE → không tự đặt → ngày hợp lệ (≤14 ngày) → đúng `day_of_week` → đặt trước ≥ 2h → không trùng →
tối đa 1 PENDING/listing → tính `confirm_deadline`/`is_urgent` → tạo booking → `dispatch` job auto-hủy.

**Vấn đề:** "god method" — mọi rule nhồi trong một hàm, khó test từng rule, thêm/bớt rule phải sửa giữa hàm,
logic tính hạn (gấp vs thường) trộn lẫn logic tạo.

**Pattern đề xuất:** (xem định nghĩa ở Mục 2)

- `[B]` **Chain of Responsibility** — mỗi rule thành một handler độc lập, nối thành chuỗi.
- `[B]` **Strategy** — `DeadlineStrategy`: `UrgentDeadlineStrategy` vs `DefaultDeadlineStrategy`.
- `[C]` **Builder** — dựng `AppointmentBooking` (gồm `meet_time`, `confirm_deadline`, `is_urgent`).
- `[B]` **Observer / Event** — bắn `BookingCreated` để gửi thông báo / ghi log.

**Ví dụ refactor (rút gọn):**

```php
// 1) Chain of Responsibility cho validate nghiệp vụ
interface BookingRule
{
    public function check(BookingContext $ctx): void; // ném BusinessException nếu sai
}

final class SlotActiveRule implements BookingRule { /* ... */ }
final class NotSelfBookingRule implements BookingRule { /* ... */ }
final class MinLeadTimeRule implements BookingRule { /* ... */ }
final class OnePendingPerListingRule implements BookingRule { /* ... */ }
// ... SlotActive, ListingActive, ValidDate, DayOfWeekMatch, NoDuplicate

final class BookingValidator
{
    /** @param BookingRule[] $rules */
    public function __construct(private array $rules) {}

    public function validate(BookingContext $ctx): void
    {
        foreach ($this->rules as $rule) {
            $rule->check($ctx); // dừng ở rule đầu tiên fail
        }
    }
}

// 2) Strategy cho hạn xác nhận
interface DeadlineStrategy { public function deadlineFor(CarbonImmutable $now, Carbon $meetTime): CarbonImmutable; }
final class UrgentDeadlineStrategy implements DeadlineStrategy  { /* meetTime - 1h */ }
final class DefaultDeadlineStrategy implements DeadlineStrategy { /* now + 6h */ }

// 3) Service sau refactor: ngắn, đọc như mô tả nghiệp vụ
public function createBooking(CreateBookingDto $dto): AppointmentBooking
{
    $ctx = BookingContext::fromDto($dto);          // gom slot, meetTime, now...
    $this->validator->validate($ctx);              // Chain of Responsibility

    $strategy = $ctx->isUrgent()
        ? new UrgentDeadlineStrategy()
        : new DefaultDeadlineStrategy();
    $deadline = $strategy->deadlineFor($ctx->now(), $ctx->meetTime());

    $booking = (new BookingBuilder($ctx))          // Builder
        ->withDeadline($deadline)
        ->buildPending();

    $saved = $this->bookingRepository->create($booking->toArray());
    BookingCreated::dispatch($saved->id);          // Observer/Event
    return $saved;
}
```

---

### 5.2. Xem thông tin lịch hẹn — `getViewerBookings()` / `getPosterBookings()`

**Code hiện tại làm gì:** controller lấy `auth('api')->id()` rồi gọi service → repository trả danh sách,
đưa qua `ViewerBookingResource`.

**Vấn đề:** quyền xem dựa hoàn toàn vào việc lọc theo `viewer_id`/`poster_id` trong query. Khi cần xem
**chi tiết một booking theo id** (ví dụ admin, hoặc deep-link), chưa có lớp kiểm soát "ai được xem booking này".

**Pattern đề xuất:** (xem định nghĩa ở Mục 2)

- `[S]` **Proxy** — `AppointmentBookingPolicy` (Laravel Policy/Gate là hiện thực sẵn có của Proxy) kiểm tra chỉ `viewer`, `poster` của booking hoặc `admin` được xem.
- `[S]` **Facade** — `AppointmentFacade` gom dữ liệu booking + slot + listing + người tham gia cho Resource, giữ controller mỏng.

**Ví dụ refactor (rút gọn):**

```php
// Laravel Policy = Proxy kiểm soát truy cập (người gác cổng)
class AppointmentBookingPolicy
{
    public function view(User $user, AppointmentBooking $b): bool
    {
        return $user->id === $b->viewer_id
            || $user->id === $b->slot->poster_id
            || $user->isAdmin();
    }
}

// Controller dùng Facade + Proxy
public function show(int $id): JsonResponse
{
    $booking = $this->appointmentFacade->detail($id); // Facade gom slot.listing, viewer, poster
    $this->authorize('view', $booking);               // Proxy/Policy
    return ApiResponse::success(new ViewerBookingResource($booking));
}
```

> Hai API danh sách hiện tại (`index`/`received`) **không cần đổi**. Proxy + Facade ở đây chủ yếu cho
> API xem **chi tiết theo id** (nếu/khi bổ sung), và để chuẩn hoá cách kiểm quyền + gom dữ liệu.

---

### 5.3. Hủy lịch hẹn — `cancelBooking()`

**Code hiện tại làm gì:** tìm booking → xác định người gọi là viewer hay poster → chỉ cho hủy khi
`PENDING`/`APPROVED` → áp rule 2 giờ (BR-01) → đặt status `CANCELLED_BY_VIEWER`/`CANCELLED_BY_POSTER`
→ ghép chuỗi note `[Khách thuê hủy] ...` / `[Chủ nhà hủy] ...`.

**Vấn đề:** kiểm tra trạng thái hợp lệ (`in_array(..., [PENDING, APPROVED])`) và rule 2 giờ nằm lẫn trong
service; logic ghép note lặp lại (thấy cả ở `updateBookingStatus`). Thao tác hủy chưa được đóng gói.

**Pattern đề xuất:**

- `[B]` **State** — chính trạng thái hiện tại quyết định `cancel()` có hợp lệ không (gồm cả rule 2 giờ).
- `[B]` **Command** — `CancelBookingCommand` đóng gói thao tác (ai hủy, lý do) → dễ ghi audit log, dễ test.
- `[B]` **Observer** — bắn `BookingCancelled` để thông báo cho bên còn lại.

**Ví dụ refactor:** xem Mục 6 (State) — `cancelBooking` rút gọn còn:

```php
public function cancelBooking(int $bookingId, int $userId, string $reason): AppointmentBooking
{
    $booking = $this->findOrFail($bookingId);
    $role    = BookingRole::of($userId, $booking);          // VIEWER | POSTER | ném BookingNotOwner
    $booking->state()->cancel($booking, $role, $reason);    // State tự kiểm tra + đổi status + note
    $booking->save();
    BookingCancelled::dispatch($booking->id, $role);        // Observer
    return $booking->load('slot.listing');
}
```

---

### 5.4. Xác nhận & Từ chối lịch hẹn — `updateBookingStatus()`

> Hai chức năng này hiện **dùng chung một endpoint** `POST /update-status` và chung một hàm service,
> phân nhánh theo `status` đầu vào (`APPROVED` = xác nhận, `CANCELLED_BY_POSTER` = từ chối).

**Code hiện tại làm gì:** tìm booking → kiểm tra người gọi là poster sở hữu slot → yêu cầu đang `PENDING`
→ cập nhật `status`; nếu từ chối thì ghép note `[Chủ nhà từ chối] {note}`.

**Vấn đề:** một hàm gánh 2 nghiệp vụ khác nhau (xác nhận / từ chối), khó đặt log/notify riêng; điều kiện
chuyển trạng thái là `if ($booking->status !== PENDING)` thủ công; logic ghép note lặp lại.

**Pattern đề xuất:**

- `[B]` **Command** — tách thành `ConfirmBookingCommand` và `RejectBookingCommand` (mỗi thao tác một lệnh).
- `[B]` **State** — `PendingState->confirm()` (PENDING→APPROVED) và `PendingState->reject()` (PENDING→CANCELLED_BY_POSTER).
- `[B]` **Observer** — bắn `BookingApproved` / `BookingRejected` để thông báo viewer.

> ✅ **Không ảnh hưởng Frontend.** Việc tách lệnh nằm **bên trong**, *sau* controller. Endpoint
> `POST /update-status`, tham số (`booking_id`, `status`, `note`) và **response giữ nguyên hoàn toàn**.
> Controller chỉ "điều phối": dựa vào `status` để gọi `ConfirmBookingCommand` hay `RejectBookingCommand`,
> rồi trả về **đúng response cũ**. FE không phải sửa một dòng nào.

**Ví dụ refactor (controller giữ nguyên endpoint, chỉ điều phối bên trong):**

```php
// Controller: vẫn 1 action updateStatus, vẫn response cũ — FE không đổi
public function updateStatus(UpdateBookingStatusRequest $request): JsonResponse
{
    $booking = $this->bookingService->updateBookingStatus(
        bookingId: (int) $request->input('booking_id'),
        posterId:  (int) auth('api')->id(),
        status:    $request->input('status'),
        note:      $request->input('note'),
    );

    return ApiResponse::success(
        data: new ViewerBookingResource($booking),       // y hệt response cũ
        message: 'Cập nhật trạng thái lịch hẹn thành công.'
    );
}

// Service: bên trong tách lệnh theo status (Command), trạng thái tự kiểm (State)
public function updateBookingStatus(int $bookingId, int $posterId, string $status, ?string $note): AppointmentBooking
{
    $booking = $this->findOwnedByPosterOrFail($bookingId, $posterId);  // Proxy kiểm quyền poster

    match ($status) {
        BookingStatus::APPROVED->value           => (new ConfirmBookingCommand($booking))->execute(),
        BookingStatus::CANCELLED_BY_POSTER->value => (new RejectBookingCommand($booking, (string) $note))->execute(),
        default => throw new BusinessException(ErrorCode::BookingNotPending),
    };

    $booking->save();
    $booking->load('slot.listing');
    return $booking;   // controller bọc lại bằng ViewerBookingResource như cũ
}
```

---

## 6. Đề xuất máy trạng thái (State pattern) chi tiết

Thay cho các chuỗi `if ($booking->status !== ...)` rải rác ở `updateBookingStatus()` và `cancelBooking()`,
gom toàn bộ luật chuyển trạng thái vào các lớp State.

```php
interface BookingState
{
    public function confirm(AppointmentBooking $b): void;                                  // poster xác nhận
    public function reject(AppointmentBooking $b, string $reason): void;                    // poster từ chối
    public function cancel(AppointmentBooking $b, BookingRole $by, string $reason): void;   // viewer/poster hủy
}

// Mặc định: mọi thao tác đều không hợp lệ → các state con chỉ override cái được phép
abstract class AbstractBookingState implements BookingState
{
    public function confirm(AppointmentBooking $b): void { throw new BusinessException(ErrorCode::BookingNotPending); }
    public function reject(AppointmentBooking $b, string $reason): void { throw new BusinessException(ErrorCode::BookingNotPending); }
    public function cancel(AppointmentBooking $b, BookingRole $by, string $reason): void { throw new BusinessException(ErrorCode::BookingNotPending); }

    protected function appendNote(AppointmentBooking $b, string $label, string $text): void
    {
        $prefix = $b->note ? $b->note . ' | ' : '';
        $b->note = $prefix . "[{$label}] " . $text;   // gom logic ghép note bị lặp 3 chỗ
    }
}

final class PendingState extends AbstractBookingState
{
    public function confirm(AppointmentBooking $b): void
    {
        $b->status = BookingStatus::APPROVED->value;
    }

    public function reject(AppointmentBooking $b, string $reason): void
    {
        $b->status = BookingStatus::CANCELLED_BY_POSTER->value;       // dùng chung trạng thái, không tách REJECTED
        $this->appendNote($b, 'Chủ nhà từ chối', $reason);
    }

    public function cancel(AppointmentBooking $b, BookingRole $by, string $reason): void
    {
        $this->guardTwoHourRule($b);                  // BR-01
        $b->status = $by->isViewer()
            ? BookingStatus::CANCELLED_BY_VIEWER->value
            : BookingStatus::CANCELLED_BY_POSTER->value;
        $this->appendNote($b, $by->label() . ' hủy', $reason);
    }
}

final class ApprovedState extends AbstractBookingState
{
    // chỉ cho hủy, không cho confirm/reject lại
    public function cancel(AppointmentBooking $b, BookingRole $by, string $reason): void { /* như PendingState::cancel */ }
}

// Các state kết thúc: CancelledState, ExpiredState → dùng nguyên AbstractBookingState (mọi thao tác đều ném lỗi)
```

Trong Model gắn factory chọn state theo `status`:

```php
// AppointmentBooking.php
public function state(): BookingState
{
    return match (BookingStatus::from($this->status)) {
        BookingStatus::PENDING  => new PendingState(),
        BookingStatus::APPROVED => new ApprovedState(),
        default                 => new TerminalState(), // CANCELLED_*, EXPIRED
    };
}
```

**Lợi ích:** luật chuyển trạng thái nằm một chỗ, đọc đúng như bảng ở Mục 4; thêm trạng thái mới chỉ cần
thêm một lớp State; service và `AutoCancelExpiredBookingJob` chỉ gọi `->state()->...` thay vì tự so chuỗi.

---

## 7. Lộ trình refactor đề xuất (ưu tiên)

Làm tuần tự, mỗi bước commit riêng để dễ review. **Tất cả đều là refactor nội bộ — không đổi API, FE giữ nguyên.**

| Bước | Nội dung | File mới (tạo) | File sửa |
|------|----------|----------------|----------|
| 1 | Tách 11 rule của `createBooking` thành **Chain of Responsibility** + `BookingContext` + `BookingValidator` | `app/Services/Appointment/Rules/*Rule.php`, `BookingContext.php`, `BookingValidator.php` | `AppointmentBookingServiceImpl::createBooking` |
| 2 | Tách tính hạn thành **Strategy** (`UrgentDeadlineStrategy`/`DefaultDeadlineStrategy`) + **Builder** dựng booking | `app/Services/Appointment/Deadline/*.php`, `BookingBuilder.php` | `createBooking` |
| 3 | Gom chuyển trạng thái + ghép note vào **State** (`PendingState`, `ApprovedState`, `TerminalState`) | `app/Domain/Booking/States/*.php`, helper `BookingRole.php` | `AppointmentBooking` (thêm `state()`), `updateBookingStatus`, `cancelBooking`, `AutoCancelExpiredBookingJob` |
| 4 | Tách thao tác thành **Command** (`Confirm`/`Reject`/`Cancel`BookingCommand). **Giữ nguyên endpoint `/update-status` & response** — controller chỉ điều phối theo `status` | `app/Services/Appointment/Commands/*.php` | Controller (chỉ phần điều phối nội bộ), Service |
| 5 | Thay thông báo thủ công bằng **Event + Listener (Observer)**: `BookingCreated`, `BookingApproved`, `BookingRejected`, `BookingCancelled` | `app/Events/Booking*.php`, `app/Listeners/Notify*.php` | Service, `EventServiceProvider` |
| 6 | (Tùy chọn) thêm **Proxy/Policy** `AppointmentBookingPolicy` + **Facade** cho API xem chi tiết | `app/Policies/AppointmentBookingPolicy.php`, `AppointmentFacade.php` | `AuthServiceProvider`, Controller |

> Bước 1–3 mang lại lợi ích lớn nhất với rủi ro thấp nhất; nếu thời gian hạn chế, ưu tiên 1–3.

---

## 8. Bảng tóm tắt pattern theo API (dán vào báo cáo)

| Chức năng | Pattern đề xuất | Cách áp dụng | Lợi ích thiết kế |
|-----------|-----------------|--------------|------------------|
| Đặt lịch hẹn | `[B]` Chain of Responsibility · `[B]` Strategy · `[C]` Builder · `[B]` Observer | Mỗi rule = 1 handler nối chuỗi; `DeadlineStrategy` gấp/thường; `BookingBuilder` dựng entity; bắn `BookingCreated` | Dễ thêm/bớt rule; tách tính hạn; service đọc như mô tả nghiệp vụ; thông báo tách rời |
| Xem thông tin lịch hẹn | `[S]` Proxy (Policy/Gate) · `[S]` Facade | `AppointmentBookingPolicy::view` chặn người không liên quan; `AppointmentFacade` gom dữ liệu | Bảo mật dữ liệu lịch; controller gọn; dễ tái dùng |
| Hủy lịch hẹn | `[B]` Command · `[B]` State · `[B]` Observer | `CancelBookingCommand`; `state()->cancel()` kiểm tra trạng thái + rule 2 giờ; bắn `BookingCancelled` | Tránh hủy sai trạng thái; gỡ trùng lặp note; dễ audit/thông báo |
| Xác nhận lịch hẹn | `[B]` Command · `[B]` State · `[B]` Observer | `ConfirmBookingCommand`; `PendingState->confirm()` (PENDING→APPROVED); bắn `BookingApproved` | Tách rõ xác nhận khỏi từ chối; luật chuyển trạng thái tập trung; **không đổi API** |
| Từ chối lịch hẹn | `[B]` Command · `[B]` State · `[B]` Observer | `RejectBookingCommand` lưu lý do vào note; `PendingState->reject()` (→ `CANCELLED_BY_POSTER`); bắn `BookingRejected` | Lưu lý do rõ ràng; dùng chung trạng thái (không cần tách); **không đổi API** |

So với `design-pattern.md` (mục 2.3 Lịch hẹn), tài liệu này **bám vào code thật** và bổ sung
**Chain of Responsibility + Strategy** cho luồng đặt lịch — vốn là phần phức tạp nhất của module.

---

## 9. Gợi ý trình bày cho thầy

Chọn **2 luồng** để vẽ sơ đồ minh hoạ (đủ cả Creational, Structural, Behavioral):

1. **Luồng Đặt lịch hẹn** — minh hoạ *Chain of Responsibility + Strategy + Builder + Observer*.
   - Vẽ **sequence diagram**: Controller → BookingValidator (chạy lần lượt các Rule) → DeadlineStrategy → BookingBuilder → Repository → bắn `BookingCreated`.
   - Điểm nhấn: thêm một rule mới (ví dụ "chặn số điện thoại trong blacklist") chỉ cần thêm 1 lớp, không sửa hàm cũ → minh hoạ **Open/Closed Principle**.

2. **Luồng Xác nhận / Từ chối / Hủy** — minh hoạ *State + Command + Observer*.
   - Vẽ **state diagram** đúng như bảng ở Mục 4, và **class diagram** cho `BookingState` + các state con.
   - Điểm nhấn: mọi luật chuyển trạng thái nằm trong State; `AutoCancelExpiredBookingJob` cũng chỉ gọi `state()` → nhất quán toàn hệ thống. Nhấn mạnh thêm: việc tách Command **không ảnh hưởng Frontend** vì API giữ nguyên.

**Thông điệp chốt:** kiến trúc tầng (Controller–Service–Repository–DTO) đã tốt; phần nâng cấp giá trị nhất là
đưa **nghiệp vụ trạng thái** vào State + Command và **chuỗi kiểm tra** vào Chain of Responsibility, giúp code
*đóng để sửa, mở để mở rộng* và dễ kiểm thử từng phần.
