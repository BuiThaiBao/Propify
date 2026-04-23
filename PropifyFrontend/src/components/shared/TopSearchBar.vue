<template>
  <!-- Spacer giữ layout khi bar fixed -->
  <div class="h-16"></div>

  <!-- Search bar: fixed, trượt lên/xuống bằng translateY -->
  <div
    class="fixed left-0 right-0 z-40 bg-white border-b border-gray-100 shadow-sm transition-transform duration-300 ease-in-out"
    :style="{ top: '64px', transform: visible ? 'translateY(0)' : 'translateY(-100%)' }"
  >
    <div class="max-w-7xl mx-auto px-4 md:px-8">
      <div class="flex items-center h-16 divide-x divide-gray-100">
        <!-- Location Dropdown -->
        <div class="flex items-center gap-2 pr-4 cursor-pointer hover:text-blue-600 text-gray-700">
          <MapPin class="w-4 h-4 text-blue-500" />
          <span class="text-sm font-medium">Tất cả</span>
          <ChevronDown class="w-4 h-4 text-gray-400" />
        </div>

        <!-- Search Input -->
        <div class="relative flex-1 flex items-center px-4">
          <Search class="w-5 h-5 text-gray-400 mr-2" />
          <input
            type="text"
            :value="modelValue"
            :placeholder="placeholder"
            class="w-full bg-transparent border-none outline-none text-sm text-gray-700 placeholder-gray-400"
            @input="onInput"
            @keydown.enter.prevent="onSearch"
            @focus="showSuggestions = true"
            @blur="hideSuggestionsWithDelay"
          />

          <div
            v-if="showSuggestionList"
            class="absolute left-4 right-4 top-[calc(100%+8px)] z-50 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl"
          >
            <button
              v-for="item in suggestions"
              :key="item"
              type="button"
              class="flex w-full items-center gap-2 px-3 py-2 text-left text-sm text-slate-700 transition hover:bg-slate-50"
              @mousedown.prevent="selectSuggestion(item)"
            >
              <MapPin class="h-4 w-4 shrink-0 text-blue-500" />
              <span class="line-clamp-1">{{ item }}</span>
            </button>
          </div>
        </div>

        <!-- Search Button -->
        <div class="px-4">
          <button class="bg-blue-500 hover:bg-blue-600 text-white rounded-full px-6 py-2 text-sm font-semibold flex items-center gap-2 transition-colors" @click="onSearch">
            <Search class="w-4 h-4" />
            Tìm kiếm
          </button>
        </div>

        <!-- Property Type Dropdown -->
        <div class="flex items-center gap-2 pl-4 cursor-pointer hover:text-blue-600 text-gray-700">
          <Home class="w-4 h-4 text-gray-400" />
          <span class="text-sm font-medium">Loại hình</span>
          <ChevronDown class="w-4 h-4 text-gray-400" />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { MapPin, ChevronDown, Search, Home } from 'lucide-vue-next';

const props = defineProps({
  modelValue: { type: String, default: '' },
  placeholder: { type: String, default: 'Tìm kiếm theo tên, địa chỉ, dự án...' },
  suggestions: { type: Array, default: () => [] },
});

const emit = defineEmits(['update:modelValue', 'search', 'select-suggestion']);

const visible = ref(true);
const showSuggestions = ref(false);
let lastScrollY = 0;
let hideTimer = null;
const THRESHOLD = 5; // px - tránh false positive từ micro-scroll

const showSuggestionList = computed(() => {
  return showSuggestions.value && Array.isArray(props.suggestions) && props.suggestions.length > 0;
});

function onInput(event) {
  emit('update:modelValue', event.target.value || '');
  showSuggestions.value = true;
}

function onSearch() {
  emit('search', props.modelValue || '');
  showSuggestions.value = false;
}

function selectSuggestion(value) {
  emit('update:modelValue', value);
  emit('select-suggestion', value);
  showSuggestions.value = false;
}

function hideSuggestionsWithDelay() {
  setTimeout(() => {
    showSuggestions.value = false;
  }, 120);
}

function resetHideTimer() {
  clearTimeout(hideTimer);
  hideTimer = setTimeout(() => {
    visible.value = false;
  }, 2000);
}

function handleScroll() {
  const currentY = window.scrollY;
  const delta = currentY - lastScrollY;

  // Bỏ qua micro-scroll nhỏ hơn threshold
  if (Math.abs(delta) < THRESHOLD) return;

  if (currentY <= 10) {
    // Đầu trang → luôn hiện, hủy timer
    visible.value = true;
    clearTimeout(hideTimer);
  } else if (delta < 0) {
    // Cuộn lên → hiện + đặt timer tự ẩn
    visible.value = true;
    resetHideTimer();
  } else {
    // Cuộn xuống → ẩn ngay, hủy timer
    visible.value = false;
    clearTimeout(hideTimer);
  }

  lastScrollY = currentY;
}

onMounted(() => {
  lastScrollY = window.scrollY;
  window.addEventListener('scroll', handleScroll, { passive: true });
});

onUnmounted(() => {
  window.removeEventListener('scroll', handleScroll);
  clearTimeout(hideTimer);
});
</script>

