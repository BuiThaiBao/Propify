<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import UsersFilter from './UsersFilter.vue'
import UsersTable from './UsersTable.vue'
import { userService } from '@/services/userService'

const PER_PAGE = 8

const searchQuery = ref('')
const filterRole = ref('all')
const users = ref([])
const loading = ref(false)
const error = ref('')
const actionError = ref('')
const updatingUserId = ref(null)

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: PER_PAGE,
  total: 0,
})

let searchTimer = null

function buildParams() {
  const params = {
    per_page: PER_PAGE,
    page: pagination.current_page,
  }

  const search = searchQuery.value.trim()
  if (search) params.search = search
  if (filterRole.value !== 'all') params.role = filterRole.value

  return params
}

async function fetchUsers() {
  loading.value = true
  error.value = ''
  actionError.value = ''

  try {
    const response = await userService.getUsers(buildParams())
    users.value = response.data?.data ?? []

    const meta = response.data?.meta ?? {}
    pagination.current_page = meta.current_page ?? pagination.current_page
    pagination.last_page = meta.last_page ?? 1
    pagination.per_page = meta.per_page ?? PER_PAGE
    pagination.total = meta.total ?? users.value.length
  } catch (err) {
    console.error('Failed to fetch admin users:', err)
    error.value = err.response?.data?.message || 'Không thể tải danh sách tài khoản.'
    users.value = []
  } finally {
    loading.value = false
  }
}

async function handleStatusChange({ id, status }) {
  updatingUserId.value = id
  actionError.value = ''

  try {
    const response = await userService.changeStatus(id, { status })
    const updatedUser = response.data?.data
    if (updatedUser) {
      const idx = users.value.findIndex((u) => u.id === id)
      if (idx !== -1) {
        users.value[idx] = updatedUser
      }
    }
  } catch (err) {
    console.error('Failed to change user status:', err)
    actionError.value = err.response?.data?.message || 'Không thể cập nhật trạng thái tài khoản.'
  } finally {
    updatingUserId.value = null
  }
}

function goToPage(page) {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  pagination.current_page = page
  fetchUsers()
}

watch(filterRole, () => {
  pagination.current_page = 1
  fetchUsers()
})

watch(searchQuery, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    pagination.current_page = 1
    fetchUsers()
  }, 400)
})

onMounted(async () => {
  await fetchUsers()
})
</script>

<template>
  <div>
    <PageHeader title="Quản lý tài khoản" subtitle="Quản lý người dùng và môi giới trên hệ thống" />

    <UsersFilter v-model:search="searchQuery" v-model:role="filterRole" />

    <UsersTable
      :users="users"
      :loading="loading"
      :error="error"
      :action-error="actionError"
      :updating-user-id="updatingUserId"
      @change-status="handleStatusChange"
    />

    <!-- Pagination -->
    <div v-if="pagination.total > 0" class="users-pagination">
      <div class="pagination-actions">
        <button
          class="page-btn"
          :disabled="pagination.current_page === 1 || loading"
          @click="goToPage(pagination.current_page - 1)"
          aria-label="Trang trước"
        >
          <ChevronLeft :size="18" />
        </button>
        <span class="page-summary">
          Trang {{ pagination.current_page }} / {{ pagination.last_page }}
        </span>
        <button
          class="page-btn"
          :disabled="pagination.current_page === pagination.last_page || loading"
          @click="goToPage(pagination.current_page + 1)"
          aria-label="Trang sau"
        >
          <ChevronRight :size="18" />
        </button>
      </div>
      <p class="total-summary">
        Hiển thị tối đa {{ pagination.per_page }} / {{ pagination.total }} tài khoản
      </p>
    </div>
  </div>
</template>

<style scoped>
.users-pagination {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-top: 16px;
}

.pagination-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.page-btn {
  width: 34px;
  height: 34px;
  border: 1px solid #dbe3ef;
  border-radius: 7px;
  background: #ffffff;
  color: #1e3a5f;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.page-btn:hover:not(:disabled) {
  background: #f8fafc;
}

.page-btn:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.page-summary,
.total-summary {
  color: #64748b;
  font-size: 13px;
  margin: 0;
}

@media (max-width: 720px) {
  .users-pagination {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
