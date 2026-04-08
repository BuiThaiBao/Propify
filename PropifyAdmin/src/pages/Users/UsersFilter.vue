<script setup>
import { Search, Filter } from 'lucide-vue-next'

const props = defineProps({
  search: String,
  role: String,
})

const emit = defineEmits(['update:search', 'update:role'])

const roleOptions = [
  { value: 'all', label: 'Tất cả vai trò' },
  { value: 'agent', label: 'Môi giới' },
  { value: 'user', label: 'Người dùng' },
]
</script>

<template>
  <div class="filter-bar">
    <div class="filter-search">
      <Search :size="16" class="search-icon" />
      <input
        :value="search"
        @input="emit('update:search', $event.target.value)"
        type="text"
        placeholder="Tìm kiếm tài khoản..."
        class="filter-input"
        id="user-search"
      />
    </div>
    <div class="filter-right">
      <Filter :size="16" class="text-slate-400" />
      <select
        :value="role"
        @change="emit('update:role', $event.target.value)"
        class="filter-select"
        id="role-filter"
      >
        <option v-for="opt in roleOptions" :key="opt.value" :value="opt.value">
          {{ opt.label }}
        </option>
      </select>
    </div>
  </div>
</template>

<style scoped>
.filter-bar {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 16px;
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  padding: 12px 16px;
}

.filter-search {
  flex: 1;
  position: relative;
}

.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
}

.filter-input {
  width: 100%;
  height: 36px;
  padding: 0 10px 0 34px;
  border: 1px solid #e2e8f0;
  border-radius: 7px;
  font-size: 13px;
  color: #0f172a;
  outline: none;
  background-color: #f8fafc;
}

.filter-input::placeholder {
  color: #94a3b8;
}

.filter-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}

.filter-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

.filter-select {
  height: 36px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 7px;
  font-size: 13px;
  color: #64748b;
  background-color: #f8fafc;
  outline: none;
  cursor: pointer;
}

.filter-select:focus {
  border-color: #2563eb;
}
</style>
