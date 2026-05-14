<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  Ban,
  Calendar,
  CheckCircle,
  Clock,
  FileCheck,
  Image as ImageIcon,
  Info,
  Lock,
  MapPin,
  ShieldCheck,
  User,
  Video,
} from 'lucide-vue-next'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { listingService } from '@/services/listingService'

const route = useRoute()
const router = useRouter()

const listing = ref(null)
const loading = ref(false)
const error = ref('')
const activeTab = ref('info')
const actionLoading = ref(false)
const reasonModal = ref({ open: false, status: '', title: '', reason: '' })

const post = computed(() => listing.value)
const property = computed(() => post.value?.property ?? {})
const images = computed(() => post.value?.images ?? [])
const videos = computed(() => post.value?.videos ?? [])
const docs = computed(() => post.value?.verification_documents ?? [])
const histories = computed(() => post.value?.status_histories ?? [])

const statusKey = computed(() => mapStatusKey(post.value?.status))
const statusLabel = computed(() => mapStatusLabel(post.value?.status))
const verificationKey = computed(() => post.value?.is_verified ? 'approved' : 'locked')
const verificationLabel = computed(() => post.value?.is_verified ? 'Đã xác thực' : 'Chưa xác thực')
const mapSrc = computed(() => {
  const lat = property.value?.lat
  const lng = property.value?.lng
  if (!lat || !lng) return ''
  return `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`
})

const unitPrice = computed(() => {
  const price = Number(property.value?.price)
  const area = Number(property.value?.area)
  if (!price || !area) return '--'
  return `${formatMoney(Math.round(price / area))} đ/m²`
})

async function fetchDetail() {
  loading.value = true
  error.value = ''
  try {
    const res = await listingService.getListingDetail(route.params.id)
    listing.value = res.data?.data ?? null
    if (listing.value?.demand_type !== 'SALE') {
      error.value = 'Trang chi tiết này hiện chỉ hỗ trợ tin mua bán.'
    }
  } catch (err) {
    console.error('Failed to fetch listing detail:', err)
    error.value = err.response?.data?.message || 'Không thể tải chi tiết tin đăng.'
  } finally {
    loading.value = false
  }
}

function formatMoney(value) {
  const amount = Number(value)
  if (!Number.isFinite(amount)) return '--'
  return new Intl.NumberFormat('vi-VN').format(amount)
}

function formatDateTime(value) {
  if (!value) return '--'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '--'
  return new Intl.DateTimeFormat('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date)
}

function mapStatusKey(status) {
  return {
    ACTIVE: 'approved',
    PENDING: 'pending',
    REJECTED: 'rejected',
    LOCKED: 'locked',
    EXPIRED: 'locked',
  }[status] || 'pending'
}

function mapStatusLabel(status) {
  return {
    ACTIVE: 'Đã duyệt',
    PENDING: 'Chờ duyệt',
    REJECTED: 'Từ chối',
    LOCKED: 'Đã khóa',
    EXPIRED: 'Tin hết hạn',
  }[status] || status || 'Chờ duyệt'
}

function demandLabel(value) {
  return value === 'SALE' ? 'Mua bán' : value === 'RENT' ? 'Cho thuê' : '--'
}

function propertyTypeLabel(value) {
  return {
    APARTMENT: 'Căn hộ chung cư',
    HOUSE: 'Nhà riêng',
    VILLA: 'Biệt thự',
    LAND: 'Đất nền',
    TOWNHOUSE: 'Liền kề',
    HOTEL: 'Khách sạn',
    RESORT: 'Resort',
  }[value] || value || '--'
}

function historyActionLabel(value) {
  return {
    ACTIVE: 'Duyệt tin',
    REJECTED: 'Từ chối',
    LOCKED: 'Khóa tin',
  }[value] || value
}

function canApprove() {
  return ['PENDING', 'REJECTED', 'LOCKED'].includes(post.value?.status)
}

function canReject() {
  return ['PENDING', 'ACTIVE'].includes(post.value?.status)
}

function canLock() {
  return post.value?.status === 'ACTIVE'
}

async function changeStatus(status, reason = null) {
  if (!post.value) return
  actionLoading.value = true
  try {
    await listingService.changeStatusForAdmin(post.value.id, {
      status,
      reason,
      rejection_reason: reason,
    })
    await fetchDetail()
  } finally {
    actionLoading.value = false
  }
}

function openReason(status) {
  reasonModal.value = {
    open: true,
    status,
    title: status === 'LOCKED' ? 'Khóa tin' : 'Từ chối tin',
    reason: '',
  }
}

function submitReason() {
  const { status, reason } = reasonModal.value
  reasonModal.value.open = false
  changeStatus(status, reason.trim() || null)
}

onMounted(fetchDetail)
</script>

<template>
  <div class="detail-page">
    <button class="back-btn" @click="router.push({ name: 'Posts' })">
      <ArrowLeft :size="16" />
      Quay lại danh sách
    </button>

    <div v-if="loading" class="state-box">Đang tải chi tiết tin đăng...</div>
    <div v-else-if="error" class="state-box error">{{ error }}</div>

    <template v-else-if="post">
      <header class="detail-header">
        <div>
          <div class="title-row">
            <h1>Chi tiết tin đăng</h1>
            <span class="listing-code">LH-{{ String(post.id).padStart(8, '0') }}</span>
            <StatusBadge :status="statusKey" :label="statusLabel" />
            <StatusBadge :status="verificationKey" :label="verificationLabel" />
          </div>
          <div class="meta-row">
            <span><Calendar :size="14" /> Tạo: {{ formatDateTime(post.created_at) }}</span>
            <span><Clock :size="14" /> Cập nhật: {{ formatDateTime(post.published_at || post.submitted_at) }}</span>
          </div>
        </div>
        <div class="header-actions">
          <button v-if="canReject()" class="outline-danger" :disabled="actionLoading" @click="openReason('REJECTED')">
            <Ban :size="15" /> Từ chối
          </button>
          <button v-if="canLock()" class="outline-neutral" :disabled="actionLoading" @click="openReason('LOCKED')">
            <Lock :size="15" /> Khóa tin
          </button>
          <button v-if="canApprove()" class="primary-action" :disabled="actionLoading" @click="changeStatus('ACTIVE')">
            <CheckCircle :size="15" /> Duyệt tin
          </button>
        </div>
      </header>

      <div class="tabs">
        <button :class="{ active: activeTab === 'info' }" @click="activeTab = 'info'">Thông tin</button>
        <button :class="{ active: activeTab === 'verify' }" @click="activeTab = 'verify'">Xác thực</button>
      </div>

      <section v-if="activeTab === 'info'" class="section-stack">
        <article class="detail-card">
          <h2><ImageIcon :size="16" /> Hình ảnh & Video</h2>
          <div v-if="images.length" class="media-grid">
            <img v-for="image in images" :key="image.id" :src="image.url" :alt="post.title" />
          </div>
          <p v-else class="muted">Không có hình ảnh đính kèm</p>
          <p class="video-note"><Video :size="14" /> {{ videos.length ? `${videos.length} video đính kèm` : 'Không có video đính kèm' }}</p>
        </article>

        <article class="detail-card">
          <h2><Info :size="16" /> Thông tin bất động sản</h2>
          <div class="info-grid">
            <div class="info-item wide">
              <span>Tên bất động sản</span>
              <strong>{{ post.title || '--' }}</strong>
            </div>
            <div class="info-item">
              <span>Nhu cầu</span>
              <strong>{{ demandLabel(post.demand_type) }}</strong>
            </div>
            <div class="info-item">
              <span>Loại BĐS</span>
              <strong>{{ propertyTypeLabel(property.type) }}</strong>
            </div>
            <div class="info-item">
              <span>Giá bán</span>
              <strong>{{ formatMoney(property.price) }} đ</strong>
            </div>
            <div class="info-item">
              <span>Diện tích</span>
              <strong>{{ property.area ? `${property.area} m²` : '--' }}</strong>
            </div>
          </div>
          <div class="description">
            <span>Mô tả</span>
            <p>{{ post.description || '--' }}</p>
          </div>
          <div class="unit-price">Đơn giá: <strong>{{ unitPrice }}</strong></div>
        </article>

        <article class="detail-card">
          <h2><Info :size="16" /> Thông tin chi tiết</h2>
          <div class="info-grid detail-grid">
            <div class="info-item"><span>Phòng ngủ</span><strong>{{ property.bedrooms ?? '--' }}</strong></div>
            <div class="info-item"><span>Phòng tắm</span><strong>{{ property.bathrooms ?? '--' }}</strong></div>
            <div class="info-item"><span>Số tầng</span><strong>{{ property.floors ?? '--' }}</strong></div>
            <div class="info-item"><span>Tầng</span><strong>{{ property.floor_number ?? '--' }}</strong></div>
            <div class="info-item"><span>Mặt tiền</span><strong>{{ property.facade_width ? `${property.facade_width} m` : '--' }}</strong></div>
            <div class="info-item"><span>Chiều sâu</span><strong>{{ property.depth ? `${property.depth} m` : '--' }}</strong></div>
            <div class="info-item"><span>Đường rộng</span><strong>{{ property.road_width ? `${property.road_width} m` : '--' }}</strong></div>
            <div class="info-item"><span>Hướng nhà</span><strong>{{ property.direction_code || '--' }}</strong></div>
            <div class="info-item"><span>Hướng ban công</span><strong>{{ property.balcony_direction_code || '--' }}</strong></div>
            <div class="info-item"><span>Nội thất</span><strong>{{ property.furniture_status || '--' }}</strong></div>
            <div class="info-item"><span>Ban công</span><strong>{{ property.balconies ?? '--' }}</strong></div>
            <div class="info-item"><span>Thỏa thuận</span><strong>{{ property.is_negotiable ? 'Có' : 'Không' }}</strong></div>
          </div>
          <div v-if="property.amenities?.length" class="chips">
            <span v-for="item in property.amenities" :key="item">{{ item }}</span>
          </div>
        </article>

        <article class="detail-card">
          <h2><MapPin :size="16" /> Địa chỉ</h2>
          <div class="info-grid">
            <div class="info-item"><span>Tỉnh/TP</span><strong>{{ property.province_code || '--' }}</strong></div>
            <div class="info-item"><span>Phường/Xã</span><strong>{{ property.ward_code || '--' }}</strong></div>
            <div class="info-item"><span>Đường</span><strong>{{ property.street_code || '--' }}</strong></div>
            <div class="info-item wide"><span>Địa chỉ chi tiết</span><strong>{{ property.address_detail || '--' }}</strong></div>
          </div>
          <iframe v-if="mapSrc" class="map-frame" :src="mapSrc" loading="lazy"></iframe>
          <div v-else class="map-empty">Listing chưa có tọa độ bản đồ.</div>
        </article>

        <article class="detail-card">
          <h2><User :size="16" /> Người đăng</h2>
          <div class="info-grid">
            <div class="info-item"><span>Loại</span><strong>{{ property.poster_type || '--' }}</strong></div>
            <div class="info-item"><span>Họ tên</span><strong>{{ property.contact_name || post.owner?.full_name || '--' }}</strong></div>
            <div class="info-item"><span>Số điện thoại</span><strong>{{ property.contact_phone || '--' }}</strong></div>
            <div class="info-item"><span>Email</span><strong>{{ property.contact_email || post.owner?.email || '--' }}</strong></div>
          </div>
        </article>
      </section>

      <section v-else class="section-stack">
        <article class="detail-card">
          <div class="card-head">
            <h2><ShieldCheck :size="16" /> Giấy tờ pháp lý</h2>
          </div>
          <div v-if="docs.length" class="docs-grid">
            <div v-for="doc in docs" :key="doc.id" class="doc-card">
              <img :src="doc.url" :alt="doc.type" />
              <div>
                <strong>{{ doc.type }}</strong>
                <span>Đã tải lên</span>
              </div>
            </div>
          </div>
          <p v-else class="muted">Không có giấy tờ xác thực.</p>
        </article>

        <article class="detail-card">
          <h2><FileCheck :size="16" /> Lịch sử xử lý</h2>
          <div v-if="histories.length" class="history-list">
            <div v-for="history in histories" :key="history.id" class="history-item">
              <strong>{{ historyActionLabel(history.action) }}</strong>
              <span>{{ history.user?.full_name || `User #${history.user_id}` }} · {{ formatDateTime(history.created_at) }}</span>
              <p v-if="history.reason">{{ history.reason }}</p>
            </div>
          </div>
          <p v-else class="muted">Chưa có lịch sử xử lý.</p>
        </article>
      </section>
    </template>

    <div v-if="reasonModal.open" class="modal-backdrop" @click.self="reasonModal.open = false">
      <div class="modal-card">
        <h3>{{ reasonModal.title }}</h3>
        <textarea v-model="reasonModal.reason" rows="4" placeholder="Nhập lý do..."></textarea>
        <div class="modal-actions">
          <button class="outline-neutral" @click="reasonModal.open = false">Hủy</button>
          <button class="primary-action" @click="submitReason">Xác nhận</button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.detail-page { max-width: 1180px; margin: 0 auto; color: #0f172a; }
.back-btn { display: inline-flex; align-items: center; gap: 6px; border: 0; background: transparent; color: #64748b; font-weight: 600; cursor: pointer; margin-bottom: 22px; }
.detail-header { display: flex; justify-content: space-between; gap: 20px; margin-bottom: 20px; }
.title-row { display: flex; align-items: center; flex-wrap: wrap; gap: 10px; }
.title-row h1 { margin: 0; font-size: 26px; }
.listing-code { color: #64748b; font-weight: 700; }
.meta-row { display: flex; gap: 18px; margin-top: 10px; color: #64748b; font-size: 13px; }
.meta-row span { display: inline-flex; align-items: center; gap: 6px; }
.header-actions { display: flex; align-items: flex-start; gap: 10px; }
.tabs { display: inline-flex; gap: 4px; padding: 4px; border-radius: 999px; background: #f1f5f9; margin-bottom: 18px; }
.tabs button { border: 0; border-radius: 999px; padding: 9px 18px; background: transparent; color: #64748b; font-weight: 700; cursor: pointer; }
.tabs button.active { background: #fff; color: #0f172a; box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08); }
.section-stack { display: grid; gap: 18px; }
.detail-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03); }
.detail-card h2, .card-head h2 { display: flex; align-items: center; gap: 8px; margin: 0 0 16px; font-size: 17px; }
.media-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.media-grid img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0; }
.video-note, .muted { display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 13px; margin: 14px 0 0; }
.info-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.info-item { display: grid; gap: 5px; min-width: 0; }
.info-item.wide { grid-column: span 4; }
.info-item span { color: #64748b; font-size: 12px; }
.info-item strong { color: #0f172a; font-size: 14px; font-weight: 650; word-break: break-word; }
.description { margin-top: 16px; }
.description span { color: #64748b; font-size: 12px; }
.description p { margin: 6px 0 0; line-height: 1.7; }
.unit-price { margin-top: 16px; padding: 12px 14px; border: 1px solid #dbeafe; border-radius: 10px; background: #eff6ff; color: #3b82f6; font-size: 13px; }
.detail-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
.chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; }
.chips span { border-radius: 999px; background: #eff6ff; color: #2563eb; padding: 7px 12px; font-size: 12px; font-weight: 700; }
.map-frame { width: 100%; height: 430px; border: 0; border-radius: 12px; margin-top: 16px; }
.map-empty { margin-top: 16px; height: 180px; display: grid; place-items: center; border-radius: 12px; background: #f8fafc; color: #64748b; }
.docs-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.doc-card { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; }
.doc-card img { width: 100%; aspect-ratio: 16 / 10; object-fit: cover; display: block; }
.doc-card div { display: flex; justify-content: space-between; gap: 10px; padding: 12px; }
.doc-card span { color: #16a34a; font-size: 12px; font-weight: 700; }
.history-list { display: grid; gap: 10px; }
.history-item { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
.history-item span { display: block; color: #64748b; font-size: 12px; margin-top: 4px; }
.history-item p { margin: 8px 0 0; color: #334155; }
.primary-action, .outline-danger, .outline-neutral { height: 40px; display: inline-flex; align-items: center; gap: 7px; border-radius: 9px; padding: 0 14px; font-weight: 800; cursor: pointer; }
.primary-action { border: 1px solid #0ea5e9; background: #0ea5e9; color: #fff; }
.outline-danger { border: 1px solid #ef4444; background: #fff; color: #ef4444; }
.outline-neutral { border: 1px solid #cbd5e1; background: #fff; color: #475569; }
.state-box { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: 28px; color: #64748b; }
.state-box.error { color: #dc2626; }
.modal-backdrop { position: fixed; inset: 0; z-index: 1200; display: grid; place-items: center; background: rgba(15, 23, 42, 0.45); }
.modal-card { width: min(440px, calc(100vw - 32px)); background: #fff; border-radius: 12px; padding: 20px; }
.modal-card h3 { margin: 0 0 12px; }
.modal-card textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 10px; resize: vertical; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; }
@media (max-width: 900px) {
  .detail-header { flex-direction: column; }
  .media-grid, .docs-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .info-grid, .detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .info-item.wide { grid-column: span 2; }
}
</style>
