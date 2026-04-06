# Hướng Dẫn Phát Triển Tính Năng Mới (CRUD) — Propify

> Tài liệu này mô tả **quy trình chuẩn** để thêm một tính năng CRUD mới vào dự án Propify. Dựa trên kiến trúc Laravel (Service/Repository/DTO) ở Backend và Vue.js (Pinia/Axios) ở Frontend.

---

## 📐 Tổng Quan Kiến Trúc

```
Frontend (Vue.js)            Backend (Laravel)
─────────────────            ─────────────────────────────────────
src/pages/[Feature]/         routes/api.php
  index.vue                  Http/Controllers/Api/V1/[Feature]/
                               [Feature]Controller.php
src/components/              Http/Requests/
  shared/                      Store[Feature]Request.php
    ListingCard.vue            Update[Feature]Request.php
                             Http/Resources/
src/services/                  [Feature]Resource.php
  [feature]Api.js            DTOs/
                               [Feature]DTO.php
src/stores/                  Services/
  [feature].js                 [Feature]Service.php (Interface)
                               Impl/[Feature]ServiceImpl.php
                             Repositories/
                               [Feature]Repository.php (Interface)
                               Impl/[Feature]RepositoryImpl.php
                             Models/[Feature].php
                             database/migrations/
                               ..._create_[features]_table.php
```

---

## 🗄️ PHẦN 1: BACKEND (Laravel)

### Bước 1: Tạo Migration (Database)

```bash
cd PropifyBackend
php artisan make:migration create_properties_table
```

Mở file trong `database/migrations/`:

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('price', 15, 2);
            $table->string('address');
            $table->unsignedInteger('area');       // m²
            $table->unsignedTinyInteger('bedrooms');
            $table->unsignedTinyInteger('bathrooms');
            $table->string('status', 32)->default('active')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
```

```bash
php artisan migrate
```

---

### Bước 2: Tạo Model

`app/Models/Property.php`:

```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class Property extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'title', 'description',
        'price', 'address', 'area', 'bedrooms', 'bathrooms', 'status',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'area'  => 'integer',
    ];

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

> [!IMPORTANT]
> - Luôn dùng `final class`.
> - Khai báo đủ `$fillable` để tránh mass assignment vulnerability.
> - Tên Model **số ít** (`Property`), tên bảng **số nhiều** (`properties`).

---

### Bước 3: Tạo DTO

`app/DTOs/PropertyDTO.php`:

```php
<?php

declare(strict_types=1);

namespace App\DTOs;

final readonly class PropertyDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $title,
        public readonly string $description,
        public readonly float $price,
        public readonly string $address,
        public readonly int $area,
        public readonly int $bedrooms,
        public readonly int $bathrooms,
    ) {}
}
```

> [!NOTE]
> DTO chỉ là túi dữ liệu, không chứa logic. Dùng `readonly` để đảm bảo bất biến.

---

### Bước 4: Tạo Repository

**Interface:** `app/Repositories/PropertyRepository.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use App\DTOs\PropertyDTO;
use App\Models\Property;
use Illuminate\Pagination\LengthAwarePaginator;

interface PropertyRepository
{
    public function all(array $filters = []): LengthAwarePaginator;
    public function findById(int $id): ?Property;
    public function create(PropertyDTO $dto): Property;
    public function update(Property $property, PropertyDTO $dto): Property;
    public function delete(Property $property): void;
}
```

**Implementation:** `app/Repositories/Impl/PropertyRepositoryImpl.php`

```php
<?php

declare(strict_types=1);

namespace App\Repositories\Impl;

use App\DTOs\PropertyDTO;
use App\Models\Property;
use App\Repositories\PropertyRepository;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

final class PropertyRepositoryImpl implements PropertyRepository
{
    public function all(array $filters = []): LengthAwarePaginator
    {
        return Property::query()
            ->with('owner')
            ->when(isset($filters['status']), fn($q) => $q->where('status', $filters['status']))
            ->latest()
            ->paginate(20);
    }

    public function findById(int $id): ?Property
    {
        return Property::with('owner')->find($id);
    }

    public function create(PropertyDTO $dto): Property
    {
        Log::info('Creating property', ['user_id' => $dto->userId]);
        return Property::create([
            'user_id'     => $dto->userId,
            'title'       => $dto->title,
            'description' => $dto->description,
            'price'       => $dto->price,
            'address'     => $dto->address,
            'area'        => $dto->area,
            'bedrooms'    => $dto->bedrooms,
            'bathrooms'   => $dto->bathrooms,
        ]);
    }

    public function update(Property $property, PropertyDTO $dto): Property
    {
        $property->update([
            'title'       => $dto->title,
            'description' => $dto->description,
            'price'       => $dto->price,
            'address'     => $dto->address,
            'area'        => $dto->area,
            'bedrooms'    => $dto->bedrooms,
            'bathrooms'   => $dto->bathrooms,
        ]);
        return $property->fresh();
    }

    public function delete(Property $property): void
    {
        $property->delete();
    }
}
```

---

### Bước 5: Tạo Service

**Interface:** `app/Services/PropertyService.php`

```php
<?php

declare(strict_types=1);

namespace App\Services;

use App\DTOs\PropertyDTO;
use App\Models\Property;
use Illuminate\Pagination\LengthAwarePaginator;

interface PropertyService
{
    public function getAll(array $filters = []): LengthAwarePaginator;
    public function getById(int $id): ?Property;
    public function create(PropertyDTO $dto): Property;
    public function update(int $id, PropertyDTO $dto): Property;
    public function delete(int $id): void;
}
```

**Implementation:** `app/Services/Impl/PropertyServiceImpl.php`

```php
<?php

declare(strict_types=1);

namespace App\Services\Impl;

use App\DTOs\PropertyDTO;
use App\Models\Property;
use App\Repositories\PropertyRepository;
use App\Services\PropertyService;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Pagination\LengthAwarePaginator;

final class PropertyServiceImpl implements PropertyService
{
    public function __construct(
        private readonly PropertyRepository $propertyRepository,
    ) {}

    public function getAll(array $filters = []): LengthAwarePaginator
    {
        return $this->propertyRepository->all($filters);
    }

    public function getById(int $id): ?Property
    {
        return $this->propertyRepository->findById($id);
    }

    public function create(PropertyDTO $dto): Property
    {
        return $this->propertyRepository->create($dto);
    }

    public function update(int $id, PropertyDTO $dto): Property
    {
        $property = $this->propertyRepository->findById($id);
        if (!$property) {
            throw new HttpResponseException(
                response()->json(['status' => false, 'message' => 'Property not found'], 404)
            );
        }
        return $this->propertyRepository->update($property, $dto);
    }

    public function delete(int $id): void
    {
        $property = $this->propertyRepository->findById($id);
        if (!$property) {
            throw new HttpResponseException(
                response()->json(['status' => false, 'message' => 'Property not found'], 404)
            );
        }
        $this->propertyRepository->delete($property);
    }
}
```

---

### Bước 6: Tạo Form Request

`app/Http/Requests/StorePropertyRequest.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\DTOs\PropertyDTO;
use Illuminate\Foundation\Http\FormRequest;

final class StorePropertyRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'address'     => ['required', 'string', 'max:500'],
            'area'        => ['required', 'integer', 'min:1'],
            'bedrooms'    => ['required', 'integer', 'min:0'],
            'bathrooms'   => ['required', 'integer', 'min:0'],
        ];
    }

    public function toDto(): PropertyDTO
    {
        return new PropertyDTO(
            userId:      (int) $this->user()->id,
            title:       $this->validated('title'),
            description: $this->validated('description') ?? '',
            price:       (float) $this->validated('price'),
            address:     $this->validated('address'),
            area:        (int) $this->validated('area'),
            bedrooms:    (int) $this->validated('bedrooms'),
            bathrooms:   (int) $this->validated('bathrooms'),
        );
    }
}
```

---

### Bước 7: Tạo API Resource

`app/Http/Resources/PropertyResource.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

final class PropertyResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id'          => $this->id,
            'title'       => $this->title,
            'description' => $this->description,
            'price'       => $this->price,
            'address'     => $this->address,
            'area'        => $this->area,
            'bedrooms'    => $this->bedrooms,
            'bathrooms'   => $this->bathrooms,
            'status'      => $this->status,
            'owner'       => [
                'id'   => $this->owner?->id,
                'name' => $this->owner?->full_name,
            ],
            'created_at'  => $this->created_at?->toISOString(),
        ];
    }
}
```

> [!CAUTION]
> **KHÔNG BAO GIỜ** trả về `password`, `remember_token` hay thông tin nhạy cảm trong Resource.

---

### Bước 8: Tạo Controller

`app/Http/Controllers/Api/V1/PropertyController.php`:

```php
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePropertyRequest;
use App\Http\Resources\PropertyResource;
use App\Services\PropertyService;
use Illuminate\Http\JsonResponse;

final class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService $propertyService,
    ) {}

    public function index(): JsonResponse
    {
        $data = $this->propertyService->getAll();
        return response()->json([
            'status' => true,
            'data'   => PropertyResource::collection($data->items()),
            'meta'   => [
                'page'     => $data->currentPage(),
                'per_page' => $data->perPage(),
                'total'    => $data->total(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        $property = $this->propertyService->getById($id);
        if (!$property) {
            return response()->json(['status' => false, 'message' => 'Not found'], 404);
        }
        return response()->json(['status' => true, 'data' => PropertyResource::make($property)]);
    }

    public function store(StorePropertyRequest $request): JsonResponse
    {
        $property = $this->propertyService->create($request->toDto());
        return response()->json([
            'status'  => true,
            'message' => 'Property created successfully',
            'data'    => PropertyResource::make($property),
        ], 201);
    }

    public function update(StorePropertyRequest $request, int $id): JsonResponse
    {
        $property = $this->propertyService->update($id, $request->toDto());
        return response()->json([
            'status'  => true,
            'message' => 'Property updated successfully',
            'data'    => PropertyResource::make($property),
        ]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->propertyService->delete($id);
        return response()->json(['status' => true, 'message' => 'Deleted successfully']);
    }
}
```

---

### Bước 9: Đăng Ký Binding & Route

**`app/Providers/AppServiceProvider.php`:**

```php
use App\Repositories\PropertyRepository;
use App\Repositories\Impl\PropertyRepositoryImpl;
use App\Services\PropertyService;
use App\Services\Impl\PropertyServiceImpl;

public function register(): void
{
    $this->app->bind(PropertyRepository::class, PropertyRepositoryImpl::class);
    $this->app->bind(PropertyService::class, PropertyServiceImpl::class);
}
```

**`routes/api.php`:**

```php
use App\Http\Controllers\Api\V1\PropertyController;

// Public
Route::prefix('v1')->group(function () {
    Route::get('/properties', [PropertyController::class, 'index']);
    Route::get('/properties/{id}', [PropertyController::class, 'show']);
});

// Protected (cần đăng nhập)
Route::prefix('v1')->middleware(['auth:api', 'throttle:60,1'])->group(function () {
    Route::post('/properties', [PropertyController::class, 'store']);
    Route::put('/properties/{id}', [PropertyController::class, 'update']);
    Route::delete('/properties/{id}', [PropertyController::class, 'destroy']);
});
```

**Kiểm tra:**

```bash
php artisan route:list | grep properties
```

---

## 🖥️ PHẦN 2: FRONTEND (Vue.js)

### Bước 10: Tạo API Service

`src/services/propertyApi.js`:

```js
import api from '@/utils/axios';

export const propertyApi = {
  getAll: (params = {}) => api.get('/v1/properties', { params }),
  getById: (id)         => api.get(`/v1/properties/${id}`),
  create: (data)        => api.post('/v1/properties', data),
  update: (id, data)    => api.put(`/v1/properties/${id}`, data),
  delete: (id)          => api.delete(`/v1/properties/${id}`),
};
```

---

### Bước 11: Tạo Pinia Store

`src/stores/property.js`:

```js
import { defineStore } from 'pinia';
import { ref } from 'vue';
import { propertyApi } from '@/services/propertyApi';

export const usePropertyStore = defineStore('property', () => {
  const properties = ref([]);
  const property   = ref(null);
  const pagination = ref({});
  const isLoading  = ref(false);
  const error      = ref(null);

  async function fetchAll(params = {}) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await propertyApi.getAll(params);
      properties.value = data.data;
      pagination.value = data.meta;
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Có lỗi xảy ra';
    } finally {
      isLoading.value = false;
    }
  }

  async function createProperty(formData) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await propertyApi.create(formData);
      properties.value.unshift(data.data);
      return { success: true };
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Tạo thất bại';
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  }

  async function updateProperty(id, formData) {
    isLoading.value = true;
    error.value = null;
    try {
      const { data } = await propertyApi.update(id, formData);
      const idx = properties.value.findIndex(p => p.id === id);
      if (idx !== -1) properties.value[idx] = data.data;
      return { success: true };
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Cập nhật thất bại';
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  }

  async function deleteProperty(id) {
    isLoading.value = true;
    error.value = null;
    try {
      await propertyApi.delete(id);
      properties.value = properties.value.filter(p => p.id !== id);
      return { success: true };
    } catch (err) {
      error.value = err.response?.data?.message ?? 'Xóa thất bại';
      return { success: false, error: error.value };
    } finally {
      isLoading.value = false;
    }
  }

  return {
    properties, property, pagination, isLoading, error,
    fetchAll, createProperty, updateProperty, deleteProperty,
  };
});
```

---

### Bước 12: Tạo Trang

**Cấu trúc thư mục:**

```
src/pages/
  Property/
    index.vue      ← Danh sách
    Create.vue     ← Form tạo mới
    [id]/
      index.vue    ← Chi tiết
      edit.vue     ← Form chỉnh sửa
```

**`src/pages/Property/index.vue` (mẫu):**

```vue
<template>
  <SaleLayout>
    <TopSearchBar />
    <div class="max-w-7xl mx-auto px-4 py-8">

      <!-- Loading -->
      <div v-if="store.isLoading" class="text-center py-20 text-gray-400">
        Đang tải...
      </div>

      <!-- Error -->
      <div v-else-if="store.error" class="text-center text-red-500 py-20">
        {{ store.error }}
      </div>

      <!-- Data -->
      <div v-else class="flex flex-col gap-6">
        <ListingCard
          v-for="item in store.properties"
          :key="item.id"
          :title="item.title"
          :price="String(item.price)"
          :location="item.address"
          :area="item.area"
          :beds="item.bedrooms"
          :baths="item.bathrooms"
        />
      </div>

    </div>
  </SaleLayout>
</template>

<script setup>
import { onMounted } from 'vue';
import { usePropertyStore } from '@/stores/property';
import SaleLayout from '@/layouts/SaleLayout.vue';
import TopSearchBar from '@/components/shared/TopSearchBar.vue';
import ListingCard from '@/components/shared/ListingCard.vue';

const store = usePropertyStore();
onMounted(() => store.fetchAll());
</script>
```

---

### Bước 13: Đăng Ký Route

`src/router/index.js`:

```js
// Route công khai
{
  path: '/properties',
  name: 'Properties',
  component: () => import('@/pages/Property/index.vue'),
},

// Route cần đăng nhập
{
  path: '/properties/create',
  name: 'PropertyCreate',
  component: () => import('@/pages/Property/Create.vue'),
  meta: { requiresAuth: true },
},
```

---

## ✅ CHECKLIST TRƯỚC KHI MERGE

### Backend
- [ ] Migration chạy thành công (`php artisan migrate`)
- [ ] Model có `$fillable`, `$casts`, Relationships đúng
- [ ] DTO dùng `readonly`, không có logic nghiệp vụ
- [ ] Repository Interface khai báo đủ method
- [ ] Service xử lý 404 tường minh
- [ ] Form Request có đủ `rules()` và `toDto()`
- [ ] API Resource không lộ `password` hay thông tin nhạy cảm
- [ ] Controller chỉ gọi Service (không gọi Model trực tiếp)
- [ ] Binding đã đăng ký trong `AppServiceProvider`
- [ ] Route public và protected được tách biệt rõ ràng

### Frontend
- [ ] API Service có đủ 5 method (getAll, getById, create, update, delete)
- [ ] Store xử lý đủ 3 state: `isLoading`, `error`, `data`
- [ ] Trang danh sách hiển thị loading/error/empty state
- [ ] Route đã thêm, `meta.requiresAuth` đặt đúng chỗ
- [ ] Không hardcode URL API (dùng instance Axios có baseURL)

---

## 📌 Quy Tắc Quan Trọng Dự Án Propify

| Quy tắc | Lý do |
|---------|-------|
| `final class` cho Controller, Service, Repository | Không cho extend không kiểm soát |
| `declare(strict_types=1)` đầu mọi file PHP | Phát hiện lỗi type sớm |
| Tên Model **số ít** (`User`, `Property`) | Chuẩn Eloquent ORM |
| Tên bảng **số nhiều** (`users`, `properties`) | Chuẩn SQL |
| Controller không gọi Model trực tiếp | Phải qua Service → Repository |
| Store Pinia luôn có `isLoading` + `error` | UI phản hồi đúng trạng thái |
| Component trong `shared/` nhận tất cả qua Props | Tái sử dụng được ở mọi trang |
