<script setup>
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import { Download, Calendar } from 'lucide-vue-next'

const period = ref('year')

const monthlyRevenue = [
  { month: 'T1', revenue: 12000000, packages: 45 },
  { month: 'T2', revenue: 18000000, packages: 62 },
  { month: 'T3', revenue: 15000000, packages: 55 },
  { month: 'T4', revenue: 22000000, packages: 78 },
  { month: 'T5', revenue: 28000000, packages: 95 },
  { month: 'T6', revenue: 25000000, packages: 88 },
  { month: 'T7', revenue: 32000000, packages: 110 },
  { month: 'T8', revenue: 30000000, packages: 105 },
  { month: 'T9', revenue: 35000000, packages: 120 },
  { month: 'T10', revenue: 38000000, packages: 130 },
  { month: 'T11', revenue: 42000000, packages: 145 },
  { month: 'T12', revenue: 45000000, packages: 155 },
]

const packageDistribution = [
  { name: 'Cơ bản', value: 40, color: 'hsl(215, 16%, 80%)' },
  { name: 'Tiêu chuẩn', value: 35, color: 'hsl(217, 91%, 60%)' },
  { name: 'Premium', value: 20, color: 'hsl(38, 92%, 50%)' },
  { name: 'Doanh nghiệp', value: 5, color: 'hsl(142, 71%, 45%)' },
]

function formatCurrency(val) {
  return new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 1 }).format(val) + 'đ'
}

// Bar chart config — wide viewBox so bars fill full container
const maxRev = Math.max(...monthlyRevenue.map(d => d.revenue))
const barChartH = 320
const barChartW = 1000
const padL = 56
const padR = 12
const padT = 16
const padB = 32
const usableW = barChartW - padL - padR
const usableH = barChartH - padT - padB
const barW = Math.floor(usableW / monthlyRevenue.length * 0.55)

function barX(i) {
  const slotW = usableW / monthlyRevenue.length
  return padL + slotW * i + (slotW - barW) / 2
}
function barH(v) {
  return (v / maxRev) * usableH
}
function barY(v) {
  return padT + usableH - barH(v)
}

function yTicks() {
  const ticks = []
  for (let i = 0; i <= 5; i++) ticks.push(Math.round(maxRev * i / 5))
  return ticks
}

// Donut chart
const total = packageDistribution.reduce((s, p) => s + p.value, 0)
const cx = 130, cy = 130, outerR = 90, innerR = 55

function getArcPath(startPct, endPct) {
  const start = (startPct / total) * Math.PI * 2 - Math.PI / 2
  const end = (endPct / total) * Math.PI * 2 - Math.PI / 2
  const x1 = cx + outerR * Math.cos(start)
  const y1 = cy + outerR * Math.sin(start)
  const x2 = cx + outerR * Math.cos(end)
  const y2 = cy + outerR * Math.sin(end)
  const ix1 = cx + innerR * Math.cos(end)
  const iy1 = cy + innerR * Math.sin(end)
  const ix2 = cx + innerR * Math.cos(start)
  const iy2 = cy + innerR * Math.sin(start)
  const large = (endPct - startPct) / total > 0.5 ? 1 : 0
  return `M${x1},${y1} A${outerR},${outerR} 0 ${large} 1 ${x2},${y2} L${ix1},${iy1} A${innerR},${innerR} 0 ${large} 0 ${ix2},${iy2} Z`
}

let cumulative = 0
const arcs = packageDistribution.map(p => {
  const start = cumulative
  cumulative += p.value
  return { ...p, path: getArcPath(start, cumulative) }
})
</script>

<template>
  <div>
    <PageHeader title="Doanh thu & Báo cáo" description="Thống kê doanh thu và phân tích hiệu quả kinh doanh">
      <template #actions>
        <div class="period-select-wrap">
          <Calendar :size="16" color="hsl(215,16%,47%)" />
          <select v-model="period" class="period-select" id="period-select">
            <option value="month">Tháng này</option>
            <option value="quarter">Quý này</option>
            <option value="year">Năm 2024</option>
          </select>
        </div>
        <button class="btn-export" id="btn-export">
          <Download :size="16" /> Xuất báo cáo
        </button>
      </template>
    </PageHeader>

    <!-- Summary cards -->
    <div class="summary-grid">
      <div class="summary-card">
        <p class="summary-label">Tổng doanh thu</p>
        <p class="summary-value">342M đ</p>
        <p class="summary-change change-positive">+18% so với năm trước</p>
      </div>
      <div class="summary-card">
        <p class="summary-label">Gói đã bán</p>
        <p class="summary-value">1,188</p>
        <p class="summary-change change-positive">+25% so với năm trước</p>
      </div>
      <div class="summary-card">
        <p class="summary-label">Doanh thu trung bình/tháng</p>
        <p class="summary-value">28.5M đ</p>
        <p class="summary-change">Ổn định</p>
      </div>
    </div>

    <!-- Charts -->
    <div class="charts-grid">
      <!-- Bar chart -->
      <div class="chart-card">
        <h2 class="chart-title">Doanh thu theo tháng</h2>
        <div class="chart-body">
          <svg :viewBox="`0 0 ${barChartW} ${barChartH}`" class="bar-svg" preserveAspectRatio="xMidYMid meet">
            <!-- Grid lines & Y labels -->
            <g v-for="t in yTicks()" :key="t">
              <line
                :x1="padL" :y1="barY(t)"
                :x2="barChartW - padR" :y2="barY(t)"
                stroke="hsl(214,20%,92%)"
                stroke-dasharray="3 3"
                stroke-width="1"
              />
              <text :x="padL - 4" :y="barY(t) + 4" text-anchor="end" font-size="11" fill="hsl(215,16%,47%)">
                {{ formatCurrency(t) }}
              </text>
            </g>
            <!-- Bars -->
            <g v-for="(d, i) in monthlyRevenue" :key="i">
              <rect
                :x="barX(i)"
                :y="barY(d.revenue)"
                :width="barW"
                :height="barH(d.revenue)"
                fill="hsl(217,91%,60%)"
                rx="6"
                ry="6"
                class="bar-rect"
              />
              <text
                :x="barX(i) + barW / 2"
                :y="barChartH - 8"
                text-anchor="middle"
                font-size="11"
                fill="hsl(215,16%,47%)"
              >{{ d.month }}</text>
            </g>
          </svg>
        </div>
      </div>

      <!-- Donut chart -->
      <div class="chart-card">
        <h2 class="chart-title">Phân bố gói tin</h2>
        <div class="donut-wrap">
          <svg width="260" height="260">
            <path
              v-for="arc in arcs"
              :key="arc.name"
              :d="arc.path"
              :fill="arc.color"
              class="donut-slice"
            />
            <text :x="cx" :y="cy - 6" text-anchor="middle" font-size="20" font-weight="700" fill="hsl(220,20%,10%)">
              {{ total }}%
            </text>
            <text :x="cx" :y="cy + 14" text-anchor="middle" font-size="12" fill="hsl(215,16%,47%)">
              Tổng cộng
            </text>
          </svg>
        </div>
        <div class="legend">
          <div v-for="p in packageDistribution" :key="p.name" class="legend-item">
            <span class="legend-dot" :style="{ backgroundColor: p.color }"></span>
            <span class="legend-name">{{ p.name }}</span>
            <span class="legend-val">{{ p.value }}%</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.period-select-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  padding: 8px 12px;
}

.period-select {
  background: transparent;
  border: none;
  font-size: 14px;
  color: hsl(var(--foreground));
  outline: none;
  cursor: pointer;
}

.btn-export {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 16px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background-color 0.15s;
}
.btn-export:hover { background-color: hsl(var(--muted)); }

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 32px;
}

.summary-card {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 20px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
}

.summary-label {
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--muted-foreground));
  margin: 0 0 4px;
}

.summary-value {
  font-size: 30px;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0 0 8px;
}

.summary-change {
  font-size: 12px;
  font-weight: 500;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

.change-positive { color: hsl(var(--success)); }

.charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
}

.chart-card {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 24px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
}

.chart-title {
  font-size: 18px;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0 0 24px;
}

.chart-body {
  position: relative;
  width: 100%;
  /* padding-bottom preserves the 1000:320 aspect ratio so SVG fills full width */
  padding-bottom: 32%;
  height: 0;
}

.bar-svg {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  display: block;
}

.bar-rect {
  transition: opacity 0.15s;
  cursor: pointer;
}
.bar-rect:hover { opacity: 0.85; }

.donut-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

.donut-slice {
  transition: opacity 0.15s;
  cursor: pointer;
}
.donut-slice:hover { opacity: 0.85; }

.legend {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.legend-item {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 14px;
}

.legend-dot {
  width: 10px;
  height: 10px;
  border-radius: 50%;
  flex-shrink: 0;
}

.legend-name {
  flex: 1;
  color: hsl(var(--muted-foreground));
}

.legend-val {
  font-weight: 600;
  color: hsl(var(--foreground));
}

@media (max-width: 1024px) {
  .summary-grid { grid-template-columns: 1fr; }
  .charts-grid { grid-template-columns: 1fr; }
}
</style>
