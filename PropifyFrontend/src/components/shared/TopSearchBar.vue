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
        <div class="flex-1 flex items-center px-4">
          <Search class="w-5 h-5 text-gray-400 mr-2" />
          <input
            type="text"
            placeholder="Tìm kiếm theo tên, địa chỉ, dự án..."
            class="w-full bg-transparent border-none outline-none text-sm text-gray-700 placeholder-gray-400"
          />
        </div>

        <!-- Search Button -->
        <div class="px-4">
          <button class="bg-blue-500 hover:bg-blue-600 text-white rounded-full px-6 py-2 text-sm font-semibold flex items-center gap-2 transition-colors">
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
import { ref, onMounted, onUnmounted } from 'vue';
import { MapPin, ChevronDown, Search, Home } from 'lucide-vue-next';

const visible = ref(true);
let lastScrollY = 0;
let hideTimer = null;
const THRESHOLD = 5; // px - tránh false positive từ micro-scroll

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

