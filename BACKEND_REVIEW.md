# PropifyBackend - Code Review Report

**Ngày review**: 2026-06-27  
**Reviewer**: Senior Developer  
**Branch**: feature/nangcapgoitin  
**Tech Stack**: Laravel 12, PHP 8.2+, MySQL/PostgreSQL, Redis, JWT (tymon/jwt-auth), Laravel Reverb (WebSockets)

---

## 📊 Tổng quan

| Tiêu chí | Đánh giá |
|----------|----------|
| **Kiến trúc** | ✅ Service/Repository/DTO pattern tốt, tách biệt rõ ràng |
| **Code Organization** | ✅ Domain-driven structure, Enums, Events, DTOs |
| **Security** | ⚠️ Có vài lỗ hổng cần fix (liệt kê dưới) |
| **Performance** | ✅ Caching, indexing, eager loading |
| **Testing** | ⚠️ Coverage thấp, thiếu integration tests cho critical flows |
| **Documentation** | ✅ Comment khá tốt trong migration, config |

---

## 🔴 Critical Issues (Phải fix ngay)

### 1. **JWT Blacklist Middleware không được đăng ký**
**File**: `bootstrap/app.php`, `routes/api.php`  
`CheckBlackListToken` middleware tồn tại nhưng **không được áp dụng** cho bất kỳ route nào. Token bị logout vẫn có thể dùng cho đến khi hết hạn.

```php
// bootstrap/app.php - middleware chỉ alias 'admin'
$middleware->alias([
    'admin' => AdminMiddleware::class,
]);
// Thiếu: 'jwt.blacklist' => CheckBlackListToken::class
```

### 4. **Missing Rate Limiting cho VNPAY Return**
Route `/v1/payments/vnpay/return` (line 201-202) **không có throttle**. VNPAY có thể flood callback.

### 5. **Refresh token logic bug - Double invalidate**
`AuthServiceImpl::refresh()` (line 166) gọi `invalidateToken($refreshToken)` rồi `tokenIssuer->issueFor()` tạo token mới. Nhưng `invalidateToken` dùng `JWTAuth::setToken($token)->invalidate()` - điều này blacklist token **mới tạo** nếu logic sai.

---

## 🟠 High Priority Issues

### 6. **N+1 Query trong ListingController::myListings**
Line 230-238: Query riêng `countsQuery` load status counts. Nên dùng `withCount()` hoặc subquery trong query chính.

```php
// Current - 2 queries
$paginator = $this->listingService->getMyListings(...);
$countsQuery = Listing::where('owner_id', ...)->selectRaw('status, count(*) as count')->groupBy('status')...
```

### 7. **Cache Key Collision Risk**
`ListingServiceImpl::getPublicListings()` (line 181-195) dùng `md5(serialize(...))` - serialize array có thể khác nhau do key order. Nên dùng `json_encode` với `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES` + sort keys.

### 8. **Missing Authorization Check trong ChatController**
Route `/v1/chat/conversations/{conversationId}/messages` (line 173) - `ChatController::getMessages` cần verify user là participant của conversation.

### 9. **Admin Routes missing explicit middleware order**
Line 301: `middleware(['auth:api', 'admin'])` - nếu `auth:api` fail thì `admin` vẫn chạy? Laravel handle đúng nhưng nên test.

### 10. **Geocoding Routes public - No Rate Limit**
Line 196-199: `/v1/geocoding/reverse` và `/search` public, no throttle. Dễ bị abuse gọi Google Maps API.

---

## 🟡 Medium Priority Issues

### 11. **Inconsistent Error Codes**
`ApiResponse::error()` nhận `ErrorCode` enum nhưng nhiều chỗ hardcode string:
- `ListingController:197` → `ApiResponse::error('Chỉ có thể phản ánh tin đang bị khóa.', 422)` - thiếu errorCode
- `AuthServiceImpl:101` → `BusinessException(ErrorCode::AuthAdminNotAllowed)` - OK

### 12. **Magic Numbers trong Throttle**
Routes dùng hardcoded: `throttle:5,1`, `throttle:10,1`, `throttle:60,1`. Nên extract constants:
```php
// config/throttle.php
return [
    'auth' => ['register' => '5,1', 'login' => '5,1', 'otp' => '10,1'],
    'public' => ['view_tracking' => '60,1', 'geocoding' => '30,1'],
];
```



### 16. **Missing Soft Deletes cho critical models**
`Listing`, `User`, `Conversation`, `Message` - không có SoftDeletes. Data loss risk khi delete nhầm.

### 17. **OtpService Interface/Implementation tách biệt nhưng test bind hardcode**
`TestCase.php` bind `OtpStoragePort` → `CacheOtpStorageAdapter`. Integration test nên dùng Redis adapter.

---

## 🟢 Low Priority / Code Quality

### 18. **ListingController::appealLock duplicate ownership check**
Line 192-194: Check ownership ở controller thay vì delegate sang Service/Command. Violation SRP.

### 19. **Inconsistent DTO Usage**
Một số FormRequest có `toDto()` method, một số không. Nên standardize tất cả.


### 22. **Missing API Versioning Strategy**
Routes hardcoded `/v1/`. Khi cần v2 sẽ breaking change. Nên dùng header-based versioning hoặc prefix strategy rõ ràng.



### 24. **Console Commands thiếu Description/Help**
`routes/console.php` commands không có mô tả. `php artisan list` sẽ hiển thị rỗng.



## 🔧 Refactoring Recommendations

### 31. **Extract Search/Filter Logic to Dedicated Classes**
`EloquentListingRepository` có 500+ lines với search logic phức tạp. Nên tách:
- `ListingSearchService` - handle keyword, filters
- `ListingSortingStrategy` - đã có (good)
- `ListingFilterCriteria` - đã có (good)

### 32. **Unify Response Format**
`ApiResponse` class tốt nhưng có inconsistency:
- `success()` trả `status: true`
- `error()` trả `status: false`
- Một số controller trả direct `response()->json()` thay vì dùng helper.


### 34. **Implement API Rate Limiting per User/IP Tier**
Hiện tại chỉ global throttle. Nên có tier: anonymous (strict), authenticated (lenient), premium (higher).

### 35. **Add Health Check Endpoint**
`bootstrap/app.php` có `health: '/up'` nhưng chỉ check Laravel boot. Nên extend check DB, Redis, Queue connectivity.

---


---

