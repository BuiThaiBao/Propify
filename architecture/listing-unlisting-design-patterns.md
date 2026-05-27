# Chức năng gỡ tin đăng và design pattern đã áp dụng

## 1. Tổng quan nghiệp vụ

Chức năng gỡ tin đăng cho phép chủ tin ngừng hiển thị công khai một tin bất động sản đang đăng.

Luồng chính:

1. Người dùng chọn `Gỡ tin đăng` trong danh sách tin của tôi.
2. Frontend kiểm tra nhanh trạng thái hiện tại, chỉ cho thao tác khi tin là `ACTIVE`.
3. Frontend hiển thị popup xác nhận.
4. Khi xác nhận, frontend gọi API `POST /api/v1/listings/{id}/unlist`.
5. Backend khóa bản ghi trong transaction, kiểm tra chủ sở hữu và trạng thái.
6. Backend cập nhật trạng thái tin thành `UNLISTED`.
7. Backend ghi lịch sử trạng thái, phát event sau khi gỡ, clear cache public listings.
8. Frontend refresh lại danh sách và hiển thị thông báo kết quả.

`UNLISTED` là mã trạng thái nội bộ tương ứng với nhãn giao diện `Đã gỡ`.

## 2. Command Pattern

### File áp dụng

- `PropifyBackend/app/Services/Listing/Commands/UnlistListingCommand.php`
- `PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php`
- `PropifyBackend/app/Http/Controllers/Api/V1/Listing/ListingController.php`

### Cách áp dụng

Nghiệp vụ gỡ tin được tách khỏi `ListingServiceImpl` sang command riêng:

- kiểm tra tin tồn tại
- kiểm tra chủ sở hữu
- kiểm tra trạng thái hợp lệ
- cập nhật trạng thái
- ghi lịch sử
- dispatch event

`ListingServiceImpl::unlist()` chỉ đóng vai trò điều phối và gọi:

```php
return $this->unlistListingCommand->handle($user, $id);
```

### Lý do áp dụng

Gỡ tin là một hành động nghiệp vụ có nhiều bước và có side effect. Tách thành command giúp service không phình to thêm, đồng thời dễ mở rộng sau này nếu cần gửi notification, hủy lịch hẹn đang chờ, hoặc ghi audit chi tiết hơn.

## 3. State Pattern

### File áp dụng

- `PropifyBackend/app/Services/Listing/State/ActiveListingState.php`
- `PropifyBackend/app/Services/Listing/State/UnlistedListingState.php`
- `PropifyBackend/app/Services/Listing/State/ListingStatusStateFactory.php`

### Cách áp dụng

Thêm trạng thái mới:

```text
UNLISTED = Đã gỡ
```

Chỉ cho phép chuyển:

```text
ACTIVE -> UNLISTED
```

Không cho phép:

```text
DRAFT -> UNLISTED
PENDING -> UNLISTED
REJECTED -> UNLISTED
LOCKED -> UNLISTED
UNLISTED -> trạng thái khác
```

### Lý do áp dụng

Tài liệu yêu cầu chỉ tin `Đang đăng` mới được gỡ. State Pattern giúp rule này nằm ở tầng domain thay vì rải điều kiện `if status === ACTIVE` ở nhiều nơi.

## 4. Observer Pattern

### File áp dụng

- `PropifyBackend/app/Events/Listing/ListingSaved.php`
- `PropifyBackend/app/Listeners/Listing/ClearPublicListingCache.php`
- `PropifyBackend/app/Listeners/Listing/LogListingSaved.php`
- `PropifyBackend/app/Providers/AppServiceProvider.php`

### Cách áp dụng

Sau khi gỡ tin thành công, `UnlistListingCommand` dispatch:

```php
ListingSaved::dispatch($loaded, $user, 'unlisted');
```

Các listener đang dùng chung sẽ xử lý:

- clear cache danh sách tin public
- ghi log hành động listing

### Lý do áp dụng

Gỡ tin làm thay đổi dữ liệu public. Nếu không clear cache, tin đã gỡ có thể vẫn xuất hiện tạm thời ở danh sách. Observer giúp phần gỡ tin không phải biết chi tiết cách clear cache hoặc log.

## 5. Facade/UI Reuse ở frontend

### File áp dụng

- `PropifyFrontend/src/services/listingService.js`
- `PropifyFrontend/src/components/shared/ConfirmActionModal.vue`
- `PropifyFrontend/src/pages/Profile/index.vue`

### Cách áp dụng

Frontend thêm hàm:

```js
unlist(id) {
  return api.post(`/v1/listings/${id}/unlist`);
}
```

Màn danh sách tin tái sử dụng `ConfirmActionModal` cho popup xác nhận thay vì tạo modal riêng.

### Lý do áp dụng

`listingService` đóng vai trò lớp service/facade phía frontend, che chi tiết endpoint khỏi component. `ConfirmActionModal` giữ giao diện xác nhận nhất quán với thao tác khóa tin đã có.

## 6. Các file chính đã thay đổi

- Backend route: `PropifyBackend/routes/api.php`
- Backend controller: `PropifyBackend/app/Http/Controllers/Api/V1/Listing/ListingController.php`
- Backend service interface: `PropifyBackend/app/Services/Listing/ListingService.php`
- Backend service implementation: `PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php`
- Backend command: `PropifyBackend/app/Services/Listing/Commands/UnlistListingCommand.php`
- Backend state: `PropifyBackend/app/Services/Listing/State/*`
- Frontend service: `PropifyFrontend/src/services/listingService.js`
- Frontend profile listing UI: `PropifyFrontend/src/pages/Profile/index.vue`

## 7. Ghi chú kiểm thử nghiệp vụ

Các trường hợp cần kiểm tra:

- Chủ tin gỡ tin `ACTIVE` thành công.
- Tin sau khi gỡ có trạng thái `UNLISTED` và hiển thị nhãn `Đã gỡ`.
- Tin đã gỡ không còn xuất hiện ở danh sách public.
- Mở chi tiết public của tin đã gỡ trả về không tìm thấy.
- Tin `PENDING`, `DRAFT`, `REJECTED`, `LOCKED` không thể gỡ.
- Người không phải chủ tin không thể gỡ tin.
