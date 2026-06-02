<template>
  <div class="relative" ref="dropdownRef">
    <button
      @click="isOpen = !isOpen"
      class="sort-trigger"
      :class="{ 'sort-trigger--active': isOpen }"
    >
      <span class="sort-trigger__label">{{ currentLabel }}</span>
      <ChevronDown
        class="sort-trigger__icon"
        :class="{ 'sort-trigger__icon--rotated': isOpen }"
      />
    </button>

    <Transition name="sort-dropdown">
      <div v-if="isOpen" class="sort-menu">
        <button
          v-for="option in options"
          :key="option.value"
          class="sort-menu__item"
          :class="{ 'sort-menu__item--active': modelValue === option.value }"
          @click="selectOption(option.value)"
        >
          <span>{{ option.label }}</span>
          <svg
            v-if="modelValue === option.value"
            xmlns="http://www.w3.org/2000/svg"
            class="sort-menu__check"
            viewBox="0 0 24 24"
            fill="none"
            stroke="currentColor"
            stroke-width="2.5"
            stroke-linecap="round"
            stroke-linejoin="round"
          >
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </button>
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

const props = defineProps({
  modelValue: { type: String, default: '' },
  options: {
    type: Array,
    default: () => [
      { value: '',          label: 'Thông thường' },
      { value: 'newest',    label: 'Tin đăng mới nhất' },
      { value: 'oldest',    label: 'Tin đăng cũ nhất' },
      { value: 'price_asc', label: 'Giá tăng dần' },
      { value: 'price_desc',label: 'Giá giảm dần' },
      { value: 'area_asc',  label: 'Diện tích tăng dần' },
      { value: 'area_desc', label: 'Diện tích giảm dần' },
    ],
  },
});

const emit = defineEmits(['update:modelValue']);

const isOpen = ref(false);
const dropdownRef = ref(null);

const currentLabel = computed(() => {
  const match = props.options.find((o) => o.value === props.modelValue);
  return match ? match.label : 'Thông thường';
});

function selectOption(value) {
  emit('update:modelValue', value);
  isOpen.value = false;
}

function handleClickOutside(event) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
    isOpen.value = false;
  }
}

onMounted(() => document.addEventListener('click', handleClickOutside));
onBeforeUnmount(() => document.removeEventListener('click', handleClickOutside));
</script>

<style scoped>
.sort-trigger {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border-radius: 12px;
  border: 1px solid #e5e7eb;
  background: #fff;
  font-size: 13px;
  font-weight: 500;
  color: #374151;
  cursor: pointer;
  transition: all 0.2s ease;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.04);
  white-space: nowrap;
  user-select: none;
}

.sort-trigger:hover {
  border-color: #93c5fd;
  background: #f0f9ff;
}

.sort-trigger--active {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.sort-trigger__label {
  overflow: hidden;
  text-overflow: ellipsis;
}

.sort-trigger__icon {
  width: 16px;
  height: 16px;
  color: #9ca3af;
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
  flex-shrink: 0;
}

.sort-trigger__icon--rotated {
  transform: rotate(180deg);
  color: #3b82f6;
}

.sort-menu {
  position: absolute;
  top: calc(100% + 6px);
  right: 0;
  z-index: 50;
  min-width: 220px;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 14px;
  box-shadow:
    0 10px 25px -5px rgba(0, 0, 0, 0.08),
    0 4px 10px -3px rgba(0, 0, 0, 0.04);
  padding: 6px;
  overflow: hidden;
}

.sort-menu__item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  width: 100%;
  padding: 10px 14px;
  border: none;
  background: transparent;
  font-size: 13.5px;
  color: #374151;
  cursor: pointer;
  border-radius: 10px;
  transition: all 0.15s ease;
  text-align: left;
}

.sort-menu__item:hover {
  background: #f0f9ff;
  color: #1d4ed8;
}

.sort-menu__item--active {
  color: #2563eb;
  font-weight: 600;
  background: #eff6ff;
}

.sort-menu__check {
  width: 18px;
  height: 18px;
  color: #2563eb;
  flex-shrink: 0;
}

/* Transitions */
.sort-dropdown-enter-active {
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
}

.sort-dropdown-leave-active {
  transition: all 0.15s ease-in;
}

.sort-dropdown-enter-from {
  opacity: 0;
  transform: translateY(-8px) scale(0.96);
}

.sort-dropdown-leave-to {
  opacity: 0;
  transform: translateY(-4px) scale(0.98);
}
</style>
