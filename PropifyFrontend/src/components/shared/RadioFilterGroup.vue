<template>
  <div class="flex flex-col gap-3">
    <!-- Highlight selected pill -->
    <div v-if="usePillSkin" class="flex flex-wrap gap-2">
      <label 
        v-for="option in options" 
        :key="option.value"
        class="cursor-pointer relative"
      >
        <input 
          type="radio" 
          :name="name" 
          :value="option.value" 
          :checked="modelValue === option.value"
          @change="$emit('update:modelValue', option.value)"
          class="peer sr-only"
        />
        <div class="px-4 py-2 rounded-xl text-sm font-medium transition-colors"
             :class="modelValue === option.value ? 'bg-blue-50 text-blue-600 border border-blue-200' : 'bg-gray-50 text-gray-600 border border-transparent hover:bg-gray-100'">
          {{ option.label }}
        </div>
      </label>
    </div>

    <!-- Standard Radio List -->
    <div v-else class="flex flex-col space-y-3">
      <label 
        v-for="option in options" 
        :key="option.value"
        class="flex items-center gap-3 cursor-pointer group"
      >
        <div class="relative flex items-center justify-center">
          <input 
            type="radio" 
            :name="name" 
            :value="option.value" 
            :checked="modelValue === option.value"
            @change="$emit('update:modelValue', option.value)"
            class="peer sr-only"
          />
          <div class="w-4 h-4 rounded-full border border-gray-300 group-hover:border-blue-400 peer-checked:border-blue-500 peer-checked:bg-white transition-colors"></div>
          <!-- Inner dot -->
          <div class="absolute w-2 h-2 rounded-full bg-blue-500 scale-0 peer-checked:scale-100 transition-transform origin-center"></div>
        </div>
        <span class="text-[14px] text-gray-700 group-hover:text-gray-900" :class="{'font-medium text-blue-600': modelValue === option.value}">
          {{ option.label }}
        </span>
      </label>
    </div>

    <!-- Expand button if active -->
    <button v-if="showExpand" class="text-xs font-semibold text-blue-500 hover:text-blue-600 flex items-center gap-1 mt-2 w-fit">
      Hiển thị thêm
      <ChevronDown class="w-3 h-3" />
    </button>
  </div>
</template>

<script setup>
import { ChevronDown } from 'lucide-vue-next';

defineProps({
  modelValue: { type: [String, Number], required: true },
  name: { type: String, required: true },
  options: { type: Array, required: true },
  usePillSkin: { type: Boolean, default: false },
  showExpand: { type: Boolean, default: false }
});

defineEmits(['update:modelValue']);
</script>
