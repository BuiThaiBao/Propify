<script setup>
import { computed, onMounted, ref, watch } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import { Calendar, Download, Loader2, RefreshCw } from 'lucide-vue-next'
import { fetchRevenueStats } from '@/services/revenueService'
import { useTransactionApi } from '@/composables/useTransactionApi'
import { formatTransactionAmount } from '@/utils/transactionFormatters'

const period = ref('year')
const stats = ref(null)
const error = ref('')
const statsLoading = ref(false)
const exportingFormat = ref('')
const customFromDate = ref(formatDateInput(addDays(new Date(), -7)))
const customToDate = ref(formatDateInput(new Date()))

const { exportReport } = useTransactionApi()

const periodOptions = [
  { value: 'month', label: 'Tháng này' },
  { value: 'quarter', label: 'Quý này' },
  { value: 'year', label: 'Năm nay' },
  { value: 'custom', label: 'Tự chọn ngày' },
]

const selectedRange = computed(() => getDateRange(period.value))
const selectedPeriodLabel = computed(
  () => periodOptions.find((item) => item.value === period.value)?.label || 'Năm nay',
)
const rangeLabel = computed(() => stats.value?.label || selectedPeriodLabel.value)
const summary = computed(() => ({
  total_revenue: 0,
  sold_packages: 0,
  average_monthly_revenue: 0,
  revenue_change_percent: 0,
  sold_packages_change_percent: 0,
  comparison_label: 'so với kỳ trước',
  ...(stats.value?.summary || {}),
}))
const monthlyRevenue = computed(() => stats.value?.monthly_revenue || defaultMonthlyRevenue())
const packageDistribution = computed(() => stats.value?.package_distribution || [])
const hasRevenueData = computed(() => monthlyRevenue.value.some((item) => Number(item.revenue || 0) > 0))
const totalPackageSales = computed(() =>
  packageDistribution.value.reduce((sum, item) => sum + Number(item.count || 0), 0),
)
const maxRevenue = computed(() => Math.max(...monthlyRevenue.value.map((item) => Number(item.revenue || 0)), 1))

const barChartH = 320
const barChartW = 1000
const padL = 70
const padR = 12
const padT = 16
const padB = 34
const usableW = barChartW - padL - padR
const usableH = barChartH - padT - padB

const barWidth = computed(() => {
  const slotWidth = usableW / Math.max(monthlyRevenue.value.length, 1)
  return Math.floor(slotWidth * 0.55)
})

const donutTotal = computed(() =>
  packageDistribution.value.reduce((total, item) => total + Number(item.percentage || 0), 0),
)
const donutArcs = computed(() => {
  if (!packageDistribution.value.length || donutTotal.value <= 0) return []

  let cumulative = 0
  return packageDistribution.value.map((item) => {
    const start = cumulative
    cumulative += Number(item.percentage || 0)

    return {
      ...item,
      path: getArcPath(start, cumulative, donutTotal.value),
    }
  })
})

async function loadRevenueStats() {
  if (period.value === 'custom' && !validateCustomDates()) return

  statsLoading.value = true
  error.value = ''

  try {
    const response = await fetchRevenueStats({
      period: period.value,
      ...selectedRange.value,
    })
    stats.value = response.data?.data || null
  } catch (err) {
    error.value = err.response?.data?.message || err.message || 'Không thể tải thống kê doanh thu.'
    stats.value = null
  } finally {
    statsLoading.value = false
  }
}

async function handleExport(format) {
  if (period.value === 'custom' && !validateCustomDates()) return

  exportingFormat.value = format

  try {
    await exportReport(format, {
      status: 'SUCCESS',
      ...selectedRange.value,
    })
  } catch (err) {
    alert('Không thể xuất báo cáo: ' + (err.response?.data?.message || err.message))
  } finally {
    exportingFormat.value = ''
  }
}

function getDateRange(value) {
  const now = new Date()

  if (value === 'custom') {
    return { from_date: customFromDate.value, to_date: customToDate.value }
  }

  let fromDate = new Date(now.getFullYear(), 0, 1)

  if (value === 'month') {
    fromDate = new Date(now.getFullYear(), now.getMonth(), 1)
  } else if (value === 'quarter') {
    const quarterMonth = Math.floor(now.getMonth() / 3) * 3
    fromDate = new Date(now.getFullYear(), quarterMonth, 1)
  }

  return {
    from_date: formatDateInput(fromDate),
    to_date: formatDateInput(now),
  }
}

function validateCustomDates() {
  if (!customFromDate.value || !customToDate.value) return false

  const from = new Date(customFromDate.value)
  const to = new Date(customToDate.value)

  if (to < from) {
    customToDate.value = customFromDate.value
    error.value = 'Ngày kết thúc phải lớn hơn hoặc bằng ngày bắt đầu.'
    return false
  }

  error.value = ''
  return true
}

function addDays(date, days) {
  const cloned = new Date(date)
  cloned.setDate(cloned.getDate() + days)
  return cloned
}

function formatDateInput(date) {
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function defaultMonthlyRevenue() {
  return Array.from({ length: 12 }, (_, index) => ({
    month: `T${index + 1}`,
    month_number: index + 1,
    revenue: 0,
    packages: 0,
  }))
}

function formatCurrency(value) {
  const amount = Number(value || 0)
  return `${new Intl.NumberFormat('vi-VN', {
    notation: 'compact',
    maximumFractionDigits: 1,
  }).format(amount)} đ`
}

function formatFullCurrency(value) {
  return formatTransactionAmount(value)
}

function formatPercent(value) {
  const number = Number(value || 0)
  const sign = number > 0 ? '+' : ''
  return `${sign}${number.toLocaleString('vi-VN', { maximumFractionDigits: 1 })}%`
}

function changeClass(value) {
  const number = Number(value || 0)
  if (number > 0) return 'change-positive'
  if (number < 0) return 'change-negative'
  return ''
}

function barX(index) {
  const slotWidth = usableW / Math.max(monthlyRevenue.value.length, 1)
  return padL + slotWidth * index + (slotWidth - barWidth.value) / 2
}

function barHeight(value) {
  return (Number(value || 0) / maxRevenue.value) * usableH
}

function barY(value) {
  return padT + usableH - barHeight(value)
}

function yTicks() {
  const ticks = []
  for (let index = 0; index <= 5; index++) {
    ticks.push(Math.round((maxRevenue.value * index) / 5))
  }
  return ticks
}

const donutCx = 130
const donutCy = 130
const donutOuterR = 90
const donutInnerR = 55

function getArcPath(startValue, endValue, total) {
  const start = (startValue / total) * Math.PI * 2 - Math.PI / 2
  const end = (endValue / total) * Math.PI * 2 - Math.PI / 2
  const x1 = donutCx + donutOuterR * Math.cos(start)
  const y1 = donutCy + donutOuterR * Math.sin(start)
  const x2 = donutCx + donutOuterR * Math.cos(end)
  const y2 = donutCy + donutOuterR * Math.sin(end)
  const ix1 = donutCx + donutInnerR * Math.cos(end)
  const iy1 = donutCy + donutInnerR * Math.sin(end)
  const ix2 = donutCx + donutInnerR * Math.cos(start)
  const iy2 = donutCy + donutInnerR * Math.sin(start)
  const large = (endValue - startValue) / total > 0.5 ? 1 : 0

  return `M${x1},${y1} A${donutOuterR},${donutOuterR} 0 ${large} 1 ${x2},${y2} L${ix1},${iy1} A${donutInnerR},${donutInnerR} 0 ${large} 0 ${ix2},${iy2} Z`
}

watch(period, loadRevenueStats)
watch([customFromDate, customToDate], () => {
  if (period.value === 'custom') loadRevenueStats()
})

onMounted(loadRevenueStats)
</script>

<template>
  <div>
    <PageHeader
      title="Doanh thu & Báo cáo"
      description="Thống kê doanh thu và phân tích hiệu quả kinh doanh từ giao dịch thực tế"
    />

    <section class="revenue-toolbar">
      <div class="toolbar-left">
        <div class="period-segment" aria-label="Bộ lọc thời gian">
          <button
            v-for="option in periodOptions"
            :key="option.value"
            type="button"
            class="period-chip"
            :class="{ active: period === option.value }"
            @click="period = option.value"
          >
            {{ option.label }}
          </button>
        </div>

        <div v-if="period === 'custom'" class="date-range-panel">
          <label class="date-field">
            <span>Từ ngày</span>
            <div class="date-control">
              <Calendar :size="15" />
              <input v-model="customFromDate" type="date" />
            </div>
          </label>
          <label class="date-field">
            <span>Đến ngày</span>
            <div class="date-control">
              <Calendar :size="15" />
              <input v-model="customToDate" type="date" />
            </div>
          </label>
        </div>
      </div>

      <div class="toolbar-actions">
        <button
          class="btn-export"
          id="btn-export-excel"
          :disabled="statsLoading || exportingFormat !== ''"
          @click="handleExport('excel')"
        >
          <Loader2 v-if="exportingFormat === 'excel'" class="spin" :size="16" />
          <Download v-else :size="16" />
          Xuất Excel
        </button>

        <button
          class="btn-export"
          id="btn-export-pdf"
          :disabled="statsLoading || exportingFormat !== ''"
          @click="handleExport('pdf')"
        >
          <Loader2 v-if="exportingFormat === 'pdf'" class="spin" :size="16" />
          <Download v-else :size="16" />
          Xuất PDF
        </button>
      </div>
    </section>

    <div v-if="error" class="state-card error-card">
      <span>{{ error }}</span>
      <button type="button" class="btn-retry" @click="loadRevenueStats">
        <RefreshCw :size="14" />
        Tải lại
      </button>
    </div>

    <div class="summary-grid">
      <div class="summary-card">
        <p class="summary-label">Tổng doanh thu</p>
        <p class="summary-value">
          <span v-if="statsLoading" class="skeleton skeleton-value"></span>
          <span v-else>{{ formatCurrency(summary.total_revenue) }}</span>
        </p>
        <p class="summary-change" :class="changeClass(summary.revenue_change_percent)">
          {{ formatPercent(summary.revenue_change_percent) }} {{ summary.comparison_label }}
        </p>
      </div>

      <div class="summary-card">
        <p class="summary-label">Gói đã bán</p>
        <p class="summary-value">
          <span v-if="statsLoading" class="skeleton skeleton-value"></span>
          <span v-else>{{ Number(summary.sold_packages || 0).toLocaleString('vi-VN') }}</span>
        </p>
        <p class="summary-change" :class="changeClass(summary.sold_packages_change_percent)">
          {{ formatPercent(summary.sold_packages_change_percent) }} {{ summary.comparison_label }}
        </p>
      </div>

      <div class="summary-card">
        <p class="summary-label">Doanh thu trung bình/tháng</p>
        <p class="summary-value">
          <span v-if="statsLoading" class="skeleton skeleton-value"></span>
          <span v-else>{{ formatCurrency(summary.average_monthly_revenue) }}</span>
        </p>
        <p class="summary-change">{{ rangeLabel }} · {{ formatFullCurrency(summary.total_revenue) }}</p>
      </div>
    </div>

    <div class="charts-grid">
      <div class="chart-card">
        <div class="chart-heading">
          <h2 class="chart-title">Doanh thu theo tháng</h2>
          <p class="chart-subtitle">Chỉ tính giao dịch thành công trong {{ rangeLabel.toLowerCase() }}</p>
        </div>

        <div class="chart-body">
          <div v-if="statsLoading" class="chart-loading">
            <Loader2 :size="20" class="spin" />
            Đang tải dữ liệu...
          </div>
          <svg
            v-else
            :viewBox="`0 0 ${barChartW} ${barChartH}`"
            class="bar-svg"
            preserveAspectRatio="xMidYMid meet"
          >
            <g v-for="tick in yTicks()" :key="tick">
              <line
                :x1="padL"
                :y1="barY(tick)"
                :x2="barChartW - padR"
                :y2="barY(tick)"
                stroke="hsl(214,20%,92%)"
                stroke-dasharray="3 3"
                stroke-width="1"
              />
              <text
                :x="padL - 6"
                :y="barY(tick) + 4"
                text-anchor="end"
                font-size="11"
                fill="hsl(215,16%,47%)"
              >
                {{ formatCurrency(tick) }}
              </text>
            </g>

            <g v-for="(item, index) in monthlyRevenue" :key="`${item.year}-${item.month_number}`">
              <rect
                :x="barX(index)"
                :y="barY(item.revenue)"
                :width="barWidth"
                :height="Math.max(barHeight(item.revenue), item.revenue > 0 ? 4 : 0)"
                fill="hsl(217,91%,60%)"
                rx="6"
                ry="6"
                class="bar-rect"
              >
                <title>{{ item.month }}: {{ formatFullCurrency(item.revenue) }} · {{ item.packages }} gói</title>
              </rect>
              <text
                :x="barX(index) + barWidth / 2"
                :y="barChartH - 8"
                text-anchor="middle"
                font-size="11"
                fill="hsl(215,16%,47%)"
              >
                {{ item.month }}
              </text>
            </g>
          </svg>
        </div>

        <p v-if="!statsLoading && !hasRevenueData" class="empty-note">
          Chưa có giao dịch thành công trong {{ rangeLabel.toLowerCase() }}.
        </p>
      </div>

      <div class="chart-card">
        <div class="chart-heading">
          <h2 class="chart-title">Phân bổ gói tin</h2>
          <p class="chart-subtitle">Theo số lượng gói đã bán</p>
        </div>

        <div class="donut-wrap">
          <svg width="260" height="260" aria-label="Biểu đồ phân bổ gói tin">
            <circle
              v-if="!donutArcs.length"
              :cx="donutCx"
              :cy="donutCy"
              :r="donutOuterR"
              fill="none"
              stroke="hsl(214,20%,90%)"
              stroke-width="35"
            />
            <path
              v-for="arc in donutArcs"
              :key="arc.id || arc.name"
              :d="arc.path"
              :fill="arc.color"
              class="donut-slice"
            >
              <title>{{ arc.name }}: {{ arc.count }} gói · {{ arc.percentage }}%</title>
            </path>
            <text
              :x="donutCx"
              :y="donutCy - 6"
              text-anchor="middle"
              font-size="20"
              font-weight="700"
              fill="hsl(220,20%,10%)"
            >
              {{ totalPackageSales.toLocaleString('vi-VN') }}
            </text>
            <text
              :x="donutCx"
              :y="donutCy + 14"
              text-anchor="middle"
              font-size="12"
              fill="hsl(215,16%,47%)"
            >
              Gói đã bán
            </text>
          </svg>
        </div>

        <div v-if="packageDistribution.length" class="legend">
          <div v-for="item in packageDistribution" :key="item.id || item.name" class="legend-item">
            <span class="legend-dot" :style="{ backgroundColor: item.color }"></span>
            <span class="legend-name">{{ item.name }}</span>
            <span class="legend-val">{{ item.percentage }}%</span>
          </div>
        </div>
        <p v-else class="empty-note">Chưa có dữ liệu gói tin.</p>
      </div>
    </div>
  </div>
</template>

<style scoped>
.revenue-toolbar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  flex-wrap: wrap;
  margin: -6px 0 24px;
  padding: 14px;
  border: 1px solid hsl(var(--border) / 0.65);
  border-radius: 14px;
  background: hsl(var(--card));
  box-shadow: var(--shadow-card);
}

.toolbar-left,
.toolbar-actions {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.period-segment {
  display: inline-flex;
  align-items: center;
  gap: 4px;
  padding: 4px;
  border: 1px solid hsl(var(--border));
  border-radius: 12px;
  background: hsl(var(--muted) / 0.55);
}

.period-chip {
  height: 34px;
  padding: 0 14px;
  border: 0;
  border-radius: 9px;
  background: transparent;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s, color 0.15s, box-shadow 0.15s;
}

.period-chip:hover {
  color: hsl(var(--foreground));
}

.period-chip.active {
  background: hsl(var(--card));
  color: hsl(var(--primary));
  box-shadow: 0 1px 2px hsl(220 40% 2% / 0.08);
}

.date-range-panel {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 4px;
  border: 1px solid hsl(var(--border));
  border-radius: 10px;
  background: hsl(var(--card));
}

.date-field {
  display: flex;
  align-items: center;
  gap: 8px;
  height: 38px;
  padding: 0 10px;
  border-radius: 8px;
  color: hsl(var(--muted-foreground));
  font-size: 12px;
  white-space: nowrap;
}

.date-control {
  display: flex;
  align-items: center;
  gap: 6px;
  color: hsl(var(--foreground));
}

.date-control input {
  width: 118px;
  border: 0;
  background: transparent;
  color: hsl(var(--foreground));
  font: inherit;
  font-size: 13px;
  outline: none;
}

.btn-export,
.btn-retry {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  border-radius: 10px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s, opacity 0.15s, box-shadow 0.15s;
}

.btn-export {
  height: 42px;
  padding: 0 16px;
  border: 1px solid hsl(var(--border));
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  font-size: 14px;
}

.btn-export:hover,
.btn-retry:hover {
  background-color: hsl(var(--muted));
}

.btn-export:disabled {
  cursor: not-allowed;
  opacity: 0.65;
}

.btn-retry {
  padding: 7px 12px;
  border: 1px solid hsl(var(--border));
  background: hsl(var(--card));
  color: hsl(var(--foreground));
  font-size: 13px;
}

.state-card {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 20px;
  border-radius: 10px;
  padding: 14px 16px;
  font-size: 14px;
}

.error-card {
  border: 1px solid hsl(var(--destructive) / 0.25);
  background: hsl(var(--destructive) / 0.08);
  color: hsl(var(--destructive));
}

.summary-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
  margin-bottom: 32px;
}

.summary-card,
.chart-card {
  background-color: hsl(var(--card));
  border: 1px solid hsl(var(--border) / 0.5);
  box-shadow: var(--shadow-card);
}

.summary-card {
  border-radius: 12px;
  padding: 20px;
}

.summary-label {
  margin: 0 0 4px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
  font-weight: 500;
}

.summary-value {
  min-height: 38px;
  margin: 0 0 8px;
  color: hsl(var(--foreground));
  font-size: 30px;
  font-weight: 700;
}

.summary-change {
  margin: 0;
  color: hsl(var(--muted-foreground));
  font-size: 12px;
  font-weight: 500;
}

.change-positive {
  color: hsl(var(--success));
}

.change-negative {
  color: hsl(var(--destructive));
}

.charts-grid {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
}

.chart-card {
  border-radius: 12px;
  padding: 24px;
}

.chart-heading {
  margin-bottom: 20px;
}

.chart-title {
  margin: 0 0 4px;
  color: hsl(var(--foreground));
  font-size: 18px;
  font-weight: 700;
}

.chart-subtitle {
  margin: 0;
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.chart-body {
  position: relative;
  width: 100%;
  height: 0;
  padding-bottom: 32%;
}

.chart-loading {
  position: absolute;
  inset: 0;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  color: hsl(var(--muted-foreground));
  font-size: 14px;
}

.bar-svg {
  position: absolute;
  inset: 0;
  display: block;
  width: 100%;
  height: 100%;
}

.bar-rect,
.donut-slice {
  cursor: pointer;
  transition: opacity 0.15s;
}

.bar-rect:hover,
.donut-slice:hover {
  opacity: 0.85;
}

.donut-wrap {
  display: flex;
  justify-content: center;
  margin-bottom: 16px;
}

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
  color: hsl(var(--foreground));
  font-weight: 700;
}

.empty-note {
  margin: 16px 0 0;
  color: hsl(var(--muted-foreground));
  font-size: 13px;
}

.skeleton {
  display: inline-block;
  border-radius: 8px;
  background: linear-gradient(90deg, hsl(var(--muted)) 25%, hsl(var(--muted) / 0.45) 37%, hsl(var(--muted)) 63%);
  background-size: 400% 100%;
  animation: shimmer 1.25s ease-in-out infinite;
}

.skeleton-value {
  width: 150px;
  height: 34px;
}

.spin {
  animation: spin 0.8s linear infinite;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@keyframes shimmer {
  0% {
    background-position: 100% 0;
  }
  100% {
    background-position: 0 0;
  }
}

@media (max-width: 1024px) {
  .summary-grid,
  .charts-grid {
    grid-template-columns: 1fr;
  }
}
</style>
