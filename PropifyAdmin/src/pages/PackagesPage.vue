<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Check, Edit, Eye, Lock, Plus, Search, Star, Unlock } from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { usePackageApi } from '@/composables/usePackageApi'

const router = useRouter()
const { fetchPackages, updatePackage, loading, error } = usePackageApi()

const packages = ref([])
const confirmModal = ref({ open: false, title: '', desc: '', action: null })
const filters = reactive({
  keyword: '',
  status: 'all',
})
const statusOptions = [
  { value: 'all', label: 'Tat ca', count: () => totalPackages.value },
  { value: 'active', label: 'Dang hoat dong', count: () => activeCount.value },
  { value: 'locked', label: 'Da khoa', count: () => lockedCount.value },
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
  } catch (err) {
    packages.value = []
  }
}

const totalPackages = computed(() => packages.value.length)
const activeCount = computed(() => packages.value.filter((pkg) => pkg.is_active).length)
const lockedCount = computed(() => packages.value.filter((pkg) => !pkg.is_active).length)

function activeDurations(pkg) {
  return (pkg.pricings || [])
    .filter((pricing) => pricing.is_active)
    .map((pricing) => Number(pricing.duration_days))
    .filter((days) => Number.isInteger(days) && days > 0)
    .sort((a, b) => a - b)
}

function buildUpdatePayload(pkg, nextActive) {
  return {
    name: pkg.name,
    price: Number(pkg.price || 0),
    priority: Number(pkg.priority || 1),
    multiplier: Number(pkg.multiplier || 1),
    daily_quota: Number(pkg.daily_quota || 0),
    decay_rate: Number(pkg.decay_rate || 0),
    badge: pkg.badge || null,
    color: pkg.color || null,
    is_active: nextActive,
    active_durations: activeDurations(pkg),
  }
}

function formatPrice(price) {
  return Number(price || 0).toLocaleString('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  })
}

function pricingSummary(pkg) {
  const durations = activeDurations(pkg)
  return durations.length ? durations.map((days) => `${days} ngay`).join(', ') : 'Chua cau hinh'
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
    title: nextActive ? 'Mo khoa goi tin' : 'Khoa goi tin',
    desc: `Ban co chac chan muon ${nextActive ? 'mo khoa' : 'khoa'} goi "${pkg.name}"?`,
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
</script>

<template>
  <div>
    <PageHeader title="Quan ly goi tin" description="Tim kiem, loc, xem chi tiet va chinh sua cac goi dich vu">
      <template #actions>
        <button class="btn-add gradient-primary" id="btn-add-package" @click="router.push({ name: 'PackageCreate' })">
          <Plus :size="16" /> Them goi moi
        </button>
      </template>
    </PageHeader>

    <section class="filter-panel">
      <div class="pkg-search">
        <Search :size="18" class="search-icon" />
        <input
          v-model.trim="filters.keyword"
          type="text"
          placeholder="Tim kiem theo ten, slug, badge..."
          class="search-input"
          id="pkg-search"
        />
      </div>

      <div class="status-tabs" aria-label="Loc trang thai goi tin">
        <button
          v-for="option in statusOptions"
          :key="option.value"
          type="button"
          :class="['status-tab', filters.status === option.value && 'status-tab-active']"
          @click="filters.status = option.value"
        >
          <span>{{ option.label }}</span>
          <strong>{{ option.count() }}</strong>
        </button>
      </div>
    </section>

    <div v-if="loading && packages.length === 0" class="state-text">Dang tai du lieu...</div>
    <div v-else-if="error" class="state-text state-error">{{ error }}</div>
    <div v-else-if="packages.length === 0" class="state-text">Khong tim thay goi tin phu hop.</div>

    <div v-else class="pkg-grid">
      <article
        v-for="pkg in packages"
        :key="pkg.id"
        class="pkg-card"
        :class="{ 'pkg-card--inactive': !pkg.is_active }"
      >
        <span v-if="!pkg.is_active" class="locked-badge">Da khoa</span>

        <div class="pkg-icon-wrap" :style="pkg.color ? { backgroundColor: `${pkg.color}20` } : null">
          <Star :size="24" :color="pkg.color || '#3b82f6'" />
        </div>

        <h3 class="pkg-name">
          {{ pkg.name }}
          <span v-if="pkg.badge" class="pkg-badge">{{ pkg.badge }}</span>
        </h3>
        <p class="pkg-price">{{ formatPrice(pkg.price) }}</p>
        <p class="pkg-duration">Uu tien: {{ pkg.priority }} | HS: {{ pkg.multiplier }}x</p>

        <ul class="pkg-features">
          <li class="pkg-feature">
            <Check :size="16" color="#16a34a" />
            {{ pkg.daily_quota }} luot hien thi/ngay
          </li>
          <li class="pkg-feature">
            <Check :size="16" color="#16a34a" />
            Toc do tut hang: {{ pkg.decay_rate }}
          </li>
          <li class="pkg-feature">
            <Check :size="16" color="#16a34a" />
            Thoi han: {{ pricingSummary(pkg) }}
          </li>
        </ul>

        <div class="pkg-actions">
          <button class="pkg-btn" @click="viewDetail(pkg)" :id="`view-pkg-${pkg.id}`">
            <Eye :size="14" /> Xem
          </button>
          <button class="pkg-btn" @click="handleEdit(pkg)" :id="`edit-pkg-${pkg.id}`">
            <Edit :size="14" /> Sua
          </button>
          <button class="pkg-btn" @click="handleToggleActive(pkg)" :id="`toggle-pkg-${pkg.id}`">
            <component :is="pkg.is_active ? Lock : Unlock" :size="14" />
            {{ pkg.is_active ? 'Khoa' : 'Mo' }}
          </button>
        </div>
      </article>
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
.btn-add {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  color: white;
  cursor: pointer;
}

.filter-panel {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 18px;
  margin-bottom: 24px;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #fff;
  padding: 14px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}

.pkg-search {
  position: relative;
  width: min(420px, 100%);
}

.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}

.search-input {
  width: 100%;
  height: 46px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #f8fafc;
  color: #0f172a;
  font-size: 14px;
  outline: none;
  padding: 0 14px 0 44px;
}

.search-input:focus {
  background: #fff;
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.status-tabs {
  display: flex;
  align-items: center;
  gap: 8px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #f8fafc;
  padding: 5px;
}

.status-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 38px;
  border: 0;
  border-radius: 10px;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  font-size: 13px;
  font-weight: 700;
  padding: 0 12px;
  white-space: nowrap;
}

.status-tab strong {
  display: inline-flex;
  min-width: 24px;
  height: 24px;
  align-items: center;
  justify-content: center;
  border-radius: 999px;
  background: #e2e8f0;
  color: #334155;
  font-size: 12px;
}

.status-tab-active {
  background: #2563eb;
  color: #fff;
  box-shadow: 0 8px 18px rgba(37, 99, 235, 0.22);
}

.status-tab-active strong {
  background: rgba(255, 255, 255, 0.22);
  color: #fff;
}

.state-text {
  padding: 28px 0;
  color: #64748b;
  font-size: 14px;
}

.state-error {
  color: #dc2626;
}

.pkg-grid {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 20px;
}

.pkg-card {
  position: relative;
  display: flex;
  min-height: 310px;
  flex-direction: column;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  padding: 24px;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
}

.pkg-card--inactive {
  opacity: 0.72;
}

.locked-badge,
.pkg-badge {
  border-radius: 999px;
  font-size: 11px;
  font-weight: 700;
}

.locked-badge {
  position: absolute;
  right: 16px;
  top: 16px;
  background: #f1f5f9;
  color: #64748b;
  padding: 3px 8px;
}

.pkg-badge {
  margin-left: 8px;
  background: #fee2e2;
  color: #dc2626;
  padding: 2px 8px;
}

.pkg-icon-wrap {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 54px;
  height: 54px;
  margin-bottom: 18px;
  border-radius: 14px;
  background: #eff6ff;
}

.pkg-name {
  margin: 0 0 8px;
  color: #0f172a;
  font-size: 20px;
  font-weight: 800;
}

.pkg-price {
  margin: 0;
  color: #3b82f6;
  font-size: 28px;
  font-weight: 800;
}

.pkg-duration {
  margin: 4px 0 18px;
  color: #64748b;
  font-size: 13px;
}

.pkg-features {
  display: flex;
  flex: 1;
  flex-direction: column;
  gap: 9px;
  margin: 0 0 20px;
  padding: 0;
  list-style: none;
}

.pkg-feature {
  display: flex;
  align-items: center;
  gap: 8px;
  color: #0f172a;
  font-size: 14px;
}

.pkg-actions {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  border-top: 1px solid #e2e8f0;
  padding-top: 18px;
}

.pkg-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  min-height: 42px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background: #fff;
  color: #0f172a;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
}

.pkg-btn:hover {
  background: #f8fafc;
}

@media (max-width: 1200px) {
  .pkg-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }
}

@media (max-width: 700px) {
  .filter-panel {
    align-items: stretch;
    flex-direction: column;
  }

  .status-tabs,
  .pkg-search {
    width: 100%;
  }

  .status-tabs {
    align-items: stretch;
    flex-direction: column;
  }

  .status-tab {
    justify-content: space-between;
  }

  .pkg-grid {
    grid-template-columns: 1fr;
  }

  .pkg-actions {
    grid-template-columns: 1fr;
  }
}
</style>
