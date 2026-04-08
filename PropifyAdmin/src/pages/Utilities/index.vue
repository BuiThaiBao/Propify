<script setup>
import { ref } from 'vue'
import { Plus } from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import UtilityCard from './UtilityCard.vue'

const utilities = ref([
  {
    id: 1,
    name: 'Wifi miễn phí',
    description: 'Kết nối internet tốc độ cao',
    icon: '📶',
    visible: true,
  },
  {
    id: 2,
    name: 'Bãi đỗ xe',
    description: 'Bãi đỗ xe rộng rãi',
    icon: '🚗',
    visible: true,
  },
  {
    id: 3,
    name: 'Công viên',
    description: 'Khu vui chơi & công viên xanh',
    icon: '🌳',
    visible: true,
  },
  {
    id: 4,
    name: 'Phòng gym',
    description: 'Phòng tập thể dục hiện đại',
    icon: '💪',
    visible: true,
  },
  {
    id: 5,
    name: 'An ninh 24/7',
    description: 'Hệ thống an ninh chuyên nghiệp',
    icon: '🔒',
    visible: false,
  },
  {
    id: 6,
    name: 'Cafeteria',
    description: 'Quán cà phê & tiện ích',
    icon: '☕',
    visible: false,
  },
])

function toggleVisibility(id) {
  const util = utilities.value.find(u => u.id === id)
  if (util) util.visible = !util.visible
}

function deleteUtility(id) {
  utilities.value = utilities.value.filter(u => u.id !== id)
}
</script>

<template>
  <div>
    <PageHeader
      title="Tiện ích hệ thống"
      subtitle="Quản lý các tiện ích hiển thị cho bất động sản"
    >
      <template #actions>
        <button class="btn-add" id="add-utility-btn">
          <Plus :size="16" />
          Thêm tiện ích
        </button>
      </template>
    </PageHeader>

    <div class="utilities-grid">
      <UtilityCard
        v-for="util in utilities"
        :key="util.id"
        :utility="util"
        @toggle="toggleVisibility(util.id)"
        @delete="deleteUtility(util.id)"
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

.utilities-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
}

@media (max-width: 1024px) {
  .utilities-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .utilities-grid {
    grid-template-columns: 1fr;
  }
}
</style>
