<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import PostsFilter from './PostsFilter.vue'
import PostsTable from './PostsTable.vue'
import { Pagination } from '@/components/crud'
import { listingService } from '@/services/listingService'
import { usePackageApi } from '@/composables/usePackageApi'

const PER_PAGE = 8
const router = useRouter()
const { fetchPackages } = usePackageApi()

const searchQuery = ref('')
const searchField = ref('title')
const filterStatus = ref('all')
const filterType = ref('all')
const filterMinPrice = ref(null)
const filterMaxPrice = ref(null)
const filterPackageId = ref('all')
const packages = ref([])
const listings = ref([])
const statusCounts = ref({
  all: 0,
  pending: 0,
  approved: 0,
  rejected: 0,
  locked: 0,
})
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

const statusParamMap = {
  pending: 'PENDING',
  approved: 'ACTIVE',
  rejected: 'REJECTED',
  locked: 'LOCKED',
}

function buildParams() {
  const params = {
    per_page: PER_PAGE,
    page: pagination.current_page,
  }

  const keyword = searchQuery.value.trim()
  if (keyword) params.keyword = keyword
  if (searchField.value) params.search_field = searchField.value
  if (filterStatus.value !== 'all')
    params.status = statusParamMap[filterStatus.value] ?? filterStatus.value
  if (filterType.value !== 'all') params.demand_type = typeParamMap[filterType.value]
  if (filterMinPrice.value !== null && filterMinPrice.value !== '') params.min_price = filterMinPrice.value
  if (filterMaxPrice.value !== null && filterMaxPrice.value !== '') params.max_price = filterMaxPrice.value
  if (filterPackageId.value !== 'all') params.package_id = filterPackageId.value

  return params
}

async function fetchPostingOptions() {
  try {
    const [postingOptionsResponse, packagesResponse] = await Promise.all([
      listingService.getPostingOptions(),
      fetchPackages({ include_inactive: 1 }),
    ])
    const options = postingOptionsResponse.data?.data ?? {}
    listingStatusOptions.value = options.listing_statuses ?? []
    adminListingStatusOptions.value = options.admin_listing_statuses ?? []
    packages.value = packagesResponse?.data ?? []
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
    statusCounts.value = {
      all: meta.status_counts?.all ?? meta.total ?? 0,
      pending: meta.status_counts?.pending ?? 0,
      approved: meta.status_counts?.approved ?? 0,
      rejected: meta.status_counts?.rejected ?? 0,
      locked: meta.status_counts?.locked ?? 0,
    }
  } catch (err) {
    console.error('Failed to fetch admin listings:', err)
    error.value = err.response?.data?.message || 'Không thể tải danh sách tin đăng.'
    listings.value = []
    statusCounts.value = { all: 0, pending: 0, approved: 0, rejected: 0, locked: 0 }
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

watch([filterStatus, filterType, filterMinPrice, filterMaxPrice, filterPackageId, searchField], () => {
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
      v-model:search-field="searchField"
      v-model:status="filterStatus"
      v-model:type="filterType"
      v-model:min-price="filterMinPrice"
      v-model:max-price="filterMaxPrice"
      v-model:package-id="filterPackageId"
      :packages="packages"
      :status-counts="statusCounts"
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

    <Pagination
      v-if="pagination.total > 0"
      :current-page="pagination.current_page"
      :last-page="pagination.last_page"
      :total="pagination.total"
      :loading="loading"
      @page-change="goToPage"
    />
  </div>
</template>

