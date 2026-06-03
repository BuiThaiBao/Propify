<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import PostsFilter from './PostsFilter.vue'
import PostsTable from './PostsTable.vue'
import { listingService } from '@/services/listingService'

const PER_PAGE = 8
const router = useRouter()

const searchQuery = ref('')
const filterStatus = ref('all')
const filterType = ref('all')
const listings = ref([])
const loading = ref(false)
const error = ref('')
const actionError = ref('')
const updatingPostId = ref(null)
const listingStatusOptions = ref([])
const adminListingStatusOptions = ref([])
const pagination = reactive({
  current_page: 1,
  last_page: 1,
  per_page: PER_PAGE,
  total: 0,
})

let searchTimer = null

const typeParamMap = {
  sale: 'SALE',
  rent: 'RENT',
}

function buildParams() {
  const params = {
    per_page: PER_PAGE,
    page: pagination.current_page,
  }

  const keyword = searchQuery.value.trim()
  if (keyword) params.keyword = keyword
  if (filterStatus.value !== 'all') params.status = filterStatus.value
  if (filterType.value !== 'all') params.demand_type = typeParamMap[filterType.value]

  return params
}

async function fetchPostingOptions() {
  try {
    const response = await listingService.getPostingOptions()
    const options = response.data?.data ?? {}
    listingStatusOptions.value = options.listing_statuses ?? []
    adminListingStatusOptions.value = options.admin_listing_statuses ?? []
  } catch (err) {
    console.error('Failed to fetch posting options:', err)
  }
}

async function fetchListings() {
  loading.value = true
  error.value = ''
  actionError.value = ''

  try {
    const response = await listingService.getAllListings(buildParams())
    listings.value = response.data?.data ?? []

    const meta = response.data?.meta ?? {}
    pagination.current_page = meta.current_page ?? pagination.current_page
    pagination.last_page = meta.last_page ?? 1
    pagination.per_page = meta.per_page ?? PER_PAGE
    pagination.total = meta.total ?? listings.value.length
  } catch (err) {
    console.error('Failed to fetch admin listings:', err)
    error.value = err.response?.data?.message || 'Không thể tải danh sách tin đăng.'
    listings.value = []
  } finally {
    loading.value = false
  }
}

async function updateListingStatus({ id, status, rejectionReason = null }) {
  updatingPostId.value = id
  actionError.value = ''

  try {
    await listingService.changeStatusForAdmin(id, {
      status,
      rejection_reason: rejectionReason,
      reason: rejectionReason,
    })
    await fetchListings()
  } catch (err) {
    console.error('Failed to update listing status:', err)
    actionError.value = err.response?.data?.message || 'Không thể cập nhật trạng thái tin đăng.'
  } finally {
    updatingPostId.value = null
  }
}

function openListingDetail(post) {
  if (!['SALE', 'RENT'].includes(post.demand_type)) return
  router.push({ name: 'PostDetail', params: { id: post.id } })
}

function goToPage(page) {
  if (page < 1 || page > pagination.last_page || page === pagination.current_page) return
  pagination.current_page = page
  fetchListings()
}

watch([filterStatus, filterType], () => {
  pagination.current_page = 1
  fetchListings()
})

watch(searchQuery, () => {
  if (searchTimer) clearTimeout(searchTimer)
  searchTimer = setTimeout(() => {
    pagination.current_page = 1
    fetchListings()
  }, 400)
})

onMounted(async () => {
  await fetchPostingOptions()
  await fetchListings()
})
</script>

<template>
  <div>
    <PageHeader
      title="Quản lý tin đăng"
      description="Duyệt, quản lý và kiểm soát tin đăng bất động sản"
    />

    <PostsFilter
      v-model:search="searchQuery"
      v-model:status="filterStatus"
      v-model:type="filterType"
      :status-options="listingStatusOptions"
    />

    <PostsTable
      :posts="listings"
      :loading="loading"
      :error="error"
      :action-error="actionError"
      :updating-post-id="updatingPostId"
      :status-options="listingStatusOptions"
      :admin-status-options="adminListingStatusOptions"
      @change-status="updateListingStatus"
      @open-detail="openListingDetail"
    />

    <div v-if="pagination.total > 0" class="posts-pagination">
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
        Hiển thị tối đa {{ pagination.per_page }} / {{ pagination.total }} tin đăng
      </p>
    </div>
  </div>
</template>

<style scoped>
.posts-pagination {
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
  .posts-pagination {
    align-items: flex-start;
    flex-direction: column;
  }
}
</style>
