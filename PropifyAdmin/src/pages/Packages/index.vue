<script setup>
import { ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import PackageCard from './PackageCard.vue'
import PackageSearchBar from './PackageSearchBar.vue'

const searchQuery = ref('')

const packages = ref([
  {
    id: 1,
    name: 'Gói Cơ bản',
    price: '50,000đ',
    duration: '7 ngày',
    features: ['5 tin đăng', 'Hiển thị thường', 'Hỗ trợ email'],
    status: 'active',
    icon: '☆',
    iconBg: '#eff6ff',
    iconColor: '#2563eb',
  },
  {
    id: 2,
    name: 'Gói Tiêu chuẩn',
    price: '150,000đ',
    duration: '30 ngày',
    features: ['20 tin đăng', 'Hiển thị ưu tiên', 'Hỗ trợ chat', 'Badge xác thực'],
    status: 'active',
    icon: '⚡',
    iconBg: '#fef3c7',
    iconColor: '#f59e0b',
  },
  {
    id: 3,
    name: 'Gói Premium',
    price: '500,000đ',
    duration: '30 ngày',
    features: ['Không giới hạn tin', 'Top đầu kết quả', 'Hỗ trợ 24/7', 'Badge Premium', 'Báo cáo chi tiết'],
    status: 'active',
    icon: '👑',
    iconBg: '#d1fae5',
    iconColor: '#10b981',
  },
  {
    id: 4,
    name: 'Gói Doanh nghiệp',
    price: '2,000,000đ',
    duration: '30 ngày',
    features: ['Không giới hạn tin', 'Trang riêng', 'API truy cập', 'Account manager'],
    status: 'locked',
    icon: '🏢',
    iconBg: '#f8fafc',
    iconColor: '#64748b',
  },
])
</script>

<template>
  <div>
    <PageHeader
      title="Quản lý gói tin"
      subtitle="Thêm, chỉnh sửa và quản lý các gói dịch vụ"
    >
      <template #actions>
        <button class="btn-add" id="add-package-btn">
          <Plus :size="16" />
          Thêm gói mới
        </button>
      </template>
    </PageHeader>

    <PackageSearchBar v-model="searchQuery" />

    <div class="packages-grid">
      <PackageCard
        v-for="pkg in packages"
        :key="pkg.id"
        :package="pkg"
      />
    </div>
  </div>
</template>

<style scoped>
.btn-add {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 9px 18px;
  background: linear-gradient(135deg, #2563eb, #1d4ed8);
  color: white;
  border: none;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: opacity 0.15s, transform 0.15s;
}

.btn-add:hover {
  opacity: 0.9;
  transform: translateY(-1px);
}

.packages-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

@media (max-width: 1200px) {
  .packages-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .packages-grid {
    grid-template-columns: 1fr;
  }
}
</style>
