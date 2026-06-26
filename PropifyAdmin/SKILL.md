# PropifyAdmin - Hướng dẫn Phát triển Frontend & Standard Best Practices
> **Tài liệu hướng dẫn dành cho Lập trình viên Vue 3 tại Propify**
> 
> *Bản cập nhật mới nhất: Tháng 6, 2026*
> *Người soạn thảo: Technical Leader & Frontend Architect*

---

## 1. Quy chuẩn Thiết kế Dashboard Doanh nghiệp (Enterprise Design System & Aesthetics)

Hệ thống quản trị doanh nghiệp đòi hỏi sự chính xác, trực quan, khả năng hiển thị mật độ thông tin cao nhưng vẫn đảm bảo tính thẩm mỹ, dễ đọc và nhất quán.

### 1.1. Nguyên tắc Giao diện Chung (UI/UX Foundation)
*   **Khoảng cách và Bố cục**: Thiết lập khoảng cách đồng đều dựa trên hệ thống spacing tiêu chuẩn của Tailwind CSS v4. Ưu tiên sử dụng `p-4` (1rem) đến `p-6` (1.5rem) làm khoảng đệm tiêu chuẩn cho các thẻ và khu vực nội dung chính.
*   **Đường viền mảnh**: Sử dụng `border border-border` làm ranh giới chia cắt các phân vùng dữ liệu để tạo cảm giác tinh tế, sắc nét thay vì các đường kẻ đậm màu.
*   **Quản lý Màu sắc**: 
    *   Tuyệt đối **không** tự ý thêm các mã màu hex rời rạc trong mã nguồn. Chỉ sử dụng bộ màu đã định nghĩa sẵn trong `@theme` ở `style.css`.
    *   **Primary (Màu chủ đạo)**: `bg-primary` / `text-primary-foreground` cho các CTA chính.
    *   **Success (Hoạt động/Thành công)**: Phê duyệt tin đăng, trạng thái tài khoản đang hoạt động (`text-success` / `bg-success/10`).
    *   **Destructive (Bị khóa/Từ chối/Lỗi)**: Khóa tài khoản, từ chối tin đăng, lỗi hệ thống (`text-destructive` / `bg-destructive/10`).
    *   **Warning (Đang chờ duyệt/Cảnh báo)**: Tin đăng chờ duyệt, giao dịch đang xử lý (`text-warning` / `bg-warning/10`).

> [!IMPORTANT]
> Toàn bộ màu sắc trạng thái phải luôn đi kèm với màu nền mờ tương ứng (opacity 10%) để tạo độ tương phản nhẹ nhàng, chuyên nghiệp cho các nhãn Badge. Ví dụ: `class="px-2 py-1 rounded text-xs font-medium bg-success/10 text-success"`.

### 1.2. Thẻ Thông số (KPI / Metric Cards)
Thẻ thông số là thành phần cốt lõi của Dashboard. Bố cục của thẻ phải tuân thủ nghiêm ngặt cấu trúc phân cấp thông tin:
1.  **Tiêu đề nhỏ bên trên**: font chữ nhỏ (`text-xs` hoặc `text-sm`), màu chữ phụ (`text-muted-foreground`), kết hợp biểu tượng minh họa.
2.  **Số liệu lớn nổi bật ở giữa**: chữ đậm (`font-bold` hoặc `font-semibold`), kích thước tối thiểu `text-2xl` hoặc `text-3xl`, màu `text-foreground`.
3.  **Chỉ số tăng trưởng ở dưới**: Thể hiện biến động phần trăm (+/- %) so với kỳ trước, đi kèm biểu tượng xu hướng (mũi tên lên/xuống). Màu sắc xanh (`text-success`) cho tăng trưởng dương, đỏ (`text-destructive`) cho tăng trưởng âm (hoặc ngược lại tùy ngữ cảnh).

**Ví dụ thiết kế chuẩn mực với Tailwind CSS v4:**
```html
<div class="rounded-xl border border-border bg-card p-6 shadow-card hover:shadow-card-hover transition-all duration-300">
  <div class="flex items-center justify-between">
    <span class="text-sm font-medium text-muted-foreground">Tổng doanh thu gói tin</span>
    <span class="text-muted-foreground"><DollarSignIcon class="size-5" /></span>
  </div>
  <div class="mt-2 flex items-baseline gap-2">
    <span class="text-3xl font-bold tracking-tight text-foreground">1,240,500,000 ₫</span>
  </div>
  <div class="mt-2 flex items-center gap-1 text-xs">
    <span class="flex items-center font-medium text-success">
      <TrendingUpIcon class="mr-0.5 size-3" />
      +12.5%
    </span>
    <span class="text-muted-foreground">so với tháng trước</span>
  </div>
</div>
```

### 1.3. Bảng Dữ liệu & Danh sách (Data Tables & List Views)
Để quản lý dữ liệu lớn một cách hiệu quả, bảng dữ liệu phải tuân thủ các quy tắc sau:
*   **Cố định Layout**: Luôn khai báo `table-layout: fixed` trên thẻ `<table>`. Đảm bảo các cột quan trọng (ví dụ: ID, Trạng thái, Hành động) được set chiều rộng cố định (`w-24`, `w-32`, etc.), các cột chứa nội dung tự do (như tên dự án, tiêu đề tin) sử dụng class `truncate` để tránh đẩy vỡ bố cục bảng khi chuỗi quá dài.
*   **Trạng thái Tải dữ liệu (Loading State)**: **Tuyệt đối không** sử dụng spinner xoay tròn toàn màn hình hoặc phủ mờ bảng làm che khuất dữ liệu cũ và gây khó chịu khi chuyển trang. Thay vào đó, hãy sử dụng **Skeletons** (khung xương giả lập cấu trúc dòng) để duy trì cảm giác ổn định bố cục.
*   **Trạng thái Trống (Empty State)**: Khi không có dữ liệu trả về từ bộ lọc, luôn hiển thị một khu vực đồ họa trống nhẹ nhàng, chỉ rõ lý do kèm nút hành động "Đặt lại bộ lọc".

```html
<!-- Bố cục Table cấu trúc xương (Skeleton) khi loading -->
<tbody v-if="isLoading">
  <tr v-for="i in 5" :key="i" class="animate-pulse border-b border-border/50">
    <td class="p-4"><div class="h-4 w-12 rounded bg-muted"></div></td>
    <td class="p-4"><div class="h-4 w-40 rounded bg-muted"></div></td>
    <td class="p-4"><div class="h-4 w-24 rounded bg-muted"></div></td>
    <td class="p-4"><div class="h-4 w-20 rounded bg-muted"></div></td>
  </tr>
</tbody>
```

### 1.4. Drawers (Slide-over) thay vì Modals
*   **Nguyên tắc**: Khi admin cần xem nhanh thông tin chi tiết của một đối tượng (ví dụ: Chi tiết log thao tác, Lịch sử nạp tiền của User), **ưu tiên sử dụng Slide-over Drawer từ cạnh phải màn hình**.
*   **Lý do**: Modals giữa màn hình che toàn bộ khu vực làm việc hiện tại, gây mất ngữ cảnh và đòi hỏi người dùng phải tắt đi mới xem được dữ liệu xung quanh. Drawers cho phép admin vừa kiểm tra thông tin chi tiết vừa đối chiếu nhanh với danh sách nền đang hiển thị ở bên trái.
*   **Cách thiết kế**: Chiều rộng chuẩn từ `max-w-md` (448px) đến `max-w-2xl` (672px) tùy lượng thông tin. Có nút đóng (`X`) rõ ràng ở góc trên bên phải và cho phép đóng nhanh bằng phím `ESC` hoặc click ngoài vùng phủ (backdrop).

---

## 2. Tiêu chuẩn tổ chức logic trang Danh sách (List & Filtering Patterns)

Một lỗi phổ biến trong các hệ thống admin là trạng thái bộ lọc biến mất khi người dùng tải lại trang (F5) hoặc nhấn nút Back/Forward của trình duyệt. 

### 2.1. Đồng bộ Lọc & Phân trang lên URL (Query Params)
Toàn bộ trạng thái bộ lọc bao gồm: từ khóa tìm kiếm (`search`), trạng thái lọc (`status`), mã phân loại (`category_id`), số trang hiện tại (`page`) **PHẢI được lưu trữ trực tiếp trên URL**.

> [!TIP]
> Coi URL là "Single Source of Truth" duy nhất cho trạng thái hiển thị của trang danh sách. Tránh dùng ref nội bộ để lưu trữ trạng thái bộ lọc độc lập mà không đồng bộ.

**Mẫu triển khai chuẩn hóa trong Vue 3:**
```javascript
import { ref, computed, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'

const route = useRoute()
const router = useRouter()

// 1. Khởi tạo trạng thái lọc dựa vào URL Query Params hiện tại
const filters = ref({
  search: route.query.search || '',
  status: route.query.status || 'all',
  page: Number(route.query.page) || 1
})

// 2. Hàm kích hoạt cập nhật URL khi người dùng thay đổi bộ lọc
function updateUrlQueryParams() {
  const query = {}
  
  if (filters.value.search) query.search = filters.value.search
  if (filters.value.status !== 'all') query.status = filters.value.status
  if (filters.value.page > 1) query.page = filters.value.page

  router.push({ query })
}

// 3. Theo dõi sự thay đổi của URL (ví dụ khi nhấn Back/Forward) để cập nhật ngược lại component state
watch(
  () => route.query,
  (newQuery) => {
    filters.value.search = newQuery.search || ''
    filters.value.status = newQuery.status || 'all'
    filters.value.page = Number(newQuery.page) || 1
  }
)
```

### 2.2. Debouncing cho Ô Tìm Kiếm
Để tránh việc gửi hàng chục request API không cần thiết lên server khi người dùng gõ từng ký tự vào ô tìm kiếm, ta bắt buộc phải áp dụng kỹ thuật Debouncing.

> [!WARNING]
> Thời gian trì hoãn (Debounce delay) tiêu chuẩn cho ô tìm kiếm text là **300ms** đến **500ms**.

**Ví dụ kết hợp Debounce với URL sync:**
```javascript
import { ref, watch } from 'vue'
import { debounce } from 'lodash-es' // Hoặc viết một hàm debounce helper đơn giản

const searchInput = ref(route.query.search || '')

// Debounce hàm cập nhật URL
const debouncedUpdateUrl = debounce(() => {
  filters.value.search = searchInput.value
  filters.value.page = 1 // Reset về trang 1 khi tìm kiếm mới
  updateUrlQueryParams()
}, 400)

// Theo dõi người dùng nhập liệu để trigger debounce
watch(searchInput, () => {
  debouncedUpdateUrl()
})

// Theo dõi khi URL thay đổi (nhấn nút Quay lại/F5) để cập nhật ngược lại ô input
watch(
  () => route.query.search,
  (newVal) => {
    searchInput.value = newVal || ''
  }
)
```

---

## 3. Quản lý API & Tránh gọi trùng lặp dữ liệu với TanStack Query

PropifyAdmin sử dụng `@tanstack/vue-query` để tối ưu hóa việc quản lý dữ liệu bất đồng bộ từ Laravel API. Việc này giúp cải thiện tốc độ tải trang đáng kể và giảm tải cho backend nếu được cấu hình đúng cách.

### 3.1. Tập trung hóa Query Keys (Query Key Factory Pattern)
Để tránh gõ tay các chuỗi string thô (ví dụ: `['users', userId]`), có thể dẫn đến sai sót và không nhất quán giữa các trang khác nhau, toàn bộ Query Keys phải được tập trung quản lý tại `src/composables/queryKeys.js`.

**File `src/composables/queryKeys.js`:**
```javascript
export const adminKeys = {
  all: ['admin'],
  
  // Phân hệ quản lý User
  users: () => [...adminKeys.all, 'users'],
  userLists: (params) => [...adminKeys.users(), { params }],
  userDetail: (id) => [...adminKeys.users(), id],
  
  // Phân hệ Audit Logs
  auditLogs: (params) => [...adminKeys.all, 'audit-logs', { params }],
  
  // Phân hệ Transactions
  transactions: (params) => [...adminKeys.all, 'transactions', { params }],
  
  // Phân hệ Packages
  packages: () => [...adminKeys.all, 'packages'],
  packageLists: (params) => [...adminKeys.packages(), { params }],
  packageDetail: (id) => [...adminKeys.packages(), id],
}
```

### 3.2. Cấu hình `staleTime` phù hợp để ngăn chặn spam API
Mặc định của TanStack Query là `staleTime: 0`, tức là dữ liệu sẽ ngay lập tức bị coi là cũ và sẽ bị fetch lại bất cứ khi nào component mount hoặc window focus. Đối với hệ thống Dashboard, điều này cực kỳ lãng phí.

*   **Dữ liệu Cấu hình / Danh mục ít thay đổi (Gói dịch vụ, Tiện ích hệ thống)**: 
    *   Cấu hình `staleTime: 5 * 60 * 1000` (5 phút).
    *   Chỉ cập nhật lại khi người dùng thực hiện thao tác Thêm/Sửa/Xóa (Mutation).
*   **Dữ liệu Nghiệp vụ động (Audit Logs, Giao dịch, Danh sách Tin đăng)**:
    *   Cấu hình `staleTime: 30000` (30 giây).
    *   Tránh việc admin bị gọi lại API liên tục khi nhấp chuyển qua lại giữa các tab chi tiết và danh sách.

```javascript
// Ví dụ Query danh sách User
const { data, isLoading } = useQuery({
  queryKey: adminKeys.userLists(queryParams),
  queryFn: () => userService.getUsers(queryParams.value),
  staleTime: 30 * 1000, // Dữ liệu được coi là mới trong 30 giây
  placeholderData: (previousData) => previousData, // Giữ giao diện cũ khi đang tải trang mới
})
```

### 3.3. Prefetch Dữ liệu (Tải trước dữ liệu trang sau)
Để tạo cảm giác lật trang phân trang nhanh tức thì không độ trễ, ta thực hiện prefetch trang tiếp theo (`page + 1`) khi người dùng di chuột (hover) vào nút chuyển trang tiếp theo.

```javascript
import { useQueryClient } from '@tanstack/vue-query'
const queryClient = useQueryClient()

// Kích hoạt prefetch trang tiếp theo khi hover vào nút Next Page
function prefetchNextPage() {
  if (filters.value.page < totalPages.value) {
    const nextPageParams = {
      ...filters.value,
      page: filters.value.page + 1
    }
    
    queryClient.prefetchQuery({
      queryKey: adminKeys.userLists(nextPageParams),
      queryFn: () => userService.getUsers(nextPageParams),
      staleTime: 30 * 1000
    })
  }
}
```
```html
<button 
  @mouseenter="prefetchNextPage"
  @click="filters.page++"
  :disabled="filters.page >= totalPages"
>
  Trang sau
</button>
```

### 3.4. Optimistic Updates (Cập nhật phản hồi giả định)
Đối với các hành động chuyển trạng thái nhanh (như Khóa/Mở khóa User, Phê duyệt nhanh bài đăng), không nên bắt người dùng đợi vòng xoay API thành công. Hãy dùng Optimistic Updates để cập nhật UI ngay lập tức.

**Ví dụ Mutation Khóa tài khoản User:**
```javascript
import { useMutation, useQueryClient } from '@tanstack/vue-query'
import { userService } from '@/services/userService'
import { adminKeys } from '@/composables/queryKeys'

const queryClient = useQueryClient()

const { mutate: toggleLockUser } = useMutation({
  mutationFn: ({ userId, isLocked }) => userService.toggleLock(userId, { is_locked: isLocked }),
  
  // 1. Khi kích hoạt mutate:
  onMutate: async ({ userId, isLocked }) => {
    // Hủy các query đang fetch dở để tránh ghi đè dữ liệu
    await queryClient.cancelQueries({ queryKey: adminKeys.users() })
    
    // Lưu lại trạng thái cache hiện tại để backup nếu lỗi
    const previousUsersData = queryClient.getQueryData(adminKeys.userLists(currentFilters))
    
    // Cập nhật giả định (Optimistic Update) vào cache của query list
    queryClient.setQueryData(adminKeys.userLists(currentFilters), (old) => {
      if (!old) return old
      return {
        ...old,
        data: old.data.map(user => 
          user.id === userId ? { ...user, is_active: !isLocked } : user
        )
      }
    })
    
    // Trả về context chứa dữ liệu cũ để rollback
    return { previousUsersData }
  },
  
  // 2. Nếu mutation bị lỗi:
  onError: (err, newVariables, context) => {
    // Khôi phục lại dữ liệu cũ từ context
    if (context?.previousUsersData) {
      queryClient.setQueryData(adminKeys.userLists(currentFilters), context.previousUsersData)
    }
    // Hiển thị thông báo Toast lỗi
  },
  
  // 3. Sau khi hoàn thành (thành công hoặc thất bại):
  onSettled: () => {
    // Revalidate lại để đồng bộ chuẩn xác với cơ sở dữ liệu
    queryClient.invalidateQueries({ queryKey: adminKeys.users() })
  }
})
```

---

## 4. Phân chia Ranh giới Quản lý State trong Hệ thống Admin

Để tránh mã nguồn trở nên hỗn loạn, lập trình viên cần phân biệt rõ ràng và tuân thủ ranh giới của 3 loại trạng thái (State) sau:

| Loại State | Phạm vi / Công nghệ | Mô tả & Quy tắc sử dụng |
| :--- | :--- | :--- |
| **Server State** | TanStack Vue Query | Toàn bộ dữ liệu fetch từ backend API. <br>**Quy tắc**: Tuyệt đối không gán dữ liệu này vào `ref` cục bộ hoặc Pinia store để thao tác trực tiếp, trừ khi làm giá trị mặc định khởi tạo cho Form. |
| **Global Client State** | Pinia Stores | Các trạng thái toàn cục phục vụ giao diện người dùng. <br>**Quy tắc**: Chỉ dùng cho trạng thái đóng mở Sidebar (`sidebarCollapsed`), Session làm việc (`token`, `user`), hoặc hệ thống thông báo Alert khẩn cấp toàn hệ thống. |
| **Local State** | Vue `ref` / `reactive` | Các trạng thái cục bộ bên trong một Component đơn lẻ. <br>**Quy tắc**: Trạng thái đóng/mở Drawer/Modal, giá trị hiện tại của form đang nhập, tab đang active. |

### 4.1. Ví dụ thực hành sai lầm (Anti-Pattern)
❌ **SAI (Anti-pattern - Gán dữ liệu API vào ref cục bộ)**:
```javascript
// Lỗi: Gây mất tính năng đồng bộ cache của Vue Query, làm phình to code quản lý loading/error thủ công
const userData = ref([])
onMounted(async () => {
  userData.value = await userService.getUsers()
})
```

### 4.2. Ví dụ thực hành chuẩn mực (Best Practice)
✅ **ĐÚNG (Sử dụng trực tiếp reactive state từ Vue Query)**:
```javascript
const { data: users, isLoading, error } = useQuery({
  queryKey: adminKeys.userLists(queryParams),
  queryFn: () => userService.getUsers(queryParams.value)
})
```

---

## 5. Danh sách kiểm tra chất lượng (Verification Checklist)

Trước khi thực hiện Pull Request (PR), lập trình viên phải tự kiểm tra các đầu mục sau:

- [ ] Trạng thái phân trang và bộ lọc đã hoạt động bình thường khi nhấn tải lại trang (F5).
- [ ] Không có mã màu Hex dạng thô nào được gõ cứng trong class Tailwind (phải dùng biến CSS hoặc các class `@theme` quy định).
- [ ] Mọi Query Key sử dụng trong component đều được gọi từ factory `adminKeys` trong `src/composables/queryKeys.js`.
- [ ] Dữ liệu tĩnh/cấu hình đã được set `staleTime` tối thiểu 5 phút để tránh gọi API dư thừa.
- [ ] Màn hình chi tiết nhanh được chuyển sang dạng Drawer bên phải thay vì sử dụng Modal giữa màn hình.
- [ ] Các hành động cập nhật trạng thái (Khóa tài khoản, Duyệt tin) đã áp dụng Optimistic Updates để tối ưu hóa tốc độ phản hồi UI.
