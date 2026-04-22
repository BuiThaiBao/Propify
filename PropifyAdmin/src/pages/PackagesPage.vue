<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import PageHeader from '@/components/shared/PageHeader.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { Plus, Search, Edit, Lock, Unlock, Check, Star, Zap, Crown } from 'lucide-vue-next'
import { usePackageApi } from '@/composables/usePackageApi'

const router = useRouter()
const search = ref('')
const confirmModal = ref({ open: false, title: '', desc: '', action: null })

const { fetchPackages, deletePackage, loading, error } = usePackageApi()
const packages = ref([])

onMounted(async () => {
  await loadPackages()
})

const loadPackages = async () => {
  try {
    const res = await fetchPackages()
    if (res?.data) {
      packages.value = res.data
    }
  } catch (e) {
    console.error('Failed to load packages', e)
  }
}

const filtered = computed(() => {
  return packages.value.filter(p => !search.value || p.name.toLowerCase().includes(search.value.toLowerCase()))
})

const getIcon = (slug) => {
  if (slug === 'gold') return Star
  if (slug === 'silver') return Zap
  if (slug === 'diamond') return Crown
  return Star
}

const getColorClass = (slug) => {
  if (slug === 'gold') return 'icon-warning'
  if (slug === 'silver') return 'icon-muted'
  if (slug === 'diamond') return 'icon-primary'
  return 'icon-success'
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(price)
}

const handleToggleActive = (pkg) => {
  confirmModal.value = {
    open: true,
    title: pkg.is_active ? 'Khóa gói' : 'Kích hoạt gói',
    desc: `Bạn có chắc chắn muốn ${pkg.is_active ? 'khóa' : 'kích hoạt'} gói "${pkg.name}"?`,
    action: async () => {
      try {
        await deletePackage(pkg.id)
        await loadPackages()
      } catch (e) {
        console.error('Failed to toggle package', e)
      } finally {
        confirmModal.value.open = false
      }
    }
  }
}

const handleEdit = (pkg) => {
  router.push({ name: 'PackageEdit', params: { id: pkg.id } })
}
</script>

<template>
  <div>
    <PageHeader title="Quản lý gói tin" description="Thêm, chỉnh sửa và quản lý các gói dịch vụ">
      <template #actions>
        <button class="btn-add gradient-primary" id="btn-add-package" @click="router.push({ name: 'PackageCreate' })">
          <Plus :size="16" /> Thêm gói mới
        </button>
      </template>
    </PageHeader>

    <!-- Search -->
    <div class="pkg-search">
      <Search :size="16" class="search-icon" />
      <input
        v-model="search"
        type="text"
        placeholder="Tìm kiếm gói tin..."
        class="search-input"
        id="pkg-search"
      />
    </div>

    <!-- Error/Loading -->
    <div v-if="loading && packages.length === 0" class="py-8 text-center text-gray-500">Đang tải dữ liệu...</div>
    <div v-if="error" class="py-4 text-red-500">{{ error }}</div>

    <!-- Grid -->
    <div class="pkg-grid" v-if="!loading || packages.length > 0">
      <div
        v-for="pkg in filtered"
        :key="pkg.id"
        class="pkg-card"
        :class="{ 'pkg-card--inactive': !pkg.is_active }"
      >
        <!-- Locked badge -->
        <span v-if="!pkg.is_active" class="locked-badge">Đã khóa</span>

        <!-- Icon -->
        <div class="pkg-icon-wrap" :class="getColorClass(pkg.slug)" :style="pkg.color ? `background-color: ${pkg.color}20` : ''">
          <component :is="getIcon(pkg.slug)" :size="24" :color="pkg.color || 'hsl(var(--primary))'" />
        </div>

        <h3 class="pkg-name">{{ pkg.name }} <span v-if="pkg.badge" class="ml-2 text-xs bg-red-100 text-red-600 px-2 py-0.5 rounded">{{ pkg.badge }}</span></h3>
        <p class="pkg-price">{{ formatPrice(pkg.price) }}</p>
        <p class="pkg-duration">Ưu tiên: {{ pkg.priority }} | HS: {{ pkg.multiplier }}x</p>

        <ul class="pkg-features">
          <li class="pkg-feature">
            <Check :size="16" color="hsl(var(--success))" class="flex-shrink-0" />
            {{ pkg.daily_quota }} lượt hiển thị/ngày
          </li>
          <li class="pkg-feature">
            <Check :size="16" color="hsl(var(--success))" class="flex-shrink-0" />
            Tốc độ tụt hạng: {{ pkg.decay_rate }}
          </li>
        </ul>

        <!-- Actions -->
        <div class="pkg-actions">
          <button class="pkg-btn" @click="handleEdit(pkg)" :id="`edit-pkg-${pkg.id}`">
            <Edit :size="14" /> Sửa
          </button>
          <button
            class="pkg-btn"
            :id="`toggle-pkg-${pkg.id}`"
            @click="handleToggleActive(pkg)"
          >
            <component :is="pkg.is_active ? Lock : Unlock" :size="14" />
            {{ pkg.is_active ? 'Khóa' : 'Mở' }}
          </button>
        </div>
      </div>
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
  font-weight: 500;
  color: white;
  cursor: pointer;
  transition: box-shadow 0.15s;
}
.btn-add:hover { box-shadow: 0 4px 12px hsl(217 91% 60% / 0.3); }

.pkg-search {
  position: relative;
  width: 320px;
  margin-bottom: 24px;
}

.search-icon {
  position: absolute;
  left: 12px;
  top: 50%;
  transform: translateY(-50%);
  color: hsl(var(--muted-foreground));
}

.search-input {
  width: 100%;
  padding: 8px 16px 8px 40px;
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  font-size: 14px;
  color: hsl(var(--foreground));
  outline: none;
}
.search-input:focus { box-shadow: 0 0 0 2px hsl(var(--primary) / 0.2); }

.pkg-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
}

.pkg-card {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 24px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
  position: relative;
  transition: box-shadow 0.2s;
}

.pkg-card:hover { box-shadow: var(--shadow-card-hover); }
.pkg-card--inactive { opacity: 0.6; }

.locked-badge {
  position: absolute;
  top: 16px;
  right: 16px;
  background-color: hsl(var(--muted));
  color: hsl(var(--muted-foreground));
  font-size: 11px;
  font-weight: 500;
  padding: 2px 8px;
  border-radius: 9999px;
}

.pkg-icon-wrap {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 16px;
}

.icon-muted { background-color: hsl(var(--muted)); }
.icon-primary { background-color: hsl(var(--primary) / 0.1); }
.icon-warning { background-color: hsl(var(--warning) / 0.1); }
.icon-success { background-color: hsl(var(--success) / 0.1); }

.pkg-name {
  font-size: 18px;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0 0 4px;
}

.pkg-price {
  font-size: 24px;
  font-weight: 700;
  color: hsl(var(--primary));
  margin: 0 0 4px;
}

.pkg-duration {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin: 0 0 16px;
}

.pkg-features {
  list-style: none;
  padding: 0;
  margin: 0 0 24px;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pkg-feature {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
  color: hsl(var(--foreground));
}

.pkg-actions {
  display: flex;
  gap: 8px;
  padding-top: 16px;
  border-top: 1px solid hsl(var(--border));
}

.pkg-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 0;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  cursor: pointer;
  transition: background-color 0.15s;
}

.pkg-btn:hover { background-color: hsl(var(--muted)); }

@media (max-width: 1200px) { .pkg-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .pkg-grid { grid-template-columns: 1fr; } }
</style>
