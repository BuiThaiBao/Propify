<script setup>
import { ref, computed } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { Search, Filter, Eye, CheckCircle, XCircle, Lock, MapPin, Maximize } from 'lucide-vue-next'

const search = ref('')
const statusFilter = ref('all')
const typeFilter = ref('all')
const confirmModal = ref({ open: false, title: '', desc: '', action: () => {} })

const mockPosts = [
  { id: 1, image: 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=300&h=200&fit=crop', title: 'Căn hộ 3PN Vinhomes Central Park', price: '5.2 tỷ', area: '120m²', location: 'Quận Bình Thạnh, TP.HCM', author: 'Nguyễn Văn A', status: 'approved', type: 'sale', date: '15/03/2024' },
  { id: 2, image: 'https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=300&h=200&fit=crop', title: 'Nhà phố Quận 2 mặt tiền đường lớn', price: '8.5 tỷ', area: '150m²', location: 'TP Thủ Đức, TP.HCM', author: 'Trần Thị B', status: 'pending', type: 'sale', date: '18/03/2024' },
  { id: 3, image: 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=300&h=200&fit=crop', title: 'Cho thuê căn hộ Masteri Thảo Điền', price: '15 tr/tháng', area: '70m²', location: 'TP Thủ Đức, TP.HCM', author: 'Lê Văn C', status: 'rejected', type: 'rent', date: '20/03/2024' },
  { id: 4, image: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=300&h=200&fit=crop', title: 'Biệt thự Phú Mỹ Hưng view sông', price: '25 tỷ', area: '350m²', location: 'Quận 7, TP.HCM', author: 'Phạm Văn D', status: 'approved', type: 'sale', date: '22/03/2024' },
  { id: 5, image: 'https://images.unsplash.com/photo-1600585154340-be6161a56a0c?w=300&h=200&fit=crop', title: 'Cho thuê văn phòng Landmark 81', price: '50 tr/tháng', area: '200m²', location: 'Quận Bình Thạnh, TP.HCM', author: 'Hoàng Thị E', status: 'pending', type: 'rent', date: '25/03/2024' },
  { id: 6, image: 'https://images.unsplash.com/photo-1600607687939-ce8a6c25118c?w=300&h=200&fit=crop', title: 'Đất nền Long An mặt tiền QL1A', price: '1.8 tỷ', area: '100m²', location: 'Long An', author: 'Đỗ Văn F', status: 'locked', type: 'sale', date: '28/03/2024' },
]

const filtered = computed(() => {
  return mockPosts.filter(p => {
    if (search.value && !p.title.toLowerCase().includes(search.value.toLowerCase())) return false
    if (statusFilter.value !== 'all' && p.status !== statusFilter.value) return false
    if (typeFilter.value !== 'all' && p.type !== typeFilter.value) return false
    return true
  })
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
          <option value="approved">Đã duyệt</option>
          <option value="pending">Chờ duyệt</option>
          <option value="rejected">Từ chối</option>
          <option value="locked">Đã khóa</option>
        </select>
        <select v-model="typeFilter" class="filter-select" id="posts-type-filter">
          <option value="all">Tất cả loại</option>
          <option value="sale">Mua bán</option>
          <option value="rent">Cho thuê</option>
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
              <th class="th">Người đăng</th>
              <th class="th">Ngày đăng</th>
              <th class="th">Trạng thái</th>
              <th class="th th-right">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="post in filtered" :key="post.id" class="table-row">
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
    <div v-if="filtered.length === 0" class="empty-state">
      <div class="empty-icon">
        <Search :size="28" color="hsl(215,16%,47%)" />
      </div>
      <h3 class="empty-title">Không tìm thấy tin đăng</h3>
      <p class="empty-desc">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm</p>
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
