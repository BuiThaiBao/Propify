<script setup>
import { ref, watch, onMounted } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { Search, Filter, Eye, CheckCircle, XCircle, Lock, MapPin, Maximize, Package as PackageIcon, ChevronLeft, ChevronRight } from 'lucide-vue-next'
import { listingService } from '@/services/listingService'

const search = ref('')
const statusFilter = ref('all')
const typeFilter = ref('all')
const confirmModal = ref({ open: false, title: '', desc: '', action: () => {} })

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

function openConfirm(title, desc) {
  confirmModal.value = {
    open: true, title, desc,
    action: () => { confirmModal.value.open = false },
  }
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
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr class="table-head-row">
              <th class="th">Tin đăng</th>
              <th class="th">Giá</th>
              <th class="th">Diện tích</th>
              <th class="th">Loại</th>
              <th class="th">Gói tin</th>
              <th class="th">Người đăng</th>
              <th class="th">Ngày đăng</th>
              <th class="th">Trạng thái</th>
              <th class="th th-right">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading" class="table-row">
              <td colspan="9" class="td text-center text-slate-500 py-8">Đang tải dữ liệu...</td>
            </tr>
            <tr v-else-if="posts.length === 0" class="table-row">
              <td colspan="9" class="td text-center text-slate-500 py-8">Không có tin đăng nào.</td>
            </tr>
            <tr v-for="post in posts" :key="post.id" class="table-row">
              <!-- Tin đăng -->
              <td class="td">
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
              <td class="td">
                <span class="text-primary font-semibold text-sm">{{ post.price }}</span>
              </td>
              <td class="td">
                <span class="area-text">
                  <Maximize :size="12" color="hsl(215,16%,47%)" />
                  {{ post.area }}
                </span>
              </td>
              <td class="td">
                <span class="type-badge" :class="post.type === 'sale' ? 'type-sale' : 'type-rent'">
                  {{ post.type === 'sale' ? 'Mua bán' : 'Cho thuê' }}
                </span>
              </td>
              <td class="td">
                <span v-if="post.package" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-xs font-semibold border" :style="{ borderColor: post.package.color + '40', color: post.package.color, backgroundColor: post.package.color + '10' }">
                  <PackageIcon :size="14" />
                  {{ post.package.name }}
                </span>
                <span v-else class="text-slate-400 text-xs italic">Tin thường</span>
              </td>
              <td class="td text-sm" style="color: hsl(var(--foreground))">{{ post.author }}</td>
              <td class="td text-sm" style="color: hsl(var(--muted-foreground))">{{ post.date }}</td>
              <td class="td"><StatusBadge :status="post.status" /></td>
              <td class="td">
                <div class="actions">
                  <button class="act-btn" title="Xem chi tiết" :id="`view-${post.id}`">
                    <Eye :size="16" color="hsl(215,16%,47%)" />
                  </button>
                  <button
                    class="act-btn act-btn-success"
                    title="Duyệt tin"
                    :id="`approve-${post.id}`"
                    @click="openConfirm('Duyệt tin', `Bạn có muốn duyệt tin &quot;${post.title}&quot;?`)"
                  >
                    <CheckCircle :size="16" color="hsl(var(--success))" />
                  </button>
                  <button
                    class="act-btn act-btn-danger"
                    title="Từ chối"
                    :id="`reject-${post.id}`"
                    @click="openConfirm('Từ chối tin', `Bạn có muốn từ chối tin &quot;${post.title}&quot;?`)"
                  >
                    <XCircle :size="16" color="hsl(var(--destructive))" />
                  </button>
                  <button
                    class="act-btn"
                    title="Khóa tin"
                    :id="`lock-${post.id}`"
                    @click="openConfirm('Khóa tin', `Bạn có muốn khóa tin &quot;${post.title}&quot;?`)"
                  >
                    <Lock :size="16" color="hsl(215,16%,47%)" />
                  </button>
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
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.table-head-row {
  border-bottom: 1px solid hsl(var(--border));
  background-color: hsl(var(--muted) / 0.5);
}

.th {
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: hsl(var(--muted-foreground));
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 20px;
}

.th-right { text-align: right; }

.table-row {
  border-bottom: 1px solid hsl(var(--border));
  transition: background-color 0.1s;
}

.table-row:last-child { border-bottom: none; }
.table-row:hover { background-color: hsl(var(--muted) / 0.3); }

.td { padding: 12px 20px; vertical-align: middle; }

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

.post-title {
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--foreground));
  margin: 0 0 2px 0;
  max-width: 260px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.post-loc {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin: 0;
  display: flex;
  align-items: center;
  gap: 4px;
}

.area-text {
  font-size: 14px;
  color: hsl(var(--foreground));
  display: flex;
  align-items: center;
  gap: 4px;
}

/* Type badge */
.type-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
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
