<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { userService } from '@/services/userService'
import {
  Mail,
  Phone,
  Calendar,
  Lock,
  Unlock,
  ShieldAlert,
  ArrowLeft,
  ChevronLeft,
  ChevronRight,
  Home,
  Sparkles,
  Layers,
  FileCheck,
  FileQuestion,
  FileWarning,
  ShieldCheck,
  AlertTriangle,
  CheckCircle2,
  X,
  History,
  UserCheck,
  UserX,
} from 'lucide-vue-next'

const route = useRoute()
const router = useRouter()
const userId = Number(route.params.id)

// ─── Lock Reasons ─────────────────────────────────────────────────────────────
const LOCK_REASONS = [
  { code: 1, label: 'Đăng tin giả mạo hoặc sai sự thật' },
  { code: 2, label: 'Lừa đảo, chiếm đoạt tài sản' },
  { code: 3, label: 'Đăng nội dung vi phạm quy định hệ thống' },
  { code: 4, label: 'Spam hoặc đăng tin trùng lặp nhiều lần' },
  { code: 5, label: 'Có hành vi quấy rối, gây ảnh hưởng đến người dùng khác' },
  { code: 6, label: 'Lý do khác' },
]

// ─── State ───────────────────────────────────────────────────────────────────
const user = ref(null)
const loading = ref(true)
const listingsLoading = ref(false)
const error = ref(null)
const actionSuccess = ref(null)

// Listings sub-table state
const listings = ref([])
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(8)
const totalListings = ref(0)

// Audit logs state
const auditLogs = ref([])
const logsLoading = ref(false)
const logsCurrentPage = ref(1)
const logsLastPage = ref(1)
const logsTotal = ref(0)
const logsPerPage = ref(5)

// Custom Lock Modal top-level refs
const lockModalOpen = ref(false)
const lockSelectedReason = ref(null)
const lockOtherText = ref('')
const lockOtherTextError = ref('')
const lockReasonError = ref('')

// Unlock confirm modal
const unlockModal = ref({ open: false })
const actionLoading = ref(false)

// ─── Computed ─────────────────────────────────────────────────────────────────
const pageNumbers = computed(() => {
  const pages = []
  const range = 2
  for (let i = Math.max(1, currentPage.value - range); i <= Math.min(lastPage.value, currentPage.value + range); i++) {
    pages.push(i)
  }
  return pages
})

const showingFrom = computed(() => totalListings.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1)
const showingTo = computed(() => Math.min(currentPage.value * perPage.value, totalListings.value))

const lockedOrRejectedCount = computed(() => {
  if (!user.value) return 0
  return (user.value.lockedPostsCount || 0) + (user.value.rejectedPostsCount || 0)
})

// Dynamic trust score — 50% for email + 50% for phone
const trustScore = computed(() => {
  if (!user.value) return 0
  let score = 0
  if (user.value.email) score += 50
  if (user.value.phone) score += 50
  return score
})

const trustLabel = computed(() => {
  const s = trustScore.value
  if (s === 100) return 'Đầy đủ thông tin'
  if (s === 50) return 'Thiếu thông tin'
  return 'Chưa xác thực'
})

const trustColor = computed(() => {
  const s = trustScore.value
  if (s === 100) return 'hsl(var(--success))'
  if (s === 50) return '#f59e0b'
  return 'hsl(var(--destructive))'
})

const isLockModalValid = computed(() => {
  if (!lockSelectedReason.value) return false
  if (Number(lockSelectedReason.value) === 6) {
    return lockOtherText.value.trim().length > 0 && lockOtherText.value.trim().length <= 500
  }
  return true
})

const otherTextRemaining = computed(() => 500 - (lockOtherText.value?.length || 0))

// ─── Formatters ───────────────────────────────────────────────────────────────
function formatPrice(price) {
  if (price === null || price === undefined || price === 0) return 'Thỏa thuận'
  if (price >= 1e9) return (price / 1e9).toFixed(1).replace(/\.0$/, '') + ' tỷ'
  if (price >= 1e6) return (price / 1e6).toFixed(1).replace(/\.0$/, '') + ' triệu'
  return price.toLocaleString('vi-VN') + ' đ'
}

function formatDateTime(val) {
  if (!val) return '—'
  return new Date(val).toLocaleString('vi-VN', {
    year: 'numeric',
    month: '2-digit',
    day: '2-digit',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

// Convert reason code to text
function getReasonLabel(code) {
  const reason = LOCK_REASONS.find(r => r.code === Number(code))
  return reason ? reason.label : code
}

function getListingStatusLabel(status) {
  switch (status?.toUpperCase()) {
    case 'PENDING': return 'Chờ duyệt'
    case 'ACTIVE': return 'Đã duyệt'
    case 'REJECTED': return 'Từ chối'
    case 'LOCKED': return 'Bị khóa'
    default: return status || 'Nháp'
  }
}

function getListingStatusClass(status) {
  switch (status?.toUpperCase()) {
    case 'PENDING': return 'status-pending'
    case 'ACTIVE': return 'status-approved'
    case 'REJECTED': return 'status-rejected'
    case 'LOCKED': return 'status-locked'
    default: return 'status-draft'
  }
}

function getAuditActionLabel(action) {
  switch (action) {
    case 'admin.user.locked': return 'Khóa tài khoản'
    case 'admin.user.unlocked': return 'Mở khóa tài khoản'
    default: return action
  }
}

// ─── API Requests ─────────────────────────────────────────────────────────────
async function fetchUserDetail() {
  loading.value = true
  error.value = null
  try {
    const res = await userService.getUser(userId)
    user.value = res.data?.data
  } catch (err) {
    console.error('[UserDetail] fetchUserDetail error:', err)
    const status = err?.response?.status
    const msg = err?.response?.data?.message
    if (status === 404) error.value = 'Không tìm thấy tài khoản.'
    else if (status === 403) error.value = 'Bạn không có quyền xem thông tin tài khoản này.'
    else error.value = msg || 'Lỗi kết nối máy chủ.'
  } finally {
    loading.value = false
  }
}

async function fetchUserListings() {
  listingsLoading.value = true
  try {
    const res = await userService.getUserListings(userId, { page: currentPage.value, per_page: perPage.value })
    const { data, meta } = res.data
    listings.value = data
    currentPage.value = meta.current_page ?? 1
    lastPage.value = meta.last_page ?? 1
    perPage.value = meta.per_page ?? 8
    totalListings.value = meta.total ?? 0
  } catch (err) {
    console.error('[UserDetail] fetchUserListings error:', err)
  } finally {
    listingsLoading.value = false
  }
}

async function fetchUserAuditLogs() {
  logsLoading.value = true
  try {
    const res = await userService.getUserAuditLogs(userId, { page: logsCurrentPage.value, per_page: logsPerPage.value })
    const { data, meta } = res.data
    auditLogs.value = data
    logsCurrentPage.value = meta.current_page ?? 1
    logsLastPage.value = meta.last_page ?? 1
    logsTotal.value = meta.total ?? 0
  } catch (err) {
    console.error('[UserDetail] fetchUserAuditLogs error:', err)
  } finally {
    logsLoading.value = false
  }
}

function handleGoToPage(page) {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  currentPage.value = page
  fetchUserListings()
}

function handleGoToLogsPage(page) {
  if (page < 1 || page > logsLastPage.value || page === logsCurrentPage.value) return
  logsCurrentPage.value = page
  fetchUserAuditLogs()
}

// ─── Lock Modal Logic ─────────────────────────────────────────────────────────
function openLockModal() {
  lockSelectedReason.value = null
  lockOtherText.value = ''
  lockOtherTextError.value = ''
  lockReasonError.value = ''
  lockModalOpen.value = true
}

function closeLockModal() {
  lockModalOpen.value = false
}

function validateLockForm() {
  lockReasonError.value = ''
  lockOtherTextError.value = ''
  if (!lockSelectedReason.value) {
    lockReasonError.value = 'Vui lòng chọn lý do khóa tài khoản.'
    return false
  }
  if (Number(lockSelectedReason.value) === 6) {
    const txt = lockOtherText.value.trim()
    if (!txt) {
      lockOtherTextError.value = 'Vui lòng mô tả lý do khóa.'
      return false
    }
    if (txt.length > 500) {
      lockOtherTextError.value = 'Lý do không được vượt quá 500 ký tự.'
      return false
    }
  }
  return true
}

async function handleLockConfirm() {
  if (!validateLockForm()) return
  actionLoading.value = true
  try {
    const reasonCode = Number(lockSelectedReason.value)
    const reasonText = reasonCode === 6 ? lockOtherText.value.trim() : null
    const res = await userService.changeStatus(userId, { status: 'locked', reason_code: reasonCode, reason_text: reasonText })
    const updated = res.data?.data
    if (updated) user.value = { ...user.value, ...updated }
    lockModalOpen.value = false
    actionSuccess.value = 'Đã khóa tài khoản thành công.'
    await fetchUserAuditLogs()
    setTimeout(() => { actionSuccess.value = null }, 4000)
  } catch (err) {
    console.error('[UserDetail] lockAccount error:', err)
    lockOtherTextError.value = err?.response?.data?.message || 'Khóa tài khoản thất bại.'
  } finally {
    actionLoading.value = false
  }
}

// ─── Unlock Logic ─────────────────────────────────────────────────────────────
function openUnlockModal() {
  unlockModal.value.open = true
}

async function handleUnlockConfirm() {
  actionLoading.value = true
  try {
    const res = await userService.changeStatus(userId, { status: 'active', reason_code: null, reason_text: null })
    const updated = res.data?.data
    if (updated) user.value = { ...user.value, ...updated }
    unlockModal.value.open = false
    actionSuccess.value = 'Đã mở khóa tài khoản thành công.'
    await fetchUserAuditLogs()
    setTimeout(() => { actionSuccess.value = null }, 4000)
  } catch (err) {
    console.error('[UserDetail] unlockAccount error:', err)
    error.value = err?.response?.data?.message || 'Mở khóa tài khoản thất bại.'
    unlockModal.value.open = false
  } finally {
    actionLoading.value = false
  }
}

onMounted(async () => {
  await fetchUserDetail()
  if (user.value) {
    await Promise.all([
      fetchUserListings(),
      fetchUserAuditLogs()
    ])
  }
})
</script>

<template>
  <div class="user-detail-page">
    <!-- Heading -->
    <div class="detail-header">
      <div class="header-left">
        <button class="back-btn" @click="router.push({ name: 'Users' })">
          <ArrowLeft :size="18" /> Quay lại
        </button>
        <div class="header-title">
          <p class="breadcrumbs">Quản lý tài khoản / Chi tiết</p>
          <h1>{{ user?.name || 'Chi tiết tài khoản' }}</h1>
        </div>
      </div>
      <div v-if="user" class="header-actions">
        <!-- LOCKED → show Unlock button -->
        <button v-if="user.status === 'locked'" class="action-btn btn-success" @click="openUnlockModal">
          <Unlock :size="18" /> Mở khóa tài khoản
        </button>
        <!-- ACTIVE → show Lock button -->
        <button v-else class="action-btn btn-destructive" @click="openLockModal">
          <Lock :size="18" /> Khóa tài khoản
        </button>
      </div>
    </div>

    <!-- Success notice -->
    <div v-if="actionSuccess" class="success-banner">
      <CheckCircle2 :size="18" /> {{ actionSuccess }}
    </div>

    <!-- Error notice -->
    <div v-if="error" class="error-banner">
      <ShieldAlert :size="18" />
      <span>{{ error }}</span>
      <button class="retry-btn" @click="fetchUserDetail">Thử lại</button>
    </div>

    <!-- Loading -->
    <div v-if="loading && !user" class="loading-state">
      <div class="spinner"></div>
      <span>Đang tải thông tin tài khoản...</span>
    </div>

    <!-- Main Grid -->
    <div v-else-if="user" class="detail-grid">
      <!-- LEFT: Profile Card -->
      <div class="profile-card">
        <div class="profile-banner"></div>
        <div class="avatar-section">
          <div class="avatar-container">
            <img v-if="user.avatarUrl" :src="user.avatarUrl" :alt="user.name" class="profile-avatar" />
            <div v-else class="profile-avatar fallback-avatar" :style="{ backgroundColor: user.avatarBg, color: user.avatarColor }">
              {{ user.initial }}
            </div>
            <span v-if="user.isGoogleAccount" class="google-badge-dot" title="Google Account">
              <svg viewBox="0 0 24 24" width="16" height="16" xmlns="http://www.w3.org/2000/svg">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
              </svg>
            </span>
          </div>
          <h2>{{ user.name }}</h2>
          <div class="badge-row">
            <span class="role-badge" :class="user.role === 'agent' ? 'role-agent' : 'role-user'">{{ user.roleLabel }}</span>
            <StatusBadge :status="user.status === 'locked' ? 'locked' : 'approved'" :label="user.statusLabel" />
          </div>
        </div>

        <!-- Contact Info -->
        <div class="info-section">
          <h3>Thông tin liên hệ</h3>
          <div class="info-box">
            <div class="info-item">
              <Mail :size="18" class="info-icon" />
              <div class="info-content">
                <label>Địa chỉ Email</label>
                <p>{{ user.email }}</p>
              </div>
            </div>
            <div class="info-item">
              <Phone :size="18" class="info-icon" />
              <div class="info-content">
                <label>Số điện thoại</label>
                <p :class="{ 'missing-info': !user.phone }">{{ user.phone || 'Chưa cung cấp' }}</p>
              </div>
            </div>
            <div class="info-item">
              <Calendar :size="18" class="info-icon" />
              <div class="info-content">
                <label>Ngày tham gia</label>
                <p>{{ user.joinDate?.replace('Tham gia: ', '') || 'Chưa rõ' }}</p>
              </div>
            </div>
            <div class="info-item">
              <span class="auth-type-icon info-icon">🔑</span>
              <div class="info-content">
                <label>Phương thức đăng nhập</label>
                <span v-if="user.isGoogleAccount" class="auth-pill google-pill">
                  <svg viewBox="0 0 24 24" width="13" height="13" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                  </svg>
                  Tài khoản Google
                </span>
                <span v-else class="auth-pill email-pill">Email & Mật khẩu</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Dynamic Trust Score -->
        <div class="info-section">
          <h3>Mức độ tin cậy</h3>
          <div class="trust-card">
            <div class="trust-top">
              <ShieldCheck :size="28" :style="{ color: trustColor }" />
              <div>
                <span class="trust-percent" :style="{ color: trustColor }">{{ trustScore }}%</span>
                <span class="trust-label-text">{{ trustLabel }}</span>
              </div>
            </div>
            <div class="trust-bar-wrap">
              <div class="trust-bar-bg">
                <div class="trust-bar-fill" :style="{ width: trustScore + '%', backgroundColor: trustColor }"></div>
              </div>
            </div>
            <ul class="trust-checklist">
              <li :class="user.email ? 'check-ok' : 'check-missing'">
                <CheckCircle2 v-if="user.email" :size="14" />
                <AlertTriangle v-else :size="14" />
                Email xác thực (+50%)
              </li>
              <li :class="user.phone ? 'check-ok' : 'check-missing'">
                <CheckCircle2 v-if="user.phone" :size="14" />
                <AlertTriangle v-else :size="14" />
                Số điện thoại (+50%)
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- RIGHT: Listings Panel & Audit Logs Panel -->
      <div class="right-column">
        <!-- Stats Row -->
        <div class="user-stats-row">
          <div class="sub-stat-card card-total">
            <div class="stat-icon-wrap"><Layers :size="22" /></div>
            <div class="stat-info">
              <span class="stat-label">Tổng tin đăng</span>
              <strong class="stat-value">{{ user.posts }}</strong>
            </div>
          </div>
          <div class="sub-stat-card card-active">
            <div class="stat-icon-wrap"><FileCheck :size="22" /></div>
            <div class="stat-info">
              <span class="stat-label">Đang hoạt động</span>
              <strong class="stat-value">{{ user.activePostsCount || 0 }}</strong>
            </div>
          </div>
          <div class="sub-stat-card card-pending">
            <div class="stat-icon-wrap"><FileQuestion :size="22" /></div>
            <div class="stat-info">
              <span class="stat-label">Chờ duyệt</span>
              <strong class="stat-value">{{ user.pendingPostsCount || 0 }}</strong>
            </div>
          </div>
          <div class="sub-stat-card card-locked">
            <div class="stat-icon-wrap"><FileWarning :size="22" /></div>
            <div class="stat-info">
              <span class="stat-label">Khóa / Từ chối</span>
              <strong class="stat-value">{{ lockedOrRejectedCount }}</strong>
            </div>
          </div>
        </div>

        <!-- Listings Table Card (Click row to go to detail) -->
        <div class="listings-panel">
          <div class="panel-header">
            <div class="panel-title">
              <Home :size="22" />
              <h2>Danh sách tin đã đăng</h2>
            </div>
          </div>

          <div v-if="listingsLoading" class="listings-loading">
            <div class="spinner mini-spinner"></div>
            <span>Đang tải tin đăng...</span>
          </div>
          <div v-else-if="listings.length === 0" class="empty-listings">
            <div class="empty-icon-wrap">🏠</div>
            <h3>Chưa có tin đăng nào</h3>
            <p>Tài khoản này chưa đăng bất kỳ tin rao nào trên hệ thống.</p>
          </div>
          <div v-else class="listings-table-wrapper">
            <table class="listings-table">
              <thead>
                <tr>
                  <th>Bất động sản</th>
                  <th>Phân loại</th>
                  <th>Giá & Diện tích</th>
                  <th>Gói hiển thị</th>
                  <th>Trạng thái</th>
                </tr>
              </thead>
              <tbody>
                <tr
                  v-for="item in listings"
                  :key="item.id"
                  class="table-row cursor-pointer"
                  @click="router.push({ name: 'PostDetail', params: { id: item.id } })"
                >
                  <td>
                    <div class="listing-info">
                      <img v-if="item.images && item.images.length > 0" :src="item.images[0].url" alt="Thumbnail" class="listing-thumb" />
                      <div v-else class="listing-thumb fallback-thumb">🏠</div>
                      <div class="listing-meta">
                        <p class="listing-title" :title="item.title">{{ item.title }}</p>
                        <p class="listing-loc">{{ item.property?.project_name || item.property?.address_detail }}</p>
                      </div>
                    </div>
                  </td>
                  <td>
                    <span class="type-badge" :class="item.demand_type === 'RENT' ? 'type-rent' : 'type-sale'">
                      {{ item.demand_type === 'RENT' ? 'Cho thuê' : 'Bán BĐS' }}
                    </span>
                  </td>
                  <td>
                    <p class="listing-price">{{ formatPrice(item.property?.price) }}</p>
                    <p class="listing-area">{{ item.property?.area || 0 }} m²</p>
                  </td>
                  <td>
                    <span v-if="item.package" class="package-badge" :style="{ backgroundColor: item.package.color ? `${item.package.color}18` : '#f1f5f9', color: item.package.color || '#475569' }">
                      <Sparkles :size="11" /> {{ item.package.name }}
                    </span>
                    <span v-else class="package-empty">Thường</span>
                  </td>
                  <td>
                    <span class="status-dot" :class="getListingStatusClass(item.status)">
                      {{ getListingStatusLabel(item.status) }}
                    </span>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="listings-pagination">
              <span class="pagination-info">Hiển thị {{ showingFrom }}–{{ showingTo }} / {{ totalListings }} tin đăng</span>
              <div class="pagination-controls">
                <button class="page-btn" :disabled="currentPage === 1" @click="handleGoToPage(currentPage - 1)"><ChevronLeft :size="18" /></button>
                <button v-for="p in pageNumbers" :key="p" class="page-btn" :class="{ active: p === currentPage }" @click="handleGoToPage(p)">{{ p }}</button>
                <button class="page-btn" :disabled="currentPage === lastPage" @click="handleGoToPage(currentPage + 1)"><ChevronRight :size="18" /></button>
              </div>
            </div>
          </div>
        </div>

        <!-- Audit Logs Table Card -->
        <div class="listings-panel audit-logs-panel">
          <div class="panel-header">
            <div class="panel-title">
              <History :size="22" />
              <h2>Lịch sử tác vụ của tài khoản</h2>
            </div>
          </div>

          <div v-if="logsLoading" class="listings-loading">
            <div class="spinner mini-spinner"></div>
            <span>Đang tải lịch sử tác vụ...</span>
          </div>
          <div v-else-if="auditLogs.length === 0" class="empty-listings">
            <div class="empty-icon-wrap">📋</div>
            <h3>Không có lịch sử tác vụ nào</h3>
            <p>Tài khoản này chưa ghi nhận bất kỳ thay đổi hoặc tác vụ quản trị nào.</p>
          </div>
          <div v-else class="listings-table-wrapper">
            <table class="listings-table logs-table">
              <thead>
                <tr>
                  <th>Tác vụ</th>
                  <th>Người thực hiện</th>
                  <th>Chi tiết lý do</th>
                  <th>Thời gian</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="log in auditLogs" :key="log.id" class="table-row">
                  <td>
                    <span class="log-action-badge" :class="log.action === 'admin.user.locked' ? 'action-locked' : 'action-unlocked'">
                      <UserX v-if="log.action === 'admin.user.locked'" :size="13" />
                      <UserCheck v-else :size="13" />
                      {{ getAuditActionLabel(log.action) }}
                    </span>
                  </td>
                  <td>
                    <div class="actor-info">
                      <p class="actor-name">{{ log.actor?.full_name || 'Hệ thống' }}</p>
                      <p class="actor-email">{{ log.actor?.email || '' }}</p>
                    </div>
                  </td>
                  <td>
                    <div v-if="log.metadata?.reason_code || log.metadata?.reason_text" class="reason-callout">
                      <p class="reason-lbl">
                        {{ getReasonLabel(log.metadata?.reason_code) }}
                      </p>
                      <p v-if="log.metadata?.reason_text" class="reason-txt">
                        "{{ log.metadata.reason_text }}"
                      </p>
                    </div>
                    <span v-else class="package-empty">—</span>
                  </td>
                  <td>
                    <span class="log-time">{{ formatDateTime(log.created_at) }}</span>
                  </td>
                </tr>
              </tbody>
            </table>

            <div class="listings-pagination">
              <span class="pagination-info">
                Hiển thị {{ (logsCurrentPage - 1) * logsPerPage + 1 }}–{{ Math.min(logsCurrentPage * logsPerPage, logsTotal) }} / {{ logsTotal }} tác vụ
              </span>
              <div class="pagination-controls">
                <button class="page-btn" :disabled="logsCurrentPage === 1" @click="handleGoToLogsPage(logsCurrentPage - 1)"><ChevronLeft :size="18" /></button>
                <button v-for="p in logsLastPage" :key="p" class="page-btn" :class="{ active: p === logsCurrentPage }" @click="handleGoToLogsPage(p)">{{ p }}</button>
                <button class="page-btn" :disabled="logsCurrentPage === logsLastPage" @click="handleGoToLogsPage(logsCurrentPage + 1)"><ChevronRight :size="18" /></button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- ════════════════════════════════════════════════════
         LOCK MODAL — Custom with reason selection
         ════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="lockModalOpen" class="modal-overlay" @click.self="closeLockModal">
        <div class="lock-modal">
          <div class="lock-modal-header">
            <div class="lock-modal-icon">
              <Lock :size="24" color="#dc2626" />
            </div>
            <div>
              <h2>Khóa tài khoản</h2>
              <p>Tài khoản <strong>{{ user?.name }}</strong> sẽ bị tạm khóa và không thể đăng nhập.</p>
            </div>
            <button class="modal-close-btn" @click="closeLockModal"><X :size="20" /></button>
          </div>

          <div class="lock-modal-body">
            <p class="reason-label">Lý do khóa tài khoản <span class="required-star">*</span></p>
            <div class="reason-list">
              <label
                v-for="r in LOCK_REASONS"
                :key="r.code"
                class="reason-item"
                :class="{ 'reason-selected': Number(lockSelectedReason) === r.code }"
                @click="lockSelectedReason = r.code"
              >
                <input
                  type="radio"
                  :value="r.code"
                  v-model="lockSelectedReason"
                  name="lock_reason"
                  class="reason-radio"
                  @click.stop
                />
                <span class="reason-text-label">{{ r.label }}</span>
              </label>
            </div>
            <p v-if="lockReasonError" class="field-error">{{ lockReasonError }}</p>

            <!-- TextArea for REASON_06 / code 6 -->
            <div v-if="Number(lockSelectedReason) === 6" class="other-reason-block">
              <label class="other-reason-label">
                Mô tả lý do <span class="required-star">*</span>
              </label>
              <textarea
                v-model="lockOtherText"
                class="other-reason-textarea"
                :class="{ 'textarea-error': lockOtherTextError }"
                placeholder="Nhập mô tả lý do khóa tài khoản (tối đa 500 ký tự)..."
                rows="4"
                maxlength="500"
              ></textarea>
              <div class="textarea-footer">
                <p v-if="lockOtherTextError" class="field-error">{{ lockOtherTextError }}</p>
                <span class="char-count" :class="{ 'char-warning': otherTextRemaining < 50 }">
                  {{ otherTextRemaining }} ký tự còn lại
                </span>
              </div>
            </div>
          </div>

          <div class="lock-modal-footer">
            <button class="modal-cancel-btn" :disabled="actionLoading" @click="closeLockModal">Hủy bỏ</button>
            <button class="modal-confirm-btn" :disabled="actionLoading || !isLockModalValid" @click="handleLockConfirm">
              <span v-if="actionLoading" class="btn-spinner"></span>
              {{ actionLoading ? 'Đang xử lý...' : 'Xác nhận khóa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <!-- ════════════════════════════════════════════════════
         UNLOCK MODAL — Simple confirm
         ════════════════════════════════════════════════════ -->
    <Teleport to="body">
      <div v-if="unlockModal.open" class="modal-overlay" @click.self="unlockModal.open = false">
        <div class="unlock-modal">
          <div class="lock-modal-header">
            <div class="lock-modal-icon unlock-icon">
              <Unlock :size="24" color="#16a34a" />
            </div>
            <div>
              <h2>Mở khóa tài khoản</h2>
              <p>Tài khoản <strong>{{ user?.name }}</strong> sẽ được kích hoạt và có thể đăng nhập trở lại.</p>
            </div>
            <button class="modal-close-btn" @click="unlockModal.open = false"><X :size="20" /></button>
          </div>
          <div class="lock-modal-footer">
            <button class="modal-cancel-btn" :disabled="actionLoading" @click="unlockModal.open = false">Hủy bỏ</button>
            <button class="modal-confirm-btn btn-confirm-unlock" :disabled="actionLoading" @click="handleUnlockConfirm">
              <span v-if="actionLoading" class="btn-spinner"></span>
              {{ actionLoading ? 'Đang xử lý...' : 'Xác nhận mở khóa' }}
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<style scoped>
.user-detail-page {
  width: 100%;
  max-width: 100%;
  padding: 0 24px;
  box-sizing: border-box;
}

/* ── Header ─────────────────────────────────── */
.detail-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 28px;
  gap: 20px;
  flex-wrap: wrap;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 20px;
}

.back-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  color: hsl(var(--foreground));
  font-size: 15px;
  font-weight: 600;
  padding: 10px 18px;
  border-radius: 8px;
  cursor: pointer;
  transition: all 0.2s ease;
  white-space: nowrap;
}

.back-btn:hover {
  background-color: hsl(var(--muted) / 0.5);
  transform: translateX(-2px);
}

.breadcrumbs {
  margin: 0 0 6px;
  font-size: 14px;
  color: hsl(var(--muted-foreground));
}

.header-title h1 {
  margin: 0;
  font-size: 28px;
  font-weight: 800;
  color: hsl(var(--foreground));
  letter-spacing: -0.5px;
}

.action-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 15px;
  font-weight: 700;
  padding: 12px 22px;
  border-radius: 8px;
  border: none;
  cursor: pointer;
  transition: all 0.2s ease;
}

.btn-destructive {
  background-color: hsl(var(--destructive));
  color: white;
}

.btn-destructive:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.btn-success {
  background-color: hsl(var(--success));
  color: white;
}

.btn-success:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

/* ── Banners ─────────────────────────────────── */
.success-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background-color: hsl(var(--success) / 0.1);
  border: 1px solid hsl(var(--success) / 0.3);
  color: hsl(var(--success));
  border-radius: 12px;
  margin-bottom: 20px;
  font-size: 15px;
  font-weight: 600;
}

.error-banner {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px 20px;
  background-color: hsl(var(--destructive) / 0.08);
  border: 1px solid hsl(var(--destructive) / 0.25);
  color: hsl(var(--destructive));
  border-radius: 12px;
  margin-bottom: 20px;
  font-size: 15px;
  font-weight: 600;
}

.retry-btn {
  margin-left: auto;
  background-color: hsl(var(--destructive));
  color: white;
  border: none;
  padding: 8px 16px;
  font-size: 13px;
  font-weight: 600;
  border-radius: 6px;
  cursor: pointer;
}

.loading-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 80px 32px;
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border) / 0.5);
  border-radius: 16px;
  gap: 20px;
  color: hsl(var(--muted-foreground));
  font-size: 16px;
}

.spinner {
  width: 44px;
  height: 44px;
  border: 4px solid hsl(var(--border));
  border-top-color: hsl(var(--primary));
  border-radius: 50%;
  animation: spin 0.9s linear infinite;
}

.mini-spinner {
  width: 28px;
  height: 28px;
  border-width: 3px;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* ── Detail Grid ─────────────────────────────── */
.detail-grid {
  display: grid;
  grid-template-columns: 370px 1fr;
  gap: 28px;
  align-items: start;
}

.right-column {
  display: flex;
  flex-direction: column;
  gap: 28px;
}

/* ── Profile Card ────────────────────────────── */
.profile-card {
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border) / 0.6);
  border-radius: 20px;
  overflow: hidden;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.profile-banner {
  height: 90px;
  background: linear-gradient(135deg, hsl(var(--primary)) 0%, #3b82f6 100%);
  opacity: 0.85;
}

.avatar-section {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  margin-top: -55px;
  padding: 0 24px 20px;
}

.avatar-container {
  position: relative;
  margin-bottom: 16px;
}

.profile-avatar {
  width: 110px;
  height: 110px;
  border-radius: 50%;
  object-fit: cover;
  border: 4px solid hsl(var(--card));
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.12);
  background-color: hsl(var(--card));
}

.fallback-avatar {
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 42px;
  font-weight: 800;
}

.google-badge-dot {
  position: absolute;
  bottom: 4px;
  right: 4px;
  background-color: hsl(var(--card));
  padding: 5px;
  border-radius: 50%;
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
  display: flex;
}

.avatar-section h2 {
  font-size: 24px;
  font-weight: 800;
  color: hsl(var(--foreground));
  margin: 0 0 14px;
  letter-spacing: -0.3px;
}

.badge-row {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
  justify-content: center;
}

.role-badge {
  display: inline-flex;
  padding: 4px 10px;
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  border-radius: 6px;
  letter-spacing: 0.4px;
}

.role-agent { background-color: hsl(var(--primary) / 0.1); color: hsl(var(--primary)); }
.role-user { background-color: hsl(var(--muted)); color: hsl(var(--muted-foreground)); }

/* ── Info Sections ───────────────────────────── */
.info-section {
  padding: 0 24px 24px;
}

.info-section h3 {
  font-size: 12px;
  font-weight: 800;
  text-transform: uppercase;
  letter-spacing: 0.8px;
  color: hsl(var(--muted-foreground));
  margin: 0 0 14px;
  border-bottom: 1px solid hsl(var(--border) / 0.4);
  padding-bottom: 8px;
}

.info-box {
  background-color: hsl(var(--muted) / 0.12);
  border: 1px solid hsl(var(--border) / 0.4);
  border-radius: 12px;
  padding: 16px;
}

.info-item {
  display: flex;
  gap: 14px;
  margin-bottom: 18px;
}

.info-item:last-child { margin-bottom: 0; }

.info-icon {
  color: hsl(var(--muted-foreground));
  margin-top: 4px;
  flex-shrink: 0;
}

.auth-type-icon { font-size: 16px; line-height: 1; }

.info-content label {
  display: block;
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin-bottom: 3px;
  font-weight: 500;
}

.info-content p {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: hsl(var(--foreground));
  word-break: break-all;
}

.missing-info {
  color: hsl(var(--muted-foreground)) !important;
  font-style: italic;
  font-weight: 500 !important;
}

.auth-pill {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  font-size: 13px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 6px;
  margin-top: 3px;
}

.google-pill {
  background-color: #ffffff;
  color: #334155;
  border: 1px solid #e2e8f0;
}

.email-pill {
  background-color: hsl(var(--muted));
  color: hsl(var(--muted-foreground));
}

/* ── Trust Score Card ────────────────────────── */
.trust-card {
  border: 1px solid hsl(var(--border) / 0.5);
  border-radius: 12px;
  padding: 18px;
  background-color: hsl(var(--muted) / 0.08);
}

.trust-top {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 14px;
}

.trust-percent {
  display: block;
  font-size: 30px;
  font-weight: 900;
  line-height: 1;
}

.trust-label-text {
  display: block;
  font-size: 13px;
  color: hsl(var(--muted-foreground));
  margin-top: 3px;
  font-weight: 600;
}

.trust-bar-wrap {
  margin-bottom: 14px;
}

.trust-bar-bg {
  height: 8px;
  background-color: hsl(var(--border));
  border-radius: 99px;
  overflow: hidden;
}

.trust-bar-fill {
  height: 100%;
  border-radius: 99px;
  transition: width 0.4s ease;
}

.trust-checklist {
  list-style: none;
  margin: 0;
  padding: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.trust-checklist li {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 600;
}

.check-ok { color: hsl(var(--success)); }
.check-missing { color: #f59e0b; }

/* ── Listings Panel ──────────────────────────── */
.listings-panel {
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border) / 0.6);
  border-radius: 20px;
  padding: 28px;
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
}

.audit-logs-panel {
  margin-top: 4px;
}

.user-stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.sub-stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  border-radius: 14px;
  padding: 16px;
  border: 1px solid hsl(var(--border) / 0.5);
  background-color: hsl(var(--card));
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.sub-stat-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
}

.stat-icon-wrap {
  width: 46px;
  height: 46px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.stat-info { display: flex; flex-direction: column; }
.stat-label { font-size: 12px; font-weight: 700; color: hsl(var(--muted-foreground)); }
.stat-value { font-size: 24px; font-weight: 900; color: hsl(var(--foreground)); margin-top: 2px; }

.card-total { border-left: 4px solid #3b82f6; }
.card-total .stat-icon-wrap { background-color: #eff6ff; color: #3b82f6; }
.card-active { border-left: 4px solid hsl(var(--success)); }
.card-active .stat-icon-wrap { background-color: hsl(var(--success) / 0.1); color: hsl(var(--success)); }
.card-pending { border-left: 4px solid #f59e0b; }
.card-pending .stat-icon-wrap { background-color: #fef3c7; color: #f59e0b; }
.card-locked { border-left: 4px solid hsl(var(--destructive)); }
.card-locked .stat-icon-wrap { background-color: hsl(var(--destructive) / 0.1); color: hsl(var(--destructive)); }

.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 20px;
  border-bottom: 2px solid hsl(var(--border) / 0.35);
  padding-bottom: 14px;
}

.panel-title {
  display: flex;
  align-items: center;
  gap: 12px;
  color: hsl(var(--foreground));
}

.panel-title h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
}

.listings-loading {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 14px;
  padding: 80px 0;
  color: hsl(var(--muted-foreground));
  font-size: 15px;
}

.empty-listings {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  padding: 80px 32px;
  color: hsl(var(--muted-foreground));
}

.empty-icon-wrap { font-size: 54px; margin-bottom: 18px; opacity: 0.65; }
.empty-listings h3 { font-size: 18px; font-weight: 800; color: hsl(var(--foreground)); margin: 0 0 10px; }
.empty-listings p { margin: 0; max-width: 440px; font-size: 14px; line-height: 1.5; }

.listings-table-wrapper { overflow-x: auto; }

.listings-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.listings-table th {
  padding: 12px 14px;
  font-size: 11px;
  font-weight: 800;
  text-transform: uppercase;
  color: hsl(var(--muted-foreground));
  border-bottom: 2px solid hsl(var(--border));
  letter-spacing: 0.4px;
}

.listings-table td {
  padding: 14px;
  border-bottom: 1px solid hsl(var(--border) / 0.5);
  vertical-align: middle;
  font-size: 15px;
}

.table-row:hover { background-color: #f8fafc; }

.listing-info { display: flex; align-items: center; gap: 14px; }

.listing-thumb {
  width: 60px;
  height: 60px;
  object-fit: cover;
  border-radius: 8px;
  border: 1px solid hsl(var(--border) / 0.5);
  background-color: #f1f5f9;
  flex-shrink: 0;
}

.fallback-thumb { display: flex; align-items: center; justify-content: center; font-size: 26px; }

.listing-meta { max-width: 280px; }

.listing-title {
  margin: 0;
  font-size: 15px;
  font-weight: 700;
  color: hsl(var(--foreground));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.listing-loc {
  margin: 4px 0 0;
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.type-badge {
  display: inline-flex;
  padding: 3px 8px;
  font-size: 11px;
  font-weight: 800;
  border-radius: 6px;
}

.type-rent { background-color: #fef3c7; color: #b45309; }
.type-sale { background-color: #dbeafe; color: #1d4ed8; }
.listing-price { margin: 0; font-size: 15px; font-weight: 800; color: hsl(var(--primary)); }
.listing-area { margin: 3px 0 0; font-size: 12px; color: hsl(var(--muted-foreground)); }

.package-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-size: 11px;
  font-weight: 800;
  padding: 3px 8px;
  border-radius: 6px;
}

.package-empty { font-size: 13px; color: hsl(var(--muted-foreground)); }

.status-dot {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  font-weight: 700;
}

.status-dot::before {
  content: '';
  width: 8px;
  height: 8px;
  border-radius: 50%;
  display: block;
}

.status-pending { color: #d97706; }
.status-pending::before { background-color: #d97706; }
.status-approved { color: hsl(var(--success)); }
.status-approved::before { background-color: hsl(var(--success)); }
.status-rejected { color: hsl(var(--destructive)); }
.status-rejected::before { background-color: hsl(var(--destructive)); }
.status-locked { color: hsl(var(--muted-foreground)); }
.status-locked::before { background-color: hsl(var(--muted-foreground)); }
.status-draft { color: hsl(var(--muted-foreground)); }
.status-draft::before { background-color: hsl(var(--border)); }

.text-right { text-align: right; }

.cursor-pointer { cursor: pointer; }

/* ── Audit Logs Styling ──────────────────────── */
.logs-table th, .logs-table td {
  padding: 12px 14px;
}

.log-action-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  font-size: 12px;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 6px;
}

.action-locked {
  background-color: hsl(var(--destructive) / 0.1);
  color: hsl(var(--destructive));
}

.action-unlocked {
  background-color: hsl(var(--success) / 0.1);
  color: hsl(var(--success));
}

.actor-info {
  display: flex;
  flex-direction: column;
}

.actor-name {
  margin: 0;
  font-size: 14px;
  font-weight: 700;
  color: hsl(var(--foreground));
}

.actor-email {
  margin: 2px 0 0;
  font-size: 11px;
  color: hsl(var(--muted-foreground));
}

.reason-callout {
  background-color: hsl(var(--muted) / 0.2);
  border-left: 3px solid hsl(var(--border));
  padding: 6px 12px;
  border-radius: 0 6px 6px 0;
  max-width: 340px;
}

.reason-lbl {
  margin: 0;
  font-size: 13px;
  font-weight: 700;
  color: hsl(var(--foreground));
}

.reason-txt {
  margin: 3px 0 0;
  font-size: 12px;
  font-style: italic;
  color: hsl(var(--muted-foreground));
  white-space: pre-wrap;
  word-break: break-all;
}

.log-time {
  font-size: 13px;
  font-weight: 600;
  color: hsl(var(--muted-foreground));
}

/* ── Pagination ──────────────────────────────── */
.listings-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-top: 20px;
  padding-top: 16px;
  border-top: 1px solid hsl(var(--border) / 0.35);
}

.pagination-info { font-size: 13px; color: hsl(var(--muted-foreground)); }
.pagination-controls { display: flex; align-items: center; gap: 8px; }

.page-btn {
  min-width: 34px;
  height: 34px;
  padding: 0 6px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  font-size: 13px;
  font-weight: 600;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  transition: all 0.2s ease;
}

.page-btn:hover:not(:disabled) { background-color: hsl(var(--muted) / 0.3); }
.page-btn:disabled { opacity: 0.45; cursor: not-allowed; }
.page-btn.active { background-color: hsl(var(--primary)); color: white; border-color: hsl(var(--primary)); font-weight: 700; }

/* ══════════════════════════════════════════════
   MODALS
   ══════════════════════════════════════════════ */
.modal-overlay {
  position: fixed;
  inset: 0;
  background-color: rgba(0, 0, 0, 0.5);
  backdrop-filter: blur(4px);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 9999;
  padding: 20px;
}

.lock-modal, .unlock-modal {
  background-color: hsl(var(--card));
  border-radius: 20px;
  width: 100%;
  max-width: 560px;
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.18);
  overflow: hidden;
  animation: modalIn 0.22s ease;
}

@keyframes modalIn {
  from { opacity: 0; transform: scale(0.95) translateY(12px); }
  to { opacity: 1; transform: scale(1) translateY(0); }
}

.lock-modal-header {
  display: flex;
  align-items: flex-start;
  gap: 16px;
  padding: 24px 24px 20px;
  border-bottom: 1px solid hsl(var(--border) / 0.5);
}

.lock-modal-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  background-color: hsl(var(--destructive) / 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.unlock-icon {
  background-color: hsl(var(--success) / 0.1) !important;
}

.lock-modal-header h2 {
  margin: 0 0 6px;
  font-size: 20px;
  font-weight: 800;
  color: hsl(var(--foreground));
}

.lock-modal-header p {
  margin: 0;
  font-size: 14px;
  color: hsl(var(--muted-foreground));
  line-height: 1.5;
}

.modal-close-btn {
  margin-left: auto;
  background: none;
  border: none;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  padding: 4px;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background 0.15s;
}

.modal-close-btn:hover { background-color: hsl(var(--muted)); }

/* Lock modal body */
.lock-modal-body {
  padding: 24px;
}

.reason-label {
  font-size: 14px;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0 0 14px;
}

.required-star { color: hsl(var(--destructive)); margin-left: 2px; }

.reason-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
  margin-bottom: 6px;
}

.reason-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 16px;
  border: 2px solid hsl(var(--border));
  border-radius: 10px;
  cursor: pointer;
  transition: all 0.18s ease;
  background-color: hsl(var(--card));
}

.reason-item:hover {
  border-color: hsl(var(--primary) / 0.5);
  background-color: hsl(var(--primary) / 0.03);
}

.reason-selected {
  border-color: hsl(var(--destructive)) !important;
  background-color: hsl(var(--destructive) / 0.04) !important;
}

.reason-radio {
  width: 18px;
  height: 18px;
  flex-shrink: 0;
  accent-color: hsl(var(--destructive));
  cursor: pointer;
}

.reason-text-label {
  font-size: 14px;
  font-weight: 600;
  color: hsl(var(--foreground));
  line-height: 1.4;
}

.field-error {
  font-size: 13px;
  color: hsl(var(--destructive));
  margin: 8px 0 0;
  font-weight: 600;
}

/* Textarea for REASON_06 */
.other-reason-block {
  margin-top: 18px;
  padding-top: 18px;
  border-top: 1px solid hsl(var(--border) / 0.5);
}

.other-reason-label {
  display: block;
  font-size: 14px;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin-bottom: 10px;
}

.other-reason-textarea {
  width: 100%;
  padding: 12px 14px;
  font-size: 14px;
  font-family: inherit;
  line-height: 1.6;
  color: hsl(var(--foreground));
  background-color: hsl(var(--card));
  border: 2px solid hsl(var(--border));
  border-radius: 10px;
  resize: vertical;
  transition: border-color 0.2s;
  outline: none;
  box-sizing: border-box;
}

.other-reason-textarea:focus {
  border-color: hsl(var(--primary));
}

.textarea-error {
  border-color: hsl(var(--destructive)) !important;
}

.textarea-footer {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-top: 6px;
  gap: 8px;
}

.char-count {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin-left: auto;
  flex-shrink: 0;
}

.char-warning {
  color: hsl(var(--destructive));
  font-weight: 700;
}

/* Modal footer */
.lock-modal-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 18px 24px 24px;
  border-top: 1px solid hsl(var(--border) / 0.5);
}

.modal-cancel-btn {
  padding: 11px 22px;
  font-size: 15px;
  font-weight: 700;
  border-radius: 8px;
  border: 1px solid hsl(var(--border));
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  cursor: pointer;
  transition: background 0.2s;
}

.modal-cancel-btn:hover:not(:disabled) { background-color: hsl(var(--muted) / 0.5); }
.modal-cancel-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.modal-confirm-btn {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 11px 24px;
  font-size: 15px;
  font-weight: 700;
  border-radius: 8px;
  border: none;
  background-color: hsl(var(--destructive));
  color: white;
  cursor: pointer;
  transition: opacity 0.2s;
}

.modal-confirm-btn:hover:not(:disabled) { opacity: 0.9; }
.modal-confirm-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.btn-confirm-unlock {
  background-color: hsl(var(--success)) !important;
}

.btn-spinner {
  width: 18px;
  height: 18px;
  border: 2.5px solid rgba(255,255,255,0.4);
  border-top-color: white;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  display: inline-block;
}

/* ── Responsive ─────────────────────────────── */
@media (max-width: 1024px) {
  .user-stats-row { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 992px) {
  .detail-grid { grid-template-columns: 1fr; }
}

@media (max-width: 576px) {
  .detail-header { flex-direction: column; align-items: flex-start; }
  .header-actions { width: 100%; }
  .action-btn { width: 100%; justify-content: center; }
  .user-stats-row { grid-template-columns: repeat(2, 1fr); }
  .listings-pagination { flex-direction: column; gap: 12px; align-items: flex-start; }
  .lock-modal-header { flex-wrap: wrap; }
}
</style>
