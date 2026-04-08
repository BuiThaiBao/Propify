<script setup>
defineProps({
  title: String,
  description: String,
})
</script>

<template>
  <div class="page-header">
    <div>
      <h1 class="page-title">{{ title }}</h1>
      <p v-if="description" class="page-desc">{{ description }}</p>
    </div>
    <div v-if="$slots.actions" class="page-actions">
      <slot name="actions" />
    </div>
  </div>
</template>

<style scoped>
.page-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  font-size: 24px;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0;
  line-height: 1.3;
}

.page-desc {
  font-size: 14px;
  color: hsl(var(--muted-foreground));
  margin: 4px 0 0 0;
}

.page-actions {
  display: flex;
  align-items: center;
  gap: 12px;
}
</style>
