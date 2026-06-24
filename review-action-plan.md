# Plan — Khắc phục các vấn đề Review toàn dự án Propify

> **Bối cảnh:** Báo cáo review chi tiết đã được tạo bởi 4 reviewer song song (Backend, Frontend, Admin, Docs/DevOps). File này **chỉ liệt kê việc cần làm**, theo thứ tự ưu tiên, kèm file:line cụ thể để bạn tự sửa sau. **Không sửa code ở đây.**
>
> **Phạm vi:** `PropifyBackend` (Laravel 12), `PropifyFrontend` & `PropifyAdmin` (Vue 3 + Vite), docs + DevOps (`docker-compose.yml`, `deploy.sh`, `.gitignore`).

---

## Quy ước ưu tiên

| Ký hiệu | Mức độ | Ý nghĩa |
|---|---|---|
| 🔴 P0 | Nghiêm trọng | Bug / bảo mật / sai nghiệp vụ, có thể đang chạy sai trên production |
| 🟠 P1 | Quan trọng | Kiến trúc / hiệu năng / UX ảnh hưởng rõ rệt |
| 🟡 P2 | Nên cải thiện | Code style / cleanup / consistency |

---

## Giai đoạn 1 — P0 Nghiêm trọng (làm trước, ~1 tuần)

### 1.1 Backend — `CACHE_STORE` chết production

- **File:** `PropifyBackend/.env.example:52`
- **Triệu chứng:** `CACHE_STORE=database` nhưng nhiều service dùng `Cache::tags(...)` (database driver của Laravel **không hỗ trợ tags** → `BadMethodCallException`).
- **Các vị trí gọi `Cache::tags`:** `PropifyBackend/app/Services/Listing/Impl/ListingServiceImpl.php:192, 255, 333, 492` (và các nơi khác — grep để xác nhận).
- **Cách sửa:**
  - **Nhanh:** đổi `.env.example` thành `CACHE_STORE=redis` (đồng thời thêm Redis container nếu chưa có).
  - **Bền vững:** viết helper `Cache::tagsSafe(...)` tự fallback khi driver không hỗ trợ.
- **Verify:** chạy `composer run test`, đặc biệt các test chạm endpoint public listings + map.

### 1.2 Backend — VNPay hash validation trước khi mutate

- **File:** `PropifyBackend/app/Http/Controllers/Api/V1/Payment/VnpayReturnController.php:27-46`
- **Triệu chứng:** `$transaction->update([...])` chạy **không điều kiện** ngay khi tìm thấy transaction, mới kiểm tra `$isValidHash` ở dưới → attacker spam GET `/api/v1/payments/vnpay/return?vnp_TxnRef=PFY{id}` để ghi đè `vnp_response_code`/`vnp_pay_date` lên transaction người khác.
- **Cách sửa:** đặt điều kiện `if ($transaction && $isValidHash) { ... }`; nếu `!$isValidHash` thì redirect về FE kèm `status=failed` và exit sớm.
- **Thêm:** nên `select('id','status','expires_at')` rồi `lockForUpdate()` khi update để chống race với `ExpirePendingTransactionJob`.
- **Test mới:** viết test tamper signature — gửi request với `vnp_SecureHash` sai → DB không đổi.

### 1.3 Backend — `RegisterUserCommand` cho phép account-takeover

- **File:** `PropifyBackend/app/Services/Auth/Commands/RegisterUserCommand.php:33-39`
- **Triệu chứng:** nếu user tồn tại ở `Pending`, code ghi đè `password = Hash::make($dto->password)` **không cần OTP cũ** → ai biết email cũng "re-register" rồi đổi pass.
- **Cách sửa:**
  - Tách luồng: nếu `Pending` → **chỉ re-send OTP**, KHÔNG đổi password.
  - Nếu email đã `Active` → báo lỗi "Email đã được đăng ký".
  - Có thể thêm `RateLimiter::hit('register:'.$email)` chống spam.
- **Test mới:** test `existing pending user` chỉ nhận OTP mới, password trong DB không đổi.

### 1.4 Backend — Xoá / harden backdoor `upgradeListing`

- **File:** `PropifyBackend/app/Services/Listing/Impl/ListingServiceImpl.php:423-444`
- **Triệu chứng:** method `upgradeListing` (không phải `createUpgradePayment` → `completePaidUpgrade`) tạo `Transaction` `SIMULATED/SUCCESS` rồi gọi thẳng `UpgradeListingCommand::execute`. Route không wire vào nhưng method public vẫn tồn tại → ai đó có thể wire lại sau.
- **Cách sửa:** xoá khỏi `ListingService` interface + impl, hoặc đổi visibility `private`. Cũng nên xoá `payment_method: 'SIMULATED` ở mọi nơi nếu chỉ dùng cho path này.
- **Audit:** grep `'SIMULATED'` trong toàn `app/` để xác nhận không còn path nào dùng.

### 1.5 Backend — Race condition đặt lịch

- **File:** `PropifyBackend/app/Services/Appointment/Impl/AppointmentBookingServiceImpl.php:73-99`
- **Triệu chứng:** check-then-act không atomic — 2 request cùng pass `exists()` rồi cùng `create()`.
- **Cách sửa (chọn 1):**
  - **Migration mới:** thêm unique constraint một phần `(viewer_id, slot_id, meet_time)` cho status `PENDING/APPROVED` (MySQL chưa hỗ trợ partial unique trực tiếp → dùng generated column `active_slot_key = IF(status IN ('PENDING','APPROVED'), slot_id, NULL)` + unique).
  - **Application-level:** `Cache::lock("booking:slot:{$slotId}", 5)` quanh đoạn check + create.
- **Test mới:** test 2 request song song cùng `slot_id` → chỉ 1 thành công.

### 1.6 Backend — `/v1/auth/refresh` chưa throttle

- **File:** `PropifyBackend/routes/api.php:102-103`
- **Cách sửa:** thêm middleware `throttle:30,1` (hoặc tương đương). Nếu đã có global throttle thì kiểm tra lại route này có nằm ngoài không.
- **Test mới:** gọi refresh 31 lần / phút → request 31 phải 429.

### 1.7 Backend — `mapListings` không phân trang

- **File:** `PropifyBackend/app/Http/Controllers/Api/V1/ListingController.php:90-108`
- **Triệu chứng:** trả về Collection không giới hạn → có thể trả 10k records.
- **Cách sửa:** bound tối đa (vd. `LIMIT 500`), hoặc chuyển sang paginate. Kết hợp với bounding box filter để giảm dataset ngay từ đầu.

### 1.8 Backend — `confirm_deadline` sai semantics

- **File:** `PropifyBackend/app/Services/Appointment/Impl/AppointmentBookingServiceImpl.php:106-113`
- **Triệu chứng:** comment nói `(T_hẹn - T_đặt) - 1 giờ` nhưng code `addHours(max($hoursUntilMeet - 1, 1))` → khi `hoursUntilMeet = 0.5` ra 1h, không khớp comment.
- **Cách sửa:** quyết định semantics đúng (vd. "deadline = meet_time - 1h, tối thiểu +1h từ hiện tại") rồi fix code + cập nhật comment.

### 1.9 Frontend — Open Redirect ở Login

- **File:** `PropifyFrontend/src/pages/Auth/Login.vue:243-244`
- **Triệu chứng:** `const redirectTo = route.query.redirect || '/'; router.push(redirectTo);` — không validate.
- **Cách sửa:** helper `safeRedirect(path)` chỉ chấp nhận path bắt đầu `/` và không chứa `//` hoặc `:`.
- **Áp dụng tương tự cho:** `Register.vue`, OAuth callback `LoginSuccess.vue` (đã không validate `state` CSRF).

### 1.10 Frontend — Pin đúng version `package.json`

- **File:** `PropifyFrontend/package.json` (xem cả `PropifyAdmin/package.json`)
- **Triệu chứng:** version không tồn tại trên npm — `vue-router ^5.0.4`, `vite ^8.0.1`, `eslint ^10.1.0`, `lucide-vue-next ^1.0.0`.
- **Cách sửa:**
  - `vue-router` → `^4.4.0`
  - `vite` → `^5.4.0` (hoặc `^6.0.0` nếu plugin tương thích)
  - `eslint` → `^9.0.0`
  - `lucide-vue-next` → `^0.460.0` (kiểm tra version mới nhất tại thời điểm sửa)
  - Sau khi sửa: `rm -rf node_modules package-lock.json && npm install`.

### 1.11 Frontend — `v-html` XSS trong `PackageUpgradeModal`

- **File:** `PropifyFrontend/src/components/shared/PackageUpgradeModal.vue:93`
- **Cách sửa:** thay `v-html="feat.text"` bằng `{{ feat.text }}` (text thuần). Nếu thật sự cần HTML, dùng DOMPurify.

### 1.12 Frontend + Admin — Race condition 401 refresh

- **File:** `PropifyFrontend/src/services/api.js:65-83`, `PropifyAdmin/src/services/api.js:65-83`
- **Cách sửa:** lưu `let refreshPromise = null` ở module scope. Khi có 401:
  - Nếu `!refreshPromise` → tạo mới, lưu lại.
  - Nếu đã có → tất cả request chờ cùng `await refreshPromise`.
  - Sau khi xong (success/fail) → reset `refreshPromise = null`.

### 1.13 Frontend — `localStorage` chứa PII

- **File:** `PropifyFrontend/src/services/recentlyViewedService.js:43`, `PropifyFrontend/src/pages/Listings/PostForm.vue:4522-4560` (`DRAFT_STORAGE_KEY`)
- **Cách sửa:**
  - `recentlyViewedService`: chỉ lưu `{ id, slug, thumbnailUrl, viewedAt }`, bỏ `contact_*`.
  - `PostForm` draft: cảnh báo user trước khi lưu, hoặc chỉ lưu field text (không lưu ảnh CCCD base64). Khi submit thành công → `localStorage.removeItem(DRAFT_STORAGE_KEY)`.

### 1.14 Frontend — Bug reset `searchKeyword`

- **File:** `PropifyFrontend/src/composables/usePublicListings.js:281-301`
- **Triệu chứng:** đổi `searchField` dropdown → `searchKeyword.value = ""` xoá luôn keyword hiện tại.
- **Cách sửa:** xoá dòng reset, hoặc chỉ reset khi user xác nhận.

### 1.15 Frontend — Dead code + bug logic trong `Detail.vue`

- **File:** `PropifyFrontend/src/pages/Listings/Detail.vue:1351-1433` (6 function `unused*`)
- **Cách sửa:** xoá toàn bộ block `unused*`; xác nhận flow report thật (`openReportModal` line 1555) còn hoạt động đúng — sửa `return;` sớm nếu có.

### 1.16 Admin — Mock data trên Dashboard / Revenue / Users

- **Files:**
  - `PropifyAdmin/src/pages/Dashboard/Page.vue:10-126`
  - `PropifyAdmin/src/pages/Revenue/Page.vue:10-128`
  - `PropifyAdmin/src/pages/Users/Page.vue:12-86` + ConfirmModal handler line 212
- **Cách sửa:**
  - Tạo `services/dashboardService.js`, `services/revenueService.js`, `services/usersService.js` (cùng pattern `listingService.js`).
  - Gọi API: `GET /v1/admin/dashboard/stats`, `/v1/admin/revenue/overview`, `GET/PATCH /v1/admin/users/:id/status`.
  - Xoá toàn bộ mảng `mockUsers`, `monthlyRevenue`, `revenueData`, `recentActivities`.
  - **Quan trọng:** `Users/Page.vue:212` `@confirm="confirmModal.open = false"` PHẢI thay bằng handler gọi API thật — admin đang tin tưởng đã khóa user nhưng thực tế không có gì xảy ra.

### 1.17 Admin — Vue Query đã cài nhưng không dùng

- **File:** `PropifyAdmin/src/main.js:3, 10-23, 26`
- **Cách sửa:** chọn 1 trong 2 hướng:
  - **Migrate toàn bộ** data fetching sang Vue Query (cache, retry, refetch on focus, optimistic update) — đề xuất.
  - **Gỡ** `@tanstack/vue-query` khỏi `package.json` + setup ở `main.js` để giảm bundle ~50KB+.

### 1.18 Admin — Phân quyền chỉ là boolean `isAdmin`

- **File:** `PropifyAdmin/src/stores/auth.js:38`, `src/components/layout/AppSidebar.vue:24-33`
- **Cách sửa:**
  - Backend: thêm `permissions: string[]` vào JWT payload (cập nhật `AuthServiceImpl`).
  - Frontend: thêm directive `v-permission="'package.edit'"` ẩn menu/button.
  - Hoặc tối thiểu: document rõ chỉ có 1 role admin → đơn giản hoá sidebar.

### 1.19 DevOps — `.gitignore` đầy đủ + bỏ `.codegraph`/`.idea`/`.playwright-cli`

- **File:** `D:\PROJECT\Meyland\.gitignore` (hiện chỉ 1 dòng)
- **Cách sửa:** tạo `.gitignore` gốc:
  ```
  # Editor / tooling cá nhân
  .codegraph/
  .idea/
  .vscode/
  .playwright-cli/
  .antigravitycli/

  # Backend
  PropifyBackend/vendor/
  PropifyBackend/.env
  PropifyBackend/.env.backup
  PropifyBackend/storage/logs/*
  !PropifyBackend/storage/logs/.gitkeep
  PropifyBackend/storage/framework/cache/data/*
  PropifyBackend/storage/framework/sessions/*
  PropifyBackend/storage/framework/views/*
  PropifyBackend/public/storage
  PropifyBackend/.phpunit.result.cache
  PropifyBackend/.phpunit.cache

  # Frontend + Admin
  node_modules/
  dist/
  .env.local
  .env.*.local
  npm-debug.log*

  # OS
  .DS_Store
  Thumbs.db

  # Deploy
  deploy.log
  ```
- Sau đó: `git rm -r --cached .codegraph .idea .playwright-cli .antigravitycli PropifyBackend/vendor 2>/dev/null` (nếu đã lỡ commit).

### 1.20 DevOps — Secrets ra khỏi `docker-compose.yml`

- **File:** `D:\PROJECT\Meyland\docker-compose.yml:9-13`
- **Cách sửa:** thay bằng `MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}` + `REDIS_PASSWORD: ${REDIS_PASSWORD}`; tạo `.env.example` ở root với placeholder. Thêm `.env` vào `.gitignore`.

---

## Giai đoạn 2 — P1 Quan trọng (~2 tuần)

### 2.1 Backend — Magic strings → Enum

- **Files:** toàn bộ `PropifyBackend/app/Services/**` và `app/Http/Controllers/**`
- **Triệu chứng:** dùng `'ACTIVE'`, `'PENDING'`, `'SALE'`, `'RENT'`, `'VNPAY'`, `'SIMULATED'`, `'ADMIN'`, `'USER'`, `'FAVORITE'`, `'VIEWED'`, `'ID_FRONT'`, `'ID_BACK'`, `'LEGAL_DOCUMENT'`. Enum tồn tại nhưng không dùng.
- **Cách sửa:** refactor dần sang `StatusEnum->value` / so sánh trực tiếp `$model->status === StatusEnum::Active` (vì model đã cast enum). Ưu tiên các path: transaction state machine, listing state, booking state.

### 2.2 Backend — `paginatePublic` subset select + resource mismatch

- **File:** `PropifyBackend/app/Repositories/Eloquent/EloquentListingRepository.php` (xem method `paginatePublic` ở khoảng line 217)
- **Triệu chứng:** select subset nhưng `ListingResource` đọc field không có (`rejection_reason`, `ai_description`) → trả `null` về FE.
- **Cách sửa:** tách `ListingSummaryResource` (cho public list) và `ListingDetailResource` (cho detail page).

### 2.3 Backend — `withQueryString()` cho pagination

- **File:** `PropifyBackend/app/Repositories/Eloquent/EloquentListingRepository.php:217` (và `paginateAdmin`)
- **Cách sửa:** đổi `->paginate($perPage)` thành `->paginate($perPage)->withQueryString()`.

### 2.4 Backend — Magic-string detect rent/sale

- **File:** `PropifyBackend/app/Repositories/Eloquent/EloquentListingRepository.php:208-233`
- **Cách sửa:** tách helper `detectDemandFromKeyword(string $q): ?DemandType` dùng enum mapping; bỏ hardcode `'cho'/'thue'/'mua'/'ban'` rải rác.

### 2.5 Backend — `normalizeSearchExpression` whitelist

- **File:** `PropifyBackend/app/Repositories/Eloquent/EloquentListingRepository.php` (nhiều chỗ dùng `whereRaw`)
- **Cách sửa:** tạo `array $ALLOWED_SEARCH_COLUMNS = ['title','description', ...]` rồi kiểm tra trước khi build SQL.

### 2.6 Backend — `completePaidUpgrade` không lookup lại pricing

- **File:** `PropifyBackend/app/Services/Listing/Impl/ListingServiceImpl.php:352-369`
- **Cách sửa:** dùng `transaction->amount` làm ground truth; nếu pricing hiện tại khác → reject hoặc log cảnh báo + email admin.

### 2.7 Backend — N+1 ở các hot path

- **Files:**
  - `PropifyBackend/app/Services/Chat/EloquentChatRepository.php:120-128` (insert message + update updated_at + participants loop)
  - `PropifyBackend/app/Services/Appointment/Impl/AppointmentSlotServiceImpl.php:88-107` (updateSlot tuần tự) và `:201-217` (createSlots tuần tự) và `:281-297` (validateSlotsAgainstExisting N+1)
  - `PropifyBackend/app/Services/Listing/Commands/CreateListingCommand.php:135-145` (`saveImages`)
- **Cách sửa:** chuyển sang batch insert/update; cache slot validation 1 query.

### 2.8 Backend — Soft delete + indexes

- **Files:** `PropifyBackend/database/migrations/*`
- **Cách sửa:**
  - Thêm `deleted_at` cho `listings`, `transactions`, `users` (nếu chấp nhận soft delete toàn hệ thống).
  - Thêm index `(province_code, ward_code)` trên `properties` (use-case search khu vực).
  - Cải thiện `countUnread` chat: dùng `max_seen_message_id + 1` thay vì `created_at > last_read_at`.

### 2.9 Backend — Log hygiene (PII)

- **Files:** `PropifyBackend/app/Services/Auth/Impl/AuthServiceImpl.php:69,76` (email), `PropifyBackend/app/Services/ViewTracking/Impl/ViewTrackingServiceImpl.php:102-103` (IP)
- **Cách sửa:** hash email (`substr(hash('sha256', $email), 0, 8)`); bỏ IP hoặc dùng CIDR `/24` rồi log.

### 2.10 Backend — Squash migrations dư thừa

- **Files:**
  - `PropifyBackend/database/migrations/2026_05_12_100208_*` + `2026_05_12_103642_*` (revert/fix lặp)
  - `PropifyBackend/database/migrations/2026_03_23_160800_create_packages_table.php` + `2026_04_22_155418_update_packages_table.php` (drop & recreate)
  - 3 khối `try{}catch{}` rỗng ở `2026_05_12_103642.php:18,28,50`
- **Cách sửa:** chỉ làm khi deploy môi trường mới (tránh break production). Dùng `squash` package hoặc viết 1 migration thay thế.

### 2.11 Backend — Tăng test coverage

- **Files:** `PropifyBackend/tests/Feature/`, `tests/Unit/`
- **Cần test mới:**
  - `VnpayReturnControllerTest` — test signature tamper
  - `RegisterUserCommandTest` — test re-registration không đổi password
  - `AppointmentBookingRaceConditionTest` — 2 request song song cùng slot
  - `ChatServiceTest` — test IDOR (user A không xem được conversation của user B)
  - `AuthServiceTest` — test token blacklist khi logout
  - `ListingUpgradeTest` — test back-to-back upgrade giữ nguyên expiry
  - `VNPayServiceTest` — test signature hợp lệ / không hợp lệ

### 2.12 Backend — Thêm CI/CD

- **Tạo:** `.github/workflows/laravel.yml` (lint + test + build) hoặc `.gitlab-ci.yml`
- **Jobs:** pint check, phpstan/psalm, phpunit, deploy tự động (chỉ main, có approval).

### 2.13 Backend — `/api/v1/payments/vnpay/return` cần thêm security

- **File:** `PropifyBackend/app/Http/Controllers/Api/V1/Payment/VnpayReturnController.php` (xem cả `VnpayService::isValidReturn`)
- **Cách sửa:** whitelist các field VNPay spec thay vì lấy tất cả `$request->query()`. Validate `vnp_Amount > 0` ở controller.

### 2.14 Frontend — Tách 3 god component

- **Files:**
  - `PropifyFrontend/src/pages/Listings/Detail.vue` (3.084 dòng) → tách thành:
    - `DetailGallery.vue` (image + lightbox)
    - `DetailMap.vue` (chỉ dynamic import map libs)
    - `DetailReport.vue`
    - `DetailRelated.vue`
    - `DetailDescription.vue`
    - `DetailShare.vue`
  - `PropifyFrontend/src/pages/Listings/PostForm.vue` (5.738 dòng) → tách theo step/section.
  - `PropifyFrontend/src/pages/Profile/index.vue` (3.749 dòng) → tách thành `ProfileAccount.vue`, `ProfileListings.vue`, `ProfileFavorites.vue`, `ProfileTransactions.vue`, `ProfilePassword.vue`, `ProfileVerifications.vue`.
  - `PropifyFrontend/src/components/common/AppHeader.vue` (630 dòng) → `AppHeader.vue` (shell) + `HeaderNotifications.vue` + `HeaderUserMenu.vue` + `HeaderLoginTrigger.vue`.

### 2.15 Frontend — Lazy load map libs

- **File:** `PropifyFrontend/src/pages/Listings/Detail.vue` (chỗ import `leaflet` + `maplibre-gl`)
- **Cách sửa:** dynamic `const L = await import('leaflet')` chỉ khi user mở map; hoặc chọn 1 trong 2 (đề xuất giữ Maplibre vì satellite tốt hơn).

### 2.16 Frontend — `<img loading="lazy" decoding="async">` toàn cục

- **Files:** tất cả component có `<img>` ngoài viewport
- **Cách sửa:** thêm attribute, hoặc wrap thành `<AppImage>` component enforce.

### 2.17 Frontend — Catch-all 404 route

- **File:** `PropifyFrontend/src/router/index.js`
- **Cách sửa:** thêm `{ path: '/:pathMatch(.*)*', name: 'NotFound', component: NotFoundPage }` (tạo `NotFoundPage.vue` đơn giản).

### 2.18 Frontend — `addressFormatter.js` timeout + abort

- **File:** `PropifyFrontend/src/utils/addressFormatter.js:37-56`
- **Cách sửa:** thêm `AbortController` + timeout 5s; cache Promise nhưng có cleanup khi timeout.

### 2.19 Frontend — Tách `@propify/utils` shared

- **Cách sửa:** tạo package con `@propify/utils` (hoặc dùng path alias `@propify-shared/`) cho `addressFormatter`, `authCookies`, `formatters`. Cấu hình trong `vite.config.js` của cả 2 FE.

### 2.20 Frontend — Sửa hardcode brand sai

- **File:** `PropifyFrontend/src/components/common/AppFooter.vue:55-67`
- **Cách sửa:** thay số ĐT/email/địa chỉ bằng config đúng của Propify (đọc từ `package.json` hoặc env `VITE_BRAND_*`).

### 2.21 Frontend — Sửa hardcode count giả ở Home

- **Files:**
  - `PropifyFrontend/src/components/common/Home/CategorySection.vue:94-131`
  - `PropifyFrontend/src/components/common/Home/HeroSection.vue:198-202`
- **Cách sửa:** gọi API `/v1/public/stats` hoặc bỏ (chỉ show "Đang cập nhật").

### 2.22 Admin — Xoá dead files

- **Files:** (grep không có import nào dùng)
  - `PropifyAdmin/src/pages/Users/{UsersTable,UsersFilter,index}.vue`
  - `PropifyAdmin/src/pages/Dashboard/{index,RecentActivity,RevenueChart}.vue`
  - `PropifyAdmin/src/pages/Packages/{index,PackageCard,PackageSearchBar}.vue`
  - `PropifyAdmin/src/pages/Revenue/{index,RevenueBarChart,PackageDonutChart}.vue`
  - `PropifyAdmin/src/pages/Utilities/{index,UtilityCard}.vue`
  - `PropifyAdmin/src/pages/Posts/PageLegacy.vue`
- **Cách sửa:** xoá hẳn, đồng thời xoá import router dead.

### 2.23 Admin — Phân trang thật + bulk actions

- **Files:** `PropifyAdmin/src/pages/Posts/PostsTable.vue`, `Transactions/Page.vue`
- **Cách sửa:**
  - Hiển thị dãy page number (dùng `visiblePages` đã có ở `Transactions/Page.vue:232-255`).
  - Bulk action bar: "Duyệt tất cả" / "Từ chối tất cả" dùng `selectedIds` Set đã có.

### 2.24 Admin — Toast component thay `alert()`

- **Files:** `PropifyAdmin/src/composables/useTransactionApi.js:171,203,223`; `PropifyAdmin/src/pages/Posts/PageLegacy.vue:166`
- **Cách sửa:** tạo `<Toast>` component + `useToast()` composable; mount Teleport vào `<body>`.

### 2.25 Admin — Global error handler 403/404/500

- **File:** `PropifyAdmin/src/services/api.js`
- **Cách sửa:** thêm `case 403: router.push('/forbidden')`, `case 500: toast.error(...)`. Tạo `Forbidden.vue` + `NotFound.vue`.

### 2.26 Admin — `Posts/DetailPage.vue` quá lớn

- **File:** `PropifyAdmin/src/pages/Posts/DetailPage.vue` (~1.500 dòng)
- **Cách sửa:** tách `<ImageLightbox>`, `<ListingVerificationTab>`, `<ListingWarningsTab>`, `<ListingHistoryTab>`.

### 2.27 Admin — Sidebar responsive

- **File:** `PropifyAdmin/src/components/layout/AdminLayout.vue:14-17`
- **Cách sửa:** thêm media query + overlay cho mobile.

### 2.28 Admin — `.env.development` + Vite proxy

- **Files:** `PropifyAdmin/.env.development` (tạo mới), `PropifyAdmin/vite.config.js`
- **Cách sửa:**
  - `.env.development`: `VITE_API_URL=http://localhost:8000/api`
  - `vite.config.js`: thêm `server.proxy['/api'] = 'http://localhost:8000'`

### 2.29 DevOps — Rollback trong `deploy.sh`

- **File:** `D:\PROJECT\Meyland\deploy.sh:38`
- **Cách sửa:**
  - Trước khi `git reset --hard`, lưu `git rev-parse HEAD` vào `/var/www/Propify/.last_good`.
  - Sau khi build fail (kiểm tra exit code), rollback: `git reset --hard $(cat .last_good)` + rebuild + restart.
  - Ghi tag version deploy (`git tag -a deploy-$(date +%Y%m%d-%H%M%S) -m "..."`).

### 2.30 DevOps — `docker-compose.yml` thêm app service

- **File:** `D:\PROJECT\Meyland\docker-compose.yml`
- **Cách sửa (chọn 1):**
  - Thêm service `app` cho Laravel (PHP-FPM + nginx sidecar), `web` cho Vue FE/Admin (nginx tĩnh), `worker` cho queue, `scheduler` cho cron.
  - Hoặc document rõ trong `AGENTS.md` rằng project chạy host-based qua `deploy.sh`, bỏ claim "Use docker-compose for full local stack".

### 2.31 DevOps — Healthcheck Docker

- **File:** `D:\PROJECT\Meyland\docker-compose.yml`
- **Cách sửa:** thêm `healthcheck:` cho MySQL (`mysqladmin ping`), Redis (`redis-cli ping`).

### 2.32 DevOps — CI/CD cơ bản

- **Tạo:** `.github/workflows/lint-test.yml`
  - Job 1: Backend — `composer install`, `vendor/bin/pint --test`, `vendor/bin/phpunit`
  - Job 2: Frontend — `npm ci`, `npm run lint`, `npm run build`
  - Job 3: Admin — `npm ci`, `npm run lint`, `npm run build`
- (Đã liệt kê ở 2.12 nhưng là 2 mảng khác nhau — backend CI vs FE CI.)

### 2.33 Docs — Cập nhật design-pattern-applied-explanation.md

- **File:** `D:\PROJECT\Meyland\design-pattern-applied-explanation.md:896-929`
- **Cách sửa:** thêm cột "Trạng thái" (✅ Đã cài / 🟡 Áp dụng một phần / ❌ Chưa cài) cho mỗi pattern. Đối chiếu với grep thực tế:
  - ✅ Strategy, Factory, Repository, DTO, Observer, Command, Chain of Responsibility, Adapter, Specification
  - 🟡 Facade (thực ra là Service Layer)
  - ❌ Singleton (Laravel container xử lý), Memento, Decorator, Prototype, Visitor, Interpreter, Mediator, Builder GoF, Flyweight

### 2.34 Docs — Thống nhất `decay_rate`

- **Files:** `D:\PROJECT\Meyland\giai-thich-tinh-diem-tin-dang.html:212`, `giai-thich-nghiep-vu-hien-thi-tin-dang.md:67`
- **Mâu thuẫn:** VIP = 0.01 (đồng ý); Thường = 0.05 vs 0.08.
- **Cách sửa:** chọn 1 con số (khớp với DB seed), cập nhật cả 2 file. Tham chiếu `COALESCE(packages.decay_rate, 0.05)` ở `DefaultPackageScoreSortingStrategy.php:20`.

### 2.35 Docs — Thống nhất công thức SQLite vs MySQL

- **File:** `PropifyBackend/app/Services/Listing/Sorting/Strategies/DefaultPackageScoreSortingStrategy.php:14-20`
- **Cách sửa (chọn 1):**
  - **Tính điểm ở PHP** rồi truyền vào SQL ORDER BY → hy sinh 1 chút performance nhưng nhất quán 100%.
  - **Polyfill `exp()` cho SQLite** (SQLite ≥ 3.35 hỗ trợ) — dùng Taylor Otwell đã có sẵn hoặc tự viết.

### 2.36 Docs — Thêm tài liệu Event/Listener

- **Tạo mới:** `D:\PROJECT\Meyland\architecture\event-listener-catalog.md`
- **Nội dung:** bảng 17 event → listener mapping từ `AppServiceProvider.php:218-234`, kèm:
  - Khi nào dispatch (command, scheduler, manual)
  - Side effects (DB write, broadcast, queue, email)
  - Điều kiện bỏ qua (vd. `ShouldDispatchAfterCommit`)

### 2.37 Docs — Slide mapping

- **Tạo mới:** `D:\PROJECT\Meyland\design-pattern-slide-mapping.md`
- **Nội dung:** map từng slide PDF trong `slide/` (Adapter, Decorator, ...) với class thực tế trong codebase (vd. `slide/Adapter.pdf` ↔ `app/Services/Cloudinary/CloudinaryServiceImpl.php`).

---

## Giai đoạn 3 — P2 Nên cải thiện (cleanup, ~tuần thứ 3-4)

### 3.1 Backend

- **Tách `ListingService` signature 11+ params → `ListingsQueryDto`.**
  - File: `PropifyBackend/app/Services/Listing/Impl/ListingServiceImpl.php` (xem method `getPublicListings`, `getMapListings`).
- **Tách `PropertyAttributeMapper::fromDto(CreateListingDto)`** để loại trùng giữa `CreateListingCommand` + `UpdateListingCommand`.
  - Files: `PropifyBackend/app/Services/Listing/Commands/CreateListingCommand.php:38-50`, `UpdateListingCommand.php:38-50`.
- **`Property::booted()` check `isDirty()` trước khi build `search_text`**.
  - File: `PropifyBackend/app/Models/Property.php:70-81`.
- **`AppointmentSlotServiceImpl::validateSlotsAgainstExisting`** → 1 query `whereIn('id', $slotIds)` thay vì N+1.
- **Listeners folder rỗng** → thêm async notification queue (vd. `SendPackageUpgradeNotification` chuyển sang `ShouldQueue`).
- **OTP an toàn hơn**: `random_bytes(3)` hex thay vì `random_int` decimal.
- **Dùng `jwt-decode` thay vì tự code ở FE** (xem 3.4).
- **Thiếu `(province_code, ward_code)` index** trên `properties` (xem 2.8).

### 3.2 Frontend

- **ESLint + Prettier + Vitest config ở root:**
  - `PropifyFrontend/.eslintrc` / `eslint.config.js`
  - `PropifyFrontend/.prettierrc`
  - `PropifyFrontend/vitest.config.js` + test đầu tiên cho `usePublicListings`, `useFavoriteListings`, `useMessageQueue`.
- **Refactor `<script setup>` đầu file** ở `Button.vue`, `Input.vue` (xem tương tự ở FE).
- **Module format đồng nhất**: default export cho service.
- **Tách notification sound + browser Notification ra `useChatNotification` composable**, có toggle, tôn trọng `prefers-reduced-motion`.
- **`useMessageQueue.js` check `navigator.onLine` trước khi flush.**
- **`stores/notifications.js` polling 30s** → bỏ khi Echo connected.
- **`useVirtualScroll.js` dead code** → xoá nếu xác nhận không dùng.
- **`AppHeader.vue:574-580` bỏ `alert()` + setTimeout**, dùng toast.
- **`ChatInput.vue:109-111`** disable/hide button đính kèm thay vì `alert()`.
- **`MapSearchModal.vue:52-57`** bỏ iframe nhúng Detail, dùng prop `previewListing`.
- **CSS conflicts / tailwind utility spam** → extract component hoặc `@apply`.
- **i18n**: cân nhắc dùng `vue-i18n` ngay từ đầu nếu sau này muốn đa ngôn ngữ.
- **PWA**: thêm service worker + manifest.json.

### 3.3 Admin

- **Refactor `<script setup>` đầu file** ở `PropifyAdmin/src/components/ui/Button.vue`, `Input.vue` (script đang đặt sau template).
- **Module format đồng nhất** (xem 3.2).
- **`USER_CACHE_KEY = 'admin_user'`** ở `auth.js:6` → xoá dead (không set bao giờ).
- **`runRequest` copy-pasted** → tách `composables/useApi.js` factory.
- **CSS trùng lắp `.table-wrap`/`.data-table`** → tách `<DataTable>` component.
- **Icon lucide-vue-next** → import từng icon đúng nơi dùng (không import 13 icon 1 chỗ).
- **Tailwind v4 CSS variables khai báo 2 lần** ở `style.css:5-43` → gộp.
- **`AmenityService.deleteAmenity` không tồn tại** → wire thật hoặc xoá nút.
- **`Posts/PostsTable.vue:144-184` `getActions()`** → dùng `switch` thay vì if-chain.
- **`formatAdminPropertyType` magic strings** (`adminListingFormatters.js:30-47`) → const map file.
- **`useTransactionApi.js:64-74` filename CSV** → helper `formatDateForFilename`.
- **Thiếu test** → cấu hình Vitest + test cho `useTransactionApi`, `usePackageApi`.

### 3.4 Frontend + Admin — Tự viết `decodeJwtPayload`

- **File:** `PropifyFrontend/src/stores/auth.js:9-24`, `PropifyAdmin/src/stores/auth.js` (tương tự)
- **Cách sửa:** dùng `jwt-decode` đã có trong deps.

### 3.5 Docs

- **Bổ sung file kiến trúc tổng thể:** `D:\PROJECT\Meyland\architecture\overall-architecture.md` mô tả Domain → Application → Interface Adapter → Infrastructure.
- **Bỏ DOCX lớn khỏi repo** (`bao-cao-design-pattern.docx` 1.1M, `design-pattern-applied-explanation.docx` 853K) — giữ `.md`.
- **Kiểm tra `giai-thich-design-pattern-bang-loi.md` + `design-pattern-datlich.md` trùng nội dung** với các file khác → nếu trùng thì gộp/tham chiếu.

---

## Checklist tiến độ (copy ra issue tracker nếu cần)

```
### Giai đoạn 1 — P0 (1 tuần)
[ ] 1.1 Backend CACHE_STORE
[ ] 1.2 Backend VNPay hash validation
[ ] 1.3 Backend RegisterUserCommand re-registration
[ ] 1.4 Backend xoá backdoor upgradeListing
[ ] 1.5 Backend race condition AppointmentBooking
[ ] 1.6 Backend throttle /v1/auth/refresh
[ ] 1.7 Backend mapListings phân trang
[ ] 1.8 Backend confirm_deadline sai semantics
[ ] 1.9 Frontend open redirect Login
[ ] 1.10 Frontend pin version package.json
[ ] 1.11 Frontend v-html XSS PackageUpgradeModal
[ ] 1.12 FE+Admin race condition 401 refresh
[ ] 1.13 Frontend localStorage PII
[ ] 1.14 Frontend bug reset searchKeyword
[ ] 1.15 Frontend dead code Detail.vue
[ ] 1.16 Admin mock data Dashboard/Revenue/Users
[ ] 1.17 Admin quyết định Vue Query
[ ] 1.18 Admin phân quyền granular
[ ] 1.19 DevOps .gitignore đầy đủ
[ ] 1.20 DevOps secrets ra khỏi docker-compose

### Giai đoạn 2 — P1 (2 tuần)
[ ] 2.1 Backend magic strings → Enum
[ ] 2.2 Backend ListingResource tách Summary/Detail
[ ] 2.3 Backend withQueryString()
[ ] 2.4 Backend detectDemandFromKeyword helper
[ ] 2.5 Backend whereRaw whitelist
[ ] 2.6 Backend completePaidUpgrade ground truth
[ ] 2.7 Backend batch insert/update hot path
[ ] 2.8 Backend soft delete + indexes
[ ] 2.9 Backend log hygiene PII
[ ] 2.10 Backend squash migrations
[ ] 2.11 Backend tăng test coverage
[ ] 2.12 Backend CI/CD
[ ] 2.13 Backend VNPay return security
[ ] 2.14 Frontend tách 3 god component
[ ] 2.15 Frontend lazy load map libs
[ ] 2.16 Frontend loading="lazy" toàn cục
[ ] 2.17 Frontend 404 route
[ ] 2.18 Frontend addressFormatter timeout
[ ] 2.19 Frontend @propify/utils shared
[ ] 2.20 Frontend sửa brand hardcode
[ ] 2.21 Frontend bỏ hardcode count giả
[ ] 2.22 Admin xoá dead files
[ ] 2.23 Admin phân trang + bulk actions
[ ] 2.24 Admin toast component
[ ] 2.25 Admin global error 403/404/500
[ ] 2.26 Admin tách Posts/DetailPage
[ ] 2.27 Admin sidebar responsive
[ ] 2.28 Admin .env.development + Vite proxy
[ ] 2.29 DevOps rollback deploy.sh
[ ] 2.30 DevOps docker-compose app service
[ ] 2.31 DevOps healthcheck Docker
[ ] 2.32 DevOps CI/CD cơ bản
[ ] 2.33 Docs cập nhật design-pattern status
[ ] 2.34 Docs thống nhất decay_rate
[ ] 2.35 Docs SQLite vs MySQL công thức điểm
[ ] 2.36 Docs thêm event-listener catalog
[ ] 2.37 Docs slide mapping

### Giai đoạn 3 — P2 (tuần 3-4)
[ ] 3.1 Backend cleanup (DTO mapper, isDirty, batch)
[ ] 3.2 Frontend ESLint/Prettier/Vitest + cleanup
[ ] 3.3 Admin cleanup (DataTable, format, format)
[ ] 3.4 FE+Admin dùng jwt-decode
[ ] 3.5 Docs overall architecture + bỏ DOCX
```

---

## File tham chiếu nhanh

- Báo cáo review gốc (đã tạo ở session trước): xem response trước trong cuộc hội thoại này.
- Codegraph index: 651 files, 6.371 nodes, 10.891 edges.
- Repo docs: `AGENTS.md`, `architecture/`, `implementation_plan*.md`.

---

**Lưu ý cuối:** Một số mục (đặc biệt 1.1, 1.2, 1.4, 1.16) có khả năng **đang chạy sai trên production** mà không ai biết. Ưu tiên P0 trước khi làm các mục khác.
