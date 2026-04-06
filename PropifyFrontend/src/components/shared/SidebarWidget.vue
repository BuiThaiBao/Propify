<template>
  <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden mb-6">
    <div v-if="title" class="px-5 py-4 flex items-center gap-2 border-b border-gray-50 bg-gray-50/50">
      <component :is="icon" v-if="icon" class="w-4 h-4 text-blue-500" />
      <h3 class="font-bold text-gray-800 text-[15px]">{{ title }}</h3>
    </div>
    <div class="p-5">
      <slot />
    </div>
  </div>
</template>

<script setup>
const props = defineProps({
  title: {
    type: String,
    default: ''
  },
  icon: {
    type: [Object, Function],
    default: null
  }
});
</script>
