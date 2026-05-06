# 🔧 Appointment Slots - Hướng Dẫn Sửa Đổi

## 📝 Tóm Tắt Thay Đổi

Hệ thống appointment slots được sửa đổi để hỗ trợ **tạo nhiều khung giờ cùng lúc** thay vì chỉ chọn một khung giờ cho toàn bộ các ngày.

### **Trước (Cũ):**
- Chọn ngày (multiple): Thứ 2, 3, 4, 5...
- Chọn giờ (single): 08:00-09:00
- ❌ Chỉ có 1 khung giờ cho tất cả ngày

### **Sau (Mới):**
- Tạo multiple khung giờ (thứ + start_time + end_time)
- ✅ Nút "+" để thêm khung giờ khác
- ✅ Validation: Không trùng day_of_week + time
- ✅ Mỗi slot lưu riêng vào table `appointment_slots`

---

## 🏗️ Thay Đổi Backend

### **1. DTO Mới**
**File:** `app/DTOs/Appointment/CreateSlotsDto.php`
```php
class CreateSlotsDto {
    int $listingId,
    int $posterId,
    array $slots  // [{"day_of_week": 1, "start_time": "09:00", "end_time": "10:00"}, ...]
}
```

### **2. Request Mới**
**File:** `app/Http/Resources/Requests/Appointment/CreateSlotsRequest.php`
- Validate mảng slots
- Mỗi slot: day_of_week (1-7), start_time (HH:mm), end_time (HH:mm)
- Validate: end_time > start_time

### **3. Service Mới**
**File:** `app/Services/Appointment/Impl/AppointmentSlotServiceImpl.php`

**Method mới:** `createSlots(CreateSlotsDto $dto): Collection`

Chức năng:
1. ✅ Kiểm tra Listing ACTIVE
2. ✅ Kiểm tra poster_id = listing owner
3. ✅ Validate không trùng trong array slots
4. ✅ Validate không trùng với DB
5. ✅ Bulk insert vào `appointment_slots`
6. ✅ Return Collection<AppointmentSlot>

**Helper methods:**
- `validateSlotsForDuplicates()` - Kiểm tra trùng trong array
- `validateSlotsAgainstExisting()` - Kiểm tra trùng với DB

### **4. Controller Mới**
**File:** `app/Http/Controllers/Api/V1/Appointment/AppointmentSlotController.php`

**Method mới:** `create(CreateSlotsRequest $request): JsonResponse`

Endpoint: `POST /api/v1/appointment-slots/create`

### **5. Route Mới**
**File:** `routes/api.php`
```php
Route::post('/create', [...AppointmentSlotController::class, 'create'])->name('create');
```

---

## 🎨 Thay Đổi Frontend

### **Component Mới**
**File:** `src/components/AppointmentSlotsForm.vue`

Features:
- ✅ Form gọn gàng với design responsive
- ✅ Multiple slots: thứ + giờ bắt đầu + giờ kết thúc
- ✅ Nút "+" để thêm slot mới
- ✅ Nút "✕" để xóa slot
- ✅ Real-time validation: không trùng
- ✅ Error message nếu có trùng
- ✅ Method `getFormData()` để parent component gọi

**Sử dụng trong PostListing.vue:**
```vue
<template>
  <AppointmentSlotsForm ref="appointmentForm" />
</template>

<script setup>
const appointmentForm = ref(null);

async function submitListing() {
  // Trước khi submit
  const appointmentSlots = appointmentForm.value.getFormData();
  
  if (!appointmentForm.value.isValid) {
    pushToast('Lịch hẹn không hợp lệ', 'error');
    return;
  }
  
  // Gửi appointment slots
  const response = await api.post('/api/v1/appointment-slots/create', {
    listing_id: listingId,
    slots: appointmentSlots,
  });
}
</script>
```

---

## 🔌 API Endpoint

### **POST `/api/v1/appointment-slots/create`**

**Headers:**
```
Authorization: Bearer <jwt_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "listing_id": 1,
  "slots": [
    {
      "day_of_week": 1,
      "start_time": "09:00",
      "end_time": "10:00"
    },
    {
      "day_of_week": 1,
      "start_time": "14:00",
      "end_time": "15:00"
    },
    {
      "day_of_week": 3,
      "start_time": "10:00",
      "end_time": "11:30"
    }
  ]
}
```

**Response (Success - 200):**
```json
{
  "status": "success",
  "message": "Tạo khung giờ hẹn thành công.",
  "data": [
    {
      "id": 1,
      "listing_id": 1,
      "poster_id": 5,
      "day_of_week": 1,
      "start_time": "09:00",
      "end_time": "10:00",
      "is_active": true,
      "created_at": "2026-05-06T10:30:00Z",
      "updated_at": "2026-05-06T10:30:00Z"
    },
    ...
  ]
}
```

**Response (Error - 422):**
```json
{
  "status": "error",
  "message": "Khung giờ thứ 1, 09:00 - 10:00 đã tồn tại.",
  "errors": {...}
}
```

---

## 🗂️ Danh Sách File Thay Đổi

| File | Loại | Mô Tả |
|------|------|-------|
| `app/DTOs/Appointment/CreateSlotsDto.php` | 🆕 Tạo mới | DTO cho bulk create |
| `app/Http/Resources/Requests/Appointment/CreateSlotsRequest.php` | 🆕 Tạo mới | Request validation |
| `app/Services/Appointment/AppointmentSlotService.php` | 🔧 Sửa | Thêm method `createSlots()` |
| `app/Services/Appointment/Impl/AppointmentSlotServiceImpl.php` | 🔧 Sửa | Implement `createSlots()` + helpers |
| `app/Http/Controllers/Api/V1/Appointment/AppointmentSlotController.php` | 🔧 Sửa | Thêm method `create()` |
| `routes/api.php` | 🔧 Sửa | Thêm route POST `/create` |
| `src/components/AppointmentSlotsForm.vue` | 🆕 Tạo mới | Vue form component |

---

## 🧪 Ví Dụ Sử Dụng

### **cURL:**
```bash
curl -X POST http://localhost:8000/api/v1/appointment-slots/create \
  -H "Authorization: Bearer <token>" \
  -H "Content-Type: application/json" \
  -d '{
    "listing_id": 1,
    "slots": [
      {"day_of_week": 2, "start_time": "09:00", "end_time": "10:00"},
      {"day_of_week": 4, "start_time": "14:00", "end_time": "16:00"}
    ]
  }'
```

### **Postman:**
1. Method: `POST`
2. URL: `http://localhost:8000/api/v1/appointment-slots/create`
3. Headers:
   - `Authorization: Bearer <token>`
   - `Content-Type: application/json`
4. Body (raw JSON):
```json
{
  "listing_id": 1,
  "slots": [
    {"day_of_week": 1, "start_time": "08:00", "end_time": "09:00"},
    {"day_of_week": 2, "start_time": "10:00", "end_time": "11:00"},
    {"day_of_week": 5, "start_time": "15:00", "end_time": "16:30"}
  ]
}
```

---

## ✅ Validation Rules

### **Trên Backend:**
1. ✅ `listing_id` phải tồn tại và ACTIVE
2. ✅ `slots` phải là array, min 1 item
3. ✅ Mỗi slot:
   - `day_of_week`: 1-7 (bắt buộc)
   - `start_time`: HH:mm format (bắt buộc)
   - `end_time`: HH:mm format, phải > start_time (bắt buộc)
4. ✅ Không trùng day_of_week + time trong array
5. ✅ Không trùng với slots đã tồn tại trong DB

### **Trên Frontend:**
1. ✅ day_of_week không được để trống
2. ✅ start_time không được để trống
3. ✅ end_time không được để trống
4. ✅ end_time > start_time
5. ✅ Không trùng khung giờ cùng thứ (real-time check)

---

## 🔄 Migration Table (Hiện Tại)

```php
Schema::create('appointment_slots', function (Blueprint $table) {
    $table->id();
    $table->foreignId('listing_id')->constrained('listings')->cascadeOnDelete();
    $table->foreignId('poster_id')->constrained('users');
    $table->tinyInteger('day_of_week')->comment('1=T2, 2=T3, ..., 7=CN');
    $table->time('start_time');
    $table->time('end_time');
    $table->boolean('is_active')->default(true)->comment('false = xóa mềm');
    $table->timestamps();
});
```

---

## 📋 Danh Sách `day_of_week` Values

| Value | Ngày | Tiếng Việt |
|-------|------|-----------|
| 1 | Monday | Thứ 2 |
| 2 | Tuesday | Thứ 3 |
| 3 | Wednesday | Thứ 4 |
| 4 | Thursday | Thứ 5 |
| 5 | Friday | Thứ 6 |
| 6 | Saturday | Thứ 7 |
| 7 | Sunday | Chủ nhật |

---

## 🚀 Cách Tích Hợp

### **1. Import component trong PostListing.vue:**
```vue
import AppointmentSlotsForm from '@/components/AppointmentSlotsForm.vue';
```

### **2. Thay thế section cũ:**
Xóa section "Đặt lịch xem nhà" cũ, thay bằng:
```vue
<AppointmentSlotsForm ref="appointmentForm" />
```

### **3. Cập nhật submitListing():**
```javascript
// Lấy data từ component
const appointmentSlots = appointmentForm.value.getFormData();

// Gửi API
await listingService.createAppointmentSlots(listingId, appointmentSlots);
```

---

## 📞 Support

Nếu có vấn đề:
1. Kiểm tra console browser (DevTools)
2. Kiểm tra server logs: `php artisan pail`
3. Test API bằng Postman
4. Verify database migration đã run: `php artisan migrate`
