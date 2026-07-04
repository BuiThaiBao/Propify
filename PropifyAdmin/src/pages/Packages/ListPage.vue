<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Check, Edit, Eye, Lock, Package, Plus, Search, Star, TrendingUp, Unlock, Users } from 'lucide-vue-next'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { usePackageApi } from '@/composables/usePackageApi'
import {
  activeDurationDays,
  formatPackagePrice,
  summarizePricingDurations,
} from '@/utils/packageFormatters'

const router = useRouter()
const { fetchPackages, updatePackage, loading, error } = usePackageApi()

const packages = ref([])
const confirmModal = ref({ open: false, title: '', desc: '', action: null })
const filters = reactive({
  keyword: '',
  status: 'all',
})
const statusOptions = [
  { value: 'all', label: 'Tất cả', count: () => totalPackages.value },
  { value: 'active', label: 'Đang hoạt động', count: () => activeCount.value, dot: 'green' },
  { value: 'locked', label: 'Đã khóa', count: () => lockedCount.value, dot: 'slate' },
]

let searchTimer = null

onMounted(() => {
  loadPackages()
})

watch(
  () => filters.keyword,
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(loadPackages, 300)
  },
)

watch(
  () => filters.status,
  () => loadPackages(),
)

async function loadPackages() {
  try {
    const response = await fetchPackages({
      include_inactive: 1,
      keyword: filters.keyword || undefined,
      status: filters.status === 'all' ? undefined : filters.status,
    })
    packages.value = response?.data || []
  } catch {
    packages.value = []
  }
}

const totalPackages = computed(() => packages.value.length)
const activeCount = computed(() => packages.value.filter((pkg) => pkg.is_active).length)
const lockedCount = computed(() => packages.value.filter((pkg) => !pkg.is_active).length)
const totalRevenue = computed(() =>
  packages.value.reduce((sum, pkg) => sum + Number(pkg.price || 0), 0),
)

function buildUpdatePayload(pkg, nextActive) {
  return {
    name: pkg.name,
    slug: pkg.slug || '',
    price: Number(pkg.price || 0),
    priority: Number(pkg.priority || 1),
    multiplier: Number(pkg.multiplier || 1),
    daily_quota: Number(pkg.daily_quota || 0),
    decay_rate: Number(pkg.decay_rate || 0),
    badge: pkg.badge || null,
    color: pkg.color || null,
    is_active: nextActive,
    active_durations: activeDurationDays(pkg),
  }
}

function viewDetail(pkg) {
  router.push({ name: 'PackageDetail', params: { id: pkg.id } })
}

function handleEdit(pkg) {
  router.push({ name: 'PackageEdit', params: { id: pkg.id } })
}

function handleToggleActive(pkg) {
  const nextActive = !pkg.is_active
  confirmModal.value = {
    open: true,
    title: nextActive ? 'Mở khóa gói tin' : 'Khóa gói tin',
    desc: `Bạn có chắc chắn muốn ${nextActive ? 'mở khóa' : 'khóa'} gói "${pkg.name}"?`,
    action: async () => {
      try {
        await updatePackage(pkg.id, buildUpdatePayload(pkg, nextActive))
        await loadPackages()
      } finally {
        confirmModal.value.open = false
      }
    },
  }
}

function getPriorityLabel(priority) {
  if (priority >= 3) return { label: 'Cao', cls: 'priority-high' }
  if (priority === 2) return { label: 'Trung bình', cls: 'priority-mid' }
  return { label: 'Thấp', cls: 'priority-low' }
}
</script>

<template>
  <div class="packages-page">
    <!-- Header -->
    <div class="page-header">
      <div class="page-header-left">
        <div class="page-icon-wrap">
          <Package :size="22" color="#fff" />
        </div>
        <div>
          <h1 class="page-title">Quản lý gói tin</h1>
          <p class="page-subtitle">Quản lý các gói dịch vụ cho người dùng hệ thống</p>
        </div>
      </div>
      <button class="btn-add" id="btn-add-package" @click="router.push({ name: 'PackageCreate' })">
        <Plus :size="16" />
        Thêm gói mới
      </button>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row">
      <div class="stat-card">
        <div class="stat-icon stat-icon--blue">
          <Package :size="18" color="#3b82f6" />
        </div>
        <div>
          <div class="stat-value">{{ totalPackages }}</div>
          <div class="stat-label">Tổng gói tin</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon--green">
          <Check :size="18" color="#10b981" />
        </div>
        <div>
          <div class="stat-value">{{ activeCount }}</div>
          <div class="stat-label">Đang hoạt động</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon--amber">
          <TrendingUp :size="18" color="#f59e0b" />
        </div>
        <div>
          <div class="stat-value">{{ lockedCount }}</div>
          <div class="stat-label">Đã khóa</div>
        </div>
      </div>
      <div class="stat-card">
        <div class="stat-icon stat-icon--purple">
          <Users :size="18" color="#8b5cf6" />
        </div>
        <div>
          <div class="stat-value">{{ Number(totalRevenue).toLocaleString('vi-VN') }}₫</div>
          <div class="stat-label">Tổng giá cơ bản</div>
        </div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="status-tabs">
        <button
          v-for="option in statusOptions"
          :key="option.value"
          type="button"
          :class="['status-tab', filters.status === option.value && 'status-tab-active']"
          @click="filters.status = option.value"
        >
          <span v-if="option.dot" :class="['status-dot', `status-dot--${option.dot}`]"></span>
          <span>{{ option.label }}</span>
          <strong class="tab-count">{{ option.count() }}</strong>
        </button>
      </div>

      <div class="search-wrap">
        <Search :size="15" class="search-icon" />
        <input
          v-model="filters.keyword"
          class="search-input"
          placeholder="Tìm kiếm gói tin..."
          id="pkg-search-input"
        />
      </div>
    </div>

    <!-- Loading / Error -->
    <div v-if="loading && packages.length === 0" class="state-box">
      <div class="spinner"></div>
      <span>Đang tải dữ liệu...</span>
    </div>
    <div v-else-if="error" class="state-box state-error">{{ error }}</div>
    <div v-else-if="packages.length === 0" class="state-box">
      <Package :size="40" color="#cbd5e1" />
      <span>Không tìm thấy gói tin phù hợp.</span>
    </div>

    <!-- Table -->
    <div v-else class="table-wrap">
      <table class="pkg-table">
        <thead>
          <tr>
            <th>Gói tin</th>
            <th>Giá / ngày</th>
            <th>Thời hạn</th>
            <th>Ưu tiên</th>
            <th>Quota / ngày</th>
            <th>Trạng thái</th>
            <th>Thao tác</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="pkg in packages"
            :key="pkg.id"
            :class="{ 'row-inactive': !pkg.is_active }"
          >
            <!-- Name & Badge -->
            <td>
              <div class="pkg-name-cell">
                <div
                  class="pkg-icon"
                  :style="pkg.color ? { background: `${pkg.color}20`, borderColor: `${pkg.color}40` } : null"
                >
                  <Star :size="16" :color="pkg.color || '#3b82f6'" />
                </div>
                <div>
                  <div class="pkg-name-text">{{ pkg.name }}</div>
                  <span v-if="pkg.badge" class="pkg-badge">{{ pkg.badge }}</span>
                  <span v-if="pkg.slug" class="pkg-slug">{{ pkg.slug }}</span>
                </div>
              </div>
            </td>

            <!-- Price -->
            <td>
              <span class="price-text">{{ formatPackagePrice(pkg.price) }}</span>
            </td>

            <!-- Durations -->
            <td>
              <span class="meta-text">{{ summarizePricingDurations(pkg) }}</span>
            </td>

            <!-- Priority -->
            <td>
              <span :class="['priority-badge', getPriorityLabel(pkg.priority).cls]">
                {{ getPriorityLabel(pkg.priority).label }}
              </span>
              <span class="meta-text ml-1">({{ pkg.priority }})</span>
            </td>

            <!-- Quota -->
            <td>
              <span class="meta-text">{{ Number(pkg.daily_quota || 0).toLocaleString('vi-VN') }} lượt</span>
            </td>

            <!-- Status -->
            <td>
              <span :class="['status-badge', pkg.is_active ? 'status-active' : 'status-locked']">
                {{ pkg.is_active ? 'Hoạt động' : 'Đã khóa' }}
              </span>
            </td>

            <!-- Actions -->
            <td>
              <div class="actions-cell">
                <button class="action-btn" title="Xem chi tiết" :id="`view-pkg-${pkg.id}`" @click="viewDetail(pkg)">
                  <Eye :size="15" />
                </button>
                <button class="action-btn action-btn--edit" title="Chỉnh sửa" :id="`edit-pkg-${pkg.id}`" @click="handleEdit(pkg)">
                  <Edit :size="15" />
                </button>
                <button
                  :class="['action-btn', pkg.is_active ? 'action-btn--lock' : 'action-btn--unlock']"
                  :title="pkg.is_active ? 'Khóa' : 'Mở khóa'"
                  :id="`toggle-pkg-${pkg.id}`"
                  @click="handleToggleActive(pkg)"
                >
                  <component :is="pkg.is_active ? Lock : Unlock" :size="15" />
                </button>
              </div>
            </td>
          </tr>
        </tbody>
      </table>
    </div>

    <ConfirmModal
      :open="confirmModal.open"
      :title="confirmModal.title"
      :description="confirmModal.desc"
      @close="confirmModal.open = false"
      @confirm="confirmModal.action"
    />
  </div>
</template>

<style scoped>
.packages-page {
  min-height: calc(100vh - 96px);
}

/* Header */
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}

.page-header-left {
  display: flex;
  align-items: center;
  gap: 14px;
}

.page-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 46px;
  height: 46px;
  border-radius: 12px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  flex-shrink: 0;
}

.page-title {
  margin: 0;
  font-size: 22px;
  font-weight: 800;
  color: #0f172a;
  letter-spacing: -0.02em;
}

.page-subtitle {
  margin: 3px 0 0;
  font-size: 13px;
  color: #64748b;
}

.btn-add {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 40px;
  padding: 0 18px;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  transition: transform 0.15s, box-shadow 0.15s;
  white-space: nowrap;
}

.btn-add:hover {
  transform: translateY(-1px);
  box-shadow: 0 6px 18px rgba(59, 130, 246, 0.4);
}

/* Stats */
.stats-row {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.stat-card {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}

.stat-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 42px;
  height: 42px;
  border-radius: 10px;
  flex-shrink: 0;
}

.stat-icon--blue { background: #eff6ff; }
.stat-icon--green { background: #f0fdf4; }
.stat-icon--amber { background: #fffbeb; }
.stat-icon--purple { background: #f5f3ff; }

.stat-value {
  font-size: 20px;
  font-weight: 800;
  color: #0f172a;
  line-height: 1.1;
}

.stat-label {
  font-size: 12px;
  color: #64748b;
  margin-top: 2px;
}

/* Toolbar */
.toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 16px;
  flex-wrap: wrap;
}

.status-tabs {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

.status-tab {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  height: 34px;
  padding: 0 14px;
  border: 1px solid #e2e8f0;
  border-radius: 17px;
  background: #fff;
  color: #64748b;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.status-tab:hover {
  border-color: #3b82f6;
  color: #3b82f6;
}

.status-tab-active {
  border-color: #3b82f6;
  background: #eff6ff;
  color: #1d4ed8;
}

.tab-count {
  background: #f1f5f9;
  color: #475569;
  border-radius: 999px;
  padding: 0 7px;
  font-size: 12px;
  font-weight: 700;
}

.status-tab-active .tab-count {
  background: #dbeafe;
  color: #1d4ed8;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
  flex-shrink: 0;
}

.status-dot--green { background: #10b981; }
.status-dot--slate { background: #94a3b8; }

.search-wrap {
  position: relative;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}

.search-input {
  height: 36px;
  padding: 0 14px 0 36px;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  background: #fff;
  font-size: 13px;
  color: #1e293b;
  outline: none;
  width: 240px;
  transition: border-color 0.15s, box-shadow 0.15s;
}

.search-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

/* State */
.state-box {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 60px 0;
  color: #64748b;
  font-size: 14px;
}

.state-error { color: #dc2626; }

.spinner {
  width: 28px;
  height: 28px;
  border: 3px solid #e2e8f0;
  border-top-color: #3b82f6;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Table */
.table-wrap {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}

.pkg-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 14px;
}

.pkg-table thead th {
  padding: 12px 16px;
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.05em;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  white-space: nowrap;
}

.pkg-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.1s;
}

.pkg-table tbody tr:last-child {
  border-bottom: none;
}

.pkg-table tbody tr:hover {
  background: #f8fafc;
}

.pkg-table tbody tr.row-inactive {
  opacity: 0.65;
}

.pkg-table td {
  padding: 14px 16px;
  vertical-align: middle;
}

/* Package name cell */
.pkg-name-cell {
  display: flex;
  align-items: center;
  gap: 12px;
}

.pkg-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 9px;
  background: #eff6ff;
  border: 1px solid #dbeafe;
  flex-shrink: 0;
}

.pkg-name-text {
  font-weight: 700;
  color: #0f172a;
  font-size: 14px;
}

.pkg-badge {
  display: inline-block;
  margin-top: 3px;
  padding: 1px 7px;
  border-radius: 999px;
  background: #fef3c7;
  color: #b45309;
  font-size: 11px;
  font-weight: 700;
  margin-right: 4px;
}

.pkg-slug {
  display: inline-block;
  font-size: 11px;
  color: #94a3b8;
  font-family: monospace;
}

/* Misc */
.price-text {
  font-size: 14px;
  font-weight: 700;
  color: #1d4ed8;
}

.meta-text {
  font-size: 13px;
  color: #475569;
}

.ml-1 { margin-left: 4px; }

/* Priority badge */
.priority-badge {
  display: inline-block;
  padding: 2px 9px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.priority-high { background: #fef2f2; color: #dc2626; }
.priority-mid { background: #fffbeb; color: #d97706; }
.priority-low { background: #f0fdf4; color: #16a34a; }

/* Status badge */
.status-badge {
  display: inline-block;
  padding: 3px 10px;
  border-radius: 999px;
  font-size: 12px;
  font-weight: 700;
}

.status-active { background: #dcfce7; color: #16a34a; }
.status-locked { background: #f1f5f9; color: #64748b; }

/* Action buttons */
.actions-cell {
  display: flex;
  align-items: center;
  gap: 6px;
}

.action-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #475569;
  cursor: pointer;
  transition: all 0.15s;
}

.action-btn:hover {
  background: #f8fafc;
  color: #1e293b;
  border-color: #cbd5e1;
}

.action-btn--edit:hover { background: #eff6ff; color: #2563eb; border-color: #bfdbfe; }
.action-btn--lock:hover { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
.action-btn--unlock:hover { background: #f0fdf4; color: #16a34a; border-color: #bbf7d0; }

@media (max-width: 1100px) {
  .stats-row { grid-template-columns: repeat(2, 1fr); }
}

@media (max-width: 700px) {
  .page-header { flex-direction: column; align-items: flex-start; }
  .toolbar { flex-direction: column; align-items: flex-start; }
  .search-input { width: 100%; }
  .stats-row { grid-template-columns: 1fr 1fr; }
  .table-wrap { overflow-x: auto; }
  .pkg-table { min-width: 700px; }
}
</style>
