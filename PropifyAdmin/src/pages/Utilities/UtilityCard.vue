<script setup>
import { Edit, Trash2, Eye, EyeOff } from 'lucide-vue-next'

const props = defineProps({
  utility: {
    type: Object,
    required: true,
  },
})

const emit = defineEmits(['toggle', 'delete'])
</script>

<template>
  <div class="utility-card">
    <!-- Visibility indicator -->
    <div class="card-top">
      <span class="visibility-icon" :class="{ active: utility.visible }">
        <Eye v-if="utility.visible" :size="14" />
        <EyeOff v-else :size="14" />
      </span>
    </div>

    <!-- Icon + Info -->
    <div class="utility-icon">{{ utility.icon }}</div>
    <h3 class="utility-name">{{ utility.name }}</h3>
    <p class="utility-desc">{{ utility.description }}</p>

    <!-- Actions -->
    <div class="utility-actions">
      <button class="btn-edit" :id="`edit-util-${utility.id}`">
        <Edit :size="14" />
        Sửa
      </button>
      <button
        class="btn-delete"
        :id="`delete-util-${utility.id}`"
        @click="emit('delete')"
      >
        <Trash2 :size="14" />
      </button>
    </div>
  </div>
</template>

<style scoped>
.utility-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  padding: 20px;
  position: relative;
  transition: box-shadow 0.2s, transform 0.2s;
}

.utility-card:hover {
  box-shadow: 0 6px 20px rgba(0, 0, 0, 0.07);
  transform: translateY(-2px);
}

.card-top {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 12px;
}

.visibility-icon {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  background-color: #f1f5f9;
  color: #94a3b8;
  cursor: pointer;
  transition: all 0.15s;
}

.visibility-icon.active {
  background-color: #d1fae5;
  color: #10b981;
}

.utility-icon {
  font-size: 32px;
  margin-bottom: 10px;
  line-height: 1;
}

.utility-name {
  font-size: 16px;
  font-weight: 700;
  color: #0f172a;
  margin: 0 0 4px 0;
}

.utility-desc {
  font-size: 13px;
  color: #94a3b8;
  margin: 0 0 18px 0;
}

.utility-actions {
  display: flex;
  align-items: center;
  gap: 8px;
}

.btn-edit {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 6px;
  padding: 8px 0;
  border: 1.5px solid #e2e8f0;
  border-radius: 7px;
  font-size: 13px;
  font-weight: 600;
  color: #475569;
  background-color: #ffffff;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-edit:hover {
  background-color: #f8fafc;
}

.btn-delete {
  width: 36px;
  height: 36px;
  border: 1.5px solid #fee2e2;
  border-radius: 7px;
  background-color: #fff;
  color: #ef4444;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: background-color 0.15s;
  flex-shrink: 0;
}

.btn-delete:hover {
  background-color: #fee2e2;
}
</style>
