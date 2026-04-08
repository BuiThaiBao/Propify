<script setup>
import { ref, onMounted } from 'vue'
import { TrendingUp } from 'lucide-vue-next'

// Simple SVG chart data
const months = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12']
const values = [15, 18, 14, 22, 25, 28, 30, 32, 35, 38, 42, 48]

const chartWidth = 600
const chartHeight = 200
const padLeft = 40
const padRight = 20
const padTop = 20
const padBottom = 30

const maxVal = Math.max(...values)
const minVal = 0

function getX(i) {
  return padLeft + (i / (values.length - 1)) * (chartWidth - padLeft - padRight)
}

function getY(v) {
  return padTop + (1 - (v - minVal) / (maxVal - minVal)) * (chartHeight - padTop - padBottom)
}

const linePath = values.map((v, i) => `${i === 0 ? 'M' : 'L'} ${getX(i)} ${getY(v)}`).join(' ')
const areaPath = `${linePath} L ${getX(values.length - 1)} ${chartHeight - padBottom} L ${getX(0)} ${chartHeight - padBottom} Z`
</script>

<template>
  <div class="chart-card">
    <div class="chart-header">
      <div>
        <h3 class="chart-title">Doanh thu theo thời gian</h3>
        <p class="chart-subtitle">Năm 2024</p>
      </div>
      <div class="chart-badge">
        <TrendingUp :size="14" />
        <span>+18%</span>
      </div>
    </div>

    <div class="chart-wrapper">
      <svg
        :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
        class="chart-svg"
        preserveAspectRatio="none"
      >
        <defs>
          <linearGradient id="areaGrad" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#2563eb" stop-opacity="0.15" />
            <stop offset="100%" stop-color="#2563eb" stop-opacity="0" />
          </linearGradient>
        </defs>

        <!-- Grid lines -->
        <g>
          <line
            v-for="n in 4"
            :key="n"
            :x1="padLeft"
            :y1="padTop + ((n - 1) / 3) * (chartHeight - padTop - padBottom)"
            :x2="chartWidth - padRight"
            :y2="padTop + ((n - 1) / 3) * (chartHeight - padTop - padBottom)"
            stroke="#f1f5f9"
            stroke-width="1"
          />
        </g>

        <!-- Y-axis labels -->
        <g class="axis-labels">
          <text
            v-for="(v, i) in [60, 45, 30, 15, 0]"
            :key="i"
            :x="padLeft - 6"
            :y="padTop + (i / 4) * (chartHeight - padTop - padBottom) + 4"
            text-anchor="end"
            font-size="10"
            fill="#94a3b8"
          >{{ v }} Tr</text>
        </g>

        <!-- Area fill -->
        <path :d="areaPath" fill="url(#areaGrad)" />

        <!-- Line -->
        <path
          :d="linePath"
          fill="none"
          stroke="#2563eb"
          stroke-width="2.5"
          stroke-linecap="round"
          stroke-linejoin="round"
        />

        <!-- X-axis labels -->
        <g>
          <text
            v-for="(month, i) in months"
            :key="month"
            :x="getX(i)"
            :y="chartHeight - 4"
            text-anchor="middle"
            font-size="10"
            fill="#94a3b8"
          >{{ month }}</text>
        </g>

        <!-- Data points -->
        <circle
          v-for="(v, i) in values"
          :key="i"
          :cx="getX(i)"
          :cy="getY(v)"
          r="3"
          fill="#2563eb"
          stroke="white"
          stroke-width="2"
          class="data-point"
        />
      </svg>
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
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 16px;
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

.chart-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  padding: 4px 10px;
  background-color: #d1fae5;
  color: #059669;
  border-radius: 20px;
  font-size: 12px;
  font-weight: 600;
}

.chart-wrapper {
  width: 100%;
  overflow: hidden;
}

.chart-svg {
  width: 100%;
  height: 200px;
  display: block;
}

.data-point {
  transition: r 0.15s ease;
}

.data-point:hover {
  r: 5;
}
</style>
