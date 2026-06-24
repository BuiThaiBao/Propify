<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref, watch } from 'vue'
import { ChevronDown, Search } from 'lucide-vue-next'

const props = defineProps({
  search: { type: String, default: '' },
  searchField: { type: String, default: 'title' },
  status: { type: String, default: 'all' },
  type: { type: String, default: 'all' },
  minPrice: { type: [Number, String], default: null },
  maxPrice: { type: [Number, String], default: null },
  packageId: { type: [String, Number], default: 'all' },
  packages: { type: Array, default: () => [] },
  statusCounts: { type: Object, default: () => ({}) },
})

const emit = defineEmits([
  'update:search',
  'update:searchField',
  'update:status',
  'update:type',
  'update:minPrice',
  'update:maxPrice',
  'update:packageId',
])

const PRICE_LIMIT = 75000000000

const searchFieldOptions = [
  { value: 'title', label: 'Tên tin đăng' },
  { value: 'owner', label: 'Người đăng' },
  { value: 'address', label: 'Địa chỉ' },
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
const draftMinPrice = ref('')
const draftMaxPrice = ref('')
const priceTrigger = ref(null)
const priceMenuStyle = ref({})

const packageOptions = computed(() => [
  { value: 'all', label: 'Gói tin: Tất cả' },
  ...props.packages.map((pkg) => ({
    value: String(pkg.id),
    label: pkg.name,
  })),
])

const normalizedMinPrice = computed(() => normalizePrice(props.minPrice))
const normalizedMaxPrice = computed(() => normalizePrice(props.maxPrice))

const priceLabel = computed(() => {
  const min = normalizedMinPrice.value
  const max = normalizedMaxPrice.value

  if (min === null && max === null) return 'Khoảng giá: Tất cả'
  if (min !== null && max !== null) return `${formatCompactPrice(min)} - ${formatCompactPrice(max)}`
  if (min !== null) return `Từ ${formatCompactPrice(min)}`
  return `Đến ${formatCompactPrice(max)}`
})

const draftMinPercent = computed(() => ((normalizePrice(draftMinPrice.value) ?? 0) / PRICE_LIMIT) * 100)
const draftMaxPercent = computed(() => ((normalizePrice(draftMaxPrice.value) ?? PRICE_LIMIT) / PRICE_LIMIT) * 100)

function normalizePrice(value) {
  if (value === null || value === undefined || value === '') return null
  const numberValue = Number(value)
  if (!Number.isFinite(numberValue) || numberValue < 0) return null
  return Math.min(Math.round(numberValue), PRICE_LIMIT)
}

function formatCurrency(value) {
  return `${Number(value || 0).toLocaleString('vi-VN')} VND`
}

function formatCompactPrice(value) {
  const numberValue = Number(value || 0)
  if (numberValue >= 1000000000) {
    return `${Number((numberValue / 1000000000).toFixed(1)).toLocaleString('vi-VN')} tỷ`
  }
  if (numberValue >= 1000000) {
    return `${Number((numberValue / 1000000).toFixed(0)).toLocaleString('vi-VN')} triệu`
  }
  return formatCurrency(numberValue)
}

function selectedLabel(options, value) {
  return options.find((option) => option.value === String(value))?.label || options[0]?.label || ''
}

function updatePriceMenuPosition() {
  if (openDropdown.value !== 'price' || !priceTrigger.value) return

  const rect = priceTrigger.value.getBoundingClientRect()
  const width = Math.min(390, window.innerWidth - 32)
  const left = Math.min(Math.max(rect.left, 16), window.innerWidth - width - 16)

  priceMenuStyle.value = {
    position: 'fixed',
    top: `${rect.bottom + 8}px`,
    left: `${left}px`,
    width: `${width}px`,
  }
}

function toggleDropdown(name) {
  openDropdown.value = openDropdown.value === name ? null : name
  if (openDropdown.value === 'price') {
    draftMinPrice.value = normalizedMinPrice.value ?? ''
    draftMaxPrice.value = normalizedMaxPrice.value ?? ''
    nextTick(updatePriceMenuPosition)
  }
}

function selectDropdown(eventName, value) {
  emit(eventName, value)
  openDropdown.value = null
}

function statusCount(value) {
  return Number(props.statusCounts?.[value] ?? 0)
}

function setDraftPrice(target, value) {
  const normalized = normalizePrice(value)
  const nextValue = normalized === null ? '' : normalized

  if (target === 'min') {
    draftMinPrice.value = nextValue
    if (draftMaxPrice.value !== '' && normalizePrice(draftMaxPrice.value) !== null && Number(draftMinPrice.value) > Number(draftMaxPrice.value)) {
      draftMaxPrice.value = draftMinPrice.value
    }
    return
  }

  draftMaxPrice.value = nextValue
  if (draftMinPrice.value !== '' && normalizePrice(draftMinPrice.value) !== null && Number(draftMaxPrice.value) < Number(draftMinPrice.value)) {
    draftMinPrice.value = draftMaxPrice.value
  }
}

function resetPrice() {
  draftMinPrice.value = ''
  draftMaxPrice.value = ''
  emit('update:minPrice', null)
  emit('update:maxPrice', null)
  openDropdown.value = null
}

function applyPrice() {
  emit('update:minPrice', normalizePrice(draftMinPrice.value))
  emit('update:maxPrice', normalizePrice(draftMaxPrice.value))
  openDropdown.value = null
}

watch(
  () => [props.minPrice, props.maxPrice],
  () => {
    if (openDropdown.value !== 'price') return
    draftMinPrice.value = normalizedMinPrice.value ?? ''
    draftMaxPrice.value = normalizedMaxPrice.value ?? ''
    nextTick(updatePriceMenuPosition)
  },
)

onMounted(() => {
  window.addEventListener('resize', updatePriceMenuPosition)
  window.addEventListener('scroll', updatePriceMenuPosition, true)
})

onUnmounted(() => {
  window.removeEventListener('resize', updatePriceMenuPosition)
  window.removeEventListener('scroll', updatePriceMenuPosition, true)
})
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
        <div class="custom-select price-select" :class="{ open: openDropdown === 'price' }">
          <button ref="priceTrigger" type="button" class="filter-trigger" @click="toggleDropdown('price')">
            <span>{{ priceLabel }}</span>
            <ChevronDown :size="17" />
          </button>
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

    <Teleport to="body">
      <div v-if="openDropdown === 'price'" class="price-menu" :style="priceMenuStyle">
        <div class="price-input-grid">
          <label class="price-field">
            <span>Từ</span>
            <input
              :value="draftMinPrice"
              type="number"
              min="0"
              :max="PRICE_LIMIT"
              step="1000000"
              placeholder="Nhập số tiền"
              @input="setDraftPrice('min', $event.target.value)"
            />
          </label>
          <label class="price-field">
            <span>Đến</span>
            <input
              :value="draftMaxPrice"
              type="number"
              min="0"
              :max="PRICE_LIMIT"
              step="1000000"
              placeholder="Nhập số tiền"
              @input="setDraftPrice('max', $event.target.value)"
            />
          </label>
        </div>

        <div class="range-wrap">
          <div class="range-track"></div>
          <div
            class="range-fill"
            :style="{ left: `${draftMinPercent}%`, right: `${100 - draftMaxPercent}%` }"
          ></div>
          <input
            :value="normalizePrice(draftMinPrice) ?? 0"
            class="range-input"
            type="range"
            min="0"
            :max="PRICE_LIMIT"
            step="1000000"
            @input="setDraftPrice('min', $event.target.value)"
          />
          <input
            :value="normalizePrice(draftMaxPrice) ?? PRICE_LIMIT"
            class="range-input"
            type="range"
            min="0"
            :max="PRICE_LIMIT"
            step="1000000"
            @input="setDraftPrice('max', $event.target.value)"
          />
        </div>

        <div class="price-scale">
          <span>{{ formatCurrency(0) }}</span>
          <span>{{ formatCurrency(PRICE_LIMIT) }}</span>
        </div>

        <div class="price-actions">
          <button type="button" class="price-reset" @click="resetPrice">Đặt lại</button>
          <button type="button" class="price-apply" @click="applyPrice">Đồng ý</button>
        </div>
      </div>
    </Teleport>

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
  appearance: none;
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

.price-select {
  min-width: 228px;
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
  line-height: 1.35;
  cursor: pointer;
  transition:
    border-color 0.16s ease,
    box-shadow 0.16s ease,
    background 0.16s ease;
}

.filter-trigger span {
  display: inline-flex;
  align-items: center;
  min-height: 18px;
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

.price-menu {
  z-index: 1000;
  padding: 18px 22px 0;
  border: 1px solid #dbe3ef;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 18px 40px rgba(15, 23, 42, 0.16);
}

.price-menu::before {
  content: '';
  position: absolute;
  top: -7px;
  left: 14px;
  width: 14px;
  height: 14px;
  border-top: 1px solid #dbe3ef;
  border-left: 1px solid #dbe3ef;
  background: #fff;
  transform: rotate(45deg);
}

.price-input-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 26px;
}

.price-field {
  display: grid;
  gap: 8px;
  color: #172554;
  font-size: 13px;
}

.price-field input {
  width: 100%;
  height: 34px;
  padding: 0 12px;
  border: 1px solid #dbe3ef;
  border-radius: 8px;
  color: #0f172a;
  font-size: 13px;
  outline: none;
}

.price-field input:focus {
  border-color: #93c5fd;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}

.range-wrap {
  position: relative;
  height: 18px;
}

.range-track,
.range-fill {
  position: absolute;
  top: 7px;
  height: 4px;
  border-radius: 999px;
}

.range-track {
  left: 0;
  right: 0;
  background: #e2e8f0;
}

.range-fill {
  background: #172554;
}

.range-input {
  position: absolute;
  inset: 0;
  width: 100%;
  margin: 0;
  background: transparent;
  appearance: none;
  pointer-events: none;
}

.range-input::-webkit-slider-thumb {
  width: 15px;
  height: 15px;
  border: 0;
  border-radius: 999px;
  background: #172554;
  cursor: pointer;
  appearance: none;
  pointer-events: auto;
}

.range-input::-moz-range-thumb {
  width: 15px;
  height: 15px;
  border: 0;
  border-radius: 999px;
  background: #172554;
  cursor: pointer;
  pointer-events: auto;
}

.range-input::-webkit-slider-runnable-track {
  background: transparent;
}

.range-input::-moz-range-track {
  background: transparent;
}

.price-scale {
  display: flex;
  justify-content: space-between;
  margin-top: 6px;
  color: #172554;
  font-size: 13px;
}

.price-actions {
  display: flex;
  justify-content: flex-end;
  gap: 18px;
  margin: 20px -22px 0;
  padding: 18px 22px;
  border-top: 1px solid #e8edf5;
}

.price-reset,
.price-apply {
  min-width: 84px;
  height: 40px;
  border: 0;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
}

.price-reset {
  background: transparent;
  color: #2563eb;
}

.price-apply {
  background: #3b82f6;
  color: #fff;
}

.price-reset:hover {
  background: #eff6ff;
}

.price-apply:hover {
  background: #2563eb;
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
  .custom-select,
  .filter-selects {
    width: 100%;
    min-width: 0;
    flex-wrap: wrap;
    margin-left: 0;
  }
}
</style>
