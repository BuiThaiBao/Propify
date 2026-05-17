<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import {
  Check,
  ChevronLeft,
  ChevronRight,
  Edit3,
  Eye,
  MoreHorizontal,
  PackageCheck,
  Plus,
  Search,
  Settings,
  X,
} from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import { amenityService } from '@/services/amenityService'

const amenities = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const keyword = ref('')
const activeFilter = ref('all')
const page = ref(1)
const perPage = ref(25)

const drawer = reactive({
  open: false,
  mode: 'create',
  amenity: null,
})

const form = reactive({
  name: '',
  icon: '',
  order_index: 0,
})

const filters = computed(() => [
  { value: 'all', label: 'Tất cả', count: amenities.value.length },
  { value: 'with_icon', label: 'Có icon', count: amenities.value.filter((item) => item.icon).length },
  { value: 'without_icon', label: 'Chưa có icon', count: amenities.value.filter((item) => !item.icon).length },
])

const filteredAmenities = computed(() => {
  const normalizedKeyword = keyword.value.trim().toLowerCase()

  return amenities.value
    .filter((amenity) => {
      if (activeFilter.value === 'with_icon') return Boolean(amenity.icon)
      if (activeFilter.value === 'without_icon') return !amenity.icon
      return true
    })
    .filter((amenity) => {
      if (!normalizedKeyword) return true

      return [amenity.id, amenity.name, amenity.icon]
        .filter((value) => value !== null && value !== undefined)
        .some((value) => String(value).toLowerCase().includes(normalizedKeyword))
    })
    .sort((a, b) => Number(a.order_index || 0) - Number(b.order_index || 0) || String(a.name).localeCompare(String(b.name)))
})

const pagedAmenities = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredAmenities.value.slice(start, start + perPage.value)
})

const totalPages = computed(() => Math.max(1, Math.ceil(filteredAmenities.value.length / perPage.value)))
const visibleFrom = computed(() => (filteredAmenities.value.length === 0 ? 0 : (page.value - 1) * perPage.value + 1))
const visibleTo = computed(() => Math.min(page.value * perPage.value, filteredAmenities.value.length))

onMounted(loadAmenities)

async function loadAmenities() {
  loading.value = true
  error.value = ''

  try {
    const response = await amenityService.getAmenities()
    amenities.value = response.data?.data || []
    page.value = 1
  } catch (err) {
    error.value = err.response?.data?.message || 'Không thể tải danh sách tiện ích.'
    amenities.value = []
  } finally {
    loading.value = false
  }
}

function openCreateDrawer() {
  drawer.mode = 'create'
  drawer.amenity = null
  form.name = ''
  form.icon = ''
  form.order_index = nextOrderIndex()
  drawer.open = true
}

function openEditDrawer(amenity) {
  drawer.mode = 'edit'
  drawer.amenity = amenity
  form.name = amenity.name || ''
  form.icon = amenity.icon || ''
  form.order_index = Number(amenity.order_index || 0)
  drawer.open = true
}

function closeDrawer() {
  if (saving.value) return
  drawer.open = false
}

async function submitForm() {
  if (!form.name.trim()) {
    error.value = 'Tên tiện ích là bắt buộc.'
    return
  }

  saving.value = true
  error.value = ''

  const payload = {
    name: form.name.trim(),
    icon: form.icon.trim() || null,
    order_index: Number(form.order_index || 0),
  }

  try {
    if (drawer.mode === 'edit' && drawer.amenity) {
      await amenityService.updateAmenity(drawer.amenity.id, payload)
    } else {
      await amenityService.createAmenity(payload)
    }

    drawer.open = false
    await loadAmenities()
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    error.value = validationErrors ? Object.values(validationErrors).flat().join(' ') : err.response?.data?.message || 'Không thể lưu tiện ích.'
  } finally {
    saving.value = false
  }
}

function setFilter(value) {
  activeFilter.value = value
  page.value = 1
}

function changePage(nextPage) {
  page.value = Math.min(Math.max(nextPage, 1), totalPages.value)
}

function nextOrderIndex() {
  const maxOrder = amenities.value.reduce((max, amenity) => Math.max(max, Number(amenity.order_index || 0)), 0)
  return maxOrder + 1
}
</script>

<template>
  <div class="amenities-page">
    <PageHeader title="Tiện ích hệ thống" description="Quản lý danh mục tiện ích dùng chung cho bất động sản">
      <template #actions>
        <button class="primary-btn" id="btn-add-amenity" type="button" @click="openCreateDrawer">
          <Plus :size="17" />
          <span>Thêm tiện ích</span>
        </button>
      </template>
    </PageHeader>

    <section class="toolbar">
      <label class="search-box" for="amenity-search">
        <Search :size="18" />
        <input
          id="amenity-search"
          v-model.trim="keyword"
          type="text"
          placeholder="Tìm kiếm..."
          @input="page = 1"
        />
      </label>

      <div class="filter-tabs" aria-label="Lọc tiện ích">
        <button
          v-for="filter in filters"
          :key="filter.value"
          type="button"
          class="filter-tab"
          :class="{ 'filter-tab--active': activeFilter === filter.value }"
          @click="setFilter(filter.value)"
        >
          <span v-if="filter.value === 'with_icon'" class="dot dot--green"></span>
          <span v-if="filter.value === 'without_icon'" class="dot dot--slate"></span>
          {{ filter.label }}
          <strong>{{ filter.count }}</strong>
        </button>
      </div>
    </section>

    <p v-if="error" class="alert">{{ error }}</p>

    <section class="table-shell">
      <table class="amenities-table">
        <thead>
          <tr>
            <th>ID</th>
            <th>Tiện ích</th>
            <th>Icon</th>
            <th>Thứ tự</th>
            <th>Nhóm</th>
            <th class="actions-col"></th>
          </tr>
        </thead>
        <tbody>
          <tr v-if="loading">
            <td colspan="6" class="state-cell">Đang tải dữ liệu...</td>
          </tr>
          <tr v-else-if="pagedAmenities.length === 0">
            <td colspan="6" class="state-cell">Không có tiện ích phù hợp.</td>
          </tr>
          <tr v-for="amenity in pagedAmenities" v-else :key="amenity.id">
            <td class="id-cell">{{ amenity.id }}</td>
            <td>
              <div class="amenity-name">
                <span class="amenity-icon">
                  <Settings v-if="!amenity.icon" :size="17" />
                  <span v-else>{{ amenity.icon }}</span>
                </span>
                <strong>{{ amenity.name }}</strong>
              </div>
            </td>
            <td class="muted-cell">{{ amenity.icon || 'Chưa cấu hình' }}</td>
            <td>{{ amenity.order_index ?? 0 }}</td>
            <td>
              <span class="status-pill">
                <PackageCheck :size="14" />
                {{ amenity.group?.name || 'Tiện ích' }}
              </span>
            </td>
            <td class="actions-col">
              <button class="icon-btn" type="button" :title="`Sửa ${amenity.name}`" @click="openEditDrawer(amenity)">
                <Edit3 :size="16" />
              </button>
              <button class="icon-btn" type="button" aria-hidden="true" tabindex="-1">
                <MoreHorizontal :size="18" />
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <footer class="table-footer">
        <span>Tất cả {{ filteredAmenities.length }} dòng</span>
        <span v-if="filteredAmenities.length" class="range-text">{{ visibleFrom }}-{{ visibleTo }}</span>
        <button class="pager-btn" type="button" :disabled="page <= 1" @click="changePage(page - 1)">
          <ChevronLeft :size="16" />
        </button>
        <span class="page-number">{{ page }}</span>
        <button class="pager-btn" type="button" :disabled="page >= totalPages" @click="changePage(page + 1)">
          <ChevronRight :size="16" />
        </button>
        <select v-model.number="perPage" class="per-page" @change="page = 1">
          <option :value="10">10</option>
          <option :value="25">25</option>
          <option :value="50">50</option>
        </select>
      </footer>
    </section>

    <div v-if="drawer.open" class="drawer-overlay" @click.self="closeDrawer">
      <aside class="drawer" aria-modal="true" role="dialog">
        <header class="drawer-header">
          <h2>{{ drawer.mode === 'edit' ? 'Sửa tiện ích' : 'Thêm mới tiện ích' }}</h2>
          <button class="close-btn" type="button" @click="closeDrawer">
            <X :size="22" />
          </button>
        </header>

        <form class="drawer-form" @submit.prevent="submitForm">
          <label class="field">
            <span>Tên tiện ích <b>*</b></span>
            <input v-model.trim="form.name" type="text" maxlength="255" placeholder="Nhập tên tiện ích" />
          </label>

          <label class="field">
            <span>Icon</span>
            <input v-model.trim="form.icon" type="text" maxlength="255" placeholder="Wifi, Car, Pool..." />
          </label>

          <label class="field">
            <span>Thứ tự hiển thị</span>
            <input v-model.number="form.order_index" type="number" min="0" />
          </label>

          <div class="preview-row">
            <span class="preview-icon">
              <Eye :size="15" />
            </span>
            <span>{{ form.name || 'Tên tiện ích' }}</span>
          </div>

          <footer class="drawer-actions">
            <button class="secondary-btn" type="button" :disabled="saving" @click="closeDrawer">Quay lại</button>
            <button class="primary-btn primary-btn--wide" type="submit" :disabled="saving">
              <Check :size="17" />
              <span>{{ saving ? 'Đang lưu...' : drawer.mode === 'edit' ? 'Lưu thay đổi' : 'Thêm mới' }}</span>
            </button>
          </footer>
        </form>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.amenities-page {
  min-height: calc(100vh - 96px);
}

.primary-btn,
.secondary-btn,
.icon-btn,
.filter-tab,
.pager-btn,
.close-btn {
  border: 0;
  cursor: pointer;
  font: inherit;
}

.primary-btn {
  display: inline-flex;
  min-height: 42px;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 8px;
  background: #18a8e6;
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  padding: 0 18px;
  box-shadow: 0 10px 22px rgba(24, 168, 230, 0.18);
}

.primary-btn:disabled,
.secondary-btn:disabled {
  cursor: not-allowed;
  opacity: 0.62;
}

.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}

.search-box {
  display: flex;
  width: min(360px, 100%);
  height: 44px;
  align-items: center;
  gap: 10px;
  border-radius: 12px;
  background: #f0eafd;
  color: #718096;
  padding: 0 14px;
}

.search-box input {
  width: 100%;
  border: 0;
  outline: 0;
  background: transparent;
  color: #1f2937;
  font-size: 14px;
}

.filter-tabs {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-tab {
  display: inline-flex;
  min-height: 40px;
  align-items: center;
  gap: 8px;
  border: 1px solid #e5edf6;
  border-radius: 18px;
  background: #fff;
  color: #718096;
  font-size: 14px;
  font-weight: 700;
  padding: 0 16px;
}

.filter-tab strong {
  color: #64748b;
}

.filter-tab--active {
  border-color: #b8dcff;
  background: #eef7ff;
  color: #18a8e6;
}

.dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
}

.dot--green {
  background: #10b981;
}

.dot--slate {
  background: #64748b;
}

.alert {
  margin: 0 0 16px;
  border-radius: 10px;
  background: #fef2f2;
  color: #b91c1c;
  font-size: 14px;
  font-weight: 600;
  padding: 12px 14px;
}

.table-shell {
  overflow: hidden;
  border: 1px solid #e9edf3;
  border-radius: 16px;
  background: #fff;
  box-shadow: 0 10px 30px rgba(15, 23, 42, 0.04);
}

.amenities-table {
  width: 100%;
  border-collapse: collapse;
  table-layout: fixed;
}

.amenities-table th,
.amenities-table td {
  height: 78px;
  border-bottom: 1px solid #eef2f7;
  color: #1f2937;
  font-size: 14px;
  padding: 0 24px;
  text-align: left;
  vertical-align: middle;
}

.amenities-table th {
  height: 48px;
  color: #718096;
  font-size: 13px;
  font-weight: 800;
}

.amenities-table th:first-child,
.amenities-table td:first-child {
  width: 110px;
  text-align: center;
}

.id-cell {
  color: #18a8e6;
  font-weight: 800;
}

.amenity-name {
  display: flex;
  align-items: center;
  gap: 12px;
  min-width: 0;
}

.amenity-name strong {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.amenity-icon,
.preview-icon {
  display: inline-flex;
  width: 34px;
  height: 34px;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: #eff6ff;
  color: #18a8e6;
  font-size: 14px;
  font-weight: 800;
  flex-shrink: 0;
}

.muted-cell {
  color: #718096;
}

.status-pill {
  display: inline-flex;
  min-height: 30px;
  align-items: center;
  gap: 6px;
  border-radius: 999px;
  background: #dcfce7;
  color: #22c55e;
  font-size: 13px;
  font-weight: 800;
  padding: 0 12px;
}

.actions-col {
  width: 116px;
  text-align: right;
}

.icon-btn {
  display: inline-flex;
  width: 34px;
  height: 34px;
  align-items: center;
  justify-content: center;
  border-radius: 9px;
  background: transparent;
  color: #718096;
}

.icon-btn:hover {
  background: #f1f5f9;
  color: #18a8e6;
}

.state-cell {
  color: #718096;
  text-align: center !important;
}

.table-footer {
  display: flex;
  min-height: 64px;
  align-items: center;
  justify-content: flex-end;
  gap: 14px;
  color: #718096;
  font-size: 14px;
  padding: 0 24px;
}

.range-text {
  color: #94a3b8;
}

.pager-btn,
.page-number,
.per-page {
  display: inline-flex;
  width: 38px;
  height: 38px;
  align-items: center;
  justify-content: center;
  border: 1px solid #e5edf6;
  border-radius: 12px;
  background: #fff;
  color: #64748b;
}

.pager-btn:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.page-number {
  color: #1f2937;
  font-weight: 700;
}

.per-page {
  width: 72px;
  padding: 0 10px;
}

.drawer-overlay {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: flex;
  justify-content: flex-end;
  background: rgba(15, 23, 42, 0.36);
}

.drawer {
  width: min(520px, 100vw);
  height: 100vh;
  overflow-y: auto;
  background: #fff;
  box-shadow: -20px 0 40px rgba(15, 23, 42, 0.14);
}

.drawer-header {
  display: flex;
  height: 76px;
  align-items: center;
  justify-content: space-between;
  border-bottom: 1px solid #e9edf3;
  padding: 0 28px;
}

.drawer-header h2 {
  margin: 0;
  color: #1f2937;
  font-size: 24px;
  font-weight: 800;
}

.close-btn {
  display: inline-flex;
  width: 38px;
  height: 38px;
  align-items: center;
  justify-content: center;
  border-radius: 10px;
  background: transparent;
  color: #9ca3af;
}

.close-btn:hover {
  background: #f1f5f9;
}

.drawer-form {
  display: flex;
  flex-direction: column;
  gap: 22px;
  padding: 24px 28px 28px;
}

.field {
  display: flex;
  flex-direction: column;
  gap: 9px;
}

.field span {
  color: #334155;
  font-size: 14px;
  font-weight: 700;
}

.field b {
  color: #ef4444;
}

.field input {
  width: 100%;
  height: 50px;
  border: 1px solid #e5edf6;
  border-radius: 12px;
  color: #1f2937;
  font-size: 15px;
  outline: 0;
  padding: 0 16px;
}

.field input:focus {
  border-color: #18a8e6;
  box-shadow: 0 0 0 3px rgba(24, 168, 230, 0.12);
}

.preview-row {
  display: flex;
  min-height: 54px;
  align-items: center;
  gap: 12px;
  border: 1px dashed #cbd5e1;
  border-radius: 14px;
  color: #334155;
  font-weight: 700;
  padding: 0 14px;
}

.drawer-actions {
  display: grid;
  grid-template-columns: 1fr 1.5fr;
  gap: 18px;
  margin-top: 12px;
}

.secondary-btn {
  min-height: 44px;
  border: 1.5px solid #18a8e6;
  border-radius: 10px;
  background: #fff;
  color: #18a8e6;
  font-weight: 800;
}

.primary-btn--wide {
  min-height: 44px;
  width: 100%;
}

@media (max-width: 900px) {
  .toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .search-box {
    width: 100%;
  }

  .amenities-table {
    min-width: 760px;
  }

  .table-shell {
    overflow-x: auto;
  }
}

@media (max-width: 560px) {
  .drawer-actions {
    grid-template-columns: 1fr;
  }

  .drawer-header {
    padding: 0 20px;
  }

  .drawer-form {
    padding: 22px 20px;
  }
}
</style>
