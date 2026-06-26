# Hướng dẫn Phát triển Phần mềm (Best Practices) — PropifyFrontend

Tài liệu này định hình các tiêu chuẩn cốt lõi, kiến trúc mã nguồn và quy tắc lập trình bắt buộc đối với tất cả lập trình viên (và các AI coding agent) khi làm việc trên codebase **PropifyFrontend**. Mục tiêu là đảm bảo tính nhất quán cao, tối ưu hiệu năng mạng, tái sử dụng mã nguồn tối đa và giữ cho trải nghiệm người dùng (UX/UI) đạt chuẩn cao cấp (Premium).

---

## 1. Kiến trúc Component & Tổ chức File (Component Architecture)

Hệ thống tuân thủ mô hình phân lớp rõ ràng để giảm thiểu độ phụ thuộc chéo (coupling) và nâng cao khả năng bảo trì.

### Phân loại Component

```
src/components/
├── common/ (hoặc ui/)   <-- Stateless & Reusable UI (Câm lặng)
├── <feature-name>/      <-- Feature Components (Chứa nghiệp vụ tính năng)
└── shared/              <-- Các card hoặc widget dùng chung ở nhiều trang
src/pages/               <-- Route Pages (Điều phối & Khởi tạo state)
```

#### A. Common / Base UI Components (`src/components/common/` hoặc `src/components/ui/`)
* **Đặc điểm**: Là các component nguyên tử (Button, Modal, Input, Badge, Dropdown...).
* **Nguyên tắc**: 
  * Phải **câm lặng (stateless)**: Không tự gọi API, không đọc trực tiếp từ Pinia store, không dùng router.
  * Giao tiếp hoàn toàn thông qua **Props** (nhận dữ liệu), **Slots** (hiển thị giao diện tùy biến) và **Emits** (phát sự kiện ra ngoài).
  * Không chứa các logic nghiệp vụ (business logic) cụ thể của dự án.

> [!IMPORTANT]
> Tuyệt đối không import `api.js` hoặc bất kỳ service nào từ `src/services/` vào trong thư mục này.

#### B. Feature Components (`src/components/<feature-name>/`)
* **Đặc điểm**: Phục vụ một nghiệp vụ cụ thể của một chức năng (ví dụ: `src/components/appointments/AppointmentSlotsForm.vue`, `src/components/chat/shared/ChatInput.vue`).
* **Nguyên tắc**: 
  * Có thể đọc dữ liệu từ Pinia store hoặc sử dụng các composable của tính năng đó.
  * Chỉ nên xử lý UI và logic tương tác của riêng tính năng đó, tránh can thiệp sâu vào luồng dữ liệu của trang lớn.

#### C. Page Components (`src/pages/`)
* **Đặc điểm**: Là các component đại diện cho một Route (ví dụ: `src/pages/Listings/Detail.vue`, `src/pages/Home/index.vue`).
* **Nguyên tắc**:
  * Đóng vai trò **điều phối (orchestration)** chính.
  * Khởi tạo các composable, lấy params từ router (`useRoute`).
  * Kích hoạt trạng thái tải dữ liệu (`init()` hoặc kích hoạt `enabled` của Vue Query).
  * Truyền dữ liệu dạng raw xuống các component con và lắng nghe sự kiện từ chúng để thực hiện các action tiếp theo.

---

### Quy tắc Thiết kế SFC (Single File Component) với `<script setup>`

Mỗi file `.vue` cần tuân thủ cấu trúc thống nhất để tăng tốc độ đọc code của lập trình viên và AI.

#### Thứ tự khai báo trong `<script setup>`
Hãy sắp xếp code theo thứ tự nghiêm ngặt sau:
1. **Imports**: Thư viện ngoài trước, sau đó là composables, services, stores, helpers nội bộ.
2. **Props & Emits**: Định nghĩa bằng `defineProps` và `defineEmits`.
3. **Reactive State**: Các biến trạng thái (`ref`, `reactive`).
4. **Computed State**: Các trạng thái phái sinh (`computed`).
5. **Watchers**: Các bộ theo dõi (`watch`, `watchEffect`).
6. **Lifecycle Hooks**: `onMounted`, `onUnmounted`, v.v.
7. **Methods / Functions**: Các hàm xử lý sự kiện và logic nghiệp vụ.

```vue
<script setup>
// 1. Imports
import { ref, computed, onMounted } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { listingKeys } from '@/composables/queryKeys';
import listingService from '@/services/listingService';

// 2. Props & Emits
const props = defineProps({
  listingId: { type: Number, required: true }
});
const emit = defineEmits(['success']);

// 3. State
const isSubmitting = ref(false);

// 4. Computed
const formattedId = computed(() => `#BDS-${props.listingId}`);

// 5. Watchers
// watch(...)

// 6. Lifecycle
onMounted(() => {
  console.log('Component mounted for', formattedId.value);
});

// 7. Functions
async function handleAction() {
  isSubmitting.value = true;
  // logic...
  isSubmitting.value = false;
  emit('success');
}
</script>
```

#### Quy tắc đặt tên (Naming Conventions)
* **Component Files**: Đặt tên theo dạng **PascalCase** (ví dụ: `ListingCard.vue`, `AppointmentBookingPopup.vue`).
* **Composables**: Đặt tên theo dạng **camelCase** bắt đầu bằng tiền tố `use` (ví dụ: `usePublicListings.js`, `useFavoriteListings.js`).
* **Services**: Đặt tên theo dạng **camelCase** kết thúc bằng hậu tố `Service` (ví dụ: `listingService.js`, `authService.js`).

---

## 2. Quy tắc Thiết kế & Styling với Tailwind CSS v4

Dự án sử dụng **Tailwind CSS v4** với cơ chế cấu hình CSS-first thông qua `@theme` đặt trong `src/assets/main.css`.

> [!IMPORTANT]
> **Quy tắc Vàng**: Tuyệt đối không tự tiện code chay các mã màu Hex (như `text-[#3B82F6]`) hoặc các giá trị khoảng cách không chuẩn (`p-[13px]`). Mọi màu sắc và kích thước phải tuân thủ bảng mã màu và spacing hệ thống đã được định nghĩa trong `@theme`.

### Sử dụng Semantic Colors & Custom Tokens

Khi styling, lập trình viên cần sử dụng các class trỏ đến CSS Variables đã định nghĩa:

| UI Element | CSS Variable (main.css) | Tailwind class tương ứng |
| :--- | :--- | :--- |
| **Màu thương hiệu / Chủ đạo** | `--primary` (Màu xanh dương) | `bg-primary`, `text-primary` |
| **Màu nhấn mạnh** | `--accent` (Màu xanh ngọc) | `bg-accent`, `text-accent` |
| **Màu nền phụ / Nhạt** | `--muted` / `--muted-foreground` | `bg-muted`, `text-muted-foreground` |
| **Màu thông báo thành công** | `--badge-success` | `bg-badge-success` |
| **Màu viền chuẩn** | `--border` | `border-border` |
| **Nền bo góc chuẩn** | `--radius` | `rounded-lg` (vừa), `rounded-md` (nhỏ) |

### Responsive Design: Mobile-First
Luôn viết CSS cho thiết bị di động trước, sau đó mới dùng các breakpoint (`md:`, `lg:`, `xl:`) để override cho màn hình máy tính.
* **Sai**: `class="w-[800px] max-w-full md:w-full"`
* **Đúng**: `class="w-full md:w-[800px]"`

### Premium Aesthetics & Interaction (Trải nghiệm cao cấp)
Để ứng dụng mang lại cảm giác mượt mà, đắt giá cho người dùng tìm kiếm bất động sản, mọi hiệu ứng hover, focus, transition cần được thiết kế chỉn chu:
* Luôn sử dụng transition khi đổi màu hoặc kích thước khi hover:
  `transition-all duration-300 ease-in-out`
* Tránh chuyển đổi đột ngột không có hiệu ứng chuyển cảnh.
* Tận dụng các custom utility hoạt họa được khai báo trong `main.css` như `@utility scroll-reveal`, `stagger-1`, `stagger-2` để áp dụng hiệu ứng xuất hiện tuần tự (staggered animation) cho danh sách tin đăng.

```vue
<!-- Ví dụ cấu trúc một Card tin đăng chuẩn Tailwind v4 -->
<div class="bg-card text-card-foreground rounded-lg border border-border overflow-hidden hover:shadow-lg transition-all duration-300 ease-in-out scroll-reveal stagger-1">
  <div class="p-4">
    <span class="text-xs font-semibold text-text-caption">CĂN HỘ</span>
    <h3 class="text-lg font-bold text-foreground mt-1 hover:text-primary transition-colors duration-200">
      Vinhomes Grand Park 2PN
    </h3>
    <button class="mt-4 w-full bg-primary hover:bg-primary/90 text-primary-foreground font-semibold py-2 px-4 rounded-md transition-colors duration-300">
      Xem chi tiết
    </button>
  </div>
</div>
```

---

## 3. Quản lý API & Tránh gọi trùng API (TanStack Query v5 Best Practices)

Tối ưu hóa số lượng kết nối mạng và bộ nhớ đệm (caching) là chìa khóa giúp Propify hoạt động cực nhanh trên thiết bị di động của khách hàng.

### Nguyên tắc Vàng: Không gọi trực tiếp Service trong Component để load data
> [!WARNING]
> Cấm tuyệt đối việc gọi trực tiếp `listingService.getPublicListings()` hay `packageService.getPackages()` bên trong hook `onMounted` của file Vue component để hiển thị dữ liệu lên màn hình. 
> Toàn bộ Server State bắt buộc phải đi qua **TanStack Query** (`useQuery`, `useMutation`).

* **Lý do**: Gọi trực tiếp qua service trong component sẽ bỏ qua hoàn toàn cơ chế cache, dẫn đến việc mỗi lần component re-render hoặc người dùng quay lại trang cũ, API đều bị gọi lại gây tốn tài nguyên máy chủ và làm chớp màn hình (flash UI).

### Quản lý Query Keys tập trung
Để tránh gõ sai chuỗi string ở các file khác nhau dẫn đến không thể vô hiệu hóa cache (invalidate) hoặc gọi trùng API, tất cả Query Keys phải được tập trung tại `src/composables/queryKeys.js`.

* Ví dụ cấu trúc Registry:
```javascript
export const listingKeys = {
  all: ['listings'],
  public: () => [...listingKeys.all, 'public'],
  publicList: (params = {}) => [...listingKeys.public(), params],
  detail: (id) => [...listingKeys.all, 'detail', Number(id)],
};
```

* Cách dùng trong Composable:
```javascript
import { computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { listingKeys } from '@/composables/queryKeys';
import listingService from '@/services/listingService';

export function useListingDetail(listingIdRef) {
  return useQuery({
    queryKey: computed(() => listingKeys.detail(listingIdRef.value)),
    queryFn: () => listingService.getDetail(listingIdRef.value),
    enabled: computed(() => !!listingIdRef.value),
    staleTime: 5 * 60 * 1000, // Dữ liệu chi tiết tin đăng ít thay đổi, giữ cache 5 phút
  });
}
```

### Tránh trùng lặp API khi chuyển trang
1. **Thiết lập `staleTime` mặc định hợp lý**: 
   * Đối với dữ liệu thường xuyên thay đổi (như real-time chat, thông báo): `staleTime: 0`.
   * Đối với dữ liệu bán biến động (danh sách tin đăng bất động sản): `staleTime: 60 * 1000` (1 phút).
   * Đối với cấu hình tĩnh (gói tin, danh mục loại nhà đất): `staleTime: 10 * 60 * 1000` (10 phút).
2. **Kiểm soát thông qua `enabled`**:
   * Chỉ kích hoạt truy vấn khi có đủ tham số cần thiết (ví dụ: `enabled: computed(() => !!userId.value)`).
   * Dùng hàm `init()` kích hoạt cờ `enabled.value = true` để trì hoãn việc gọi API cho đến khi component hoàn toàn sẵn sàng hoặc Layout tương ứng đã được render.

### Tối ưu hóa UX nâng cao (Advanced UX Pattern)

#### A. Prefetching dữ liệu trước khi chuyển trang (Nạp trước)
Tận dụng `queryClient.prefetchQuery` trong watcher trang hiện tại để tự động tải trang kế tiếp (pagination) hoặc tải dữ liệu khi người dùng hover vào link/card trước khi bấm. Điều này tạo cảm giác trang tải ngay lập tức (Instant Load).

```javascript
// Ví dụ nạp trước trang tiếp theo trong usePublicListings.js
watch(
  () => [currentPage.value, enabled.value],
  ([page, isEnabled]) => {
    if (!isEnabled) return;
    const nextPage = page + 1;
    
    // Nạp trước dữ liệu của trang tiếp theo vào cache
    queryClient.prefetchQuery({
      queryKey: listingKeys.publicList({ page: nextPage, ...otherParams }),
      queryFn: () => fetchPublicListingsPage({ page: nextPage, ...options }),
      staleTime: 60 * 1000,
    });
  }
);
```

#### B. Tránh chớp trắng giao diện bằng `keepPreviousData`
Khi người dùng chuyển trang hoặc đổi bộ lọc, giao diện mặc định sẽ chuyển sang trạng thái Loading (biến mất danh sách cũ, hiển thị spinner). Để duy trì danh sách cũ mờ đi trong lúc tải dữ liệu mới (Pagination mượt mà):

```javascript
import { keepPreviousData, useQuery } from "@tanstack/vue-query";

const query = useQuery({
  queryKey,
  queryFn: () => fetchPage(currentPage.value),
  placeholderData: keepPreviousData, // Giữ dữ liệu cũ hiển thị trong lúc chờ API trang mới
});
```

---

## 4. Phân chia Quản lý State (State Management Boundaries)

Dự án áp dụng quy tắc phân chia ranh giới quản lý state nghiêm ngặt để đảm bảo "Một nguồn dữ liệu đáng tin cậy duy nhất" (Single Source of Truth).

```
┌────────────────────────────────────────────────────────┐
│                      STATE TYPES                       │
├───────────────────┬───────────────────┬────────────────┤
│   Server State    │   Global Client   │  Local Client  │
│   (Vue Query)     │      (Pinia)      │   (ref/react)  │
├───────────────────┼───────────────────┼────────────────┤
│ - Danh sách BĐS   │ - Session User    │ - Trạng thái   │
│ - Chi tiết tin    │ - Danh sách Chat  │   đóng/mở      │
│ - Danh mục bds    │ - Số thông báo    │ - Giá trị form │
│ - Danh sách gói   │   chưa đọc        │   đang nhập    │
└───────────────────┴───────────────────┴────────────────┘
```

### 1. Server State (Trạng thái Máy chủ)
* **Phạm vi**: Tất cả dữ liệu trả về từ các API Endpoint của backend.
* **Cách quản lý**: Để **TanStack Query** tự động lưu trữ, cập nhật và dọn dẹp bộ nhớ đệm thông qua `queryKey`.
* **Cấm kỵ**:
  > [!WARNING]
  > Tuyệt đối không sao chép danh sách trả về từ API (ví dụ: `response.data.data`) vào một `ref([])` cục bộ của component hoặc đưa vào Pinia store để lưu trữ thủ công, sau đó tự viết hàm xóa/sửa trên mảng đó.
  
  * **Giải pháp đúng**: Nếu muốn cập nhật dữ liệu (như khi người dùng bấm "Yêu thích"), hãy sử dụng `useMutation` để gọi API cập nhật lên server, sau đó chạy `queryClient.invalidateQueries({ queryKey })` để Vue Query tự động kích hoạt lấy lại dữ liệu mới nhất từ server.

### 2. Client Global State (Trạng thái Toàn cục)
* **Phạm vi**: Dữ liệu cần chia sẻ chéo giữa nhiều trang độc lập, cần duy trì xuyên suốt phiên làm việc.
* **Cách quản lý**: Dùng **Pinia store** đặt tại `src/stores/`.
* **Các store được định nghĩa sẵn**:
  * `useAuthStore` (`stores/auth.js`): Quản lý token JWT, trạng thái đăng nhập và thông tin tài khoản cá nhân hiện tại.
  * `useChatStore` (`stores/chat.js`): Quản lý danh sách hội thoại realtime và tin nhắn hiện hành.
  * `useNotificationStore` (`stores/notifications.js`): Quản lý các thông báo đẩy trong hệ thống.

### 3. Local Component State (Trạng thái Cục bộ)
* **Phạm vi**: Các trạng thái chỉ có ý nghĩa hiển thị tạm thời bên trong một Component hoặc một Page.
* **Cách quản lý**: Dùng `ref` hoặc `reactive` ngay trong component đó.
* **Ví dụ**:
  * Trạng thái đóng/mở của hộp thoại: `const isOpen = ref(false)`.
  * Tab đang được chọn: `const activeTab = ref('sale')`.
  * Dữ liệu form đăng tin đang nhập dở: `const formData = reactive({ title: '', price: 0 })`.

---

## Tóm tắt Luồng Xử lý Dữ liệu Chuẩn (Data Flow)

Khi phát triển một tính năng mới (ví dụ: Quản lý tin đăng yêu thích của Khách hàng):

```
1. Định nghĩa API Call (Axios) tại src/services/favoriteService.js
                        │
                        ▼
2. Định nghĩa Query Key tại src/composables/queryKeys.js
                        │
                        ▼
3. Viết Composable useFavoriteListings.js gói useQuery/useMutation
                        │
                        ▼
4. Sử dụng Composable trong Page Component (src/pages/Profile/Favorites.vue)
                        │
                        ▼
5. Truyền dữ liệu dạng prop cho các Component con (ListingCard.vue) để hiển thị
```

> [!TIP]
> Việc tuân thủ nghiêm ngặt ranh giới dữ liệu và cách tổ chức component giúp ứng dụng hoạt động ổn định, tránh được 90% lỗi bất đồng bộ trạng thái (out-of-sync state) thường gặp trong ứng dụng Vue 3 quy mô lớn.
