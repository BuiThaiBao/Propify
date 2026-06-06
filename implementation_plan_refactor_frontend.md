# Kế hoạch Refactor Frontend (PropifyFrontend) — Tối ưu hóa Reusability & Chuẩn hóa Tailwind CSS (Đã cập nhật theo feedback)

Kế hoạch này tập trung vào việc loại bỏ mã nguồn trùng lặp trong phần frontend của dự án Propify (Vite, Vue 3.5, Pinia, Tailwind CSS), tăng cường khả năng tái sử dụng component/composable, và chuẩn hóa các mã CSS tự viết (basic CSS) sang Tailwind CSS theo lộ trình từng bước an toàn (backward compatible).

---

## Nguyên tắc cốt lõi khi Refactor
- **Không xóa ngay lập tức:** Không xóa ngay component/composable cũ trong lần refactor đầu tiên. Ưu tiên tạo wrapper hoặc migrate từng bước để đảm bảo tương thích ngược. Chỉ xóa file cũ sau khi đã tìm kiếm toàn project, build thành công và chắc chắn không còn import.
- **Không ép buộc Tailwind 100%:** Giữ lại các CSS phức tạp, animation đặc thù, hoặc hiệu ứng đặc biệt trong thẻ `<style scoped>` nếu việc chuyển đổi sang Tailwind làm mã nguồn quá dài và khó đọc.
- **Null-safety:** Đảm bảo các hàm định dạng dữ liệu trong utils chịu được dữ liệu null/undefined từ API.
- **Bảo toàn nghiệp vụ:** Tuyệt đối giữ nguyên UI/UX, router path, API endpoints và business logic hiện tại.

---

## Proposed Changes — Lộ trình triển khai từng bước (Phases)

### Phase 1: Trích xuất các hàm định dạng (Formatters) dùng chung

Chúng ta sẽ gom tất cả các hàm định dạng dữ liệu bị trùng lặp ở nhiều file khác nhau về một utility file duy nhất để dễ bảo trì.

#### [NEW] [listingFormatters.js](file:///d:/PROJECT/Meyland/PropifyFrontend/src/utils/listingFormatters.js)
Tạo tệp tiện ích mới chứa các hàm null-safe:
- `formatPrice(value)`: Định dạng tiền tệ (tỷ, triệu, đồng, thỏa thuận).
- `propertyTypeLabel(type)`: Bản đồ nhãn loại hình bất động sản.
- `timeAgo(dateStr)`: Tính thời gian tương đối đăng tin.
- `isVerified(item)`: Trả về boolean đại diện trạng thái đã xác thực.
- `getThumb(item)`: Lấy ảnh thumbnail hoặc ảnh đầu tiên trong mảng ảnh.
- `getAuthor(item)`: Trích xuất thông tin tác giả tin đăng (name, role, phone).

#### [MODIFY] [index.vue (Rent)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Rent/index.vue)
- Import và dùng các hàm từ `src/utils/listingFormatters.js`.
- Loại bỏ khai báo cục bộ của các hàm này.

#### [MODIFY] [index.vue (Sale)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Sale/index.vue)
- Import và dùng các hàm từ `src/utils/listingFormatters.js`.
- Loại bỏ khai báo cục bộ của các hàm này.

#### [MODIFY] [index.vue (Profile)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Profile/index.vue)
- Import và dùng các hàm từ `src/utils/listingFormatters.js`.
- Loại bỏ khai báo cục bộ của các hàm này.

#### [MODIFY] [Detail.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Listings/Detail.vue)
- Import và dùng các hàm từ `src/utils/listingFormatters.js`.
- Loại bỏ khai báo cục bộ của các hàm này.

#### [MODIFY] [ListingSection.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/common/Home/ListingSection.vue)
- Import và dùng các hàm từ `src/utils/listingFormatters.js`.
- Loại bỏ khai báo cục bộ của các hàm này.

---

### Phase 2: Tạo component dùng chung ListingRowCard.vue và thiết lập Wrapper

#### [NEW] [ListingRowCard.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/shared/ListingRowCard.vue)
Tạo component chung hỗ trợ đầy đủ các hiệu ứng (image carousel, border glow của các gói VIP/Diamond/Ruby/Gold, views/phone toggle, v.v.).
Props thiết kế:
```javascript
const props = defineProps({
  listing: {
    type: Object,
    required: true
  },
  unit: {
    type: String,
    default: ''
  },
  mode: {
    type: String,
    default: 'sale' // 'rent' | 'sale'
  }
})
```

#### [MODIFY] [RentCard.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/shared/RentCard.vue)
Thay đổi ruột của `RentCard.vue` thành wrapper gọi `ListingRowCard.vue` nhằm đảm bảo tương thích ngược:
```html
<template>
  <ListingRowCard
    :listing="listing"
    unit="/tháng"
    mode="rent"
    @toggleFavorite="$emit('toggleFavorite')"
  />
</template>
```

#### [MODIFY] [SaleCard.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/shared/SaleCard.vue)
Thay đổi ruột của `SaleCard.vue` thành wrapper gọi `ListingRowCard.vue`:
```html
<template>
  <ListingRowCard
    :listing="listing"
    unit=""
    mode="sale"
    @toggleFavorite="$emit('toggleFavorite')"
  />
</template>
```

---

### Phase 3: Di chuyển trang Rent/Sale sang dùng trực tiếp ListingRowCard.vue

#### [MODIFY] [index.vue (Rent)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Rent/index.vue)
- Thay đổi import từ `RentCard.vue` sang `ListingRowCard.vue`.
- Cập nhật template để truyền trực tiếp `:listing="item"`, `:unit="'/tháng'"`, `:mode="'rent'"`.

#### [MODIFY] [index.vue (Sale)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Sale/index.vue)
- Thay đổi import từ `SaleCard.vue` sang `ListingRowCard.vue`.
- Cập nhật template để truyền trực tiếp `:listing="item"`, `:unit="''"`, `:mode="'sale'"`.

#### [DELETE] [RentCard.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/shared/RentCard.vue) (Chỉ sau khi kiểm tra không còn tệp nào import)
#### [DELETE] [SaleCard.vue](file:///d:/PROJECT/Meyland/PropifyFrontend/src/components/shared/SaleCard.vue) (Chỉ sau khi kiểm tra không còn tệp nào import)

---

### Phase 4: Thiết lập Composable dùng chung usePublicListings.js

#### [NEW] [usePublicListings.js](file:///d:/PROJECT/Meyland/PropifyFrontend/src/composables/usePublicListings.js)
Tạo composable dùng chung nhận vào config object. Để tránh việc xung đột dữ liệu cache giữa Rent và Sale khi sử dụng `@tanstack/vue-query`, queryKey sẽ chứa `demandType`.
```javascript
export function usePublicListings(options = {}) {
  const {
    demandType, // 'RENT' | 'SALE'
    pageSize = 10,
    initialFilters = {}
  } = options
  
  // queryKey: ['public-listings', demandType, page, filters, keyword, ...]
  // logic phân trang, lọc, suggestions...
}
```

#### [MODIFY] [index.vue (Rent)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Rent/index.vue)
- Dùng `usePublicListings({ demandType: 'RENT' })` thay cho `useRentListings()`.

#### [MODIFY] [index.vue (Sale)](file:///d:/PROJECT/Meyland/PropifyFrontend/src/pages/Sale/index.vue)
- Dùng `usePublicListings({ demandType: 'SALE' })` thay cho `useSaleListings()`.

#### [DELETE] [useRentListings.js](file:///d:/PROJECT/Meyland/PropifyFrontend/src/composables/useRentListings.js) (Chỉ xóa sau khi không còn tệp nào import)
#### [DELETE] [useSaleListings.js](file:///d:/PROJECT/Meyland/PropifyFrontend/src/composables/useSaleListings.js) (Chỉ xóa sau khi không còn tệp nào import)

---

### Phase 5: Refactor CSS trong PostForm.vue theo các bước nhỏ

Thực hiện chỉnh sửa CSS từng phần nhỏ trong `src/pages/Listings/PostForm.vue`, ưu tiên chuẩn hóa các lớp Tailwind tiêu chuẩn:
1. **Bước 5.1: Chuyển đổi phần tử nhập liệu & nút bấm cơ bản:**
   - Chuyển `.input` thành Tailwind classes (ví dụ: `w-full border border-slate-200 rounded-lg bg-slate-50/50 px-3 py-2 text-[12px] text-slate-800 outline-none focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20`).
   - Chuyển `.search-btn` thành Tailwind classes.
   - Chuyển `.quick-chip`, `.quick-chip-active` sang Tailwind classes.
2. **Bước 5.2: Chuyển đổi cấu trúc Card Layout:**
   - Thay thế `.section-card`, `.section-title`, `.collapsible-title` bằng Tailwind class phù hợp (sử dụng `p-4` hoặc `p-5` thay cho `.p-4.5`).
3. **Bước 5.3: Chuyển đổi Dropdown Legal:**
   - Thay thế `.legal-trigger`, `.legal-dropdown`, `.legal-option`, `.legal-option.selected`.
   - Dùng icon `ChevronDown` của `lucide-vue-next` để thay thế CSS vẽ mũi tên thủ công `.dropdown-arrow-icon`.
4. **Bước 5.4: Giữ lại các hiệu ứng đặc biệt:**
   - Giữ nguyên các CSS animation toast stack `.toast-item` và các transition đặc thù nếu Tailwind quá cồng kềnh.

---

## Verification Plan

### Automated Checks
1. Kiểm tra build dự án:
```powershell
npm run build
```
2. Kiểm tra lỗi cú pháp và code style:
```powershell
npx eslint .
npx prettier --check .
```

### Script & Config Updates
Cập nhật `package.json` để bổ sung các lệnh chạy tiện ích giúp kiểm tra định dạng và code style nhanh hơn:
- `lint`: `eslint .`
- `format`: `prettier --write .`

### Manual Verification
- Chạy môi trường local dev (`npm run dev`).
- Truy cập vào đúng các route hiện tại của trang Cho thuê, Mua bán, Profile và Đăng tin theo cấu hình router.
- Đảm bảo giao diện không bị lệch và các chức năng tìm kiếm, phân trang hoạt động bình thường.
