<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { userService } from '@/services/userService'
import { DataTable, Pagination, Modal, ErrorState } from '@/components/crud'
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

function goToUserDetail(user) {
  if (!user?.id) return
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
    <ErrorState
      v-if="error"
      :message="error"
      :retryable="true"
      @retry="fetchUsers"
    />

    <!-- Table -->
    <div class="bg-card border border-border/50 rounded-xl shadow-card overflow-hidden">
      <DataTable
        :columns="tableColumns"
        :rows="normalizedUsers"
        :loading="loading"
        empty-text="Không tìm thấy tài khoản"
        @row-click="goToUserDetail"
      >
        <template #cell(userInfo)="{ row }">
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
        </template>
        <template #cell(contact)="{ row }">
          <div class="flex flex-col gap-1">
            <p class="text-xs flex items-center gap-1 m-0"><Mail :size="12" class="text-muted-foreground shrink-0" /> {{ row.email }}</p>
            <p class="text-xs flex items-center gap-1 m-0"><Phone :size="12" class="text-muted-foreground shrink-0" /> {{ row.phone || '—' }}</p>
          </div>
        </template>
        <template #cell(authType)="{ row }">
          <span v-if="row.isGoogleAccount" class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200">
            <svg viewBox="0 0 24 24" width="12" height="12"><path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/><path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/><path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/><path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/></svg>
            Google
          </span>
          <span v-else class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
            <Mail :size="12" /> Email
          </span>
        </template>
        <template #cell(role)="{ row }">
          <span class="inline-flex px-2 py-0.5 rounded text-xs font-semibold"
            :class="row.role === 'agent' ? 'bg-primary/10 text-primary' : 'bg-muted text-muted-foreground'">
            {{ row.roleLabel }}
          </span>
        </template>
        <template #cell(status)="{ row }">
          <StatusBadge :status="row.status === 'locked' ? 'locked' : 'approved'" :label="row.statusLabel" />
        </template>
        <template #actions="{ row }">
          <button
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold border transition-all cursor-pointer"
            :class="row.status === 'locked'
              ? 'bg-success/10 border-success/30 text-success hover:bg-success/20'
              : 'bg-destructive/10 border-destructive/30 text-destructive hover:bg-destructive/20'"
            @click.stop="openToggleModal(row._raw)"
          >
            <component :is="row.status === 'locked' ? Unlock : Lock" :size="14" />
            {{ row.status === 'locked' ? 'Mở khóa' : 'Khóa' }}
          </button>
        </template>
      </DataTable>

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
.stats-bar { display: flex; align-items: center; gap: 16px; margin-bottom: 20px; flex-wrap: wrap; }
.stat-card { display: flex; align-items: center; gap: 12px; background-color: hsl(var(--card)); border: 1px solid hsl(var(--border) / 0.5); border-radius: 12px; padding: 12px 18px; box-shadow: var(--shadow-card); min-width: 160px; }
.filter-bar { background-color: hsl(var(--card)); border-radius: 12px; padding: 16px; box-shadow: var(--shadow-card); border: 1px solid hsl(var(--border) / 0.5); margin-bottom: 24px; display: flex; flex-wrap: wrap; align-items: center; gap: 16px; }
.filter-search { flex: 1; min-width: 240px; position: relative; }
.search-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: hsl(var(--muted-foreground)); }
.filter-input { width: 100%; padding: 8px 16px 8px 40px; background-color: hsl(var(--muted)); border: none; border-radius: 8px; font-size: 14px; color: hsl(var(--foreground)); outline: none; box-sizing: border-box; }
.filter-right { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.filter-select { background-color: hsl(var(--muted)); font-size: 14px; border-radius: 8px; padding: 8px 12px; border: none; color: hsl(var(--foreground)); outline: none; cursor: pointer; }
</style>
