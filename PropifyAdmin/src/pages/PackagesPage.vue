<script setup>
import { ref, computed } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { Plus, Search, Edit, Lock, Unlock, Check, Star, Zap, Crown } from 'lucide-vue-next'

const search = ref('')
const confirmModal = ref({ open: false, title: '', desc: '' })

const packages = ref([
  { id: 1, name: 'Gói Cơ bản', price: '50,000đ', duration: '7 ngày', features: ['5 tin đăng', 'Hiển thị thường', 'Hỗ trợ email'], active: true, icon: Star, color: 'icon-muted' },
  { id: 2, name: 'Gói Tiêu chuẩn', price: '150,000đ', duration: '30 ngày', features: ['20 tin đăng', 'Hiển thị ưu tiên', 'Hỗ trợ chat', 'Badge xác thực'], active: true, icon: Zap, color: 'icon-primary' },
  { id: 3, name: 'Gói Premium', price: '500,000đ', duration: '30 ngày', features: ['Không giới hạn tin', 'Top đầu kết quả', 'Hỗ trợ 24/7', 'Badge Premium', 'Báo cáo chi tiết'], active: true, icon: Crown, color: 'icon-warning' },
  { id: 4, name: 'Gói Doanh nghiệp', price: '2,000,000đ', duration: '90 ngày', features: ['Không giới hạn tin', 'Trang riêng', 'API truy cập', 'Account manager'], active: false, icon: Crown, color: 'icon-success' },
])

const filtered = computed(() => {
  return packages.value.filter(p => !search.value || p.name.toLowerCase().includes(search.value.toLowerCase()))
})
</script>

<template>
  <div>
    <PageHeader title="Quản lý gói tin" description="Thêm, chỉnh sửa và quản lý các gói dịch vụ">
      <template #actions>
        <button class="btn-add gradient-primary" id="btn-add-package">
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

    <!-- Grid -->
    <div class="pkg-grid">
      <div
        v-for="pkg in filtered"
        :key="pkg.id"
        class="pkg-card"
        :class="{ 'pkg-card--inactive': !pkg.active }"
      >
        <!-- Locked badge -->
        <span v-if="!pkg.active" class="locked-badge">Đã khóa</span>

        <!-- Icon -->
        <div class="pkg-icon-wrap" :class="pkg.color">
          <component :is="pkg.icon" :size="24" color="hsl(var(--primary))" />
        </div>

        <h3 class="pkg-name">{{ pkg.name }}</h3>
        <p class="pkg-price">{{ pkg.price }}</p>
        <p class="pkg-duration">{{ pkg.duration }}</p>

        <ul class="pkg-features">
          <li v-for="f in pkg.features" :key="f" class="pkg-feature">
            <Check :size="16" color="hsl(var(--success))" class="flex-shrink-0" />
            {{ f }}
          </li>
        </ul>

        <!-- Actions -->
        <div class="pkg-actions">
          <button class="pkg-btn" :id="`edit-pkg-${pkg.id}`">
            <Edit :size="14" /> Sửa
          </button>
          <button
            class="pkg-btn"
            :id="`toggle-pkg-${pkg.id}`"
            @click="confirmModal = { open: true, title: pkg.active ? 'Khóa gói' : 'Kích hoạt gói', desc: `Bạn có muốn ${pkg.active ? 'khóa' : 'kích hoạt'} &quot;${pkg.name}&quot;?` }"
          >
            <component :is="pkg.active ? Lock : Unlock" :size="14" />
            {{ pkg.active ? 'Khóa' : 'Mở' }}
          </button>
        </div>
      </div>
    </div>

    <ConfirmModal
      :open="confirmModal.open"
      :title="confirmModal.title"
      :description="confirmModal.desc"
      @close="confirmModal.open = false"
      @confirm="confirmModal.open = false"
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
