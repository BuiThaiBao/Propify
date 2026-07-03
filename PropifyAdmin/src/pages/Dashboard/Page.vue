<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { FileText, CheckCircle, XCircle, DollarSign, TrendingUp, Clock, User, Calendar } from 'lucide-vue-next'
import { formatCompactCurrency, formatTransactionAmount } from '@/utils/transactionFormatters'
import dashboardService from '@/services/dashboardService'

const loading = ref(true)
const stats = ref(null)

const selectedBarIndex = ref(null)

const period = ref('year')
const customFromDate = ref(formatDateInput(addDays(new Date(), -7)))
const customToDate = ref(formatDateInput(new Date()))

const periodOptions = [
  { value: 'month', label: 'Tháng này' },
  { value: 'quarter', label: 'Quý này' },
  { value: 'year', label: 'Năm nay' },
  { value: 'custom', label: 'Tự chọn ngày' },
]

const selectedRange = computed(() => getDateRange(period.value))

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

const formatCurrency = formatCompactCurrency

async function loadData() {
  loading.value = true
  try {
    const res = await dashboardService.getStats({
      period: period.value,
      ...selectedRange.value,
    })
    stats.value = res.data?.data || null
  } catch (e) {
    console.error('Failed to load dashboard stats', e)
  } finally {
    loading.value = false
  }
}

watch(period, loadData)
watch([customFromDate, customToDate], () => {
  if (period.value === 'custom') loadData()
})

onMounted(() => {
  loadData()
})

function calcPercentageChange(current, previous) {
  if (!previous) return current > 0 ? '+100%' : '0%'
  const change = ((current - previous) / previous) * 100
  const sign = change >= 0 ? '+' : ''
  return `${sign}${change.toFixed(1)}%`
}

// SVG chart math
const chartW = 560
const chartH = 280
const padL = 58
const padR = 20
const padT = 10
const padB = 30

const barWidth = computed(() => {
  const count = getChartData().length || 1
  const usableW = chartW - padL - padR
  return Math.min(60, usableW / count * 0.5)
})

function getChartData() {
  return stats.value?.revenue_chart || []
}

function getMaxRev() {
  const data = getChartData()
  return Math.max(...data.map((d) => d.revenue), 1)
}

function getX(i) {
  const len = getChartData().length
  if (len <= 1) return padL
  return padL + (i / (len - 1)) * (chartW - padL - padR)
}

function getY(v) {
  const maxRev = getMaxRev()
  return padT + (1 - v / maxRev) * (chartH - padT - padB)
}

function yTicks() {
  const maxRev = getMaxRev()
  const step = Math.ceil(maxRev / 5 / 1000000) * 1000000 || 1000000
  const ticks = []
  for (let v = 0; v <= maxRev; v += step) {
    ticks.push(v)
  }
  if (ticks[ticks.length - 1] < maxRev) ticks.push(maxRev)
  return ticks
}
</script>

<template>
  <div>
    <PageHeader title="Dashboard" description="Tổng quan hệ thống Propify" />

    <div v-if="loading" class="flex items-center justify-center py-20 text-muted-foreground">
      Đang tải dữ liệu...
    </div>

    <template v-else-if="stats">
      <section class="dashboard-toolbar">
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
      </section>

      <!-- Stats grid -->
      <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          title="Tổng số tin đăng"
          :value="stats.listings.total.toLocaleString('vi-VN')"
          :change="`+${calcPercentageChange(stats.listings_change.current_month, stats.listings_change.last_month)} so với kỳ trước`"
          change-type="positive"
          :icon="FileText"
        />
        <StatCard
          title="Tin đã duyệt"
          :value="stats.listings.approved.toLocaleString('vi-VN')"
          :change="`Đang chờ: ${stats.listings.pending.toLocaleString('vi-VN')}`"
          change-type="positive"
          :icon="CheckCircle"
          icon-color="bg-success/10"
        />
        <StatCard
          title="Tin bị từ chối / Khóa"
          :value="(stats.listings.rejected + stats.listings.locked).toLocaleString('vi-VN')"
          :change="`Từ chối: ${stats.listings.rejected} / Khóa: ${stats.listings.locked}`"
          :change-type="(stats.listings.rejected + stats.listings.locked) > 0 ? 'negative' : 'positive'"
          :icon="XCircle"
          icon-color="bg-destructive/10"
        />
        <StatCard
          title="Doanh thu"
          :value="formatCurrency(stats.revenue.total)"
          :change="`Kỳ này: ${formatCurrency(stats.revenue.current_month)}`"
          change-type="positive"
          :icon="DollarSign"
          icon-color="bg-warning/10"
        />
      </div>

      <!-- Bottom section -->
      <div class="grid grid-cols-1 gap-6 xl:grid-cols-[2fr_1fr]">
        <!-- Revenue chart -->
        <div class="rounded-xl border border-border/50 bg-card p-6 shadow-card">
          <div class="mb-6 flex items-start justify-between">
            <div>
              <h2 class="m-0 text-[18px] font-bold text-foreground">
                Doanh thu theo tháng
              </h2>
            </div>
            <div
              v-if="stats.revenue.last_month > 0"
              class="flex items-center gap-1 text-sm font-medium"
              :class="stats.revenue.current_month >= stats.revenue.last_month ? 'text-success' : 'text-destructive'"
            >
              <TrendingUp :size="16" />
              {{ calcPercentageChange(stats.revenue.current_month, stats.revenue.last_month) }}
            </div>
          </div>
          <div class="relative w-full" style="padding-bottom: 50%;">
            <svg
              :viewBox="`0 0 ${chartW} ${chartH}`"
              class="absolute inset-0 block h-full w-full"
              preserveAspectRatio="xMidYMid meet"
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
                  v-for="t in yTicks()"
                  :key="'grid' + t"
                  :x1="padL"
                  :y1="getY(t)"
                  :x2="chartW - padR"
                  :y2="getY(t)"
                  stroke="#f1f5f9"
                  stroke-width="1"
                />
              </g>

              <!-- Y labels -->
              <g class="axis-labels">
                <text
                  v-for="t in yTicks()"
                  :key="'y' + t"
                  :x="padL - 6"
                  :y="getY(t) + 4"
                  text-anchor="end"
                  font-size="10"
                  fill="#94a3b8"
                >
                  {{ formatCompactCurrency(t).replace('Trđ', 'Tr') }}
                </text>
              </g>

              <!-- Bars -->
              <g
                v-for="(d, i) in getChartData()"
                :key="'bar' + i"
              >
                <rect
                  :x="getX(i) - barWidth / 2"
                  :y="getY(d.revenue)"
                  :width="barWidth"
                  :height="Math.max(chartH - padB - getY(d.revenue), d.revenue > 0 ? 4 : 0)"
                  fill="hsl(217,91%,60%)"
                  rx="6"
                  ry="6"
                  class="cursor-pointer transition-opacity hover:opacity-85"
                >
                  <title>{{ d.month }}: {{ formatCurrency(d.revenue) }}</title>
                </rect>
                <!-- X labels -->
                <text
                  :x="getX(i)"
                  :y="chartH - 4"
                  text-anchor="middle"
                  font-size="10"
                  fill="#94a3b8"
                >
                  {{ d.month }}
                </text>
              </g>
            </svg>
          </div>
        </div>

        <!-- Recent activities -->
        <div class="rounded-xl border border-border/50 bg-card p-6 shadow-card">
          <h2 class="m-0 mb-4 text-lg font-semibold text-foreground">Hoạt động gần đây</h2>
          <div v-if="stats.recent_activities.length === 0" class="py-8 text-center text-sm text-muted-foreground">
            Chưa có hoạt động nào.
          </div>
          <div v-else class="flex flex-col gap-4">
            <div v-for="a in stats.recent_activities" :key="a.id" class="flex items-start gap-3">
              <div
                class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-muted"
              >
                <Clock :size="14" color="hsl(215,16%,47%)" />
              </div>
              <div class="min-w-0 flex-1">
                <p class="m-0 mb-0.5 truncate text-sm font-medium text-foreground">
                  {{ a.action }}
                </p>
                <p class="m-0 text-xs text-muted-foreground">
                  {{ a.actor?.full_name || 'Hệ thống' }} · {{ a.created_at ? new Date(a.created_at).toLocaleString('vi-VN') : '' }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </template>

    <div v-else class="py-20 text-center text-muted-foreground">
      Không thể tải dữ liệu dashboard. Vui lòng thử lại sau.
    </div>
  </div>
</template>

<style scoped>
.dashboard-toolbar {
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

.toolbar-left {
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
</style>
