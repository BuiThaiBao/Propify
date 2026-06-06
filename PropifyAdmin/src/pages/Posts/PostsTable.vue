<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { Ban, CheckCircle, ChevronDown, Info, Lock, MoreHorizontal, Unlock } from 'lucide-vue-next'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import {
  formatAdminDateTime,
  formatAdminDemandType,
  formatAdminListingPrice,
  formatAdminPropertyType,
  mapAdminListingStatusKey,
} from '@/utils/adminListingFormatters'

const props = defineProps({
  posts: {
    type: Array,
    default: () => [],
  },
  loading: {
    type: Boolean,
    default: false,
  },
  error: {
    type: String,
    default: '',
  },
  actionError: {
    type: String,
    default: '',
  },
  updatingPostId: {
    type: [Number, String],
    default: null,
  },
  statusOptions: {
    type: Array,
    default: () => [],
  },
  adminStatusOptions: {
    type: Array,
    default: () => [],
  },
})

const emit = defineEmits(['change-status', 'open-detail'])

const selectedIds = ref(new Set())
const activeMenuId = ref(null)
const menuPosition = ref({ top: 0, left: 0 })
const reasonModal = ref({
  open: false,
  post: null,
  action: null,
  selectedReason: '',
  reasonDropdownOpen: false,
  reason: '',
  error: '',
})

const REJECT_REASON_OTHER = '__OTHER__'
const REJECT_REASONS = [
  'Thông tin bài đăng không chính xác hoặc thiếu nhất quán.',
  'Hình ảnh không rõ ràng hoặc không phù hợp với nội dung tin.',
  'Nội dung mô tả sơ sài, thiếu thông tin bắt buộc.',
  'Thông tin liên hệ không hợp lệ.',
  'Tin đăng có dấu hiệu trùng lặp hoặc spam.',
]
const LOCK_REASONS = [
  'Tin đăng vi phạm quy định nội dung.',
  'Tin đăng có dấu hiệu gian lận hoặc lừa đảo.',
  'Tin đăng bị nhiều người dùng báo cáo.',
  'Thông tin bất động sản không còn phù hợp để hiển thị.',
  'Yêu cầu khóa tin từ bộ phận quản trị.',
]

const normalizedPosts = computed(() =>
  props.posts.map((post) => ({
    ...post,
    imageUrl: getImageUrl(post),
    priceText: formatAdminListingPrice(post.property?.price),
    packageName: post.package?.name || 'Free',
    ownerName: post.owner?.full_name || '--',
    approverName: post.approver?.full_name || (post.approved_by ? `ID ${post.approved_by}` : '--'),
    demandTypeText: formatAdminDemandType(post.demand_type),
    propertyType: formatAdminPropertyType(post.property?.type),
    address: post.property?.address_detail || post.property?.project_name || '--',
    statusKey: mapAdminListingStatusKey(post.status),
    statusLabel: mapStatusLabel(post.status),
    verificationKey: post.is_verified ? 'approved' : 'locked',
    verificationLabel: post.is_verified ? 'Đã xác thực' : 'Chưa xác thực',
    createdAtText: formatAdminDateTime(post.created_at),
    submittedAtText: formatAdminDateTime(post.submitted_at),
    publishedAtText: formatAdminDateTime(post.published_at),
  })),
)

const allVisibleSelected = computed(() => {
  return (
    normalizedPosts.value.length > 0 &&
    normalizedPosts.value.every((post) => selectedIds.value.has(post.id))
  )
})

function getImageUrl(post) {
  const images = post.images ?? []
  return (
    images.find((image) => image.is_thumbnail)?.url ||
    images[0]?.url ||
    'https://placehold.co/80x60?text=No+Image'
  )
}

function mapStatusLabel(status) {
  return (
    props.statusOptions.find((option) => option.value === status)?.label || status || 'Chờ duyệt'
  )
}

function adminStatusLabel(status) {
  return (
    props.adminStatusOptions.find((option) => option.value === status)?.label ||
    mapStatusLabel(status)
  )
}

function toggleSelected(id) {
  const next = new Set(selectedIds.value)
  if (next.has(id)) {
    next.delete(id)
  } else {
    next.add(id)
  }
  selectedIds.value = next
}

function toggleAllVisible() {
  if (allVisibleSelected.value) {
    selectedIds.value = new Set()
    return
  }

  selectedIds.value = new Set(normalizedPosts.value.map((post) => post.id))
}

function getActions(post) {
  const actions = []

  if (post.status === 'PENDING') {
    actions.push({
      label: adminStatusLabel('ACTIVE'),
      status: 'ACTIVE',
      icon: CheckCircle,
      className: 'approve-action',
    })
  }

  if (post.status === 'ACTIVE') {
    actions.push({
      label: adminStatusLabel('LOCKED'),
      status: 'LOCKED',
      icon: Lock,
      className: 'lock-action',
    })
  }

  if (post.status === 'LOCKED') {
    actions.push({
      label: 'Mở khóa',
      status: 'ACTIVE',
      icon: Unlock,
      className: 'approve-action',
    })
  }

  if (post.status === 'PENDING') {
    actions.push({
      label: adminStatusLabel('REJECTED'),
      status: 'REJECTED',
      icon: Ban,
      className: 'reject-action',
    })
  }

  return actions
}

function toggleMenu(post, event) {
  if (activeMenuId.value === post.id) {
    activeMenuId.value = null
    return
  }

  const rect = event.currentTarget.getBoundingClientRect()
  const menuWidth = 128
  const left = Math.max(12, rect.left - menuWidth - 8)

  menuPosition.value = {
    top: rect.top,
    left,
  }
  activeMenuId.value = post.id
}

function closeMenu() {
  activeMenuId.value = null
}

function handleAction(post, action) {
  if (['REJECTED', 'LOCKED'].includes(action.status)) {
    closeMenu()
    reasonModal.value = {
      open: true,
      post,
      action,
      selectedReason: '',
      reasonDropdownOpen: false,
      reason: '',
      error: '',
    }
    return
  }

  closeMenu()
  emit('change-status', {
    id: post.id,
    status: action.status,
    rejectionReason: null,
  })
}

function closeReasonModal() {
  reasonModal.value = {
    open: false,
    post: null,
    action: null,
    selectedReason: '',
    reasonDropdownOpen: false,
    reason: '',
    error: '',
  }
}

function getReasonLabel() {
  if (!reasonModal.value.selectedReason) {
    return reasonModal.value.action?.status === 'LOCKED'
      ? 'Chọn lý do khóa tin'
      : 'Chọn lý do từ chối'
  }
  if (reasonModal.value.selectedReason === REJECT_REASON_OTHER) return 'Khác'
  return reasonModal.value.selectedReason
}

function getReasonOptions() {
  return reasonModal.value.action?.status === 'LOCKED' ? LOCK_REASONS : REJECT_REASONS
}

function selectRejectReason(reason) {
  reasonModal.value.selectedReason = reason
  reasonModal.value.reasonDropdownOpen = false
  reasonModal.value.error = ''
  if (reason !== REJECT_REASON_OTHER) {
    reasonModal.value.reason = ''
  }
}

function submitReasonModal() {
  const { post, action, selectedReason, reason } = reasonModal.value
  if (!post || !action) return
  const usesDropdown = ['REJECTED', 'LOCKED'].includes(action.status)
  const finalReason = usesDropdown
    ? selectedReason === REJECT_REASON_OTHER
      ? reason.trim()
      : selectedReason
    : reason.trim()

  if (usesDropdown && !finalReason) {
    reasonModal.value.error =
      action.status === 'LOCKED'
        ? 'Vui lòng chọn hoặc nhập lý do khóa tin.'
        : 'Vui lòng chọn hoặc nhập lý do từ chối.'
    return
  }

  emit('change-status', {
    id: post.id,
    status: action.status,
    rejectionReason: finalReason || null,
  })
  closeReasonModal()
}

function handleDocumentClick(event) {
  if (!event.target.closest('.action-menu') && !event.target.closest('.more-btn')) {
    closeMenu()
  }
}

onMounted(() => {
  document.addEventListener('click', handleDocumentClick)
  window.addEventListener('resize', closeMenu)
  window.addEventListener('scroll', closeMenu, true)
})

onBeforeUnmount(() => {
  document.removeEventListener('click', handleDocumentClick)
  window.removeEventListener('resize', closeMenu)
  window.removeEventListener('scroll', closeMenu, true)
})
</script>

<template>
  <div class="posts-table-wrap">
    <div class="posts-table-scroll">
      <table class="posts-table">
        <thead>
          <tr>
            <th class="sticky-left select-cell">
              <input
                type="checkbox"
                class="row-checkbox"
                :checked="allVisibleSelected"
                :disabled="loading || normalizedPosts.length === 0"
                aria-label="Chọn tất cả tin đăng"
                @change="toggleAllVisible"
              />
            </th>
            <th class="col-id">ID</th>
            <th class="col-image">Ảnh</th>
            <th class="col-title">Tên tin đăng</th>
            <th class="col-address">Địa chỉ</th>
            <th class="col-price">
              <span class="header-with-info">Giá <Info :size="14" /></span>
            </th>
            <th class="col-package">Gói tin</th>
            <th class="col-owner">Chủ nhà</th>
            <th class="col-demand">Nhu cầu</th>
            <th class="col-type">Loại hình</th>
            <th class="col-date">Ngày tạo</th>
            <th class="col-date">
              <span class="header-with-info">Ngày đăng tin <Info :size="14" /></span>
            </th>
            <th class="col-date">
              <span class="header-with-info">Ngày duyệt đăng tin <Info :size="14" /></span>
            </th>
            <th class="col-approver">Người duyệt</th>
            <th class="sticky-right sticky-verify col-verify">TT xác minh</th>
            <th class="sticky-right sticky-status col-status">Trạng thái tin</th>
            <th class="sticky-right sticky-action action-cell"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="actionError">
            <td class="sticky-left select-cell"></td>
            <td colspan="12" class="state-cell error-cell">{{ actionError }}</td>
            <td class="sticky-right sticky-verify col-verify"></td>
            <td class="sticky-right sticky-status col-status"></td>
            <td class="sticky-right sticky-action action-cell"></td>
          </tr>
          <tr v-if="loading">
            <td class="sticky-left select-cell"></td>
            <td colspan="12" class="state-cell">Đang tải dữ liệu...</td>
            <td class="sticky-right sticky-verify col-verify"></td>
            <td class="sticky-right sticky-status col-status"></td>
            <td class="sticky-right sticky-action action-cell"></td>
          </tr>
          <tr v-else-if="error">
            <td class="sticky-left select-cell"></td>
            <td colspan="12" class="state-cell error-cell">{{ error }}</td>
            <td class="sticky-right sticky-verify col-verify"></td>
            <td class="sticky-right sticky-status col-status"></td>
            <td class="sticky-right sticky-action action-cell"></td>
          </tr>
          <tr v-else-if="normalizedPosts.length === 0">
            <td class="sticky-left select-cell"></td>
            <td colspan="12" class="state-cell">Không có tin đăng phù hợp.</td>
            <td class="sticky-right sticky-verify col-verify"></td>
            <td class="sticky-right sticky-status col-status"></td>
            <td class="sticky-right sticky-action action-cell"></td>
          </tr>
          <tr
            v-for="post in normalizedPosts"
            v-else
            :key="post.id"
            :class="{ 'clickable-row': ['SALE', 'RENT'].includes(post.demand_type) }"
            @click="emit('open-detail', post)"
          >
            <td class="sticky-left select-cell">
              <input
                type="checkbox"
                class="row-checkbox"
                :checked="selectedIds.has(post.id)"
                :aria-label="`Chọn tin đăng ${post.id}`"
                @click.stop
                @change="toggleSelected(post.id)"
              />
            </td>
            <td class="col-id id-text">{{ post.id }}</td>
            <td class="col-image">
              <img :src="post.imageUrl" :alt="post.title" class="post-img" />
            </td>
            <td class="col-title title-text">{{ post.title || '--' }}</td>
            <td class="col-address address-text">{{ post.address }}</td>
            <td class="col-price price-text">{{ post.priceText }}</td>
            <td class="col-package">{{ post.packageName }}</td>
            <td class="col-owner truncate-text">{{ post.ownerName }}</td>
            <td class="col-demand">{{ post.demandTypeText }}</td>
            <td class="col-type">{{ post.propertyType }}</td>
            <td class="col-date">{{ post.createdAtText }}</td>
            <td class="col-date">{{ post.submittedAtText }}</td>
            <td class="col-date">{{ post.publishedAtText }}</td>
            <td class="col-approver truncate-text">{{ post.approverName }}</td>
            <td class="sticky-right sticky-verify col-verify">
              <StatusBadge :status="post.verificationKey" :label="post.verificationLabel" />
            </td>
            <td class="sticky-right sticky-status col-status">
              <StatusBadge :status="post.statusKey" :label="post.statusLabel" />
            </td>
            <td class="sticky-right sticky-action action-cell">
              <button
                v-if="getActions(post).length"
                class="more-btn"
                :disabled="updatingPostId === post.id"
                :aria-label="`Mở thao tác tin đăng ${post.id}`"
                @click.stop="toggleMenu(post, $event)"
              >
                <MoreHorizontal :size="20" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <div
      v-if="activeMenuId"
      class="action-menu"
      :style="{ top: `${menuPosition.top}px`, left: `${menuPosition.left}px` }"
      @click.stop
    >
      <template v-for="post in normalizedPosts" :key="post.id">
        <button
          v-for="action in getActions(post)"
          v-show="activeMenuId === post.id"
          :key="action.status"
          class="action-menu-item"
          :class="action.className"
          :disabled="updatingPostId === post.id"
          @click="handleAction(post, action)"
        >
          <component :is="action.icon" :size="15" />
          <span>{{ action.label }}</span>
        </button>
      </template>
    </div>

    <div v-if="reasonModal.open" class="reason-modal-backdrop" @click.self="closeReasonModal">
      <div class="reason-modal" role="dialog" aria-modal="true">
        <h3 class="reason-modal-title">
          {{ reasonModal.action?.status === 'LOCKED' ? 'Khóa tin' : 'Từ chối tin' }}
        </h3>
        <p class="reason-modal-desc">
          {{ reasonModal.post?.title }}
        </p>
        <label class="reason-label" for="status-reason">
          {{ reasonModal.action?.status === 'LOCKED' ? 'Lý do khóa tin' : 'Lý do từ chối' }}
        </label>
        <div
          v-if="['REJECTED', 'LOCKED'].includes(reasonModal.action?.status)"
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
          id="status-reason-custom"
          v-model="reasonModal.reason"
          class="reason-textarea"
          rows="4"
          placeholder="Nhập lý do..."
        ></textarea>
        <p v-if="reasonModal.error" class="reason-error">{{ reasonModal.error }}</p>
        <div class="reason-actions">
          <button class="reason-btn secondary" type="button" @click="closeReasonModal">Hủy</button>
          <button class="reason-btn primary" type="button" @click="submitReasonModal">
            Xác nhận
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.posts-table-wrap {
  background: #ffffff;
  border: 1px solid #e7edf5;
  border-radius: 8px;
  overflow: hidden;
}

.posts-table-scroll {
  max-width: 100%;
  max-height: calc(100vh - 260px);
  overflow-x: auto;
  overflow-y: auto;
}

.posts-table {
  min-width: 1840px;
  width: max-content;
  border-collapse: separate;
  border-spacing: 0;
  color: #1b365d;
  font-size: 14px;
}

.posts-table th,
.posts-table td {
  min-height: 64px;
  padding: 10px 12px;
  border-bottom: 1px solid #f0f3f7;
  background: #ffffff;
  text-align: left;
  white-space: nowrap;
  vertical-align: middle;
}

.posts-table th {
  height: 44px;
  min-height: 44px;
  position: sticky;
  top: 0;
  z-index: 5;
  background: #f1f3f6;
  color: #40536f;
  font-size: 13px;
  font-weight: 700;
}

.posts-table tbody tr:nth-child(even) td {
  background: #f7f8fa;
}

.posts-table tbody tr:hover td {
  background: #f1f6ff;
}

.clickable-row {
  cursor: pointer;
}

.sticky-left {
  position: sticky;
  left: 0;
  z-index: 3;
  box-shadow: 1px 0 0 #e8edf3;
}

.sticky-right {
  position: sticky;
  z-index: 3;
  box-shadow: -1px 0 0 #e8edf3;
}

.sticky-action {
  right: 0;
}

.sticky-status {
  right: 56px;
}

.sticky-verify {
  right: 186px;
}

thead .sticky-left,
thead .sticky-right {
  z-index: 8;
}

.select-cell {
  width: 48px;
  min-width: 48px;
  max-width: 48px;
  text-align: center !important;
}

.action-cell {
  width: 56px;
  min-width: 56px;
  max-width: 56px;
  text-align: center !important;
}

.row-checkbox {
  width: 18px;
  height: 18px;
  border: 1px solid #17365f;
  border-radius: 4px;
  accent-color: #1d4ed8;
  cursor: pointer;
}

.col-id {
  width: 58px;
  min-width: 58px;
}
.col-image {
  width: 54px;
  min-width: 54px;
}
.col-title {
  width: 240px;
  max-width: 240px;
}
.col-address {
  width: 280px;
  max-width: 280px;
}
.col-price {
  width: 132px;
  min-width: 132px;
}
.col-package {
  width: 104px;
  min-width: 104px;
}
.col-owner {
  width: 196px;
  max-width: 196px;
}
.col-demand {
  width: 110px;
  min-width: 110px;
}
.col-approver {
  width: 160px;
  max-width: 160px;
}
.col-type {
  width: 110px;
  min-width: 110px;
}
.col-date {
  width: 150px;
  min-width: 150px;
}
.col-status {
  width: 130px;
  min-width: 130px;
}
.col-verify {
  width: 130px;
  min-width: 130px;
}

.header-with-info {
  display: inline-flex;
  align-items: center;
  gap: 4px;
}

.header-with-info svg {
  color: #b13cff;
}

.post-img {
  width: 30px;
  height: 30px;
  border-radius: 4px;
  object-fit: cover;
  border: 1px solid #e2e8f0;
}

.truncate-text {
  overflow: hidden;
  text-overflow: ellipsis;
}

.title-text {
  max-width: 240px;
  min-width: 240px;
  white-space: normal !important;
  overflow: visible;
  color: #1b365d;
  font-weight: 500;
  line-height: 1.45;
  word-break: break-word;
}

.address-text {
  max-width: 280px;
  min-width: 280px;
  white-space: normal !important;
  overflow: visible;
  color: #334155;
  line-height: 1.45;
  word-break: break-word;
}

.id-text {
  color: #0094ff;
  font-weight: 500;
}

.price-text {
  color: #12335e;
  font-weight: 600;
}

.more-btn {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: #17365f;
  cursor: pointer;
}

.more-btn:hover {
  background: #e8eef7;
}

.more-btn:disabled {
  cursor: wait;
  opacity: 0.55;
}

.action-menu {
  position: fixed;
  z-index: 1000;
  min-width: 128px;
  overflow: hidden;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  background: #ffffff;
  box-shadow: 0 10px 24px rgba(15, 23, 42, 0.14);
}

.action-menu-item {
  width: 100%;
  height: 36px;
  padding: 0 10px;
  display: flex;
  align-items: center;
  gap: 8px;
  border: 0;
  background: #ffffff;
  color: #17365f;
  font-size: 13px;
  font-weight: 600;
  text-align: left;
  cursor: pointer;
}

.action-menu-item:hover:not(:disabled) {
  background: #f4f7fb;
}

.action-menu-item:disabled {
  cursor: wait;
  opacity: 0.6;
}

.approve-action {
  color: #059669;
}

.lock-action {
  color: #475569;
}

.reject-action {
  color: #dc2626;
}

.reason-modal-backdrop {
  position: fixed;
  inset: 0;
  z-index: 1100;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 20px;
  background: rgba(15, 23, 42, 0.45);
}

.reason-modal {
  width: min(460px, 100%);
  border-radius: 10px;
  border: 1px solid #dbe3ef;
  background: #ffffff;
  box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
  padding: 20px;
}

.reason-modal-title {
  margin: 0;
  color: #0f172a;
  font-size: 18px;
  font-weight: 700;
}

.reason-modal-desc {
  margin: 6px 0 16px;
  color: #64748b;
  font-size: 13px;
  line-height: 1.45;
}

.reason-label {
  display: block;
  margin-bottom: 8px;
  color: #334155;
  font-size: 13px;
  font-weight: 600;
}

.reason-textarea {
  width: 100%;
  border: 1px solid #cbd5e1;
  border-radius: 8px;
  padding: 10px 12px;
  color: #0f172a;
  font: inherit;
  outline: none;
}

.reason-dropdown {
  position: relative;
}

.reason-dropdown-trigger {
  width: 100%;
  min-height: 42px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  border: 1px solid #cbd5e1;
  border-radius: 9px;
  background: #ffffff;
  color: #0f172a;
  padding: 10px 12px;
  font: inherit;
  font-size: 13px;
  text-align: left;
  cursor: pointer;
  transition:
    border-color 0.2s,
    box-shadow 0.2s;
}

.reason-dropdown-trigger.placeholder {
  color: #64748b;
}

.reason-dropdown-trigger:hover,
.reason-dropdown-trigger:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
  outline: none;
}

.reason-dropdown-trigger svg {
  flex-shrink: 0;
  color: #64748b;
  transition: transform 0.2s;
}

.reason-dropdown-trigger svg.open {
  transform: rotate(180deg);
}

.reason-dropdown-menu {
  position: absolute;
  z-index: 3;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  max-height: 230px;
  overflow-y: auto;
  border: 1px solid #dbe3ef;
  border-radius: 10px;
  background: #ffffff;
  box-shadow: 0 18px 42px rgba(15, 23, 42, 0.18);
  padding: 6px;
}

.reason-dropdown-option {
  width: 100%;
  border: 0;
  border-radius: 8px;
  background: transparent;
  color: #0f172a;
  padding: 9px 10px;
  font: inherit;
  font-size: 13px;
  line-height: 1.35;
  text-align: left;
  cursor: pointer;
}

.reason-dropdown-option:hover {
  background: #eff6ff;
  color: #1d4ed8;
}

.reason-dropdown-option.selected {
  background: #dbeafe;
  color: #1d4ed8;
  font-weight: 700;
}

.reason-textarea {
  resize: vertical;
  min-height: 108px;
  margin-top: 10px;
}

.reason-textarea:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
}

.reason-error {
  margin: 8px 0 0;
  color: #dc2626;
  font-size: 13px;
}

.reason-actions {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  margin-top: 16px;
}

.reason-btn {
  height: 36px;
  padding: 0 14px;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
}

.reason-btn.secondary {
  border: 1px solid #dbe3ef;
  background: #ffffff;
  color: #334155;
}

.reason-btn.primary {
  border: 1px solid #2563eb;
  background: #2563eb;
  color: #ffffff;
}

.state-cell {
  height: 80px !important;
  color: #64748b;
  text-align: center !important;
}

.error-cell {
  color: #dc2626;
}
</style>
