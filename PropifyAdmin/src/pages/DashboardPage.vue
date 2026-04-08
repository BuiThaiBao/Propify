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
    <div class="stats-grid">
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
    <div class="dashboard-bottom">
      <!-- Revenue chart -->
      <div class="chart-card">
        <div class="chart-header">
          <div>
            <h2 class="chart-title">Doanh thu theo thời gian</h2>
            <p class="chart-sub">Năm 2024</p>
          </div>
          <div class="chart-badge">
            <TrendingUp :size="16" />
            +18%
          </div>
        </div>
        <div class="chart-wrap">
          <svg :viewBox="`0 0 ${chartW} ${chartH}`" class="chart-svg" preserveAspectRatio="none">
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
      <div class="activity-card">
        <h2 class="activity-title">Hoạt động gần đây</h2>
        <div class="activity-list">
          <div v-for="a in recentActivities" :key="a.id" class="activity-item">
            <div class="activity-icon">
              <Clock :size="14" color="hsl(215,16%,47%)" />
            </div>
            <div class="activity-content">
              <p class="activity-name">{{ a.title }}</p>
              <p class="activity-meta">{{ a.user }} · {{ a.time }}</p>
            </div>
            <StatusBadge :status="a.status" />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 20px;
  margin-bottom: 32px;
}

.dashboard-bottom {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 24px;
}

/* Chart card */
.chart-card {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 24px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
}

.chart-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 24px;
}

.chart-title {
  font-size: 18px;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0 0 2px 0;
}

.chart-sub {
  font-size: 14px;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

.chart-badge {
  display: flex;
  align-items: center;
  gap: 4px;
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--success));
}

.chart-wrap {
  width: 100%;
}

.chart-svg {
  width: 100%;
  height: 280px;
  display: block;
}

/* Activity card */
.activity-card {
  background-color: hsl(var(--card));
  border-radius: 12px;
  padding: 24px;
  box-shadow: var(--shadow-card);
  border: 1px solid hsl(var(--border) / 0.5);
}

.activity-title {
  font-size: 18px;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0 0 16px 0;
}

.activity-list {
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.activity-item {
  display: flex;
  align-items: flex-start;
  gap: 12px;
}

.activity-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background-color: hsl(var(--muted));
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-top: 2px;
}

.activity-content {
  flex: 1;
  min-width: 0;
}

.activity-name {
  font-size: 14px;
  font-weight: 500;
  color: hsl(var(--foreground));
  margin: 0 0 2px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-meta {
  font-size: 12px;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

@media (max-width: 1200px) {
  .stats-grid { grid-template-columns: repeat(2, 1fr); }
  .dashboard-bottom { grid-template-columns: 1fr; }
}

@media (max-width: 640px) {
  .stats-grid { grid-template-columns: 1fr; }
}
</style>
