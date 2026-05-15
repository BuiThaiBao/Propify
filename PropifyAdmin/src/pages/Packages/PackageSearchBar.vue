<script setup>
import { Search } from 'lucide-vue-next'

const props = defineProps({
  modelValue: String,
})

const emit = defineEmits(['update:modelValue'])
</script>

<template>
  <div class="relative mb-5 max-w-[360px]">
    <Search :size="16" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
    <input
      :value="modelValue"
      @input="emit('update:modelValue', $event.target.value)"
      type="text"
      placeholder="Tìm kiếm gói tin..."
      class="h-10 w-full rounded-lg border border-slate-200 bg-white px-3 pl-9 text-sm text-slate-900 outline-none placeholder:text-slate-400 focus:border-blue-600 focus:ring-4 focus:ring-blue-600/10"
      id="package-search"
    />
  </div>
</template>
