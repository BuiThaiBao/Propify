<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { userService } from '@/services/userService'
import {
  Search,
  Filter,
  Lock,
  Unlock,
  Mail,
  Phone,
  ChevronLeft,
  ChevronRight,
  Users,
  AlertCircle,
  RefreshCw,
  X,
} from 'lucide-vue-next'

const router = useRouter()

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
const search = ref('')
const roleFilter = ref('all')
const authTypeFilter = ref('all')
const users = ref([])
const loading = ref(false)
const error = ref(null)

// Pagination
const currentPage = ref(1)
const lastPage = ref(1)
const perPage = ref(20)
const total = ref(0)

// Stats
const googleCount = ref(0)
const emailCount = ref(0)

// Custom Lock Modal refs
const lockModalOpen = ref(false)
const lockSelectedUserId = ref(null)
const lockSelectedReason = ref(null)
const lockOtherText = ref('')
const lockOtherTextError = ref('')
const lockReasonError = ref('')

// Unlock Modal refs
const unlockModalOpen = ref(false)
const unlockSelectedUserId = ref(null)

const actionLoading = ref(false)

// Search debounce
let searchTimer = null

// ─── Computed ─────────────────────────────────────────────────────────────────
const pageNumbers = computed(() => {
  const pages = []
  const range = 2
  for (let i = Math.max(1, currentPage.value - range); i <= Math.min(lastPage.value, currentPage.value + range); i++) {
    pages.push(i)
  }
  return pages
})

const showingFrom = computed(() => total.value === 0 ? 0 : (currentPage.value - 1) * perPage.value + 1)
const showingTo = computed(() => Math.min(currentPage.value * perPage.value, total.value))

const isLockModalValid = computed(() => {
  if (!lockSelectedReason.value) return false
  if (Number(lockSelectedReason.value) === 6) {
    return lockOtherText.value.trim().length > 0 && lockOtherText.value.trim().length <= 500
  }
  return true
})

const otherTextRemaining = computed(() => 500 - (lockOtherText.value?.length || 0))

// ─── API ──────────────────────────────────────────────────────────────────────
async function fetchUsers() {
  loading.value = true
  error.value = null
  try {
    const res = await userService.getUsers({
      search: search.value || undefined,
      role: roleFilter.value !== 'all' ? roleFilter.value : undefined,
      auth_type: authTypeFilter.value !== 'all' ? authTypeFilter.value : undefined,
      page: currentPage.value,
      per_page: perPage.value,
    })
    const { data, meta } = res.data
    users.value = data
    currentPage.value = meta.current_page
    lastPage.value = meta.last_page
    perPage.value = meta.per_page
    total.value = meta.total

    // Count Google vs email from current page for quick stats
    googleCount.value = data.filter((u) => u.isGoogleAccount).length
    emailCount.value = data.filter((u) => !u.isGoogleAccount).length
  } catch (err) {
    console.error('[Users] fetchUsers error:', err?.response?.status, err?.response?.data, err)
    const status = err?.response?.status
    const msg = err?.response?.data?.message
    if (status === 401) {
      error.value = 'Phiên đăng nhập hết hạn. Vui lòng đăng nhập lại.'
    } else if (status === 403) {
      error.value = 'Bạn không có quyền truy cập danh sách tài khoản.'
    } else {
      error.value = msg || `Không thể tải danh sách tài khoản. (${status ?? 'network error'})`
    }
  } finally {
    loading.value = false
  }
}

function onSearchInput() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    currentPage.value = 1
    fetchUsers()
  }, 400)
}

function onFilterChange() {
  currentPage.value = 1
  fetchUsers()
}

function goToPage(page) {
  if (page < 1 || page > lastPage.value || page === currentPage.value) return
  currentPage.value = page
  fetchUsers()
}

function goToUserDetail(user, event) {
  // If clicked on action button cell, do NOT navigate!
  if (event.target.closest('.act-btn') || event.target.closest('.td-actions')) return
  router.push({ name: 'UserDetail', params: { id: user.id } })
}

// ─── Lock / Unlock Modal Actions ────────────────────────────────────────────────
function openToggleModal(user) {
  if (user.status === 'locked') {
    unlockSelectedUserId.value = user.id
    unlockModalOpen.value = true
  } else {
    lockSelectedUserId.value = user.id
    lockSelectedReason.value = null
    lockOtherText.value = ''
    lockOtherTextError.value = ''
    lockReasonError.value = ''
    lockModalOpen.value = true
  }
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
    await userService.changeStatus(lockSelectedUserId.value, { status: 'locked', reason_code: reasonCode, reason_text: reasonText })
    lockModalOpen.value = false
    await fetchUsers()
  } catch (err) {
    lockOtherTextError.value = err?.response?.data?.message || 'Khóa tài khoản thất bại.'
  } finally {
    actionLoading.value = false
  }
}

async function handleUnlockConfirm() {
  actionLoading.value = true
  try {
    await userService.changeStatus(unlockSelectedUserId.value, { status: 'active', reason_code: null, reason_text: null })
    unlockModalOpen.value = false
    await fetchUsers()
  } catch (err) {
    error.value = err?.response?.data?.message || 'Mở khóa tài khoản thất bại.'
    unlockModalOpen.value = false
  } finally {
    actionLoading.value = false
  }
}

onMounted(fetchUsers)
</script>

<template>
  <div class="users-list-container">
    <PageHeader
      title="Quản lý tài khoản"
      description="Quản lý người dùng và môi giới trên hệ thống"
    />

    <div class="stats-bar">
      <div class="stat-card">
        <Users :size="18" class="stat-icon" />
        <div>
          <p class="stat-num">{{ total.toLocaleString() }}</p>
          <p class="stat-label">Tổng tài khoản</p>
        </div>
      </div>
      <div class="stat-card stat-google">
        <svg class="google-logo-stat" viewBox="0 0 24 24" width="18" height="18" xmlns="http://www.w3.org/2000/svg">
          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
        </svg>
        <div>
          <p class="stat-num stat-num-google">{{ googleCount }}</p>
          <p class="stat-label">Tài khoản Google</p>
        </div>
      </div>
      <div class="stat-card stat-email">
        <Mail :size="18" class="stat-icon-email" />
        <div>
          <p class="stat-num">{{ emailCount }}</p>
          <p class="stat-label">Tài khoản Email</p>
        </div>
      </div>
    </div>

    <!-- Filters -->
    <div class="filter-bar">
      <div class="filter-search">
        <Search :size="16" class="search-icon" />
        <input
          v-model="search"
          @input="onSearchInput"
          type="text"
          placeholder="Tìm kiếm theo tên, email, số điện thoại..."
          class="filter-input"
          id="users-search"
        />
      </div>
      <div class="filter-right">
        <Filter :size="16" color="hsl(215,16%,47%)" />
        <select v-model="roleFilter" @change="onFilterChange" class="filter-select" id="users-role-filter">
          <option value="all">Tất cả vai trò</option>
          <option value="user">Người dùng</option>
          <option value="agent">Môi giới</option>
        </select>
        <select v-model="authTypeFilter" @change="onFilterChange" class="filter-select" id="users-auth-filter">
          <option value="all">Tất cả loại ĐN</option>
          <option value="google">Google</option>
          <option value="email">Email</option>
        </select>
      </div>
    </div>

    <!-- Error -->
    <div v-if="error" class="error-banner">
      <AlertCircle :size="16" />
      <span>{{ error }}</span>
      <button class="retry-btn" @click="fetchUsers" id="users-retry-btn">
        <RefreshCw :size="14" /> Thử lại
      </button>
    </div>

    <!-- Table -->
    <div class="table-wrap">
      <!-- Loading overlay -->
      <div v-if="loading" class="loading-overlay">
        <div class="spinner" />
        <span>Đang tải dữ liệu...</span>
      </div>

      <!-- Empty state -->
      <div v-else-if="users.length === 0" class="empty-state">
        <Users :size="48" class="empty-icon" />
        <p class="empty-title">Không tìm thấy tài khoản</p>
        <p class="empty-desc">Thử thay đổi bộ lọc hoặc từ khóa tìm kiếm.</p>
      </div>

      <!-- Data table -->
      <div v-else class="table-scroll">
        <table class="data-table">
          <thead>
            <tr class="table-head-row">
              <th class="th">Người dùng</th>
              <th class="th">Liên hệ</th>
              <th class="th">Đăng nhập</th>
              <th class="th">Vai trò</th>
              <th class="th">Tin đăng</th>
              <th class="th">Trạng thái</th>
              <th class="th th-actions">Hành động</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="user in users"
              :key="user.id"
              class="table-row cursor-pointer"
              @click="goToUserDetail(user, $event)"
            >
              <!-- User info -->
              <td class="td">
                <div class="user-info">
                  <div class="avatar-wrapper">
                    <img
                      v-if="user.avatarUrl"
                      :src="user.avatarUrl"
                      :alt="user.name"
                      class="user-avatar user-avatar-img"
                    />
                    <div
                      v-else
                      class="user-avatar"
                      :style="{ backgroundColor: user.avatarBg, color: user.avatarColor }"
                    >
                      {{ user.initial }}
                    </div>
                    <span v-if="user.isGoogleAccount" class="google-dot" title="Tài khoản Google">
                      <svg viewBox="0 0 24 24" width="10" height="10" xmlns="http://www.w3.org/2000/svg">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                      </svg>
                    </span>
                  </div>
                  <div>
                    <p class="user-name">{{ user.name }}</p>
                    <p class="user-join">{{ user.joinDate }}</p>
                  </div>
                </div>
              </td>
              <!-- Contact -->
              <td class="td">
                <div class="contact">
                  <p class="contact-line">
                    <Mail :size="14" color="hsl(215,16%,47%)" /> {{ user.email }}
                  </p>
                  <p class="contact-line">
                    <Phone :size="14" color="hsl(215,16%,47%)" /> {{ user.phone || '—' }}
                  </p>
                </div>
              </td>
              <!-- Auth type -->
              <td class="td">
                <span v-if="user.isGoogleAccount" class="auth-badge auth-google">
                  <svg viewBox="0 0 24 24" width="12" height="12" xmlns="http://www.w3.org/2000/svg" style="flex-shrink:0">
                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                  </svg>
                  Google
                </span>
                <span v-else class="auth-badge auth-email">
                  <Mail :size="12" /> Email
                </span>
              </td>
              <!-- Role -->
              <td class="td">
                <span
                  class="role-badge"
                  :class="user.role === 'agent' ? 'role-agent' : 'role-user'"
                >
                  {{ user.roleLabel }}
                </span>
              </td>
              <!-- Posts -->
              <td class="td user-posts">{{ user.posts }}</td>
              <!-- Status -->
              <td class="td">
                <StatusBadge
                  :status="user.status === 'locked' ? 'locked' : 'approved'"
                  :label="user.statusLabel"
                />
              </td>
              <!-- Actions (Contains only Lock/Unlock toggle) -->
              <td class="td td-actions">
                <button
                  class="act-btn"
                  :class="user.status === 'locked' ? 'btn-unlock' : 'btn-lock'"
                  :id="`toggle-user-${user.id}`"
                  :title="user.status === 'locked' ? 'Mở khóa tài khoản' : 'Khóa tài khoản'"
                  @click.stop="openToggleModal(user)"
                >
                  <component
                    :is="user.status === 'locked' ? Unlock : Lock"
                    :size="15"
                  />
                  <span class="act-btn-label">{{ user.status === 'locked' ? 'Mở khóa' : 'Khóa' }}</span>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="!loading && total > 0" class="pagination">
        <span class="pagination-info">
          Hiển thị {{ showingFrom }}–{{ showingTo }} / {{ total }} tài khoản
        </span>
        <div class="pagination-controls">
          <button
            class="page-btn"
            :disabled="currentPage === 1"
            @click="goToPage(1)"
            id="users-first-page"
            title="Trang đầu"
          >
            <ChevronLeft :size="14" style="margin-right:-6px" /><ChevronLeft :size="14" />
          </button>
          <button
            class="page-btn"
            :disabled="currentPage === 1"
            @click="goToPage(currentPage - 1)"
            id="users-prev-page"
            title="Trang trước"
          >
            <ChevronLeft :size="16" />
          </button>
          <button
            v-for="page in pageNumbers"
            :key="page"
            class="page-btn"
            :class="{ active: page === currentPage }"
            :id="`users-page-${page}`"
            @click="goToPage(page)"
          >
            {{ page }}
          </button>
          <button
            class="page-btn"
            :disabled="currentPage === lastPage"
            @click="goToPage(currentPage + 1)"
            id="users-next-page"
            title="Trang sau"
          >
            <ChevronRight :size="16" />
          </button>
          <button
            class="page-btn"
            :disabled="currentPage === lastPage"
            @click="goToPage(lastPage)"
            id="users-last-page"
            title="Trang cuối"
          >
            <ChevronRight :size="14" style="margin-right:-6px" /><ChevronRight :size="14" />
          </button>
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
              <p>Tài khoản này sẽ bị tạm khóa và không thể đăng nhập.</p>
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
              >
                <input
                  type="radio"
                  :value="r.code"
                  v-model="lockSelectedReason"
                  name="lock_reason_page"
                  class="reason-radio"
                />
                <span class="reason-text-label">{{ r.label }}</span>
              </label>
            </div>
            <p v-if="lockReasonError" class="field-error">{{ lockReasonError }}</p>

            <!-- TextArea for REASON_06 / code 6 -->
            <div v-if="Number(lockSelectedReason) === 6 || lockSelectedReason === 6" class="other-reason-block">
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
      <div v-if="unlockModalOpen" class="modal-overlay" @click.self="unlockModalOpen = false">
        <div class="unlock-modal">
          <div class="lock-modal-header">
            <div class="lock-modal-icon unlock-icon">
              <Unlock :size="24" color="#16a34a" />
            </div>
            <div>
              <h2>Mở khóa tài khoản</h2>
              <p>Tài khoản này sẽ được kích hoạt và có thể đăng nhập trở lại.</p>
            </div>
            <button class="modal-close-btn" @click="unlockModalOpen = false"><X :size="20" /></button>
          </div>
          <div class="lock-modal-footer">
            <button class="modal-cancel-btn" :disabled="actionLoading" @click="unlockModalOpen = false">Hủy bỏ</button>
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
.users-list-container {
  width: 100%;
  max-width: 100%;
  padding: 0 24px;
  box-sizing: border-box;
}

/* ── Stats bar ───────────────────────────────────────────── */
.stats-bar {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 20px;
  flex-wrap: wrap;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 12px;
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border) / 0.5);
  border-radius: 12px;
  padding: 12px 18px;
  box-shadow: var(--shadow-card);
  min-width: 160px;
}

.stat-google {
  border-color: rgba(66, 133, 244, 0.25);
  background: linear-gradient(135deg, hsl(var(--card)), rgba(66,133,244,0.04));
}

.stat-email {
  border-color: hsl(var(--border) / 0.5);
}

.stat-icon {
  color: hsl(var(--primary));
  flex-shrink: 0;
}

.stat-icon-email {
  color: hsl(215, 16%, 47%);
  flex-shrink: 0;
}

.google-logo-stat {
  flex-shrink: 0;
}

.stat-num {
  font-size: 20px;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0 0 2px;
  line-height: 1;
}

.stat-num-google {
  color: #4285F4;
}

.stat-label {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

/* ── Filters ──────────────────────────────────────────────── */
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

.search-icon {
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
  box-sizing: border-box;
}
.filter-input:focus {
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2);
}

.filter-right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
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
}
.filter-select:focus {
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2);
}

/* ── Error banner ─────────────────────────────────────────── */
.error-banner {
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: hsl(var(--destructive) / 0.1);
  color: hsl(var(--destructive));
  border-radius: 10px;
  padding: 12px 16px;
  margin-bottom: 16px;
  font-size: 14px;
}

.retry-btn {
  margin-left: auto;
  display: flex;
  align-items: center;
  gap: 4px;
  background: none;
  border: 1px solid currentColor;
  border-radius: 6px;
  padding: 4px 10px;
  font-size: 13px;
  color: inherit;
  cursor: pointer;
}
.retry-btn:hover {
  background-color: hsl(var(--destructive) / 0.1);
}

/* ── Table wrap ───────────────────────────────────────────── */
.table-wrap {
  background-color: hsl(var(--card));
  border-radius: 12px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
  overflow: hidden;
  position: relative;
  min-height: 200px;
}

.loading-overlay {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 60px 20px;
  font-size: 14px;
  color: hsl(var(--muted-foreground));
}

.spinner {
  width: 36px;
  height: 36px;
  border: 3px solid hsl(var(--border));
  border-top-color: hsl(var(--primary));
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 20px;
  gap: 8px;
}

.empty-icon {
  color: hsl(var(--muted-foreground));
  opacity: 0.4;
}

.empty-title {
  font-size: 16px;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0;
}

.empty-desc {
  font-size: 14px;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

/* ── Table ────────────────────────────────────────────────── */
.table-scroll {
  overflow-x: auto;
}

.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.table-head-row {
  border-bottom: 1px solid hsl(var(--border));
  background-color: hsl(var(--muted) / 0.4);
}

.th {
  font-size: 12px;
  font-weight: 600;
  color: hsl(var(--muted-foreground));
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 12px 20px;
  white-space: nowrap;
}

.th-right {
  text-align: right;
}

.table-row {
  border-bottom: 1px solid hsl(var(--border) / 0.5);
  background-color: hsl(var(--card));
  transition: background-color 0.1s ease;
}

.table-row:last-child {
  border-bottom: none;
}

.table-row:hover {
  background-color: #f8fafc;
}

.td {
  padding: 14px 20px;
  vertical-align: middle;
}

/* ── Avatar ───────────────────────────────────────────────── */
.user-info {
  display: flex;
  align-items: center;
  gap: 12px;
}

.avatar-wrapper {
  position: relative;
  flex-shrink: 0;
}

.user-avatar {
  width: 38px;
  height: 38px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 15px;
  font-weight: 600;
}

.user-avatar-img {
  object-fit: cover;
  border: 2px solid hsl(var(--border));
}

.google-dot {
  position: absolute;
  bottom: -2px;
  right: -2px;
  width: 16px;
  height: 16px;
  background: white;
  border-radius: 50%;
  border: 1.5px solid hsl(var(--border));
  display: flex;
  align-items: center;
  justify-content: center;
  box-shadow: 0 1px 3px rgba(0,0,0,0.15);
}

.user-name {
  font-size: 14px;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0;
}

.user-join {
  font-size: 11px;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

/* ── Contact ──────────────────────────────────────────────── */
.contact {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.contact-line {
  font-size: 13px;
  color: hsl(var(--foreground));
  margin: 0;
  display: flex;
  align-items: center;
  gap: 6px;
}

/* ── Auth badge ───────────────────────────────────────────── */
.auth-badge {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
}

.auth-google {
  background: linear-gradient(135deg, rgba(66,133,244,0.1), rgba(52,168,83,0.08));
  color: #4285F4;
  border: 1px solid rgba(66,133,244,0.25);
}

.auth-email {
  background-color: hsl(var(--muted));
  color: hsl(var(--muted-foreground));
  border: 1px solid hsl(var(--border) / 0.5);
}

/* ── Role badge ───────────────────────────────────────────── */
.role-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 8px;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
}

.role-agent {
  background-color: hsl(var(--primary) / 0.1);
  color: hsl(var(--primary));
}

.role-user {
  background-color: hsl(var(--muted));
  color: hsl(var(--muted-foreground));
}

.user-posts {
  font-weight: 600;
  color: hsl(var(--foreground));
}

/* ── Actions ──────────────────────────────────────────────── */
.th-actions {
  width: 120px;
  text-align: center;
}

.td-actions {
  width: 120px;
  text-align: center;
  vertical-align: middle;
}

.act-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 6px 12px;
  border-radius: 8px;
  border: 1.5px solid transparent;
  cursor: pointer;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
  transition: all 0.15s;
  line-height: 1;
}

.act-btn-label {
  font-size: 13px;
  font-weight: 600;
}

.btn-lock {
  background-color: hsl(var(--destructive) / 0.08);
  border-color: hsl(var(--destructive) / 0.3);
  color: hsl(var(--destructive));
}
.btn-lock:hover {
  background-color: hsl(var(--destructive) / 0.18);
  border-color: hsl(var(--destructive) / 0.6);
}

.btn-unlock {
  background-color: hsl(var(--success) / 0.08);
  border-color: hsl(var(--success) / 0.3);
  color: hsl(var(--success));
}
.btn-unlock:hover {
  background-color: hsl(var(--success) / 0.18);
  border-color: hsl(var(--success) / 0.6);
}

.cursor-pointer { cursor: pointer; }

/* ── Pagination ───────────────────────────────────────────── */
.pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 12px;
  padding: 14px 20px;
  border-top: 1px solid hsl(var(--border));
}

.pagination-info {
  font-size: 13px;
  color: hsl(var(--muted-foreground));
}

.pagination-controls {
  display: flex;
  align-items: center;
  gap: 4px;
}

.page-btn {
  min-width: 32px;
  height: 32px;
  padding: 0 8px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  font-size: 13px;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}
.page-btn:hover:not(:disabled) {
  background-color: hsl(var(--muted));
}
.page-btn.active {
  background-color: hsl(var(--primary));
  color: white;
  border-color: hsl(var(--primary));
}
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

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
</style>
