# Áp dụng Design Pattern cho chức năng đăng tin

Tài liệu này ghi lại các design pattern đã áp dụng trực tiếp vào code của chức năng đăng tin bất động sản.

## Tổng quan pattern đã áp dụng

Các pattern đã được sửa code thật:

```text
Builder Pattern
Facade Pattern
Command Pattern
State Pattern
Chain of Responsibility Pattern
Observer Pattern
Adapter Pattern
```

## 1. Builder Pattern

### Áp dụng ở đâu

```text
PropifyFrontend/src/services/listingPayloadBuilder.js
PropifyFrontend/src/services/listingPreviewBuilder.js
PropifyFrontend/src/services/listingService.js
```

### Mục đích

Builder Pattern được dùng để tách logic build payload đăng tin ra khỏi service gọi API.

Trước đó, `listingService.create()` và `listingService.update()` đều tự mapping dữ liệu form sang payload API. Hai hàm này bị lặp nhiều field như:

```js
demand_type: payload.demandType
property_type: payload.propertyType
province_code: payload.provinceCode
contact_phone: payload.contactPhone
legal_documents: payload.legalDocuments
save_as_draft: payload.saveAsDraft
```

### Code đã áp dụng

File `listingPayloadBuilder.js` có hàm:

```js
buildListingPayload(payload)
```

Hàm này chịu trách nhiệm:

- chuyển field frontend `camelCase` sang field API `snake_case`,
- gán giá trị mặc định cho array/boolean,
- loại bỏ field rỗng,
- trả về payload cuối cùng.

Trong `listingService.js`, create/update hiện chỉ còn:

```js
update(id, payload) {
  return api.put(`/v1/listings/${id}`, buildListingPayload(payload));
}

create(payload) {
  return api.post("/v1/listings", buildListingPayload(payload));
}
```

### Lợi ích

- Giảm lặp code.
- API payload được quản lý ở một nơi.
- Dễ thêm/sửa field đăng tin.
- `listingService.js` chỉ còn nhiệm vụ gọi API.

### Builder cho xem trước tin đăng

Ngoài payload gửi API, chức năng xem trước cũng được tách Builder riêng:

```text
PropifyFrontend/src/services/listingPreviewBuilder.js
```

Hàm chính:

```js
buildListingPreview({
  form,
  imagePreviews,
  selectedAmenities,
  authUser,
  provinceName,
  wardName,
})
```

Mục đích:

- Chuyển dữ liệu đang nhập trong form thành object giống dữ liệu chi tiết tin đăng.
- Adapter dữ liệu form để dùng lại component `ListingDetail`.
- Gom logic build ảnh preview, địa chỉ, owner, property vào một nơi.

Trong `PostForm.vue`, preview hiện được tạo bằng:

```js
const previewListing = computed(() => buildListingPreview({
  form,
  imagePreviews: imagePreviews.value,
  selectedAmenities: selectedAmenities.value,
  authUser: authStore.user,
  provinceName: selectedProvinceName.value,
  wardName: selectedWardName.value,
}));
```

Ý nghĩa pattern:

- `PostForm.vue` không còn phải tự dựng object preview dài.
- `ListingDetail.vue` nhận dữ liệu đã đúng shape để render.
- Đây vừa là Builder, vừa có vai trò Adapter nhẹ vì chuyển form state sang view model của trang chi tiết.

## 2. Facade Pattern

### Áp dụng ở đâu

```text
PropifyFrontend/src/composables/useListingMediaUpload.js
PropifyFrontend/src/pages/Listings/PostForm.vue
```

### Mục đích

Facade Pattern được dùng để che giấu toàn bộ chi tiết upload media trong form đăng tin.

Trước đó `PostForm.vue` tự xử lý:

- upload ảnh,
- upload video,
- kiểm tra video tối đa 100MB,
- upload CCCD,
- upload giấy tờ pháp lý,
- upload khi lưu nháp,
- upload khi chỉ cập nhật xác thực.

Các helper `uploadSingle()` và `uploadMultiple()` bị lặp trong nhiều hàm.

### Code đã áp dụng

File `useListingMediaUpload.js` cung cấp một facade gồm:

```js
uploadSingle(file, mode)
uploadMultiple(files, mode)
uploadListingMediaPayload(payload)
uploadDraftMediaPayload(payload)
uploadVerificationPayload(payload)
```

Trong `PostForm.vue`, thay vì tự upload từng loại file, code chỉ gọi:

```js
await listingMediaUpload.uploadListingMediaPayload(form);
```

Khi lưu nháp:

```js
await listingMediaUpload.uploadDraftMediaPayload(payload);
```

Khi chỉ gửi xác thực:

```js
await listingMediaUpload.uploadVerificationPayload(payload);
```

### Lợi ích

- `PostForm.vue` gọn hơn.
- Logic upload dùng chung một nơi.
- Dễ đổi logic upload sau này.
- Giảm lặp code giữa đăng tin, lưu nháp và xác thực.

## 3. Command Pattern

### Áp dụng ở đâu

```text
PropifyBackend/app/Services/Listing/Commands/CreateListingCommand.php
PropifyBackend/app/Services/Listing/Commands/UpdateListingCommand.php
PropifyBackend/app/Services/Listing/Commands/SaveDraftListingCommand.php
PropifyBackend/app/Services/Listing/Commands/SubmitListingVerificationCommand.php
PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php
```

### Mục đích

Command Pattern được dùng để tách từng nghiệp vụ đăng tin thành một class riêng.

Trước đó `ListingServiceImpl.php` xử lý quá nhiều việc trong các hàm:

```php
create()
update()
updateVerification()
```

Các hàm này vừa:

- kiểm tra user,
- tạo/cập nhật property,
- tạo/cập nhật listing,
- lưu ảnh,
- lưu video,
- lưu giấy tờ xác thực,
- set trạng thái,
- clear cache.

### Code đã áp dụng

`CreateListingCommand` xử lý nghiệp vụ tạo tin:

```php
public function handle(User $user, CreateListingDto $dto): Listing
```

`UpdateListingCommand` xử lý nghiệp vụ cập nhật tin:

```php
public function handle(User $user, int $id, CreateListingDto $dto): Listing
```

`SaveDraftListingCommand` xử lý trường hợp lưu nháp:

```php
public function create(User $user, CreateListingDto $dto): Listing
public function update(User $user, int $id, CreateListingDto $dto): Listing
```

`SubmitListingVerificationCommand` xử lý gửi/cập nhật xác thực:

```php
public function handle(User $user, int $id, array $payload): Listing
```

Trong `ListingServiceImpl`, các hàm public giờ đóng vai trò điều phối:

```php
public function create(User $user, CreateListingDto $dto): Listing
{
    if ($dto->saveAsDraft) {
        return $this->saveDraftListingCommand->create($user, $dto);
    }

    return $this->createListingCommand->handle($user, $dto);
}
```

### Lợi ích

- Mỗi nghiệp vụ có class riêng.
- `ListingServiceImpl` gọn hơn nhiều.
- Dễ test từng command.
- Dễ mở rộng thêm command mới như `PublishListingCommand`, `RejectListingCommand`.

## 4. State Pattern

### Áp dụng ở đâu

```text
PropifyBackend/app/Services/Listing/State/ListingStatusState.php
PropifyBackend/app/Services/Listing/State/AbstractListingStatusState.php
PropifyBackend/app/Services/Listing/State/DraftListingState.php
PropifyBackend/app/Services/Listing/State/PendingListingState.php
PropifyBackend/app/Services/Listing/State/ActiveListingState.php
PropifyBackend/app/Services/Listing/State/RejectedListingState.php
PropifyBackend/app/Services/Listing/State/LockedListingState.php
PropifyBackend/app/Services/Listing/State/ListingStatusStateFactory.php
```

### Mục đích

State Pattern được dùng để quản lý vòng đời trạng thái của tin đăng.

Các trạng thái đang được tách thành class:

```text
DRAFT
PENDING
ACTIVE
REJECTED
LOCKED
```

### Code đã áp dụng

Interface chung:

```php
interface ListingStatusState
{
    public function value(): string;

    public function canTransitionTo(string $nextStatus): bool;
}
```

Ví dụ `PendingListingState`:

```php
final class PendingListingState extends AbstractListingStatusState
{
    public function value(): string
    {
        return 'PENDING';
    }

    protected function allowedTransitions(): array
    {
        return ['ACTIVE', 'REJECTED'];
    }
}
```

`ListingStatusStateFactory` chịu trách nhiệm tạo state:

```php
$this->statusStateFactory->make($listing->status);
```

và kiểm tra chuyển trạng thái:

```php
$this->statusStateFactory->assertCanTransition($listing->status, $status);
```

### Lợi ích

- Logic chuyển trạng thái không còn hard-code trong service.
- Mỗi trạng thái tự biết nó được chuyển sang trạng thái nào.
- Dễ thêm trạng thái mới.
- Hợp với workflow duyệt tin/admin reject/lock.

## 5. Chain of Responsibility Pattern

### Áp dụng ở đâu

```text
PropifyBackend/app/Services/Listing/Validation/ListingSubmissionValidationContext.php
PropifyBackend/app/Services/Listing/Validation/ListingSubmissionValidationHandler.php
PropifyBackend/app/Services/Listing/Validation/AbstractListingSubmissionValidationHandler.php
PropifyBackend/app/Services/Listing/Validation/UserPhoneVerifiedHandler.php
PropifyBackend/app/Services/Listing/Validation/VerificationDocumentsHandler.php
PropifyBackend/app/Services/Listing/Validation/ListingSubmissionValidationPipeline.php
PropifyBackend/app/Services/Listing/Commands/CreateListingCommand.php
```

### Mục đích

Chain of Responsibility được dùng cho các rule kiểm tra business trước khi tạo tin.

Hiện tại pipeline gồm:

```text
UserPhoneVerifiedHandler
-> VerificationDocumentsHandler
```

### Code đã áp dụng

Context truyền qua chain:

```php
final readonly class ListingSubmissionValidationContext
{
    public function __construct(
        public User $user,
        public CreateListingDto $dto,
    ) {
    }
}
```

Handler kiểm tra user có số điện thoại:

```php
final class UserPhoneVerifiedHandler extends AbstractListingSubmissionValidationHandler
{
    protected function validate(ListingSubmissionValidationContext $context): void
    {
        if (!$context->user->phone || trim((string) $context->user->phone) === '') {
            throw new BusinessException(ErrorCode::AuthPhoneNotVerified);
        }
    }
}
```

Pipeline:

```php
$this->userPhoneVerifiedHandler
    ->setNext($this->verificationDocumentsHandler);

$this->userPhoneVerifiedHandler->handle($context);
```

Trong `CreateListingCommand`:

```php
$this->validationPipeline->validate(
    new ListingSubmissionValidationContext($user, $dto)
);
```

### Lợi ích

- Mỗi rule validate là một handler riêng.
- Có thể thêm handler mới mà không sửa command nhiều.
- Hợp cho các bước như kiểm tra ảnh, giá, xác thực, lịch hẹn.
- Laravel `FormRequest` vẫn xử lý validate input; chain này xử lý business validation.

## 6. Observer Pattern

### Áp dụng ở đâu

```text
PropifyBackend/app/Events/Listing/ListingSaved.php
PropifyBackend/app/Listeners/Listing/ClearPublicListingCache.php
PropifyBackend/app/Listeners/Listing/LogListingSaved.php
PropifyBackend/app/Providers/AppServiceProvider.php
PropifyBackend/app/Services/Listing/Commands/CreateListingCommand.php
PropifyBackend/app/Services/Listing/Commands/UpdateListingCommand.php
PropifyBackend/app/Services/Listing/Commands/SubmitListingVerificationCommand.php
PropifyBackend/app/Services/Listing/impl/ListingServiceImpl.php
```

### Mục đích

Observer Pattern được dùng để tách các side effect sau khi lưu tin ra khỏi service/command chính.

Trước đó service tự clear cache trực tiếp:

```php
Cache::tags(['listings:public'])->flush();
```

Sau refactor, các command phát event:

```php
ListingSaved::dispatch($listing, $user, 'created');
```

Listener xử lý side effect:

```php
ClearPublicListingCache
LogListingSaved
```

### Code đã áp dụng

Event:

```php
final class ListingSaved
{
    public function __construct(
        public readonly Listing $listing,
        public readonly ?User $user,
        public readonly string $action,
    ) {
    }
}
```

Đăng ký observer trong `AppServiceProvider`:

```php
Event::listen(ListingSaved::class, ClearPublicListingCache::class);
Event::listen(ListingSaved::class, LogListingSaved::class);
```

### Lợi ích

- Tách side effect khỏi nghiệp vụ chính.
- Sau này thêm gửi email/thông báo admin không cần sửa command.
- Clear cache và log hoạt động được xử lý theo event.

## 7. Adapter Pattern

### Áp dụng ở đâu

```text
PropifyBackend/app/Services/Media/UploadSignatureAdapter.php
PropifyBackend/app/Services/Media/CloudinaryUploadSignatureAdapter.php
PropifyBackend/app/Http/Controllers/Api/V1/Cloudinary/CloudinaryController.php
PropifyBackend/app/Providers/AppServiceProvider.php
```

### Mục đích

Adapter Pattern được dùng để tách controller khỏi provider upload cụ thể là Cloudinary.

Trước đó `CloudinaryController` phụ thuộc trực tiếp vào:

```php
CloudinaryService
```

Sau refactor, controller phụ thuộc vào cổng chung:

```php
UploadSignatureAdapter
```

### Code đã áp dụng

Interface adapter:

```php
interface UploadSignatureAdapter
{
    public function generateSignature(string $folder, string $uploadType): array;
}
```

Adapter Cloudinary:

```php
final class CloudinaryUploadSignatureAdapter implements UploadSignatureAdapter
{
    public function generateSignature(string $folder, string $uploadType): array
    {
        return $this->cloudinaryService->generateSignature($folder, $uploadType);
    }
}
```

Binding:

```php
$this->app->bind(UploadSignatureAdapter::class, CloudinaryUploadSignatureAdapter::class);
```

### Lợi ích

- Controller không phụ thuộc trực tiếp vào Cloudinary.
- Sau này đổi sang S3/Firebase chỉ cần tạo adapter mới.
- Giữ API controller ổn định.

## Kiểm tra sau khi sửa

Đã kiểm tra cú pháp PHP cho các file backend mới/sửa bằng:

```bash
php -l
```

Kết quả:

```text
Không có lỗi cú pháp ở các file pattern mới/sửa.
```

Đã chạy backend test:

```bash
composer run test
```

Kết quả:

```text
18 tests passed.
3 tests failed ở nhóm AuthControllerTest liên quan login/refresh token.
Các lỗi này nằm ở chức năng Auth, không nằm ở phần đăng tin vừa refactor.
```

Đã chạy frontend build trước đó:

```bash
npm run build
```

Kết quả:

```text
Build frontend thành công.
```
