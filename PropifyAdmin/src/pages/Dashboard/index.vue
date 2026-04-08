<script setup>
import { FileText, CheckCircle, XCircle, DollarSign } from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import RevenueChart from './RevenueChart.vue'
import RecentActivity from './RecentActivity.vue'
</script>

<template>
  <div>
    <PageHeader
      title="Dashboard"
      subtitle="Tổng quan hệ thống Propify"
    />

    <!-- Stats Grid -->
    <div class="stats-grid">
      <StatCard
        label="Tổng số tin đăng"
        value="1,247"
        change="+12% so với tháng trước"
        change-type="positive"
        :icon="FileText"
        icon-bg="#eff6ff"
        icon-color="#2563eb"
      />
      <StatCard
        label="Tin đã duyệt"
        value="1,089"
        change="+8% so với tháng trước"
        change-type="positive"
        :icon="CheckCircle"
        icon-bg="#d1fae5"
        icon-color="#10b981"
      />
      <StatCard
        label="Tin bị từ chối"
        value="58"
        change="-3% so với tháng trước"
        change-type="negative"
        :icon="XCircle"
        icon-bg="#fee2e2"
        icon-color="#ef4444"
      />
      <StatCard
        label="Doanh thu"
        value="45M đ"
        change="+18% so với tháng trước"
        change-type="positive"
        :icon="DollarSign"
        icon-bg="#fef3c7"
        icon-color="#f59e0b"
      />
    </div>

    <!-- Charts & Activity -->
    <div class="dashboard-bottom">
      <RevenueChart />
      <RecentActivity />
    </div>
  </div>
</template>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.dashboard-bottom {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
}

@media (max-width: 1200px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }

  .dashboard-bottom {
    grid-template-columns: 1fr;
  }
}
</style>
