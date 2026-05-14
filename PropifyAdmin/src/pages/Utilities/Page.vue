<script setup>
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { Plus, Edit, Trash2, Wifi, Car, Trees, Dumbbell, ShieldCheck, Coffee, Eye, EyeOff } from 'lucide-vue-next'

const utilities = ref([
  { id: 1, name: 'Wifi miễn phí', icon: Wifi, visible: true, description: 'Kết nối internet tốc độ cao' },
  { id: 2, name: 'Bãi đỗ xe', icon: Car, visible: true, description: 'Bãi đỗ xe rộng rãi' },
  { id: 3, name: 'Công viên', icon: Trees, visible: true, description: 'Khu vui chơi & công viên xanh' },
  { id: 4, name: 'Phòng gym', icon: Dumbbell, visible: true, description: 'Phòng tập thể dục hiện đại' },
  { id: 5, name: 'An ninh 24/7', icon: ShieldCheck, visible: false, description: 'Hệ thống an ninh chuyên nghiệp' },
  { id: 6, name: 'Cafeteria', icon: Coffee, visible: false, description: 'Quán cà phê & tiện ích' },
])

const confirmModal = ref({ open: false, title: '', desc: '', action: () => {} })

function toggleVisibility(id) {
  const u = utilities.value.find(u => u.id === id)
  if (u) u.visible = !u.visible
}

function openDeleteConfirm(util) {
  confirmModal.value = {
    open: true,
    title: 'Xóa tiện ích',
    desc: `Bạn có muốn xóa "${util.name}"?`,
    action: () => {
      utilities.value = utilities.value.filter(u => u.id !== util.id)
      confirmModal.value.open = false
    },
  }
}
</script>

<template>
  <div>
    <PageHeader title="Tiện ích hệ thống" description="Quản lý các tiện ích hiển thị cho bất động sản">
      <template #actions>
        <button class="btn-add gradient-primary" id="btn-add-utility">
          <Plus :size="16" /> Thêm tiện ích
        </button>
      </template>
    </PageHeader>

    <div class="util-grid">
      <div
        v-for="util in utilities"
        :key="util.id"
        class="util-card"
        :class="{ 'util-card--hidden': !util.visible }"
      >
        <!-- Top row: icon + visibility toggle -->
        <div class="util-top">
          <div class="util-icon-wrap">
            <component :is="util.icon" :size="20" color="hsl(var(--primary))" />
          </div>
          <button
            class="vis-btn"
            :title="util.visible ? 'Ẩn tiện ích' : 'Hiện tiện ích'"
            :id="`vis-${util.id}`"
            @click="toggleVisibility(util.id)"
          >
            <Eye v-if="util.visible" :size="16" color="hsl(var(--success))" />
            <EyeOff v-else :size="16" color="hsl(215,16%,47%)" />
          </button>
        </div>

        <h3 class="util-name">{{ util.name }}</h3>
        <p class="util-desc">{{ util.description }}</p>

        <!-- Actions -->
        <div class="util-actions">
          <button class="util-btn" :id="`edit-util-${util.id}`">
            <Edit :size="14" /> Sửa
          </button>
          <button
            class="util-del-btn"
            :id="`del-util-${util.id}`"
            @click="openDeleteConfirm(util)"
          >
            <Trash2 :size="14" color="hsl(var(--destructive))" />
          </button>
        </div>
      </div>
    </div>

    <ConfirmModal
      :open="confirmModal.open"
      :title="confirmModal.title"
      :description="confirmModal.desc"
      variant="destructive"
      confirm-text="Xóa"
      @close="confirmModal.open = false"
      @confirm="confirmModal.action()"
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

.util-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

.util-card {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 20px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
  transition: box-shadow 0.2s;
}

.util-card:hover { box-shadow: var(--shadow-card-hover); }
.util-card--hidden { opacity: 0.5; }

.util-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 16px;
}

.util-icon-wrap {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  background-color: hsl(var(--primary) / 0.1);
  display: flex;
  align-items: center;
  justify-content: center;
}

.vis-btn {
  width: 32px;
  height: 32px;
  border: none;
  border-radius: 8px;
  background: none;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
}
.vis-btn:hover { background-color: hsl(var(--muted)); }

.util-name {
  font-size: 16px;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0 0 4px;
}

.util-desc {
  font-size: 14px;
  color: hsl(var(--muted-foreground));
  margin: 0 0 16px;
}

.util-actions {
  display: flex;
  align-items: center;
  gap: 8px;
  padding-top: 12px;
  border-top: 1px solid hsl(var(--border));
}

.util-btn {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  cursor: pointer;
  transition: background-color 0.15s;
}
.util-btn:hover { background-color: hsl(var(--muted)); }

.util-del-btn {
  width: 36px;
  height: 36px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background: hsl(var(--card));
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  transition: background-color 0.15s;
}
.util-del-btn:hover { background-color: hsl(var(--destructive) / 0.1); }

@media (max-width: 1024px) { .util-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .util-grid { grid-template-columns: 1fr; } }
</style>
