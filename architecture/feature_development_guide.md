# SỞ HỮU TRÍ TUỆ - QUY TRÌNH PHÁT TRIỂN CHỨC NĂNG MỚI (S.O.P)

Để giữ cho dự án luôn chuẩn **Clean Architecture** và bảo đảm khả năng mở rộng như một dự án Enterprise, khi bạn cần phát triển thêm một Module/Chức năng mới (Ví dụ: Chức năng `Bất Động Sản` - Property), hãy tuân thủ tuần tự **9 Bước** sau:

---

## BƯỚC 1: Xây dựng Nền móng (Model, Migration, Enum)
Mọi thứ bắt đầu từ cơ sở dữ liệu.

1. **Migration**: Tạo bảng cơ sở dữ liệu (`php artisan make:migration create_properties_table`).
2. **Enum**: Nếu đối tượng có Trạng thái (Ví dụ: `Đang Bán`, `Đã Bán`), **bắt buộc** tạo file Enum (Ví dụ: `app/Enums/PropertyStatus.php`).
3. **Model**: Bật bảo vệ chống *Mass-Assignment*.
   - Tạo file `app/Models/Property.php`.
   - Liệt kê các cột an toàn vào `$fillable`. Cột nào nhạy cảm (VD: Lượt xem, Trạng thái phê duyệt) thì **KHÔNG** đưa vào `$fillable`.
   - Ép kiểu (cast) Enum:
   ```php
   protected $casts = [
       'status' => PropertyStatus::class,
   ];
   ```

## BƯỚC 2: Tổ chức Code Truy Vấn (Repository Pattern)
Không bao giờ gọi Eloquent Query (VD: `Property::where(...)`) ở Service hay Controller. Hãy gói nó lại:

1. **Interface**: Tạo `app/Repositories/PropertyRepository.php` (Khai báo các tên hàm cần thiết: mảng trả về list, tạo mới, v.v).
2. **Implement**: Tạo `app/Repositories/Eloquent/EloquentPropertyRepository.php`. Tại đây, bạn chèn logic Eloquent thực tế.
3. **Đăng ký (Bind)**: Mở [app/Providers/AppServiceProvider.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Providers/AppServiceProvider.php) và thêm 1 dòng nối Interface với Implementation của bạn:
   ```php
   $this->app->bind(PropertyRepository::class, EloquentPropertyRepository::class);
   ```

## BƯỚC 3: Xử lý Request Bằng DTO (Data Transfer Object)
Service KHÔNG ĐƯỢC nhận dữ liệu là một mảng `array` (Không an toàn).
Service KHÔNG ĐƯỢC nhận thẳng `FormRequest` (Gây dính liền với lớp HTTP).

1. Tạo thư mục `app/DTOs/Property/`.
2. Tạo 1 file **Request DTO** (Ví dụ `CreatePropertyDto.php`) dành riêng chứa input client.
   ```php
   final readonly class CreatePropertyDto {
       public function __construct(public string $title, public float $price) {}
       
       public static function fromRequest(CreatePropertyRequest $request): self {
           return new self($request->validated('title'), $request->validated('price'));
       }
   }
   ```
3. *(Tùy chọn)*: Tạo 1 file **Result DTO** nếu hàm đó gộp nhiều Model trả về phức tạp.

## BƯỚC 4: Khai báo Mã Lỗi (Exception Xịn xò)
Trước khi làm logic, dự tính luôn những lỗi nào có thể văng ra (Ví dụ: "Không tìm thấy nhà", "Bạn không có quyền sửa").

1. Mở [app/Enums/ErrorCode.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Enums/ErrorCode.php), thêm 1 mã lỗi mới.
   ```php
   case PropertyNotFound = 2001;
   ```
2. Mapping thông báo lỗi tương ứng với mã đó trong hàm [message()](file:///d:/PROJECT/Meyland/PropifyBackend/app/Enums/ErrorCode.php#35-59) của file [ErrorCode.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Enums/ErrorCode.php).
   ```php
   self::PropertyNotFound => 'Bất động sản này không tồn tại hoặc đã bị xóa',
   ```
3. Mapping mã văng (HTTP Status) trong hàm [httpStatus()](file:///d:/PROJECT/Meyland/PropifyBackend/app/Enums/ErrorCode.php#60-91):
   ```php
   self::PropertyNotFound => 404,
   ```
4. Tạo file Exception cụ thể kế thừa [BusinessException](file:///d:/PROJECT/Meyland/PropifyBackend/app/Exceptions/BusinessException.php#9-27) ở `app/Exceptions/PropertyNotFoundException.php`:
   ```php
   class PropertyNotFoundException extends BusinessException {
       public function __construct() { parent::__construct(ErrorCode::PropertyNotFound); }
   }
   ```

## BƯỚC 5: Nghiệp vụ Lõi (Service Layer)
Ngôi nhà của toàn bộ "Bộ Não". Gác cổng đầu cuối ở đây.

1. **Interface**: Tạo `app/Services/PropertyService.php`.
2. **Implement**: Tạo `app/Services/Impl/PropertyServiceImpl.php`.
3. **Tiêm phụ thuộc (Dependency Injection - DI)**: Khai báo `PropertyRepository` vào Constructor.
4. Viết Code:
   ```php
   public function create(CreatePropertyDto $dto): Property {
       // NẾU CÓ THAO TÁC SỬA NHIỀU BẢNG: LUÔN DÙNG DB::transaction()
       return DB::transaction(function() use ($dto) {
            // Nghiệp vụ A...
            // Nghiệp vụ B... quăng lỗi: throw new PropertyNotFoundException();
            return $this->propertyRepository->create(['title' => $dto->title]);
       });
   }
   ```
5. Đừng quên **Bind Interface** vào Provide. Mở [AppServiceProvider.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Providers/AppServiceProvider.php) gõ thêm:
   ```php
   $this->app->bind(PropertyService::class, PropertyServiceImpl::class);
   ```

## BƯỚC 6: Kiểm Duyệt Bức Tường Lửa Đầu Tiên (Validation)
Không bao giờ để dữ liệu thủng vào Controller.

1. Chạy chạy `php artisan make:request Property/CreatePropertyRequest`.
2. Gõ các rules (`required`, `numeric`, `max:255`...) vào hàm [rules()](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Requests/Auth/RegisterRequest.php#14-24).
3. Tùy chỉnh Tiếng Việt ở hàm [messages()](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Requests/Auth/RegisterRequest.php#25-45).

## BƯỚC 7: Định Dạng Trả Về (API Resource)
Khi lấy được Data ở Controller, KHÔNG trả thẳng array đó cho Fe.

1. Chạy lệnh `php artisan make:resource PropertyResource`.
2. Viết lại cấu trúc JSON ra Front-end mong muốn tại hàm [toArray()](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Resources/UserResource.php#10-28). (Đây là nơi gọt bớt các trường `created_at` dư thừa hoặc chuẩn hóa ngày tháng ISO8601).

## BƯỚC 8: Chốt Chặn Giao Thông (Controller & Swagger)
Nhiệm vụ của Controller rất nhàn: Nhận - Gọi - Chuyển kết quả.

1. Tạo `app/Http/Controllers/Api/V1/Property/PropertyController.php`.
2. Viết các method sử dụng Swagger (Lưu ý: Bạn chỉ cần chép Block `#[OA\Post(....)]` từ [AuthController.php](file:///d:/PROJECT/Meyland/PropifyBackend/app/Http/Controllers/AuthController.php) qua để đỡ gõ tay, rồi thay tên Path & RequestBody).
3. Logic nội tại:
   ```php
   public function store(CreatePropertyRequest $request): JsonResponse {
       // Controller KHÔNG BAO GIỜ CÓ TRY-CATCH. Lỗi do ExceptionHandler xử auto.
       $dto = CreatePropertyDto::fromRequest($request);
       
       $result = $this->propertyService->create($dto);
       
       return ApiResponse::created(
           data: new PropertyResource($result),    // ApiResponse tự động bóp format lại
           message: 'Tạo bất động sản thành công'
       );
   }
   ```

## BƯỚC 9: Đạp Chân Ga Mở Cửa (Routing)
1. Mở [routes/api.php](file:///d:/PROJECT/Meyland/PropifyBackend/routes/api.php)
2. Tạo Nhóm Routes mới ứng với chức năng.
   ```php
   Route::prefix('v1/properties')->as('properties.')->middleware('auth:api')->group(function () {
       Route::post('/', [PropertyController::class, 'store'])->name('store');
   });
   ```
3. Chạy `php artisan l5-swagger:generate` để nó render API Document cho màn hình `/api/documentation`.

***

**TỔNG KẾT**:
Với sơ đồ nhịp nhàng 9 Bước này, dù team sau này có nâng lên 10 người, bộ mã nguồn vẫn sẽ sạch sẽ, gãy gọn, lỗi văng ra chỗ nào tự biết đường dò file chỗ đó mà không phải mò "một đống mì Ý" (Spaghetti Code) trộn lộn lạo Validation, Query DB, Trả JSON trong cùng 1 Controller nữa! Lên xe và code thôi!
