<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import {
  ArrowLeft,
  Ban,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  Calendar,
  CheckCircle,
  X,
  Clock,
  FileCheck,
  Image as ImageIcon,
  Info,
  Lock,
  MapPin,
  MessageSquareWarning,
  ShieldCheck,
  Unlock,
  User,
  Video,
} from 'lucide-vue-next'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { listingService } from '@/services/listingService'
import { hydratePropertyAddress } from '@/utils/addressFormatter'

const route = useRoute()
const router = useRouter()

const listing = ref(null)
const loading = ref(false)
const error = ref('')
const activeTab = ref('info')
const listingReports = ref([])
const reportsLoading = ref(false)
const reportsLoaded = ref(false)
const reportsError = ref('')
const actionLoading = ref(false)
const listingStatusOptions = ref([])
const adminListingStatusOptions = ref([])
const reasonModal = ref({ open: false, type: 'status', status: '', title: '', selectedReason: '', reasonDropdownOpen: false, reason: '', error: '' })
const confirmModal = ref({ open: false, title: '', message: '', confirmText: 'Xác nhận', tone: 'primary', action: null })
const lightbox = ref({ open: false, items: [], index: 0 })
const lightboxZoomed = ref(false)
const panState = ref({
  dragging: false,
  moved: false,
  startX: 0,
  startY: 0,
  originX: 0,
  originY: 0,
  x: 0,
  y: 0,
})
let previousBodyOverflow = ''

const REJECT_REASON_OTHER = '__OTHER__'
const EMPTY_LABEL = 'Không có'
const LISTING_REJECT_REASONS = [
  'Thông tin bài đăng không chính xác hoặc thiếu nhất quán.',
  'Hình ảnh không rõ ràng hoặc không phù hợp với nội dung tin.',
  'Nội dung mô tả sơ sài, thiếu thông tin bắt buộc.',
  'Thông tin liên hệ không hợp lệ.',
  'Tin đăng có dấu hiệu trùng lặp hoặc spam.',
]
const LISTING_LOCK_REASONS = [
  'Tin đăng vi phạm quy định nội dung.',
  'Tin đăng có dấu hiệu gian lận hoặc lừa đảo.',
  'Tin đăng bị nhiều người dùng báo cáo.',
  'Thông tin bất động sản không còn phù hợp để hiển thị.',
  'Yêu cầu khóa tin từ bộ phận quản trị.',
]
const VERIFICATION_REJECT_REASONS = [
  'Giấy tờ xác thực không rõ ràng hoặc bị che khuất thông tin.',
  'Giấy tờ không trùng khớp với thông tin bất động sản.',
  'Thiếu giấy tờ pháp lý cần thiết.',
  'Giấy tờ có dấu hiệu chỉnh sửa hoặc không hợp lệ.',
  'Thông tin chủ sở hữu không khớp với hồ sơ đăng tin.',
]

const post = computed(() => listing.value)
const property = computed(() => post.value?.property ?? {})
const images = computed(() => post.value?.images ?? [])
const videos = computed(() => post.value?.videos ?? [])
const docs = computed(() => post.value?.verification_documents ?? [])
const histories = computed(() => post.value?.status_histories ?? [])
const warningCount = computed(() => listingReports.value.length)
const mediaItems = computed(() => [
  ...images.value.map((image) => ({ type: 'image', url: image.url, title: post.value?.title || 'Hình ảnh' })),
  ...videos.value.map((video) => ({ type: 'video', url: video.url, title: 'Video đính kèm' })),
])
const docItems = computed(() => docs.value.map((doc) => ({ type: 'image', url: doc.url, title: doc.type })))
const activeLightboxItem = computed(() => lightbox.value.items[lightbox.value.index])
const isSaleListing = computed(() => post.value?.demand_type === 'SALE')
const isRentListing = computed(() => post.value?.demand_type === 'RENT')

const statusKey = computed(() => mapStatusKey(post.value?.status))
const statusLabel = computed(() => mapStatusLabel(post.value?.status))
const verificationKey = computed(() => post.value?.is_verified ? 'approved' : 'locked')
const verificationLabel = computed(() => post.value?.is_verified ? 'Đã xác thực' : 'Chưa xác thực')
const canShowVerificationActions = computed(() => {
  return isSaleListing.value && post.value?.status === 'ACTIVE' && docs.value.length > 0
})
const mapSrc = computed(() => {
  const lat = property.value?.lat
  const lng = property.value?.lng
  if (!lat || !lng) return ''
  return `https://maps.google.com/maps?q=${lat},${lng}&z=15&output=embed`
})

const unitPrice = computed(() => {
  const price = Number(property.value?.price)
  const area = Number(property.value?.area)
  if (!price || !area) return EMPTY_LABEL
  return `${formatMoney(Math.round(price / area))} đ/m²`
})

const priceLabel = computed(() => isRentListing.value ? 'Giá thuê' : 'Giá bán')
const priceValue = computed(() => {
  const price = formatMoney(property.value?.price)
  if (price === EMPTY_LABEL) return EMPTY_LABEL
  return isRentListing.value ? `${price} đ/tháng` : `${price} đ`
})
const rentMinTermText = computed(() => formatRentTerm(post.value?.rent_min_term))
const rentPaymentText = computed(() => formatRentPaymentInterval(post.value?.rent_payment_interval))
const rentDepositText = computed(() => formatRentDeposit(post.value?.rent_deposit))

async function fetchDetail() {
  loading.value = true
  error.value = ''
  try {
    const res = await listingService.getListingDetail(route.params.id)
    listing.value = res.data?.data ?? null
    await hydratePropertyAddress(listing.value?.property)
    if (!['SALE', 'RENT'].includes(listing.value?.demand_type)) {
      error.value = 'Trang chi tiết này hiện chỉ hỗ trợ tin mua bán và cho thuê.'
    }
    if (listing.value?.demand_type === 'RENT') {
      activeTab.value = 'info'
    }
    reportsLoaded.value = false
    listingReports.value = []
    fetchListingReports()
  } catch (err) {
    console.error('Failed to fetch listing detail:', err)
    error.value = err.response?.data?.message || 'Không thể tải chi tiết tin đăng.'
  } finally {
    loading.value = false
  }
}

async function fetchListingReports() {
  if (!route.params.id) return

  reportsLoading.value = true
  reportsError.value = ''
  try {
    const res = await listingService.getListingReports(route.params.id)
    listingReports.value = res.data?.data ?? []
    reportsLoaded.value = true
  } catch (err) {
    console.error('Failed to fetch listing reports:', err)
    reportsError.value = err.response?.data?.message || 'Không thể tải cảnh báo tin đăng.'
  } finally {
    reportsLoading.value = false
  }
}

async function fetchPostingOptions() {
  try {
    const res = await listingService.getPostingOptions()
    const options = res.data?.data ?? {}
    listingStatusOptions.value = options.listing_statuses ?? []
    adminListingStatusOptions.value = options.admin_listing_statuses ?? []
  } catch (err) {
    console.error('Failed to fetch posting options:', err)
  }
}

function formatMoney(value) {
  const amount = Number(value)
  if (!Number.isFinite(amount)) return EMPTY_LABEL
  return new Intl.NumberFormat('vi-VN').format(amount)
}

function formatDateTime(value) {
  if (!value) return EMPTY_LABEL
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return EMPTY_LABEL
  return new Intl.DateTimeFormat('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date)
}

function displayValue(value) {
  return value === null || value === undefined || value === '' ? EMPTY_LABEL : value
}

function formatMetric(value, unit) {
  return value === null || value === undefined || value === '' ? EMPTY_LABEL : `${value} ${unit}`
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
  return listingStatusOptions.value.find((option) => option.value === status)?.label || status || 'Chờ duyệt'
}

function adminStatusLabel(status) {
  return adminListingStatusOptions.value.find((option) => option.value === status)?.label || mapStatusLabel(status)
}

function reportStatusLabel(status) {
  return {
    WARNING: 'Mới',
    NEW: 'Mới',
    PROCESSING: 'Đang xử lý',
    RESOLVED: 'Đã xử lý',
    REJECTED: 'Bỏ qua',
  }[status] || status || 'Mới'
}

function reportStatusClass(status) {
  return {
    WARNING: 'new',
    NEW: 'new',
    PROCESSING: 'processing',
    RESOLVED: 'resolved',
    REJECTED: 'rejected',
  }[status] || 'new'
}

function reporterInitials(report) {
  const name = report?.reporter?.full_name || report?.reporter?.email || 'U'
  return name
    .split(/\s+/)
    .filter(Boolean)
    .slice(-2)
    .map((part) => part[0]?.toUpperCase())
    .join('') || 'U'
}

function reportImages(report) {
  return Array.isArray(report?.image_urls) ? report.image_urls.filter(Boolean) : []
}

function demandLabel(value) {
  return value === 'SALE' ? 'Mua bán' : value === 'RENT' ? 'Cho thuê' : EMPTY_LABEL
}

function formatRentTerm(value) {
  if (!value) return EMPTY_LABEL
  const text = String(value)
  const monthMatch = text.match(/\d+/)
  if (monthMatch) return `${monthMatch[0]} tháng`
  return text
}

function formatRentPaymentInterval(value) {
  if (!value) return EMPTY_LABEL
  const normalized = String(value).toUpperCase()
  const monthMatch = normalized.match(/\d+/)
  if (monthMatch) return `${monthMatch[0]} tháng/lần`
  return {
    MONTHLY: '1 tháng/lần',
    QUARTERLY: '3 tháng/lần',
    HALF_YEARLY: '6 tháng/lần',
    YEARLY: '12 tháng/lần',
  }[normalized] || value
}

function formatRentDeposit(value) {
  if (!value) return EMPTY_LABEL
  const text = String(value)
  const monthMatch = text.match(/\d+/)
  if (monthMatch) return `${monthMatch[0]} tháng`
  return text
}

function propertyTypeLabel(value) {
  return {
    APARTMENT: 'Căn hộ chung cư',
    MINI_APARTMENT: 'Chung cư mini',
    HOUSE: 'Nhà riêng',
    PRIVATE_HOUSE: 'Nhà riêng',
    STREET_HOUSE: 'Nhà mặt phố',
    VILLA: 'Biệt thự',
    VILLA_TOWNHOUSE: 'Biệt thự liền kề',
    SHOPHOUSE: 'Shophouse',
    KIOSK: 'Ki-ốt',
    LAND: 'Đất nền',
    TOWNHOUSE: 'Liền kề',
    OFFICE: 'Văn phòng',
    ROOM: 'Phòng',
    RENT_ROOM: 'Phòng trọ',
    BOARDING_HOUSE: 'Nhà trọ',
    HOTEL: 'Khách sạn',
    RESORT: 'Resort',
    RESTAURANT_HOTEL: 'Nhà hàng - Khách sạn',
  }[value] || value || EMPTY_LABEL
}

function posterTypeLabel(value) {
  return {
    OWNER: 'Chủ sở hữu',
    BROKER: 'Môi giới',
  }[value] || value || EMPTY_LABEL
}

function directionLabel(value) {
  const normalized = String(value || '').trim().toUpperCase()
  return {
    E: 'Đông',
    EAST: 'Đông',
    W: 'Tây',
    WEST: 'Tây',
    S: 'Nam',
    SOUTH: 'Nam',
    N: 'Bắc',
    NORTH: 'Bắc',
    NE: 'Đông Bắc',
    NORTHEAST: 'Đông Bắc',
    NORTH_EAST: 'Đông Bắc',
    NW: 'Tây Bắc',
    NORTHWEST: 'Tây Bắc',
    NORTH_WEST: 'Tây Bắc',
    SE: 'Đông Nam',
    SOUTHEAST: 'Đông Nam',
    SOUTH_EAST: 'Đông Nam',
    SW: 'Tây Nam',
    SOUTHWEST: 'Tây Nam',
    SOUTH_WEST: 'Tây Nam',
  }[normalized] || value || EMPTY_LABEL
}

function furnitureLabel(value) {
  const normalized = String(value || '').trim().toUpperCase()
  return {
    NONE: 'Không có nội thất',
    BASIC: 'Nội thất cơ bản',
    FULL: 'Đầy đủ nội thất',
  }[normalized] || value || EMPTY_LABEL
}

function historyActionLabel(value) {
  const verificationLabels = {
    VERIFY_APPROVED: 'Đã xác thực',
    VERIFY_REJECTED: 'Từ chối xác thực',
  }
  return verificationLabels[value] || adminStatusLabel(value)
}

function canApprove() {
  return post.value?.status === 'PENDING'
}

function canReject() {
  return post.value?.status === 'PENDING'
}

function canLock() {
  return post.value?.status === 'ACTIVE'
}

function canUnlock() {
  return post.value?.status === 'LOCKED'
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

async function updateVerification(isVerified, reason = null) {
  if (!post.value) return
  actionLoading.value = true
  try {
    await listingService.updateVerificationForAdmin(post.value.id, {
      is_verified: isVerified,
      reason,
    })
    await fetchDetail()
  } finally {
    actionLoading.value = false
  }
}

function openConfirm({ title, message, confirmText = 'Xác nhận', tone = 'primary', action }) {
  confirmModal.value = { open: true, title, message, confirmText, tone, action }
}

function closeConfirm() {
  confirmModal.value = { open: false, title: '', message: '', confirmText: 'Xác nhận', tone: 'primary', action: null }
}

function runConfirmedAction() {
  const action = confirmModal.value.action
  closeConfirm()
  if (typeof action === 'function') {
    action()
  }
}

function confirmStatusChange(status, reason = null) {
  const isReject = status === 'REJECTED'
  const isLock = status === 'LOCKED'
  const isUnlock = status === 'ACTIVE' && post.value?.status === 'LOCKED'
  openConfirm({
    title: isReject ? 'Xác nhận từ chối tin' : isLock ? 'Xác nhận khóa tin' : isUnlock ? 'Xác nhận mở khóa tin' : 'Xác nhận duyệt tin',
    message: isReject
      ? 'Bạn chắc chắn muốn từ chối tin đăng này?'
      : isLock
        ? 'Bạn chắc chắn muốn khóa tin đăng này?'
        : isUnlock
          ? 'Bạn chắc chắn muốn mở khóa tin đăng này?'
          : 'Bạn chắc chắn muốn duyệt tin đăng này?',
    confirmText: isReject ? 'Từ chối' : isLock ? 'Khóa tin' : isUnlock ? 'Mở khóa' : 'Duyệt tin',
    tone: isReject ? 'danger' : 'primary',
    action: () => changeStatus(status, reason),
  })
}

function confirmVerificationChange(isVerified, reason = null) {
  openConfirm({
    title: isVerified ? 'Xác nhận phê duyệt xác thực' : 'Xác nhận từ chối xác thực',
    message: isVerified
      ? 'Bạn chắc chắn muốn phê duyệt giấy tờ xác thực này?'
      : 'Bạn chắc chắn muốn từ chối giấy tờ xác thực này?',
    confirmText: isVerified ? 'Phê duyệt' : 'Từ chối',
    tone: isVerified ? 'primary' : 'danger',
    action: () => updateVerification(isVerified, reason),
  })
}

function openReason(status) {
  reasonModal.value = {
    open: true,
    type: 'status',
    status,
    title: status === 'LOCKED' ? 'Khóa tin' : 'Từ chối tin',
    selectedReason: '',
    reasonDropdownOpen: false,
    reason: '',
    error: '',
  }
}

function openVerificationReject() {
  reasonModal.value = {
    open: true,
    type: 'verification',
    status: '',
    title: 'Từ chối xác thực',
    selectedReason: '',
    reasonDropdownOpen: false,
    reason: '',
    error: '',
  }
}

function submitReason() {
  const { type, status, selectedReason, reason } = reasonModal.value
  const usesDropdown = type === 'verification' || ['REJECTED', 'LOCKED'].includes(status)
  const finalReason = usesDropdown
    ? (selectedReason === REJECT_REASON_OTHER ? reason.trim() : selectedReason)
    : reason.trim()

  if (type === 'verification') {
    if (!finalReason) {
      reasonModal.value.error = 'Vui lòng chọn hoặc nhập lý do từ chối xác thực.'
      return
    }
    reasonModal.value.open = false
    confirmVerificationChange(false, finalReason)
    return
  }

  if (status === 'REJECTED' && !finalReason) {
    reasonModal.value.error = 'Vui lòng chọn hoặc nhập lý do từ chối.'
    return
  }

  reasonModal.value.open = false
  if (status === 'LOCKED' && !finalReason) {
    reasonModal.value.error = 'Vui lòng chọn hoặc nhập lý do khóa tin.'
    return
  }

  confirmStatusChange(status, finalReason || null)
}

function getReasonOptions() {
  if (reasonModal.value.type === 'verification') return VERIFICATION_REJECT_REASONS
  return reasonModal.value.status === 'LOCKED' ? LISTING_LOCK_REASONS : LISTING_REJECT_REASONS
}

function getReasonLabel() {
  if (!reasonModal.value.selectedReason) {
    return reasonModal.value.status === 'LOCKED' ? 'Chọn lý do khóa tin' : 'Chọn lý do từ chối'
  }
  if (reasonModal.value.selectedReason === REJECT_REASON_OTHER) return 'Khác'
  return reasonModal.value.selectedReason
}

function selectRejectReason(reason) {
  reasonModal.value.selectedReason = reason
  reasonModal.value.reasonDropdownOpen = false
  reasonModal.value.error = ''
  if (reason !== REJECT_REASON_OTHER) {
    reasonModal.value.reason = ''
  }
}

function openLightbox(items, index = 0) {
  if (!items.length) return
  resetLightboxZoom()
  lightbox.value = { open: true, items, index }
}

function closeLightbox() {
  resetLightboxZoom()
  lightbox.value = { open: false, items: [], index: 0 }
}

function moveLightbox(step) {
  const total = lightbox.value.items.length
  if (!total) return
  resetLightboxZoom()
  lightbox.value.index = (lightbox.value.index + step + total) % total
}

function resetLightboxZoom() {
  lightboxZoomed.value = false
  panState.value = { dragging: false, moved: false, startX: 0, startY: 0, originX: 0, originY: 0, x: 0, y: 0 }
}

function toggleLightboxZoom() {
  lightboxZoomed.value = !lightboxZoomed.value
  panState.value = { dragging: false, moved: false, startX: 0, startY: 0, originX: 0, originY: 0, x: 0, y: 0 }
}

function handleLightboxImageClick() {
  if (panState.value.moved) {
    panState.value.moved = false
    return
  }

  toggleLightboxZoom()
}

function startPan(event) {
  if (!lightboxZoomed.value || activeLightboxItem.value?.type !== 'image') return
  panState.value = {
    ...panState.value,
    dragging: true,
    moved: false,
    startX: event.clientX - panState.value.x,
    startY: event.clientY - panState.value.y,
    originX: event.clientX,
    originY: event.clientY,
  }
}

function movePan(event) {
  if (!panState.value.dragging) return
  if (Math.abs(event.clientX - panState.value.originX) > 3 || Math.abs(event.clientY - panState.value.originY) > 3) {
    panState.value.moved = true
  }
  panState.value.x = event.clientX - panState.value.startX
  panState.value.y = event.clientY - panState.value.startY
}

function stopPan() {
  panState.value.dragging = false
}

watch(() => lightbox.value.open, (isOpen) => {
  if (isOpen) {
    previousBodyOverflow = document.body.style.overflow
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = previousBodyOverflow
  }
})

onMounted(async () => {
  await fetchPostingOptions()
  await fetchDetail()
})

onBeforeUnmount(() => {
  document.body.style.overflow = previousBodyOverflow
})
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
            <StatusBadge v-if="isSaleListing" :status="verificationKey" :label="verificationLabel" />
          </div>
          <div class="meta-row">
            <span><Calendar :size="14" /> Tạo: {{ formatDateTime(post.created_at) }}</span>
            <span><Clock :size="14" /> Cập nhật: {{ formatDateTime(post.published_at || post.submitted_at) }}</span>
          </div>
        </div>
        <div class="header-actions">
          <button v-if="canReject()" class="outline-danger" :disabled="actionLoading" @click="openReason('REJECTED')">
            <Ban :size="15" /> {{ adminStatusLabel('REJECTED') }}
          </button>
          <button v-if="canLock()" class="outline-neutral" :disabled="actionLoading" @click="openReason('LOCKED')">
            <Lock :size="15" /> {{ adminStatusLabel('LOCKED') }}
          </button>
          <button v-if="canUnlock()" class="primary-action" :disabled="actionLoading" @click="confirmStatusChange('ACTIVE')">
            <Unlock :size="15" /> Mở khóa
          </button>
          <button v-if="canApprove()" class="primary-action" :disabled="actionLoading" @click="confirmStatusChange('ACTIVE')">
            <CheckCircle :size="15" /> {{ adminStatusLabel('ACTIVE') }}
          </button>
        </div>
      </header>

      <div class="tabs">
        <button :class="{ active: activeTab === 'info' }" @click="activeTab = 'info'">Thông tin</button>
        <button v-if="isSaleListing" :class="{ active: activeTab === 'verify' }" @click="activeTab = 'verify'">Xác thực</button>
        <button :class="{ active: activeTab === 'warnings' }" @click="activeTab = 'warnings'">
          Cảnh báo
          <span v-if="warningCount" class="tab-count">{{ warningCount }}</span>
        </button>
        <button :class="{ active: activeTab === 'history' }" @click="activeTab = 'history'">Lịch sử</button>
      </div>

      <section v-if="activeTab === 'info'" class="section-stack">
        <article class="detail-card">
          <h2><ImageIcon :size="16" /> Hình ảnh & Video</h2>
          <div v-if="images.length" class="media-grid">
            <button
              v-for="(image, index) in images"
              :key="image.id"
              class="media-thumb"
              type="button"
              @click="openLightbox(mediaItems, index)"
            >
              <img :src="image.url" :alt="post.title" />
            </button>
          </div>
          <p v-else class="muted">Không có hình ảnh đính kèm</p>
          <div v-if="videos.length" class="video-list">
            <button
              v-for="(video, index) in videos"
              :key="video.id"
              class="video-chip"
              type="button"
              @click="openLightbox(mediaItems, images.length + index)"
            >
              <Video :size="14" /> Video {{ index + 1 }}
            </button>
          </div>
          <p v-else class="video-note"><Video :size="14" /> Không có video đính kèm</p>
        </article>

        <article class="detail-card">
          <h2><Info :size="16" /> Thông tin bất động sản</h2>
          <div class="info-grid">
            <div class="info-item wide">
              <span>Tên bất động sản</span>
              <strong>{{ displayValue(post.title) }}</strong>
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
              <span>{{ priceLabel }}</span>
              <strong>{{ priceValue }}</strong>
            </div>
            <div class="info-item">
              <span>Diện tích</span>
              <strong>{{ formatMetric(property.area, 'm²') }}</strong>
            </div>
          </div>
          <div class="description">
            <span>Mô tả</span>
            <p>{{ displayValue(post.description) }}</p>
          </div>
          <div v-if="isSaleListing" class="unit-price">Đơn giá: <strong>{{ unitPrice }}</strong></div>
          <div v-else-if="isRentListing" class="rent-terms">
            <span>Thời gian tối thiểu: <strong>{{ rentMinTermText }}</strong></span>
            <span>Kỳ thanh toán: <strong>{{ rentPaymentText }}</strong></span>
            <span>Đặt cọc: <strong>{{ rentDepositText }}</strong></span>
          </div>
        </article>

        <article class="detail-card">
          <h2><Info :size="16" /> Thông tin chi tiết</h2>
          <div class="info-grid detail-grid">
            <div class="info-item"><span>Phòng ngủ</span><strong>{{ displayValue(property.bedrooms) }}</strong></div>
            <div class="info-item"><span>Phòng tắm</span><strong>{{ displayValue(property.bathrooms) }}</strong></div>
            <div class="info-item"><span>Số tầng</span><strong>{{ displayValue(property.floors) }}</strong></div>
            <div class="info-item"><span>Tầng</span><strong>{{ displayValue(property.floor_number) }}</strong></div>
            <div class="info-item"><span>Mặt tiền</span><strong>{{ formatMetric(property.facade_width, 'm') }}</strong></div>
            <div class="info-item"><span>Chiều sâu</span><strong>{{ formatMetric(property.depth, 'm') }}</strong></div>
            <div class="info-item"><span>Hướng nhà</span><strong>{{ directionLabel(property.direction_code) }}</strong></div>
            <div class="info-item"><span>Hướng ban công</span><strong>{{ directionLabel(property.balcony_direction_code) }}</strong></div>
            <div class="info-item"><span>Nội thất</span><strong>{{ furnitureLabel(property.furniture_status) }}</strong></div>
            <div class="info-item"><span>Ban công</span><strong>{{ displayValue(property.balconies) }}</strong></div>
            <div class="info-item"><span>Thỏa thuận</span><strong>{{ property.is_negotiable ? 'Có' : 'Không' }}</strong></div>
          </div>
          <div v-if="property.amenities?.length" class="chips">
            <span v-for="item in property.amenities" :key="item">{{ item }}</span>
          </div>
        </article>

        <article class="detail-card">
          <h2><MapPin :size="16" /> Địa chỉ</h2>
          <div class="info-grid">
            <div class="info-item"><span>Tỉnh/TP</span><strong>{{ displayValue(property.province_name || property.province_code) }}</strong></div>
            <div class="info-item"><span>Phường/Xã</span><strong>{{ displayValue(property.ward_name || property.ward_code) }}</strong></div>
            <div class="info-item"><span>Đường</span><strong>{{ displayValue(property.street_code) }}</strong></div>
            <div class="info-item wide"><span>Địa chỉ chi tiết</span><strong>{{ displayValue(property.address_detail) }}</strong></div>
          </div>
          <iframe v-if="mapSrc" class="map-frame" :src="mapSrc" loading="lazy"></iframe>
          <div v-else class="map-empty">Listing chưa có tọa độ bản đồ.</div>
        </article>

        <article class="detail-card">
          <h2><User :size="16" /> Người đăng</h2>
          <div class="info-grid">
            <div class="info-item"><span>Loại</span><strong>{{ posterTypeLabel(property.poster_type) }}</strong></div>
            <div class="info-item"><span>Họ tên</span><strong>{{ displayValue(property.contact_name || post.owner?.full_name) }}</strong></div>
            <div class="info-item"><span>Số điện thoại</span><strong>{{ displayValue(property.contact_phone) }}</strong></div>
            <div class="info-item"><span>Email</span><strong>{{ displayValue(property.contact_email || post.owner?.email) }}</strong></div>
          </div>
        </article>
      </section>

      <section v-else-if="activeTab === 'verify'" class="section-stack">
        <article class="detail-card">
          <div class="card-head">
            <h2><ShieldCheck :size="16" /> Giấy tờ pháp lý</h2>
            <div v-if="canShowVerificationActions" class="verification-actions">
              <button class="outline-danger" :disabled="actionLoading" @click="openVerificationReject">
                <Ban :size="15" /> Từ chối
              </button>
              <button v-if="!post.is_verified" class="primary-action" :disabled="actionLoading" @click="confirmVerificationChange(true)">
                <CheckCircle :size="15" /> Phê duyệt
              </button>
            </div>
          </div>
          <div v-if="docs.length" class="docs-grid">
            <button
              v-for="(doc, index) in docs"
              :key="doc.id"
              class="doc-card"
              type="button"
              @click="openLightbox(docItems, index)"
            >
              <img :src="doc.url" :alt="doc.type" />
              <div>
                <strong>{{ doc.type }}</strong>
                <span>Đã tải lên</span>
              </div>
            </button>
          </div>
          <p v-else class="muted">Không có giấy tờ xác thực.</p>
        </article>
      </section>

      <section v-else-if="activeTab === 'warnings'" class="section-stack">
        <article class="detail-card">
          <h2><MessageSquareWarning :size="16" /> Cảnh báo từ người dùng ({{ warningCount }})</h2>

          <div v-if="reportsLoading" class="state-box">Đang tải cảnh báo...</div>
          <div v-else-if="reportsError" class="state-box error">{{ reportsError }}</div>
          <div v-else-if="listingReports.length" class="warning-list">
            <article v-for="report in listingReports" :key="report.report_group_id || report.id" class="warning-card">
              <div class="warning-avatar">
                <img v-if="report.reporter?.avatar_url" :src="report.reporter.avatar_url" :alt="report.reporter.full_name || 'Người dùng'" />
                <span v-else>{{ reporterInitials(report) }}</span>
              </div>

              <div class="warning-body">
                <div class="warning-head">
                  <div>
                    <div class="warning-user-row">
                      <strong>{{ report.reporter?.full_name || report.reporter?.email || `User #${report.reporter?.id || '--'}` }}</strong>
                      <span class="warning-status" :class="reportStatusClass(report.status)">{{ reportStatusLabel(report.status) }}</span>
                    </div>
                    <div class="warning-reasons">
                      <span v-for="label in report.reason_labels" :key="label">{{ label }}</span>
                    </div>
                  </div>
                  <time>{{ formatDateTime(report.created_at) }}</time>
                </div>

                <p v-if="report.description" class="warning-description">{{ report.description }}</p>

                <div v-if="reportImages(report).length" class="warning-images">
                  <button
                    v-for="(image, index) in reportImages(report)"
                    :key="image"
                    type="button"
                    class="warning-image"
                    @click="openLightbox(reportImages(report).map((url) => ({ type: 'image', url, title: 'Ảnh cảnh báo' })), index)"
                  >
                    <img :src="image" alt="Ảnh cảnh báo" />
                  </button>
                </div>
              </div>
            </article>
          </div>
          <p v-else class="muted">Tin đăng này chưa có cảnh báo từ người dùng.</p>
        </article>
      </section>

      <section v-else-if="activeTab === 'history'" class="section-stack">
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
        <div
          v-if="reasonModal.type === 'verification' || ['REJECTED', 'LOCKED'].includes(reasonModal.status)"
          class="reason-dropdown"
        >
          <button
            type="button"
            class="reason-dropdown-trigger"
            :class="{ placeholder: !reasonModal.selectedReason }"
            @click="reasonModal.reasonDropdownOpen = !reasonModal.reasonDropdownOpen"
          >
            <span>{{ getReasonLabel() }}</span>
            <ChevronDown :size="16" :class="{ open: reasonModal.reasonDropdownOpen }" />
          </button>
          <div v-if="reasonModal.reasonDropdownOpen" class="reason-dropdown-menu">
            <button
              v-for="reason in getReasonOptions()"
              :key="reason"
              type="button"
              class="reason-dropdown-option"
              :class="{ selected: reasonModal.selectedReason === reason }"
              @click="selectRejectReason(reason)"
            >
              {{ reason }}
            </button>
            <button
              type="button"
              class="reason-dropdown-option"
              :class="{ selected: reasonModal.selectedReason === REJECT_REASON_OTHER }"
              @click="selectRejectReason(REJECT_REASON_OTHER)"
            >
              Khác
            </button>
          </div>
        </div>
        <textarea
          v-if="reasonModal.selectedReason === REJECT_REASON_OTHER"
          v-model="reasonModal.reason"
          rows="4"
          placeholder="Nhập lý do..."
        ></textarea>
        <p v-if="reasonModal.error" class="modal-error">{{ reasonModal.error }}</p>
        <div class="modal-actions">
          <button class="outline-neutral" @click="reasonModal.open = false">Hủy</button>
          <button class="primary-action" @click="submitReason">Xác nhận</button>
        </div>
      </div>
    </div>

    <div v-if="confirmModal.open" class="modal-backdrop" @click.self="closeConfirm">
      <div class="modal-card confirm-card">
        <h3>{{ confirmModal.title }}</h3>
        <p>{{ confirmModal.message }}</p>
        <div class="modal-actions">
          <button class="outline-neutral" @click="closeConfirm">Hủy</button>
          <button
            :class="confirmModal.tone === 'danger' ? 'outline-danger' : 'primary-action'"
            :disabled="actionLoading"
            @click="runConfirmedAction"
          >
            {{ confirmModal.confirmText }}
          </button>
        </div>
      </div>
    </div>

    <div v-if="lightbox.open" class="lightbox-backdrop" @click.self="closeLightbox" @wheel.prevent @touchmove.prevent>
      <button class="lightbox-close" type="button" aria-label="Đóng" @click="closeLightbox">
        <X :size="22" />
      </button>
      <button
        v-if="lightbox.items.length > 1"
        class="lightbox-nav left"
        type="button"
        aria-label="Ảnh trước"
        @click.stop="moveLightbox(-1)"
      >
        <ChevronLeft :size="28" />
      </button>
      <div class="lightbox-content" :class="{ zoomed: lightboxZoomed }">
        <img
          v-if="activeLightboxItem?.type === 'image'"
          :src="activeLightboxItem.url"
          :alt="activeLightboxItem.title"
          :style="lightboxZoomed ? { transform: `translate(${panState.x}px, ${panState.y}px)` } : null"
          @click.stop="handleLightboxImageClick"
          @mousedown.stop.prevent="startPan"
          @mousemove.stop.prevent="movePan"
          @mouseup.stop="stopPan"
          @mouseleave.stop="stopPan"
        />
        <video
          v-else-if="activeLightboxItem?.type === 'video'"
          :src="activeLightboxItem.url"
          controls
          autoplay
        ></video>
      </div>
      <button
        v-if="lightbox.items.length > 1"
        class="lightbox-nav right"
        type="button"
        aria-label="Ảnh sau"
        @click.stop="moveLightbox(1)"
      >
        <ChevronRight :size="28" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.detail-page { max-width: 1180px; min-width: 0; margin: 0 auto; color: #0f172a; }
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
.tab-count { display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px; margin-left: 6px; border-radius: 999px; background: #fee2e2; color: #ef4444; font-size: 11px; font-weight: 800; }
.section-stack { display: grid; gap: 18px; }
.detail-card { min-width: 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 18px; box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03); }
.card-head { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; margin-bottom: 16px; }
.detail-card h2, .card-head h2 { display: flex; align-items: center; gap: 8px; margin: 0 0 16px; font-size: 17px; }
.card-head h2 { margin-bottom: 0; }
.verification-actions { display: inline-flex; align-items: center; gap: 10px; flex-shrink: 0; }
.media-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 14px; }
.media-thumb { display: block; padding: 0; border: 0; background: transparent; cursor: zoom-in; }
.media-thumb img { width: 100%; aspect-ratio: 4 / 3; object-fit: cover; border-radius: 10px; border: 1px solid #e2e8f0; display: block; }
.media-thumb:hover img, .doc-card:hover img { filter: brightness(0.94); }
.video-list { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; }
.video-chip { display: inline-flex; align-items: center; gap: 6px; height: 32px; border: 1px solid #dbeafe; border-radius: 999px; background: #eff6ff; color: #2563eb; padding: 0 12px; font-weight: 700; cursor: pointer; }
.video-note, .muted { display: flex; align-items: center; gap: 6px; color: #64748b; font-size: 13px; margin: 14px 0 0; }
.info-grid { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px; }
.info-item { display: grid; gap: 5px; min-width: 0; }
.info-item.wide { grid-column: span 4; }
.info-item span { color: #64748b; font-size: 12px; }
.info-item strong { color: #0f172a; font-size: 14px; font-weight: 650; word-break: break-word; }
.description { margin-top: 16px; }
.description span { color: #64748b; font-size: 12px; }
.description p { margin: 6px 0 0; line-height: 1.7; overflow-wrap: anywhere; word-break: break-word; }
.unit-price { margin-top: 16px; padding: 12px 14px; border: 1px solid #dbeafe; border-radius: 10px; background: #eff6ff; color: #3b82f6; font-size: 13px; }
.rent-terms { display: flex; flex-wrap: wrap; gap: 26px; margin-top: 16px; padding: 12px 14px; border: 1px solid #dbeafe; border-radius: 10px; background: #eff6ff; color: #64748b; font-size: 13px; }
.rent-terms strong { color: #2563eb; font-weight: 700; }
.detail-grid { grid-template-columns: repeat(5, minmax(0, 1fr)); }
.chips { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 18px; }
.chips span { border-radius: 999px; background: #eff6ff; color: #2563eb; padding: 7px 12px; font-size: 12px; font-weight: 700; }
.map-frame { width: 100%; height: 430px; border: 0; border-radius: 12px; margin-top: 16px; }
.map-empty { margin-top: 16px; height: 180px; display: grid; place-items: center; border-radius: 12px; background: #f8fafc; color: #64748b; }
.docs-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 14px; }
.doc-card { display: block; padding: 0; text-align: left; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; cursor: zoom-in; }
.doc-card img { width: 100%; aspect-ratio: 16 / 10; object-fit: cover; display: block; }
.doc-card div { display: flex; justify-content: space-between; gap: 10px; padding: 12px; }
.doc-card span { color: #16a34a; font-size: 12px; font-weight: 700; }
.history-list { display: grid; gap: 10px; }
.history-item { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; }
.history-item span { display: block; color: #64748b; font-size: 12px; margin-top: 4px; }
.history-item p { margin: 8px 0 0; color: #334155; }
.warning-list { display: grid; gap: 12px; }
.warning-card { display: grid; grid-template-columns: 42px minmax(0, 1fr); gap: 12px; border: 1px solid #e5e7eb; border-radius: 12px; padding: 14px; background: #fff; }
.warning-avatar { width: 36px; height: 36px; border-radius: 999px; overflow: hidden; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 800; }
.warning-avatar img { width: 100%; height: 100%; object-fit: cover; display: block; }
.warning-body { min-width: 0; }
.warning-head { display: flex; justify-content: space-between; align-items: flex-start; gap: 14px; }
.warning-head time { color: #64748b; font-size: 12px; white-space: nowrap; }
.warning-user-row { display: flex; align-items: center; flex-wrap: wrap; gap: 8px; }
.warning-user-row strong { font-size: 14px; color: #0f172a; }
.warning-status { border-radius: 999px; padding: 2px 8px; font-size: 11px; font-weight: 800; }
.warning-status.new { background: #fee2e2; color: #ef4444; }
.warning-status.processing { background: #fef3c7; color: #d97706; }
.warning-status.resolved { background: #dcfce7; color: #16a34a; }
.warning-status.rejected { background: #f1f5f9; color: #64748b; }
.warning-reasons { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
.warning-reasons span { color: #ef4444; font-size: 12px; font-weight: 700; }
.warning-description { margin: 10px 0 0; color: #334155; line-height: 1.6; font-size: 13px; }
.warning-images { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 10px; }
.warning-image { width: 120px; height: 88px; border: 0; border-radius: 10px; padding: 0; overflow: hidden; background: #f1f5f9; cursor: zoom-in; }
.warning-image img { width: 100%; height: 100%; display: block; object-fit: cover; }
.warning-image:hover img { filter: brightness(0.94); }
.primary-action, .outline-danger, .outline-neutral { height: 40px; display: inline-flex; align-items: center; gap: 7px; border-radius: 9px; padding: 0 14px; font-size: 14px; font-weight: 500; cursor: pointer; }
.primary-action { border: 1px solid #0ea5e9; background: #0ea5e9; color: #fff; }
.outline-danger { border: 1px solid #ef4444; background: #fff; color: #ef4444; }
.outline-neutral { border: 1px solid #cbd5e1; background: #fff; color: #475569; }
.state-box { border: 1px solid #e2e8f0; border-radius: 12px; background: #fff; padding: 28px; color: #64748b; }
.state-box.error { color: #dc2626; }
.modal-backdrop { position: fixed; inset: 0; z-index: 1200; display: grid; place-items: center; background: rgba(15, 23, 42, 0.45); }
.modal-card { width: min(440px, calc(100vw - 32px)); background: #fff; border-radius: 12px; padding: 20px; }
.modal-card h3 { margin: 0 0 12px; }
.confirm-card p { margin: 0; color: #475569; line-height: 1.6; }
.modal-card textarea { width: 100%; border: 1px solid #cbd5e1; border-radius: 9px; padding: 10px; font: inherit; color: #0f172a; }
.modal-card textarea { resize: vertical; margin-top: 10px; }
.reason-dropdown { position: relative; }
.reason-dropdown-trigger {
  width: 100%; min-height: 42px; display: flex; align-items: center; justify-content: space-between; gap: 10px;
  border: 1px solid #cbd5e1; border-radius: 9px; background: #fff; color: #0f172a;
  padding: 10px 12px; font: inherit; font-size: 13px; text-align: left; cursor: pointer;
  transition: border-color 0.2s, box-shadow 0.2s;
}
.reason-dropdown-trigger.placeholder { color: #64748b; }
.reason-dropdown-trigger:hover, .reason-dropdown-trigger:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12); outline: none; }
.reason-dropdown-trigger svg { flex-shrink: 0; color: #64748b; transition: transform 0.2s; }
.reason-dropdown-trigger svg.open { transform: rotate(180deg); }
.reason-dropdown-menu {
  position: absolute; z-index: 3; left: 0; right: 0; top: calc(100% + 6px);
  max-height: 230px; overflow-y: auto; border: 1px solid #dbe3ef; border-radius: 10px;
  background: #fff; box-shadow: 0 18px 42px rgba(15, 23, 42, 0.18); padding: 6px;
}
.reason-dropdown-option {
  width: 100%; border: 0; border-radius: 8px; background: transparent; color: #0f172a;
  padding: 9px 10px; font: inherit; font-size: 13px; line-height: 1.35; text-align: left; cursor: pointer;
}
.reason-dropdown-option:hover { background: #eff6ff; color: #1d4ed8; }
.reason-dropdown-option.selected { background: #dbeafe; color: #1d4ed8; font-weight: 700; }
.modal-error { margin: 8px 0 0; color: #dc2626; font-size: 13px; }
.modal-actions { display: flex; justify-content: flex-end; gap: 10px; margin-top: 14px; }
.lightbox-backdrop { position: fixed; inset: 0; z-index: 1300; display: flex; align-items: center; justify-content: center; background: rgba(2, 6, 23, 0.86); padding: 56px 88px; }
.lightbox-content { max-width: min(980px, calc(100vw - 176px)); max-height: 100%; display: grid; justify-items: center; gap: 12px; overflow: hidden; }
.lightbox-content img, .lightbox-content video { width: auto; height: auto; max-width: 100%; max-height: calc(100vh - 150px); object-fit: contain; border-radius: 12px; background: transparent; box-shadow: 0 24px 80px rgba(0,0,0,0.35); user-select: none; }
.lightbox-content img { cursor: zoom-in; }
.lightbox-content.zoomed { width: min(1400px, calc(100vw - 176px)); justify-items: start; align-items: start; }
.lightbox-content.zoomed img { max-width: none; max-height: none; width: min(1400px, 160vw); cursor: grab; }
.lightbox-content.zoomed img:active { cursor: grabbing; }
.lightbox-close, .lightbox-nav { position: fixed; border: 0; border-radius: 999px; background: rgba(255,255,255,0.95); color: #0f172a; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
.lightbox-close { top: 22px; right: 24px; width: 42px; height: 42px; }
.lightbox-nav { top: 50%; width: 48px; height: 48px; transform: translateY(-50%); }
.lightbox-nav.left { left: 28px; }
.lightbox-nav.right { right: 28px; }
.lightbox-close:hover, .lightbox-nav:hover { background: #ffffff; }
@media (max-width: 900px) {
  .detail-header { flex-direction: column; }
  .media-grid, .docs-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .info-grid, .detail-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .info-item.wide { grid-column: span 2; }
}
</style>
