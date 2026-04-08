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
  <div class="stat-card">
    <div class="stat-header">
      <div class="stat-info">
        <p class="stat-title">{{ title }}</p>
        <p class="stat-value">{{ value }}</p>
        <p
          v-if="change"
          class="stat-change"
          :class="{
            'change-positive': changeType === 'positive',
            'change-negative': changeType === 'negative',
            'change-neutral': changeType === 'neutral',
          }"
        >
          {{ change }}
        </p>
      </div>
      <div
        v-if="icon"
        class="stat-icon"
        :class="iconColor || 'icon-default'"
      >
        <component :is="icon" :size="20" class="stat-icon-svg" :class="iconColor ? 'icon-colored' : 'icon-primary'" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.stat-card {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 20px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
  transition: box-shadow 0.2s ease;
}

.stat-card:hover {
  box-shadow: var(--shadow-card-hover);
}

.stat-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
}

.stat-info {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.stat-title {
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

.stat-value {
  font-size: 24px;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0;
}

.stat-change {
  font-size: 12px;
  font-weight: 500;
  margin: 0;
}

.change-positive { color: hsl(var(--success)); }
.change-negative { color: hsl(var(--destructive)); }
.change-neutral { color: hsl(var(--muted-foreground)); }

.stat-icon {
  width: 44px;
  height: 44px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}

.icon-default {
  background-color: hsl(var(--primary) / 0.1);
}

/* Dynamic background classes - need to be handled inline for HSL */
.bg-success\/10 { background-color: hsl(var(--success) / 0.1); }
.bg-destructive\/10 { background-color: hsl(var(--destructive) / 0.1); }
.bg-warning\/10 { background-color: hsl(var(--warning) / 0.1); }
.bg-primary\/10 { background-color: hsl(var(--primary) / 0.1); }

.icon-primary { color: hsl(var(--primary)); }
.icon-colored { color: hsl(var(--primary)); }
</style>
