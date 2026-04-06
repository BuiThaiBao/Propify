<template>
  <div class="bg-blue-50 rounded-2xl p-6 flex flex-col items-center justify-center text-center cursor-pointer border border-blue-100 hover:shadow-md transition-shadow group mb-6">
    <div class="w-12 h-12 bg-white rounded-full shadow-sm flex items-center justify-center mb-3 group-hover:scale-110 transition-transform">
      <MapIcon class="w-5 h-5 text-blue-500" />
    </div>
    <h3 class="font-bold text-blue-600 text-[15px] mb-1">Tìm kiếm theo bản đồ</h3>
    <p class="text-xs text-blue-400 font-medium">Xem vị trí trên bản đồ</p>
  </div>
</template>

<script setup>
import { MapIcon } from 'lucide-vue-next';
</script>
