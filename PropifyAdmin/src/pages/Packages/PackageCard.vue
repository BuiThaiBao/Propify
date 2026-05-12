<script setup>
import { Check, Edit, Lock, Unlock } from 'lucide-vue-next'

const props = defineProps({
  package: {
    type: Object,
    required: true,
  },
})
</script>

<template>
  <div class="package-card" :class="{ locked: package.status === 'locked' }">
    <!-- Locked badge -->
    <div v-if="package.status === 'locked'" class="locked-label">Đã khóa</div>

    <!-- Icon -->
    <div class="pkg-icon" :style="{ backgroundColor: package.iconBg }">
      <span class="pkg-icon-text">{{ package.icon }}</span>
    </div>

    <!-- Name -->
    <h3 class="pkg-name">{{ package.name }}</h3>

    <p class="pkg-duration">{{ package.duration }}</p>

    <!-- Features -->
    <ul class="pkg-features">
      <li v-for="feat in package.features" :key="feat" class="pkg-feature">
        <Check :size="14" class="check-icon" />
        <span>{{ feat }}</span>
      </li>
    </ul>

    <!-- Actions -->
    <div class="pkg-actions">
      <button class="btn-edit" :id="`edit-pkg-${package.id}`">
        <Edit :size="14" />
        Sửa
      </button>
      <button
        class="btn-toggle"
        :class="package.status === 'locked' ? 'btn-unlock' : 'btn-lock'"
        :id="`toggle-pkg-${package.id}`"
      >
        <component :is="package.status === 'locked' ? Unlock : Lock" :size="14" />
        {{ package.status === 'locked' ? 'Mở' : 'Khóa' }}
      </button>
    </div>
  </div>
</template>

<style scoped>
.package-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 24px;
  position: relative;
  transition: box-shadow 0.2s, transform 0.2s;
}

.package-card:hover {
  box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
  transform: translateY(-2px);
}

.package-card.locked {
  opacity: 0.7;
}

.locked-label {
  position: absolute;
  top: 14px;
  right: 14px;
  font-size: 11px;
  font-weight: 600;
  color: #94a3b8;
  background-color: #f1f5f9;
  padding: 2px 8px;
  border-radius: 20px;
}

.pkg-icon {
  width: 48px;
  height: 48px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 14px;
}

.pkg-icon-text {
  font-size: 22px;
}

.pkg-name {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 6px 0;
}

.pkg-price {
  font-size: 22px;
  font-weight: 800;
  color: #2563eb;
  margin: 0 0 2px 0;
}

.pkg-duration {
  font-size: 13px;
  color: #94a3b8;
  margin: 0 0 16px 0;
}

.pkg-features {
  list-style: none;
  padding: 0;
  margin: 0 0 20px 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.pkg-feature {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
  color: #475569;
}

.check-icon {
  color: #10b981;
  flex-shrink: 0;
}

.pkg-actions {
  display: flex;
  gap: 8px;
}

.btn-edit,
.btn-toggle {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 5px;
  padding: 8px 0;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  border: 1.5px solid;
  transition: background-color 0.15s;
}

.btn-edit {
  border-color: #e2e8f0;
  background-color: #ffffff;
  color: #475569;
}

.btn-edit:hover {
  background-color: #f8fafc;
}

.btn-lock {
  border-color: #e2e8f0;
  background-color: #ffffff;
  color: #64748b;
}

.btn-lock:hover {
  background-color: #f1f5f9;
}

.btn-unlock {
  border-color: #bbf7d0;
  background-color: #f0fdf4;
  color: #16a34a;
}

.btn-unlock:hover {
  background-color: #dcfce7;
}
</style>
