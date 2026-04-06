<template>
  <div class="bg-slate-50 p-1 rounded-xl flex">
    <button 
      v-for="tab in tabs" 
      :key="tab.value"
      @click="$emit('update:modelValue', tab.value)"
      :class="[
        'flex-1 py-1.5 px-3 text-[13px] font-semibold rounded-lg transition-all duration-200 ease-in-out',
        modelValue === tab.value 
          ? 'bg-white text-blue-600 shadow-sm ring-1 ring-gray-100' 
          : 'text-gray-500 hover:text-gray-700 hover:bg-slate-100'
      ]"
    >
      {{ tab.label }}
    </button>
  </div>
</template>

<script setup>
defineProps({
  modelValue: { type: String, required: true },
  tabs: { 
    type: Array, 
    required: true,
    // Example: [{ label: 'Tất cả', value: 'all' }, ...]
  }
});
defineEmits(['update:modelValue']);
</script>
