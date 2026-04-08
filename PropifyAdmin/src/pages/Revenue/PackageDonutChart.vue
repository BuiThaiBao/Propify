<script setup>
const packages = [
  { name: 'Gói Cơ bản', value: 30, color: '#93c5fd' },
  { name: 'Gói Tiêu chuẩn', value: 35, color: '#2563eb' },
  { name: 'Gói Premium', value: 25, color: '#1d4ed8' },
  { name: 'Gói DN', value: 10, color: '#dbeafe' },
]

const total = packages.reduce((s, p) => s + p.value, 0)
const size = 150
const cx = size / 2
const cy = size / 2
const r = 54
const innerR = 32

function getArcPath(start, end, outerR, innerR) {
  const startAngle = (start / total) * Math.PI * 2 - Math.PI / 2
  const endAngle = (end / total) * Math.PI * 2 - Math.PI / 2
  const x1 = cx + outerR * Math.cos(startAngle)
  const y1 = cy + outerR * Math.sin(startAngle)
  const x2 = cx + outerR * Math.cos(endAngle)
  const y2 = cy + outerR * Math.sin(endAngle)
  const ix1 = cx + innerR * Math.cos(endAngle)
  const iy1 = cy + innerR * Math.sin(endAngle)
  const ix2 = cx + innerR * Math.cos(startAngle)
  const iy2 = cy + innerR * Math.sin(startAngle)
  const largeArc = (end - start) / total > 0.5 ? 1 : 0
  return `M ${x1} ${y1} A ${outerR} ${outerR} 0 ${largeArc} 1 ${x2} ${y2} L ${ix1} ${iy1} A ${innerR} ${innerR} 0 ${largeArc} 0 ${ix2} ${iy2} Z`
}

let cumulative = 0
const arcs = packages.map(pkg => {
  const start = cumulative
  cumulative += pkg.value
  return {
    ...pkg,
    path: getArcPath(start, cumulative, r, innerR),
  }
})
</script>

<template>
  <div class="donut-card">
    <h3 class="donut-title">Phân bổ gói tin</h3>

    <div class="donut-wrap">
      <svg :width="size" :height="size" class="donut-svg">
        <path
          v-for="arc in arcs"
          :key="arc.name"
          :d="arc.path"
          :fill="arc.color"
          class="donut-slice"
        />
        <text
          :x="cx"
          :y="cy - 4"
          text-anchor="middle"
          font-size="18"
          font-weight="700"
          fill="#0f172a"
        >{{ total }}%</text>
        <text
          :x="cx"
          :y="cy + 14"
          text-anchor="middle"
          font-size="10"
          fill="#94a3b8"
        >Tổng cộng</text>
      </svg>
    </div>

    <!-- Legend -->
    <div class="donut-legend">
      <div v-for="pkg in packages" :key="pkg.name" class="legend-item">
        <span class="legend-dot" :style="{ backgroundColor: pkg.color }"></span>
        <span class="legend-name">{{ pkg.name }}</span>
        <span class="legend-val">{{ pkg.value }}%</span>
      </div>
    </div>
  </div>
</template>

<style scoped>
.donut-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
}

.donut-title {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
  margin: 0 0 16px 0;
}

.donut-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 20px;
}

.donut-svg {
  overflow: visible;
}

.donut-slice {
  transition: opacity 0.2s;
  cursor: pointer;
}

.donut-slice:hover {
  opacity: 0.85;
}

.donut-legend {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 13px;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-name {
  flex: 1;
  color: #475569;
}

.legend-val {
  font-weight: 600;
  color: #0f172a;
}
</style>
