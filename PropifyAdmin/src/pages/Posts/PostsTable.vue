<script setup>
import { computed } from 'vue'
import { Eye, CheckCircle, XCircle, Lock } from 'lucide-vue-next'
import StatusBadge from '@/components/shared/StatusBadge.vue'

const props = defineProps({
  search: String,
  filterStatus: String,
  filterType: String,
})

const allPosts = [
  {
    id: 1,
    title: 'Căn hộ 3PN Vinhomes Central Park',
    location: 'Quận Bình Thạnh, TP.HCM',
    price: '5.2 tỷ',
    area: '120m²',
    type: 'sale',
    typeLabel: 'Mua bán',
    poster: 'Nguyễn Văn A',
    date: '15/03/2024',
    status: 'approved',
    statusLabel: 'Đã duyệt',
    image: 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=80&h=60&fit=crop',
  },
  {
    id: 2,
    title: 'Nhà phố Quận 2 mặt tiền đường lớn',
    location: 'Quận 2, TP.HCM',
    price: '8.5 tỷ',
    area: '150m²',
    type: 'sale',
    typeLabel: 'Mua bán',
    poster: 'Trần Thị B',
    date: '18/03/2024',
    status: 'pending',
    statusLabel: 'Chờ duyệt',
    image: 'https://images.unsplash.com/photo-1570129477492-45c003edd2be?w=80&h=60&fit=crop',
  },
  {
    id: 3,
    title: 'Cho thuê căn hộ Masteri Thảo Điền',
    location: 'TP Thủ Đức, TP.HCM',
    price: '15 tr/tháng',
    area: '70m²',
    type: 'rent',
    typeLabel: 'Cho thuê',
    poster: 'Lê Văn C',
    date: '20/03/2024',
    status: 'rejected',
    statusLabel: 'Từ chối',
    image: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=80&h=60&fit=crop',
  },
  {
    id: 4,
    title: 'Biệt thự Phú Mỹ Hưng view sông',
    location: 'Quận 7, TP.HCM',
    price: '25 tỷ',
    area: '350m²',
    type: 'sale',
    typeLabel: 'Mua bán',
    poster: 'Phạm Văn D',
    date: '22/03/2024',
    status: 'approved',
    statusLabel: 'Đã duyệt',
    image: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=80&h=60&fit=crop',
  },
  {
    id: 5,
    title: 'Cho thuê văn phòng Landmark 81',
    location: 'Quận Bình Thạnh, TP.HCM',
    price: '50 tr/tháng',
    area: '200m²',
    type: 'rent',
    typeLabel: 'Cho thuê',
    poster: 'Hoàng Thị E',
    date: '25/03/2024',
    status: 'pending',
    statusLabel: 'Chờ duyệt',
    image: 'https://images.unsplash.com/photo-1497366216548-37526070297c?w=80&h=60&fit=crop',
  },
  {
    id: 6,
    title: 'Đất nền Long An mặt tiền QL1A',
    location: 'Long An',
    price: '1.8 tỷ',
    area: '100m²',
    type: 'sale',
    typeLabel: 'Mua bán',
    poster: 'Đỗ Văn F',
    date: '28/03/2024',
    status: 'locked',
    statusLabel: 'Đã khóa',
    image: 'https://images.unsplash.com/photo-1500382017468-9049fed747ef?w=80&h=60&fit=crop',
  },
]

const statusVariantMap = {
  approved: 'success',
  pending: 'warning',
  rejected: 'danger',
  locked: 'locked',
}

const filteredPosts = computed(() => {
  return allPosts.filter(post => {
    const matchSearch = !props.search ||
      post.title.toLowerCase().includes(props.search.toLowerCase()) ||
      post.poster.toLowerCase().includes(props.search.toLowerCase())
    const matchStatus = props.filterStatus === 'all' || post.status === props.filterStatus
    const matchType = props.filterType === 'all' || post.type === props.filterType
    return matchSearch && matchStatus && matchType
  })
})
</script>

<template>
  <div class="posts-table-wrap">
    <table class="posts-table">
      <thead>
        <tr>
          <th>Tin đăng</th>
          <th>Giá</th>
          <th>Diện tích</th>
          <th>Loại</th>
          <th>Người đăng</th>
          <th>Ngày đăng</th>
          <th>Trạng thái</th>
          <th>Hành động</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="post in filteredPosts" :key="post.id" class="post-row">
          <!-- Tin đăng -->
          <td>
            <div class="post-info">
              <img :src="post.image" :alt="post.title" class="post-img" />
              <div>
                <p class="post-title">{{ post.title }}</p>
                <p class="post-location">📍 {{ post.location }}</p>
              </div>
            </div>
          </td>

          <!-- Giá -->
          <td>
            <span class="post-price">{{ post.price }}</span>
          </td>

          <!-- Diện tích -->
          <td>
            <span class="post-area">⊡ {{ post.area }}</span>
          </td>

          <!-- Loại -->
          <td>
            <StatusBadge :text="post.typeLabel" variant="info" />
          </td>

          <!-- Người đăng -->
          <td>
            <span class="post-poster">{{ post.poster }}</span>
          </td>

          <!-- Ngày đăng -->
          <td>
            <span class="post-date">{{ post.date }}</span>
          </td>

          <!-- Trạng thái -->
          <td>
            <StatusBadge
              :text="post.statusLabel"
              :variant="statusVariantMap[post.status]"
            />
          </td>

          <!-- Hành động -->
          <td>
            <div class="post-actions">
              <button class="action-btn view" title="Xem" :id="`view-post-${post.id}`">
                <Eye :size="15" />
              </button>
              <button class="action-btn approve" title="Duyệt" :id="`approve-post-${post.id}`">
                <CheckCircle :size="15" />
              </button>
              <button class="action-btn reject" title="Từ chối" :id="`reject-post-${post.id}`">
                <XCircle :size="15" />
              </button>
              <button class="action-btn lock" title="Khóa" :id="`lock-post-${post.id}`">
                <Lock :size="15" />
              </button>
            </div>
          </td>
        </tr>
      </tbody>
    </table>

    <div v-if="filteredPosts.length === 0" class="empty-state">
      <p>Không tìm thấy tin đăng phù hợp</p>
    </div>
  </div>
</template>

<style scoped>
.posts-table-wrap {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
}

.posts-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.posts-table thead tr {
  background-color: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.posts-table th {
  padding: 12px 14px;
  text-align: left;
  font-size: 12px;
  font-weight: 600;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.4px;
  white-space: nowrap;
}

.post-row {
  border-bottom: 1px solid #f1f5f9;
  transition: background-color 0.1s;
}

.post-row:last-child {
  border-bottom: none;
}

.post-row:hover {
  background-color: #f8fafc;
}

.posts-table td {
  padding: 12px 14px;
  vertical-align: middle;
}

/* Post info */
.post-info {
  display: flex;
  align-items: center;
  gap: 10px;
}

.post-img {
  width: 60px;
  height: 45px;
  object-fit: cover;
  border-radius: 6px;
  flex-shrink: 0;
  border: 1px solid #e2e8f0;
}

.post-title {
  font-size: 13px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 2px 0;
  max-width: 220px;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.post-location {
  font-size: 11px;
  color: #94a3b8;
  margin: 0;
}

.post-price {
  font-weight: 600;
  color: #2563eb;
}

.post-area {
  color: #64748b;
}

.post-poster {
  color: #0f172a;
  font-weight: 500;
}

.post-date {
  color: #64748b;
  white-space: nowrap;
}

/* Actions */
.post-actions {
  display: flex;
  align-items: center;
  gap: 4px;
}

.action-btn {
  width: 30px;
  height: 30px;
  border: none;
  border-radius: 6px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.action-btn.view {
  background-color: #f1f5f9;
  color: #64748b;
}

.action-btn.view:hover {
  background-color: #e2e8f0;
  color: #0f172a;
}

.action-btn.approve {
  background-color: #d1fae5;
  color: #059669;
}

.action-btn.approve:hover {
  background-color: #a7f3d0;
}

.action-btn.reject {
  background-color: #fee2e2;
  color: #dc2626;
}

.action-btn.reject:hover {
  background-color: #fecaca;
}

.action-btn.lock {
  background-color: #f1f5f9;
  color: #64748b;
}

.action-btn.lock:hover {
  background-color: #e2e8f0;
}

.empty-state {
  padding: 40px;
  text-align: center;
  color: #94a3b8;
  font-size: 14px;
}
</style>
