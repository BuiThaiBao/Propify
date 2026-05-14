<script setup>
defineProps({
  title: { type: String, required: true },
  value: { type: String, required: true },
  change: { type: String, default: '' },
  changeType: { type: String, default: 'neutral' }, // 'positive' | 'negative' | 'neutral'
  icon: { type: Object, default: null },
  iconColor: { type: String, default: '' }, // e.g. 'bg-success/10'
})
</script>

<template>
  <div class="rounded-xl border border-border/50 bg-card p-5 shadow-card transition-shadow duration-200 hover:shadow-card-hover">
    <div class="flex items-start justify-between">
      <div class="flex flex-col gap-2">
        <p class="m-0 text-sm font-medium text-muted-foreground">{{ title }}</p>
        <p class="m-0 text-2xl font-bold text-foreground">{{ value }}</p>
        <p
          v-if="change"
          class="m-0 text-xs font-medium"
          :class="{
            'text-success': changeType === 'positive',
            'text-destructive': changeType === 'negative',
            'text-muted-foreground': changeType === 'neutral',
          }"
        >
          {{ change }}
        </p>
      </div>
      <div
        v-if="icon"
        class="flex size-11 shrink-0 items-center justify-center rounded-xl"
        :class="iconColor || 'bg-primary/10'"
      >
        <component :is="icon" :size="20" class="text-primary" />
      </div>
    </div>
  </div>
</template>
