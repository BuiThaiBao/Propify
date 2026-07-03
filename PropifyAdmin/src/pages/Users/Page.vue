<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { userService } from '@/services/userService'
import { Pagination, Modal, ErrorState } from '@/components/crud'
import {
  Search,
  Filter,
  Lock,
  Unlock,
  Mail,
  Phone,
  ChevronLeft,
  ChevronRight,
  ChevronDown,
  Users,
  X,
  MoreHorizontal,
} from 'lucide-vue-next'

const router = useRouter()

const actionLoading = ref(false)

// ─── Action Menu State ────────────────────────────────────────────────────────
const activeMenuId = ref(null)
const menuPosition = ref({ top: 0, left: 0 })

function toggleMenu(user, event) {
  if (activeMenuId.value === user.id) {
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
  activeMenuId.value = user.id
}

function closeMenu() {
  activeMenuId.value = null
}

function handleDocumentClick(event) {
  if (!event.target.closest('.action-menu') && !event.target.closest('.more-btn')) {
    closeMenu()
  }
}

// ─── API & Data ───────────────────────────────────────────────────────────────
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

// Search debounce
let searchTimer = null

// ─── Computed ─────────────────────────────────────────────────────────────────
const tableColumns = [
  { key: 'userInfo', label: 'Người dùng', width: '25%', nowrap: false },
  { key: 'contact', label: 'Liên hệ', width: '25%', nowrap: false },
  { key: 'authType', label: 'Đăng nhập', width: '15%' },
  { key: 'role', label: 'Vai trò', width: '12%' },
  { key: 'posts', label: 'Tin đăng', width: '8%' },
  { key: 'status', label: 'Trạng thái', width: '15%' },
]

const normalizedUsers = computed(() =>
  users.value.map((u) => ({
    ...u,
    _raw: u,
    posts: u.posts ?? 0,
  }))
)

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

// ─── Lock / Unlock Modal Actions ────────────────────────────────────────────────
function openToggleModal(user) {
  closeMenu()
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

onMounted(() => {
  fetchUsers()
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
  <div class="users-list-container">
    <PageHeader
      title="Quản lý tài khoản"
      description="Quản lý người dùng và môi giới trên hệ thống"
    />

    <div class="filter-main">
      <div class="search-control">
        <div class="search-input-wrap">
          <input
            v-model="search"
            @input="onSearchInput"
            type="text"
            placeholder="Tìm kiếm theo tên, email, số điện thoại..."
            class="search-input"
            id="users-search"
          />
          <Search :size="22" class="search-icon" />
        </div>
      </div>
      <div class="filter-selects">
        <div class="custom-select">
          <select v-model="roleFilter" @change="onFilterChange" class="filter-trigger" id="users-role-filter">
            <option value="all">Tất cả vai trò</option>
            <option value="user">Người dùng</option>
            <option value="agent">Môi giới</option>
          </select>
          <ChevronDown :size="17" class="select-icon" />
        </div>
      </div>
    </div>

    <div class="status-tabs">
      <button
        type="button"
        class="status-tab"
        :class="{ active: authTypeFilter === 'all' }"
        @click="authTypeFilter = 'all'; currentPage = 1; fetchUsers()"
      >
        Tổng tài khoản <span class="status-count">{{ total.toLocaleString() }}</span>
      </button>
      <button
        type="button"
        class="status-tab"
        :class="{ active: authTypeFilter === 'google' }"
        @click="authTypeFilter = 'google'; currentPage = 1; fetchUsers()"
      >
        <span class="status-dot" style="background: #4285F4"></span>
        Tài khoản Google <span class="status-count">{{ googleCount }}</span>
      </button>
      <button
        type="button"
        class="status-tab"
        :class="{ active: authTypeFilter === 'email' }"
        @click="authTypeFilter = 'email'; currentPage = 1; fetchUsers()"
      >
        <span class="status-dot" style="background: #10b981"></span>
        Tài khoản Email <span class="status-count">{{ emailCount }}</span>
      </button>
    </div>

    <!-- Error -->
    <ErrorState
      v-if="error"
      :message="error"
      :retryable="true"
      @retry="fetchUsers"
    />

    <!-- Table -->
    <div class="users-table-wrap">
      <div class="users-table-scroll">
        <table class="users-table">
          <thead>
            <tr>
              <th class="col-user">Người dùng</th>
              <th class="col-contact">Liên hệ</th>
              <th class="col-auth">Đăng nhập</th>
              <th class="col-role">Vai trò</th>
              <th class="col-posts">Tin đăng</th>
              <th class="sticky-right sticky-status col-status">Trạng thái</th>
              <th class="sticky-right sticky-action action-cell"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="7" class="state-cell">Đang tải dữ liệu...</td>
            </tr>
            <tr v-else-if="error">
              <td colspan="7" class="state-cell error-cell">{{ error }}</td>
            </tr>
            <tr v-else-if="normalizedUsers.length === 0">
              <td colspan="7" class="state-cell">Không tìm thấy tài khoản</td>
            </tr>
            <tr
              v-else
              v-for="row in normalizedUsers"
              :key="row.id"
            >
              <td>
                <div class="flex items-center gap-3">
                  <div class="relative shrink-0">
                    <img v-if="row.avatarUrl" :src="row.avatarUrl" :alt="row.name" class="w-9 h-9 rounded-full object-cover border border-border" />
                    <div v-else class="w-9 h-9 rounded-full flex items-center justify-center text-sm font-bold" :style="{ backgroundColor: row.avatarBg, color: row.avatarColor }">{{ row.initial }}</div>
                    <span v-if="row.isGoogleAccount" class="absolute -bottom-0.5 -right-0.5 w-4 h-4 bg-white rounded-full border border-border flex items-center justify-center shadow-sm">
                      <svg viewBox="0 0 24 24" width="10" height="10"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                    </span>
                  </div>
                  <div>
                    <p class="text-sm font-semibold text-foreground m-0">{{ row.name }}</p>
                    <p class="text-xs text-muted-foreground m-0">{{ row.joinDate }}</p>
                  </div>
                </div>
              </td>
              <td>
                <div class="flex flex-col gap-1">
                  <p class="text-xs flex items-center gap-1 m-0"><Mail :size="12" class="text-muted-foreground shrink-0" /> {{ row.email }}</p>
                  <p class="text-xs flex items-center gap-1 m-0"><Phone :size="12" class="text-muted-foreground shrink-0" /> {{ row.phone || '—' }}</p>
                </div>
              </td>
              <td>
                <span v-if="row.isGoogleAccount" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
                  <svg viewBox="0 0 24 24" width="12" height="12"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
                  Google
                </span>
                <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                  <Mail :size="12" /> Email
                </span>
              </td>
              <td>
                <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold"
                  :class="row.role === 'agent' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'">
                  {{ row.roleLabel }}
                </span>
              </td>
              <td>
                {{ row.posts }}
              </td>
              <td class="sticky-right sticky-status col-status">
                <StatusBadge :status="row.status === 'locked' ? 'locked' : 'approved'" :label="row.statusLabel" />
              </td>
              <td class="sticky-right sticky-action action-cell">
                <button
                  class="more-btn"
                  :disabled="actionLoading"
                  :aria-label="`Mở thao tác tài khoản ${row.id}`"
                  @click.stop="toggleMenu(row, $event)"
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
        <template v-for="row in normalizedUsers" :key="row.id">
          <button
            v-show="activeMenuId === row.id"
            class="action-menu-item"
            :class="row.status === 'locked' ? 'approve-action' : 'lock-action'"
            :disabled="actionLoading"
            @click="openToggleModal(row._raw)"
          >
            <component :is="row.status === 'locked' ? Unlock : Lock" :size="15" />
            <span>{{ row.status === 'locked' ? 'Mở khóa' : 'Khóa' }}</span>
          </button>
        </template>
      </div>

      <div class="px-5 py-4 border-t border-[#e7edf5] bg-white">
        <Pagination
          v-if="total > 0"
          :current-page="currentPage"
          :last-page="lastPage"
          :total="total"
          :per-page="perPage"
          :loading="loading"
          @page-change="goToPage"
        />
      </div>
    </div>

    <!-- LOCK MODAL -->
    <Modal :open="lockModalOpen" title="Khóa tài khoản" max-width="lg" @close="closeLockModal">
      <p class="text-sm text-muted-foreground mb-4">Tài khoản này sẽ bị tạm khóa và không thể đăng nhập.</p>

      <p class="text-sm font-bold text-foreground mb-3">Lý do khóa tài khoản <span class="text-destructive">*</span></p>
      <div class="flex flex-col gap-2 mb-3">
        <label
          v-for="r in LOCK_REASONS"
          :key="r.code"
          class="flex items-center gap-3 p-3 border border-border rounded-lg cursor-pointer transition-all"
          :class="{ '!border-destructive bg-destructive/5': Number(lockSelectedReason) === r.code }"
        >
          <input type="radio" :value="r.code" v-model="lockSelectedReason" name="lock_reason_page" class="accent-destructive w-4 h-4" />
          <span class="text-sm font-semibold text-foreground">{{ r.label }}</span>
        </label>
      </div>
      <p v-if="lockReasonError" class="text-xs text-destructive font-semibold mb-2">{{ lockReasonError }}</p>

      <div v-if="Number(lockSelectedReason) === 6" class="pt-3 border-t border-border">
        <label class="text-sm font-bold text-foreground block mb-2">Mô tả lý do <span class="text-destructive">*</span></label>
        <textarea
          v-model="lockOtherText"
          class="w-full p-3 text-sm border border-border rounded-lg outline-none resize-vertical focus:border-primary box-border"
          :class="{ '!border-destructive': lockOtherTextError }"
          placeholder="Nhập mô tả lý do khóa tài khoản (tối đa 500 ký tự)..."
          rows="4"
          maxlength="500"
        ></textarea>
        <div class="flex justify-between mt-1">
          <p v-if="lockOtherTextError" class="text-xs text-destructive font-semibold m-0">{{ lockOtherTextError }}</p>
          <span class="text-xs text-muted-foreground ml-auto" :class="{ 'text-destructive font-bold': otherTextRemaining < 50 }">{{ otherTextRemaining }} ký tự còn lại</span>
        </div>
      </div>

      <template #footer>
        <button class="px-5 py-2 text-sm font-bold rounded-lg border border-border bg-card text-foreground cursor-pointer hover:bg-muted transition disabled:opacity-50" :disabled="actionLoading" @click="closeLockModal">Hủy bỏ</button>
        <button class="px-5 py-2 text-sm font-bold rounded-lg border-none bg-destructive text-white cursor-pointer hover:opacity-90 transition disabled:opacity-50 inline-flex items-center gap-2" :disabled="actionLoading || !isLockModalValid" @click="handleLockConfirm">
          <span v-if="actionLoading" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
          {{ actionLoading ? 'Đang xử lý...' : 'Xác nhận khóa' }}
        </button>
      </template>
    </Modal>

    <!-- UNLOCK MODAL -->
    <Modal :open="unlockModalOpen" title="Mở khóa tài khoản" max-width="sm" @close="unlockModalOpen = false">
      <p class="text-sm text-muted-foreground">Tài khoản này sẽ được kích hoạt và có thể đăng nhập trở lại.</p>
      <template #footer>
        <button class="px-5 py-2 text-sm font-bold rounded-lg border border-border bg-card text-foreground cursor-pointer hover:bg-muted transition disabled:opacity-50" :disabled="actionLoading" @click="unlockModalOpen = false">Hủy bỏ</button>
        <button class="px-5 py-2 text-sm font-bold rounded-lg border-none bg-success text-white cursor-pointer hover:opacity-90 transition disabled:opacity-50 inline-flex items-center gap-2" :disabled="actionLoading" @click="handleUnlockConfirm">
          <span v-if="actionLoading" class="w-4 h-4 border-2 border-white/40 border-t-white rounded-full animate-spin" />
          {{ actionLoading ? 'Đang xử lý...' : 'Xác nhận mở khóa' }}
        </button>
      </template>
    </Modal>
  </div>
</template>

<style scoped>
.users-list-container { width: 100%; }

.filter-main {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid #e8edf5;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
  margin-bottom: 16px;
}

.search-control {
  min-width: 380px;
  display: flex;
  align-items: center;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  background: #fff;
  overflow: hidden;
  flex: 1;
}

.search-input-wrap {
  min-width: 0;
  flex: 1;
  position: relative;
}

.search-input {
  width: 100%;
  height: 38px;
  border: 0;
  padding: 0 42px 0 12px;
  color: #0f172a;
  font-size: 13px;
  outline: none;
}

.search-input::placeholder {
  color: #94a3b8;
}

.search-icon {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  color: #172554;
}

.filter-selects {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: auto;
}

.custom-select {
  position: relative;
  min-width: 158px;
}

.filter-trigger {
  width: 100%;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 0 11px 0 13px;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  background: #fff;
  color: #1e3a5f;
  font-size: 13px;
  line-height: 1.35;
  cursor: pointer;
  transition: border-color 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
  appearance: none;
}

.filter-trigger:hover {
  border-color: #93c5fd;
  background: #f8fbff;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.select-icon {
  position: absolute;
  right: 11px;
  top: 50%;
  transform: translateY(-50%);
  pointer-events: none;
  color: #172554;
}

.status-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
  margin-bottom: 24px;
}

.status-tab {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 0 14px;
  border: 1px solid #e7edf5;
  border-radius: 999px;
  background: #fff;
  color: #64748b;
  font-size: 13px;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
}

.status-tab.active {
  border-color: #0ea5e9;
  color: #0284c7;
  background: #f0f9ff;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 999px;
}

.status-count {
  color: inherit;
  font-weight: 700;
}

@media (max-width: 1100px) {
  .filter-main {
    align-items: stretch;
    flex-direction: column;
  }

  .search-control,
  .custom-select,
  .filter-selects {
    width: 100%;
    min-width: 0;
    flex-wrap: wrap;
    margin-left: 0;
  }
}

.users-table-wrap {
  background: #ffffff;
  border: 1px solid #e7edf5;
  border-radius: 8px;
  overflow: hidden;
}

.users-table-scroll {
  max-width: 100%;
  overflow-x: auto;
  overflow-y: auto;
}

.users-table {
  width: 100%;
  min-width: 1000px;
  border-collapse: separate;
  border-spacing: 0;
  color: #1b365d;
  font-size: 14px;
}

.users-table th,
.users-table td {
  min-height: 64px;
  padding: 14px 12px;
  border-bottom: 1px solid #f0f3f7;
  background: #ffffff;
  text-align: left;
  vertical-align: middle;
}

.users-table th {
  height: 44px;
  min-height: 44px;
  position: sticky;
  top: 0;
  z-index: 5;
  background: #f1f3f6;
  color: #40536f;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}

.users-table tbody tr:nth-child(even) td {
  background: #f7f8fa;
}

.users-table tbody tr:hover td {
  background: #f1f6ff;
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

thead .sticky-right {
  z-index: 8;
}

.col-user { width: 25%; }
.col-contact { width: 25%; }
.col-auth { width: 15%; }
.col-role { width: 12%; }
.col-posts { width: 8%; }
.col-status {
  width: 130px;
  min-width: 130px;
}

.action-cell {
  width: 56px;
  min-width: 56px;
  max-width: 56px;
  text-align: center !important;
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

.state-cell {
  text-align: center !important;
  color: #64748b;
  padding: 40px !important;
}

.error-cell {
  color: #ef4444;
}
</style>
