<script setup>
import { ref } from 'vue'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import StatusBadge from '@/components/shared/StatusBadge.vue'
import { FileText, CheckCircle, XCircle, DollarSign, TrendingUp, Clock } from 'lucide-vue-next'

const revenueData = [
  { month: 'T1', revenue: 12000000 },
  { month: 'T2', revenue: 18000000 },
  { month: 'T3', revenue: 15000000 },
  { month: 'T4', revenue: 22000000 },
  { month: 'T5', revenue: 28000000 },
  { month: 'T6', revenue: 25000000 },
  { month: 'T7', revenue: 32000000 },
  { month: 'T8', revenue: 30000000 },
  { month: 'T9', revenue: 35000000 },
  { month: 'T10', revenue: 38000000 },
  { month: 'T11', revenue: 42000000 },
  { month: 'T12', revenue: 45000000 },
]

const recentActivities = [
  { id: 1, title: 'Căn hộ 3PN Vinhomes Central Park', user: 'Nguyễn Văn A', time: '5 phút trước', status: 'pending' },
  { id: 2, title: 'Nhà phố Quận 2 - 120m²', user: 'Trần Thị B', time: '15 phút trước', status: 'approved' },
  { id: 3, title: 'Đất nền Long An giá rẻ', user: 'Lê Văn C', time: '1 giờ trước', status: 'rejected' },
  { id: 4, title: 'Gói VIP 30 ngày - Premium', user: 'Phạm Văn D', time: '2 giờ trước', status: 'approved' },
  { id: 5, title: 'Đăng ký tài khoản môi giới', user: 'Hoàng Thị E', time: '3 giờ trước', status: 'pending' },
]

function formatCurrency(val) {
  return new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 1 }).format(val) + 'đ'
}

// SVG chart math
const chartW = 560
const chartH = 280
const padL = 58
const padR = 20
const padT = 10
const padB = 30
const maxRev = Math.max(...revenueData.map(d => d.revenue))

function getX(i) {
  return padL + (i / (revenueData.length - 1)) * (chartW - padL - padR)
}
function getY(v) {
  return padT + (1 - v / maxRev) * (chartH - padT - padB)
}

const linePath = revenueData.map((d, i) => `${i === 0 ? 'M' : 'L'}${getX(i)},${getY(d.revenue)}`).join(' ')
const areaPath = `${linePath} L${getX(revenueData.length - 1)},${chartH - padB} L${getX(0)},${chartH - padB} Z`

const yTicks = [0, 10000000, 20000000, 30000000, 40000000, 45000000]
</script>

<template>
  <div>
    <PageHeader title="Dashboard" description="Tổng quan hệ thống Propify" />

    <!-- Stats grid -->
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 xl:grid-cols-4">
      <StatCard
        title="Tổng số tin đăng"
        value="1,247"
        change="+12% so với tháng trước"
        change-type="positive"
        :icon="FileText"
      />
      <StatCard
        title="Tin đã duyệt"
        value="1,089"
        change="+8% so với tháng trước"
        change-type="positive"
        :icon="CheckCircle"
        icon-color="bg-success/10"
      />
      <StatCard
        title="Tin bị từ chối"
        value="58"
        change="-3% so với tháng trước"
        change-type="negative"
        :icon="XCircle"
        icon-color="bg-destructive/10"
      />
      <StatCard
        title="Doanh thu"
        value="45M đ"
        change="+18% so với tháng trước"
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
            <h2 class="m-0 mb-0.5 text-lg font-semibold text-foreground">Doanh thu theo thời gian</h2>
            <p class="m-0 text-sm text-muted-foreground">Năm 2024</p>
          </div>
          <div class="flex items-center gap-1 text-sm font-medium text-success">
            <TrendingUp :size="16" />
            +18%
          </div>
        </div>
        <div class="w-full">
          <svg :viewBox="`0 0 ${chartW} ${chartH}`" class="block h-[280px] w-full" preserveAspectRatio="none">
            <defs>
              <linearGradient id="dashGrad" x1="0" y1="0" x2="0" y2="1">
                <stop offset="5%" stop-color="hsl(217,91%,60%)" stop-opacity="0.2" />
                <stop offset="95%" stop-color="hsl(217,91%,60%)" stop-opacity="0" />
              </linearGradient>
            </defs>
            <!-- Grid lines -->
            <line
              v-for="t in yTicks"
              :key="t"
              :x1="padL" :y1="getY(t)"
              :x2="chartW - padR" :y2="getY(t)"
              stroke="hsl(214,20%,92%)"
              stroke-dasharray="3 3"
              stroke-width="1"
            />
            <!-- Y labels -->
            <text
              v-for="t in yTicks"
              :key="'y'+t"
              :x="padL - 4"
              :y="getY(t) + 4"
              text-anchor="end"
              font-size="11"
              fill="hsl(215,16%,47%)"
            >{{ formatCurrency(t) }}</text>
            <!-- Area -->
            <path :d="areaPath" fill="url(#dashGrad)" />
            <!-- Line -->
            <path :d="linePath" fill="none" stroke="hsl(217,91%,60%)" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" />
            <!-- X labels -->
            <text
              v-for="(d, i) in revenueData"
              :key="'x'+i"
              :x="getX(i)"
              :y="chartH - 4"
              text-anchor="middle"
              font-size="11"
              fill="hsl(215,16%,47%)"
            >{{ d.month }}</text>
          </svg>
        </div>
      </div>

      <!-- Recent activities -->
      <div class="rounded-xl border border-border/50 bg-card p-6 shadow-card">
        <h2 class="m-0 mb-4 text-lg font-semibold text-foreground">Hoạt động gần đây</h2>
        <div class="flex flex-col gap-4">
          <div v-for="a in recentActivities" :key="a.id" class="flex items-start gap-3">
            <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-muted">
              <Clock :size="14" color="hsl(215,16%,47%)" />
            </div>
            <div class="min-w-0 flex-1">
              <p class="m-0 mb-0.5 truncate text-sm font-medium text-foreground">{{ a.title }}</p>
              <p class="m-0 text-xs text-muted-foreground">{{ a.user }} · {{ a.time }}</p>
            </div>
            <StatusBadge :status="a.status" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
