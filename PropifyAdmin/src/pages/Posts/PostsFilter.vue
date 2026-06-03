<script setup>
import { computed, ref } from 'vue'
import { ChevronDown, Search } from 'lucide-vue-next'

const props = defineProps({
  search: { type: String, default: '' },
  searchField: { type: String, default: 'title' },
  status: { type: String, default: 'all' },
  type: { type: String, default: 'all' },
  priceRange: { type: String, default: 'all' },
  packageId: { type: [String, Number], default: 'all' },
  packages: { type: Array, default: () => [] },
  statusCounts: { type: Object, default: () => ({}) },
})

const emit = defineEmits([
  'update:search',
  'update:searchField',
  'update:status',
  'update:type',
  'update:priceRange',
  'update:packageId',
])

const searchFieldOptions = [
  { value: 'title', label: 'Tên tin đăng' },
  { value: 'owner', label: 'Người đăng' },
  { value: 'address', label: 'Địa chỉ' },
]

const priceOptions = [
  { value: 'all', label: 'Khoảng giá: Tất cả' },
  { value: 'under_1b', label: 'Dưới 1 tỷ' },
  { value: '1b_3b', label: '1 - 3 tỷ' },
  { value: '3b_5b', label: '3 - 5 tỷ' },
  { value: '5b_10b', label: '5 - 10 tỷ' },
  { value: 'over_10b', label: 'Trên 10 tỷ' },
]

const typeOptions = [
  { value: 'all', label: 'Loại tin : Tất cả' },
  { value: 'sale', label: 'Mua bán' },
  { value: 'rent', label: 'Cho thuê' },
]

const statusOptions = [
  { value: 'all', label: 'Tất cả', dot: null },
  { value: 'pending', label: 'Chờ duyệt', dot: '#f97316' },
  { value: 'approved', label: 'Tin đang đăng', dot: '#10b981' },
  { value: 'rejected', label: 'Từ chối', dot: '#ef4444' },
  { value: 'locked', label: 'Tin bị khóa', dot: '#ef4444' },
]

const openDropdown = ref(null)

const packageOptions = computed(() => [
  { value: 'all', label: 'Gói tin: Tất cả' },
  ...props.packages.map((pkg) => ({
    value: String(pkg.id),
    label: pkg.name,
  })),
])

function selectedLabel(options, value) {
  return options.find((option) => option.value === String(value))?.label || options[0]?.label || ''
}

function toggleDropdown(name) {
  openDropdown.value = openDropdown.value === name ? null : name
}

function selectDropdown(eventName, value) {
  emit(eventName, value)
  openDropdown.value = null
}

function statusCount(value) {
  return Number(props.statusCounts?.[value] ?? 0)
}
</script>

<template>
  <section class="posts-filter">
    <div class="filter-main">
      <div class="search-control">
        <select
          :value="searchField"
          class="field-select"
          @change="emit('update:searchField', $event.target.value)"
        >
          <option v-for="option in searchFieldOptions" :key="option.value" :value="option.value">
            {{ option.label }}
          </option>
        </select>
        <div class="search-input-wrap">
          <input
            :value="search"
            type="text"
            placeholder="Nhập thông tin"
            class="search-input"
            @input="emit('update:search', $event.target.value)"
          />
          <Search :size="22" class="search-icon" />
        </div>
      </div>

      <div class="filter-selects">
        <div class="custom-select" :class="{ open: openDropdown === 'price' }">
          <button type="button" class="filter-trigger" @click="toggleDropdown('price')">
            <span>{{ selectedLabel(priceOptions, priceRange) }}</span>
            <ChevronDown :size="17" />
          </button>
          <div v-if="openDropdown === 'price'" class="select-menu">
            <button
              v-for="option in priceOptions"
              :key="option.value"
              type="button"
              class="select-option"
              :class="{ selected: priceRange === option.value }"
              @click="selectDropdown('update:priceRange', option.value)"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <div class="custom-select" :class="{ open: openDropdown === 'type' }">
          <button type="button" class="filter-trigger" @click="toggleDropdown('type')">
            <span>{{ selectedLabel(typeOptions, type) }}</span>
            <ChevronDown :size="17" />
          </button>
          <div v-if="openDropdown === 'type'" class="select-menu">
            <button
              v-for="option in typeOptions"
              :key="option.value"
              type="button"
              class="select-option"
              :class="{ selected: type === option.value }"
              @click="selectDropdown('update:type', option.value)"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

        <div class="custom-select" :class="{ open: openDropdown === 'package' }">
          <button type="button" class="filter-trigger" @click="toggleDropdown('package')">
            <span>{{ selectedLabel(packageOptions, packageId) }}</span>
            <ChevronDown :size="17" />
          </button>
          <div v-if="openDropdown === 'package'" class="select-menu">
            <button
              v-for="option in packageOptions"
              :key="option.value"
              type="button"
              class="select-option"
              :class="{ selected: String(packageId) === option.value }"
              @click="selectDropdown('update:packageId', option.value)"
            >
              {{ option.label }}
            </button>
          </div>
        </div>

      </div>
    </div>

    <div class="status-tabs">
      <button
        v-for="option in statusOptions"
        :key="option.value"
        type="button"
        class="status-tab"
        :class="{ active: status === option.value }"
        @click="emit('update:status', option.value)"
      >
        <span v-if="option.dot" class="status-dot" :style="{ background: option.dot }"></span>
        {{ option.label }} <span class="status-count">{{ statusCount(option.value) }}</span>
      </button>
    </div>
  </section>
</template>

<style scoped>
.posts-filter {
  margin-bottom: 16px;
}

.filter-main {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 12px 14px;
  border: 1px solid #e8edf5;
  border-radius: 12px;
  background: #ffffff;
  box-shadow: 0 10px 26px rgba(15, 23, 42, 0.06);
}

.search-control {
  min-width: 380px;
  display: flex;
  align-items: center;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  background: #fff;
  overflow: hidden;
}

.field-select {
  width: 130px;
  height: 38px;
  padding: 0 30px 0 12px;
  border: 0;
  border-right: 1px solid #e2e8f0;
  background: #fff;
  color: #1e3a5f;
  font-size: 13px;
  outline: none;
  cursor: pointer;
}

.search-input-wrap {
  min-width: 0;
  flex: 1;
  position: relative;
}

.search-input {
  width: 100%;
  height: 38px;
  border: 0;
  padding: 0 42px 0 12px;
  color: #0f172a;
  font-size: 13px;
  outline: none;
}

.search-input::placeholder {
  color: #94a3b8;
}

.search-icon {
  position: absolute;
  top: 50%;
  right: 12px;
  transform: translateY(-50%);
  color: #172554;
}

.filter-selects {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-left: auto;
}

.custom-select {
  position: relative;
  min-width: 158px;
}

.filter-trigger {
  width: 100%;
  height: 38px;
  display: inline-flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 0 11px 0 13px;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  background: #fff;
  color: #1e3a5f;
  font-size: 13px;
  line-height: 1;
  cursor: pointer;
  transition: border-color 0.16s ease, box-shadow 0.16s ease, background 0.16s ease;
}

.filter-trigger span {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.filter-trigger svg {
  flex: 0 0 auto;
  color: #172554;
  transition: transform 0.16s ease;
}

.custom-select.open .filter-trigger,
.filter-trigger:hover {
  border-color: #93c5fd;
  background: #f8fbff;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.custom-select.open .filter-trigger svg {
  transform: rotate(180deg);
}

.select-menu {
  position: absolute;
  z-index: 30;
  top: calc(100% + 6px);
  left: 0;
  width: max-content;
  min-width: 100%;
  max-width: 260px;
  max-height: 260px;
  overflow-y: auto;
  padding: 6px;
  border: 1px solid #dbe3ef;
  border-radius: 10px;
  background: #fff;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
}

.select-option {
  width: 100%;
  min-height: 34px;
  display: flex;
  align-items: center;
  padding: 0 10px;
  border: 0;
  border-radius: 7px;
  background: transparent;
  color: #334155;
  font-size: 13px;
  text-align: left;
  white-space: nowrap;
  cursor: pointer;
}

.select-option:hover {
  background: #f1f5f9;
  color: #0f172a;
}

.select-option.selected {
  background: #e0f2fe;
  color: #0369a1;
  font-weight: 700;
}

.filter-select {
  min-width: 158px;
  height: 38px;
  padding: 0 34px 0 12px;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  background: #fff;
  color: #1e3a5f;
  font-size: 13px;
  outline: none;
  cursor: pointer;
}

.field-select,
.filter-select {
  appearance: none;
  background-image: none;
}

.status-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
  margin-top: 10px;
}

.status-tab {
  min-height: 34px;
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 0 14px;
  border: 1px solid #e7edf5;
  border-radius: 999px;
  background: #fff;
  color: #64748b;
  font-size: 13px;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
}

.status-tab.active {
  border-color: #0ea5e9;
  color: #0284c7;
  background: #f0f9ff;
}

.status-dot {
  width: 6px;
  height: 6px;
  border-radius: 999px;
}

.status-count {
  color: inherit;
  font-weight: 700;
}

@media (max-width: 1100px) {
  .filter-main {
    align-items: stretch;
    flex-direction: column;
  }

  .search-control,
  .filter-select,
  .custom-select,
  .filter-selects {
    width: 100%;
    min-width: 0;
    flex-wrap: wrap;
    margin-left: 0;
  }
}
</style>
