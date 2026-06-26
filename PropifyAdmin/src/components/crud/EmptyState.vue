<template>
  <div class="flex flex-col items-center justify-center gap-3 px-6 py-12">
    <slot name="icon">
      <svg
        class="h-12 w-12"
        :class="iconClass"
        xmlns="http://www.w3.org/2000/svg"
        fill="none"
        viewBox="0 0 24 24"
        stroke="currentColor"
        stroke-width="1"
      >
        <path stroke-linecap="round" stroke-linejoin="round" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
      </svg>
    </slot>
    <p class="text-sm text-muted-foreground">{{ message }}</p>
    <slot name="action" />
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: 'Không có dữ liệu.' },
  iconClass: { type: String, default: 'text-muted-foreground/30' },
})
</script>
