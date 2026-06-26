<script setup>
import { ref, onMounted } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { FileText, CheckCircle, XCircle, DollarSign, TrendingUp, Clock, User } from 'lucide-vue-next'
import { formatCompactCurrency, formatTransactionAmount } from '@/utils/transactionFormatters'
import dashboardService from '@/services/dashboardService'

const loading = ref(true)
const stats = ref(null)

const formatCurrency = formatCompactCurrency

onMounted(async () => {
  try {
    const res = await dashboardService.getStats()
    stats.value = res.data?.data || null
  } catch (e) {
    console.error('Failed to load dashboard stats', e)
  } finally {
    loading.value = false
  }
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

function chartLinePath() {
  const data = getChartData()
  if (!data.length) return ''
  return data
    .map((d, i) => `${i === 0 ? 'M' : 'L'}${getX(i)},${getY(d.revenue)}`)
    .join(' ')
}

function chartAreaPath() {
  const data = getChartData()
  if (!data.length) return ''
  const line = chartLinePath()
  const lastIdx = data.length - 1
  return `${line} L${getX(lastIdx)},${chartH - padB} L${getX(0)},${chartH - padB} Z`
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
      <!-- Stats grid -->
      <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard
          title="Tổng số tin đăng"
          :value="stats.listings.total.toLocaleString('vi-VN')"
          :change="`+${calcPercentageChange(stats.listings_change.current_month, stats.listings_change.last_month)} so với tháng trước`"
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
          :change="`Tháng này: ${formatCurrency(stats.revenue.current_month)}`"
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
              <h2 class="m-0 mb-0.5 text-lg font-semibold text-foreground">
                Doanh thu theo thời gian
              </h2>
              <p class="m-0 text-sm text-muted-foreground">Năm {{ new Date().getFullYear() }}</p>
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
          <div class="w-full">
            <svg
              :viewBox="`0 0 ${chartW} ${chartH}`"
              class="block h-[280px] w-full"
              preserveAspectRatio="none"
            >
              <defs>
                <linearGradient id="dashGrad" x1="0" y1="0" x2="0" y2="1">
                  <stop offset="5%" stop-color="hsl(217,91%,60%)" stop-opacity="0.2" />
                  <stop offset="95%" stop-color="hsl(217,91%,60%)" stop-opacity="0" />
                </linearGradient>
              </defs>
              <!-- Grid lines -->
              <line
                v-for="t in yTicks()"
                :key="t"
                :x1="padL"
                :y1="getY(t)"
                :x2="chartW - padR"
                :y2="getY(t)"
                stroke="hsl(214,20%,92%)"
                stroke-dasharray="3 3"
                stroke-width="1"
              />
              <!-- Y labels -->
              <text
                v-for="t in yTicks()"
                :key="'y' + t"
                :x="padL - 4"
                :y="getY(t) + 4"
                text-anchor="end"
                font-size="11"
                fill="hsl(215,16%,47%)"
              >
                {{ formatCurrency(t) }}
              </text>
              <!-- Area -->
              <path :d="chartAreaPath()" fill="url(#dashGrad)" />
              <!-- Line -->
              <path
                :d="chartLinePath()"
                fill="none"
                stroke="hsl(217,91%,60%)"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
              />
              <!-- X labels -->
              <text
                v-for="(d, i) in getChartData()"
                :key="'x' + i"
                :x="getX(i)"
                :y="chartH - 4"
                text-anchor="middle"
                font-size="11"
                fill="hsl(215,16%,47%)"
              >
                {{ d.month }}
              </text>
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
