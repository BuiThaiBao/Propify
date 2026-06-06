<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { Check, Edit, Eye, Lock, Plus, Star, Unlock } from 'lucide-vue-next'
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
  { value: 'active', label: 'Kích hoạt', count: () => activeCount.value, dot: 'green' },
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
</script>

<template>
  <div class="packages-page">
    <section class="packages-hero">
      <div>
        <h1>Quản lý gói tin</h1>
        <p>Quản lý các gói dịch vụ hiển thị cho người dùng</p>
      </div>

      <button class="btn-add" id="btn-add-package" @click="router.push({ name: 'PackageCreate' })">
        <Plus :size="16" /> Thêm gói mới
      </button>
    </section>

    <section class="package-toolbar">
      <div class="status-tabs" aria-label="Lọc trạng thái gói tin">
        <button
          v-for="option in statusOptions"
          :key="option.value"
          type="button"
          :class="['status-tab', filters.status === option.value && 'status-tab-active']"
          @click="filters.status = option.value"
        >
          <span v-if="option.dot" :class="['status-dot', `status-dot--${option.dot}`]"></span>
          <span>{{ option.label }}</span>
          <strong>{{ option.count() }}</strong>
        </button>
      </div>
    </section>

    <div v-if="loading && packages.length === 0" class="state-text">Đang tải dữ liệu...</div>
    <div v-else-if="error" class="state-text state-error">{{ error }}</div>
    <div v-else-if="packages.length === 0" class="state-text">Không tìm thấy gói tin phù hợp.</div>

    <div v-else class="pkg-grid">
      <article
        v-for="pkg in packages"
        :key="pkg.id"
        class="pkg-card"
        :class="{ 'pkg-card--inactive': !pkg.is_active }"
      >
        <span v-if="!pkg.is_active" class="locked-badge">Đã khóa</span>

        <div
          class="pkg-icon-wrap"
          :style="pkg.color ? { backgroundColor: `${pkg.color}20` } : null"
        >
          <Star :size="24" :color="pkg.color || '#3b82f6'" />
        </div>

        <h3 class="pkg-name">
          {{ pkg.name }}
          <span v-if="pkg.badge" class="pkg-badge">{{ pkg.badge }}</span>
        </h3>
        <p class="pkg-price">{{ formatPackagePrice(pkg.price) }}</p>
        <p class="pkg-duration">Ưu tiên: {{ pkg.priority }} | HS: {{ pkg.multiplier }}x</p>

        <ul class="pkg-features">
          <li class="pkg-feature">
            <Check :size="16" color="#16a34a" />
            {{ pkg.daily_quota }} lượt hiển thị/ngày
          </li>
          <li class="pkg-feature">
            <Check :size="16" color="#16a34a" />
            Tốc độ tụt hạng: {{ pkg.decay_rate }}
          </li>
          <li class="pkg-feature">
            <Check :size="16" color="#16a34a" />
            Thời hạn: {{ summarizePricingDurations(pkg) }}
          </li>
        </ul>

        <div class="pkg-actions">
          <button class="pkg-btn" @click="viewDetail(pkg)" :id="`view-pkg-${pkg.id}`">
            <Eye :size="14" /> Xem
          </button>
          <button class="pkg-btn" @click="handleEdit(pkg)" :id="`edit-pkg-${pkg.id}`">
            <Edit :size="14" /> Sửa
          </button>
          <button class="pkg-btn" @click="handleToggleActive(pkg)" :id="`toggle-pkg-${pkg.id}`">
            <component :is="pkg.is_active ? Lock : Unlock" :size="14" />
            {{ pkg.is_active ? 'Khóa' : 'Mở' }}
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
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  min-height: 40px;
  padding: 10px 16px;
  border: none;
  border-radius: 8px;
  background: #18a8e6;
  font-size: 14px;
  font-weight: 700;
  color: white;
  cursor: pointer;
  box-shadow: 0 10px 22px rgba(24, 168, 230, 0.18);
}

.packages-page {
  min-height: calc(100vh - 96px);
}

.packages-hero {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
}

.packages-hero h1 {
  margin: 0;
  color: #1f2937;
  font-size: 30px;
  font-weight: 800;
  letter-spacing: -0.02em;
  line-height: 1.15;
}

.packages-hero p {
  margin: 6px 0 0;
  color: #64748b;
  font-size: 13px;
}

.package-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}

.status-tabs {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.status-tab {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  min-height: 34px;
  border: 1px solid #e5edf6;
  border-radius: 17px;
  background: #fff;
  color: #718096;
  cursor: pointer;
  font-size: 13px;
  font-weight: 700;
  padding: 0 14px;
}

.status-tab strong {
  color: #64748b;
  font-size: 13px;
}

.status-tab-active {
  border-color: #b8dcff;
  background: #eef7ff;
  color: #18a8e6;
}

.status-dot {
  width: 7px;
  height: 7px;
  border-radius: 999px;
}

.status-dot--green {
  background: #10b981;
}

.status-dot--slate {
  background: #64748b;
}

.status-tab-active strong {
  color: #18a8e6;
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
  .packages-hero,
  .package-toolbar {
    align-items: stretch;
    flex-direction: column;
  }

  .btn-add,
  .status-tabs {
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
