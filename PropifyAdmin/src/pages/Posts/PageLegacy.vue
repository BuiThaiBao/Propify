<script setup>
import { ref, watch, onMounted } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { Search, Filter, Eye, CheckCircle, XCircle, Lock, MapPin, Maximize, Package as PackageIcon, ChevronLeft, ChevronRight, MoreVertical } from 'lucide-vue-next'
import { listingService } from '@/services/listingService'

const search = ref('')
const statusFilter = ref('all')
const typeFilter = ref('all')
const confirmModal = ref({ open: false, title: '', desc: '', action: () => {} })
const activeDropdown = ref(null)
const dropdownPosition = ref({ top: 0, left: 0 })

const toggleDropdown = (id, event) => {
  if (activeDropdown.value === id) {
    activeDropdown.value = null
  } else {
    if (event && event.currentTarget) {
      const rect = event.currentTarget.getBoundingClientRect()
      const dropdownWidth = 140 // min-w-[140px]
      const viewportWidth = window.innerWidth
      let left = rect.right + 8

      // Prevent overflow on right edge - flip to left side
      if (left + dropdownWidth > viewportWidth - 16) {
        left = rect.left - dropdownWidth - 8
      }

      dropdownPosition.value = {
        top: rect.top + rect.height / 2, // center vertically
        left: left
      }
    }
    activeDropdown.value = id
  }
}

// Close dropdown when clicking outside
onMounted(() => {
  document.addEventListener('click', (e) => {
    if (!e.target.closest('.action-menu-container')) {
      activeDropdown.value = null
    }
  })
})

const posts = ref([])
const loading = ref(false)
const currentPage = ref(1)
const totalPages = ref(1)
const totalItems = ref(0)
let searchTimeout = null

const fetchPosts = async () => {
  loading.value = true
  try {
    const params = {
      per_page: 8,
      page: currentPage.value,
    }
    if (statusFilter.value !== 'all') params.status = statusFilter.value
    if (typeFilter.value !== 'all') params.demand_type = typeFilter.value
    if (search.value) params.keyword = search.value

    const res = await listingService.getAllListings(params)
    
    // Map dữ liệu từ API sang format cho UI
    posts.value = res.data.data.map(p => {
      // Map status API -> StatusBadge props
      let badgeStatus = 'pending'
      if (p.status === 'ACTIVE') badgeStatus = 'approved'
      else if (p.status === 'REJECTED') badgeStatus = 'rejected'
      else if (p.status === 'LOCKED' || p.status === 'EXPIRED') badgeStatus = 'locked'

      // Format giá
      let priceText = 'Thỏa thuận'
      if (p.property?.price) {
        priceText = p.property.price >= 1000000000 
          ? (p.property.price / 1000000000).toFixed(1) + ' tỷ' 
          : (p.property.price / 1000000).toFixed(0) + ' triệu'
        if (p.demand_type === 'RENT') priceText += '/tháng'
      }

      // Format ngày
      const dateObj = new Date(p.submitted_at || new Date())
      const dateText = `${String(dateObj.getDate()).padStart(2, '0')}/${String(dateObj.getMonth() + 1).padStart(2, '0')}/${dateObj.getFullYear()}`

      return {
        id: p.id,
        image: p.images?.[0]?.url || 'https://placehold.co/300x200?text=No+Image',
        title: p.title || 'Không có tiêu đề',
        price: priceText,
        area: p.property?.area ? p.property.area + 'm²' : '--',
        location: p.property?.address_detail || '--',
        author: p.owner?.full_name || 'Khách',
        status: badgeStatus,
        type: p.demand_type === 'SALE' ? 'sale' : 'rent',
        date: dateText,
        package: p.package,
        views: p.views ?? 0,
      }
    })
    
    totalPages.value = res.data.meta.last_page || 1
    totalItems.value = res.data.meta.total || 0
  } catch (error) {
    console.error('Error fetching listings:', error)
  } finally {
    loading.value = false
  }
}

const changePage = (page) => {
  if (page >= 1 && page <= totalPages.value) {
    currentPage.value = page
    fetchPosts()
  }
}

// Theo dõi thay đổi của filter và search
watch([statusFilter, typeFilter], () => {
  currentPage.value = 1
  fetchPosts()
})

watch(search, () => {
  if (searchTimeout) clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    currentPage.value = 1
    fetchPosts()
  }, 500)
})

onMounted(() => {
  fetchPosts()
})

function openConfirm(title, desc, actionFn) {
  confirmModal.value = {
    open: true, 
    title, 
    desc,
    action: async () => { 
      try {
        if (actionFn) {
          await actionFn()
        }
      } catch (err) {
        console.error(err)
        alert('Có lỗi xảy ra: ' + (err.response?.data?.message || err.message))
      } finally {
        confirmModal.value.open = false
      }
    },
  }
}

const updateStatus = async (id, status) => {
  let rejectionReason = null
  if (status === 'REJECTED') {
    const reason = prompt("Lý do từ chối (có thể để trống):")
    if (reason === null) return // User cancelled
    rejectionReason = reason
  }
  await listingService.changeStatusForAdmin(id, { status, rejection_reason: rejectionReason })
  fetchPosts()
}
</script>

<template>
  <div>
    <PageHeader title="Quản lý tin đăng" description="Duyệt, quản lý và kiểm soát tin đăng bất động sản" />

    <!-- Filters -->
    <div class="filter-bar">
      <div class="filter-search">
        <Search :size="16" class="filter-icon" />
        <input
          v-model="search"
          type="text"
          placeholder="Tìm kiếm tin đăng..."
          class="filter-input"
          id="posts-search"
        />
      </div>
      <div class="filter-right">
        <Filter :size="16" color="hsl(215,16%,47%)" />
        <select v-model="statusFilter" class="filter-select" id="posts-status-filter">
          <option value="all">Tất cả trạng thái</option>
          <option value="ACTIVE">Đã duyệt</option>
          <option value="PENDING">Chờ duyệt</option>
          <option value="REJECTED">Từ chối</option>
          <option value="LOCKED">Đã khóa</option>
        </select>
        <select v-model="typeFilter" class="filter-select" id="posts-type-filter">
          <option value="all">Tất cả loại</option>
          <option value="SALE">Mua bán</option>
          <option value="RENT">Cho thuê</option>
        </select>
      </div>
    </div>

     <!-- Table -->
     <div class="table-wrap">
       <div class="table-scroll" @scroll="activeDropdown = null">
         <table class="data-table">
           <thead class="sticky-thead">
             <tr class="table-head-row">
              <th class="th th-post">Tin đăng</th>
              <th class="th">Giá</th>
              <th class="th">Diện tích</th>
              <th class="th">Loại</th>
              <th class="th">Gói tin</th>
              <th class="th">Lượt xem</th>
              <th class="th">Người đăng</th>
              <th class="th">Ngày đăng</th>
              <th class="th">Trạng thái</th>
              <th class="th th-right sticky-col w-12 text-center" style="padding: 12px 10px;"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading" class="table-row">
              <td colspan="10" class="td text-center text-slate-500 py-8">Đang tải dữ liệu...</td>
            </tr>
            <tr v-else-if="posts.length === 0" class="table-row">
              <td colspan="10" class="td text-center text-slate-500 py-8">Không có tin đăng nào.</td>
            </tr>
            <tr v-for="post in posts" :key="post.id" class="table-row">
              <!-- Tin đăng -->
              <td class="td td-post">
                <div class="post-info">
                  <img :src="post.image" :alt="post.title" class="post-img" />
                  <div class="post-text">
                    <p class="post-title">{{ post.title }}</p>
                    <p class="post-loc">
                      <MapPin :size="12" />
                      {{ post.location }}
                    </p>
                  </div>
                </div>
              </td>
              <td class="td whitespace-nowrap">
                <span class="text-primary font-semibold text-[13px]">{{ post.price }}</span>
              </td>
              <td class="td whitespace-nowrap">
                <span class="area-text text-[13px]">
                  <Maximize :size="12" color="hsl(215,16%,47%)" />
                  {{ post.area }}
                </span>
              </td>
              <td class="td whitespace-nowrap">
                <span class="type-badge" :class="post.type === 'sale' ? 'type-sale' : 'type-rent'">
                  {{ post.type === 'sale' ? 'Mua bán' : 'Cho thuê' }}
                </span>
              </td>
              <td class="td whitespace-nowrap">
                <span v-if="post.package" class="inline-flex items-center gap-1 px-2 py-1 rounded-md text-[11px] font-semibold border" :style="{ borderColor: post.package.color + '40', color: post.package.color, backgroundColor: post.package.color + '10' }">
                  <PackageIcon :size="12" />
                  {{ post.package.name }}
                </span>
                <span v-else class="text-slate-400 text-xs italic">Tin thường</span>
              </td>
              <td class="td text-[13px] whitespace-nowrap" style="color: hsl(var(--foreground))">
                <span class="views-text">
                  <Eye :size="14" color="hsl(215,16%,47%)" />
                  {{ post.views.toLocaleString('vi-VN') }}
                </span>
              </td>
              <td class="td text-[13px]" style="color: hsl(var(--foreground))">{{ post.author }}</td>
              <td class="td text-[13px]" style="color: hsl(var(--muted-foreground))">{{ post.date }}</td>
              <td class="td"><StatusBadge :status="post.status" /></td>
               <td class="td sticky-col action-menu-container p-0 align-middle">
                 <div class="flex justify-center w-full">
                    <button class="act-btn-more" @click.stop="toggleDropdown(post.id, $event)">
                     <MoreVertical :size="18" color="hsl(215,16%,47%)" />
                   </button>

                   <!-- Dropdown positioned as fixed to escape overflow clipping -->
                   <div v-if="activeDropdown === post.id"
                        class="fixed bg-white rounded-md shadow-lg border border-slate-200 py-1 min-w-[140px] z-[100] -translate-y-1/2"
                        :style="{ top: dropdownPosition.top + 'px', left: dropdownPosition.left + 'px' }">
                     <button class="menu-item" :id="`view-${post.id}`" @click="toggleDropdown(null)">
                       <Eye :size="15" />
                       Xem chi tiết
                     </button>
                     <button v-if="post.status === 'pending'" class="menu-item text-green-600" :id="`approve-${post.id}`" @click="toggleDropdown(null); openConfirm('Duyệt tin', `Bạn có muốn duyệt tin &quot;${post.title}&quot;?`, () => updateStatus(post.id, 'ACTIVE'))">
                       <CheckCircle :size="15" />
                       Duyệt tin
                     </button>
                     <button v-if="post.status === 'pending'" class="menu-item text-red-600" :id="`reject-${post.id}`" @click="toggleDropdown(null); openConfirm('Từ chối', `Bạn có muốn từ chối tin &quot;${post.title}&quot;?`, () => updateStatus(post.id, 'REJECTED'))">
                       <XCircle :size="15" />
                       Từ chối
                     </button>
                     <button v-if="post.status === 'locked' || post.status === 'rejected'" class="menu-item text-green-600" :id="`unlock-${post.id}`" @click="toggleDropdown(null); openConfirm('Mở khóa', `Bạn có muốn mở khóa tin &quot;${post.title}&quot;?`, () => updateStatus(post.id, 'ACTIVE'))">
                       <CheckCircle :size="15" />
                       Mở khóa
                     </button>
                     <button v-if="post.status === 'approved'" class="menu-item text-slate-700" :id="`lock-${post.id}`" @click="toggleDropdown(null); openConfirm('Khóa tin', `Bạn có muốn khóa tin &quot;${post.title}&quot;?`, () => updateStatus(post.id, 'LOCKED'))">
                       <Lock :size="15" />
                       Khóa tin
                     </button>
                   </div>
                 </div>
               </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Empty -->
    <div v-if="!loading && posts.length === 0" class="empty-state">
      <div class="empty-icon">
        <Search :size="28" color="hsl(215,16%,47%)" />
      </div>
      <h3 class="empty-title">Không tìm thấy tin đăng</h3>
      <p class="empty-desc">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
    </div>

    <!-- Pagination -->
    <div class="relative flex flex-col md:flex-row items-center justify-center mt-6 gap-3 md:gap-0" v-if="totalPages > 1">
      <div class="flex items-center gap-1">
        <button 
          @click="changePage(currentPage - 1)" 
          :disabled="currentPage === 1"
          class="p-1.5 border border-slate-200 rounded-md bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed text-slate-600 transition-colors"
        >
          <ChevronLeft :size="18" />
        </button>
        
        <button 
          v-for="page in totalPages" 
          :key="page"
          @click="changePage(page)"
          class="w-8 h-8 flex items-center justify-center border rounded-md text-sm font-semibold transition-colors"
          :class="page === currentPage ? 'bg-blue-600 text-white border-blue-600 shadow-sm' : 'bg-white border-slate-200 hover:bg-slate-50 text-slate-700'"
        >
          {{ page }}
        </button>
        
        <button 
          @click="changePage(currentPage + 1)" 
          :disabled="currentPage === totalPages"
          class="p-1.5 border border-slate-200 rounded-md bg-white hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed text-slate-600 transition-colors"
        >
          <ChevronRight :size="18" />
        </button>
      </div>
      <div class="md:absolute md:right-0 text-sm text-slate-500 font-medium">
        Hiển thị {{ (currentPage - 1) * 8 + 1 }} đến {{ Math.min(currentPage * 8, totalItems) }} của {{ totalItems }} tin đăng
      </div>
    </div>

    <ConfirmModal
      :open="confirmModal.open"
      :title="confirmModal.title"
      :description="confirmModal.desc"
      @close="confirmModal.open = false"
      @confirm="confirmModal.action()"
    />
  </div>
</template>

<style scoped>
/* Filter bar */
.filter-bar {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 16px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
  margin-bottom: 24px;
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 16px;
}

.filter-search {
  flex: 1;
  min-width: 240px;
  position: relative;
}

.filter-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: hsl(var(--muted-foreground));
}

.filter-input {
  width: 100%;
  padding: 8px 16px 8px 40px;
  background-color: hsl(var(--muted));
  border: none;
  border-radius: 8px;
  font-size: 14px;
  color: hsl(var(--foreground));
  outline: none;
  transition: box-shadow 0.15s;
}

.filter-input:focus {
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2);
}

.filter-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-select {
  background-color: hsl(var(--muted));
  font-size: 14px;
  border-radius: 8px;
  padding: 8px 12px;
  border: none;
  color: hsl(var(--foreground));
  outline: none;
  cursor: pointer;
  transition: box-shadow 0.15s;
}

.filter-select:focus {
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2);
}

/* Table */
.table-wrap {
  background-color: hsl(var(--card));
  border-radius: 12px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
  overflow: hidden;
}

.table-scroll {
  overflow: auto;
  max-height: calc(100vh - 280px);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.sticky-thead {
  position: sticky;
  top: 0;
  z-index: 5;
}

.table-head-row {
  border-bottom: 1px solid hsl(var(--border));
  background-color: hsl(var(--muted) / 0.5);
}

.th {
  text-align: left;
  font-size: 11px;
  font-weight: 600;
  color: hsl(var(--muted-foreground));
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 10px;
  position: sticky;
  top: 0;
  background-color: hsl(var(--muted) / 0.5);
  z-index: 20;
}

.th-right { text-align: right; }

.table-row {
  border-bottom: 1px solid hsl(var(--border));
  transition: background-color 0.1s;
  height: 85px;
}

.table-row:last-child { border-bottom: none; }
.table-row:hover { background-color: hsl(var(--muted) / 0.3); }

.td { padding: 8px 10px; vertical-align: middle; }

.sticky-col {
  position: sticky;
  right: 0;
  background-color: hsl(var(--card));
  z-index: 10;
  box-shadow: -4px 0 6px -4px rgba(0,0,0,0.1);
}

.table-head-row .sticky-col {
  background-color: hsl(var(--muted));
  z-index: 25;
}

.table-row:hover .sticky-col {
  background-color: hsl(var(--card) / 0.95);
}

/* Post info */
.post-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.post-img {
  width: 56px;
  height: 40px;
  border-radius: 8px;
  object-fit: cover;
  flex-shrink: 0;
}

.post-text {
  flex: 1;
  min-width: 0; /* needed for overflow to work */
}

.type-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 500;
  white-space: nowrap;
}

.type-sale {
  background-color: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

.type-rent {
  background-color: hsl(var(--accent));
  color: hsl(var(--accent-foreground));
}

/* Actions */
.actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 4px;
}

.act-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  background: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
}

.act-btn:hover { background-color: hsl(var(--muted)); }
.act-btn-success:hover { background-color: hsl(var(--success) / 0.1); }
.act-btn-danger:hover { background-color: hsl(var(--destructive) / 0.1); }

.act-btn-more {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 6px;
  background: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
}

.act-btn-more:hover {
  background-color: hsl(var(--muted));
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 8px;
  width: 100%;
  padding: 8px 12px;
  border: none;
  background: none;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  text-align: left;
  transition: background-color 0.15s;
}
.menu-item:hover {
  background-color: hsl(var(--muted) / 0.5);
}

.th-post {
  width: 25%;
  min-width: 200px;
}

.td-post {
  max-width: 280px;
}

.post-title {
  font-size: 13px;
  font-weight: 500;
  color: hsl(var(--foreground));
  margin: 0 0 4px 0;
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  white-space: normal;
  overflow: hidden;
  text-overflow: ellipsis;
  line-height: 1.4;
}

.post-loc {
  font-size: 11px;
  color: hsl(var(--muted-foreground));
  margin: 0;
  display: flex;
  align-items: center;
  gap: 4px;
  white-space: normal;
  overflow: hidden;
  text-overflow: ellipsis;
  display: -webkit-box;
  -webkit-line-clamp: 1;
  -webkit-box-orient: vertical;
}

.area-text {
  display: flex;
  align-items: center;
  gap: 4px;
  color: hsl(var(--foreground));
  white-space: nowrap;
}

.views-text {
  display: flex;
  align-items: center;
  gap: 4px;
  font-weight: 500;
  color: hsl(var(--muted-foreground));
  white-space: nowrap;
}

/* Empty state */
.empty-state {
  text-align: center;
  padding: 64px 0;
}

.empty-icon {
  width: 64px;
  height: 64px;
  border-radius: 50%;
  background-color: hsl(var(--muted));
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 16px;
}

.empty-title {
  font-size: 18px;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0 0 4px;
}

.empty-desc {
  font-size: 14px;
  color: hsl(var(--muted-foreground));
  margin: 0;
}
</style>
