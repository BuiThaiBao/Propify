<script setup>
defineProps({
  title: String,
  description: String,
})
</script>

<template>
  <div class="mb-6 flex items-center justify-between">
    <div>
      <h1 class="m-0 text-2xl font-bold leading-[1.3] text-foreground">{{ title }}</h1>
      <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
    </div>
    <div v-if="$slots.actions" class="flex items-center gap-3">
      <slot name="actions" />
    </div>
  </div>
</template>
