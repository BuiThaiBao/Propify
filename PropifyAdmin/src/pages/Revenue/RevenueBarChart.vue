<script setup>
const props = defineProps({
  year: {
    type: String,
    default: '2024',
  },
})

const months = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12']
const values = [25, 32, 28, 38, 42, 45, 50, 46, 55, 60, 52, 65]

const maxVal = Math.max(...values)
const chartH = 180
const barW = 28
const gap = 12

function barHeight(v) {
  return (v / maxVal) * chartH
}
</script>

<template>
  <div class="chart-card">
    <div class="chart-header">
      <div>
        <h3 class="chart-title">Doanh thu theo tháng</h3>
        <p class="chart-subtitle">Năm {{ year }}</p>
      </div>
    </div>

    <div class="bar-chart">
      <div
        v-for="(val, i) in values"
        :key="i"
        class="bar-col"
      >
        <div class="bar-val">{{ val }}M</div>
        <div
          class="bar"
          :style="{ height: barHeight(val) + 'px' }"
        ></div>
        <div class="bar-label">{{ months[i] }}</div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.chart-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
}

.chart-header {
  margin-bottom: 20px;
}

.chart-title {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 2px 0;
}

.chart-subtitle {
  font-size: 13px;
  color: #94a3b8;
  margin: 0;
}

.bar-chart {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  height: 220px;
  padding-bottom: 24px;
  overflow-x: auto;
}

.bar-col {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 4px;
  flex: 1;
  min-width: 28px;
}

.bar-val {
  font-size: 10px;
  color: #94a3b8;
  white-space: nowrap;
}

.bar {
  width: 100%;
  max-width: 36px;
  background: linear-gradient(180deg, #2563eb, #1d4ed8);
  border-radius: 5px 5px 0 0;
  transition: opacity 0.2s;
  min-height: 4px;
}

.bar:hover {
  opacity: 0.85;
}

.bar-label {
  font-size: 10px;
  color: #94a3b8;
  white-space: nowrap;
}
</style>
