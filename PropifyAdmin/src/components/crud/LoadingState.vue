<template>
  <div class="flex flex-col items-center justify-center gap-3 px-6 py-12">
    <div
      class="h-8 w-8 animate-spin rounded-full border-3"
      :class="message ? 'border-border border-t-primary' : 'border-border border-t-primary'"
    />
    <p v-if="message" class="text-sm text-muted-foreground">{{ message }}</p>
  </div>
</template>

<script setup>
defineProps({
  message: { type: String, default: '' },
})
</script>
