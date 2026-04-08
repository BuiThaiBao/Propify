<script setup>
import { ref } from 'vue'
import { Download, TrendingUp, DollarSign, Package } from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import RevenueBarChart from './RevenueBarChart.vue'
import PackageDonutChart from './PackageDonutChart.vue'

const selectedYear = ref('2024')

const years = ['2024', '2023', '2022', '2021']
</script>

<template>
  <div>
    <PageHeader
      title="Doanh thu & báo cáo"
      subtitle="Thống kê doanh thu và hiệu suất hệ thống"
    >
      <template #actions>
        <select v-model="selectedYear" class="year-select" id="year-select">
          <option v-for="y in years" :key="y" :value="y">Năm {{ y }}</option>
        </select>
        <button class="btn-export" id="export-btn">
          <Download :size="16" />
          Xuất báo cáo
        </button>
      </template>
    </PageHeader>

    <!-- Stats -->
    <div class="revenue-stats">
      <StatCard
        label="Tổng doanh thu"
        value="540M đ"
        change="+18% so với năm ngoái"
        change-type="positive"
        :icon="DollarSign"
        icon-bg="#d1fae5"
        icon-color="#10b981"
      />
      <StatCard
        label="Gói đã bán"
        value="1,250"
        change="+12% so với năm ngoái"
        change-type="positive"
        :icon="Package"
        icon-bg="#dbeafe"
        icon-color="#2563eb"
      />
      <StatCard
        label="Tăng trưởng"
        value="+18%"
        change="So với năm 2023"
        change-type="positive"
        :icon="TrendingUp"
        icon-bg="#fef3c7"
        icon-color="#f59e0b"
      />
    </div>

    <!-- Charts -->
    <div class="charts-row">
      <RevenueBarChart :year="selectedYear" />
      <PackageDonutChart />
    </div>
  </div>
</template>

<style scoped>
.revenue-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 16px;
  margin-bottom: 24px;
}

.charts-row {
  display: grid;
  grid-template-columns: 2fr 1fr;
  gap: 16px;
}

.year-select {
  height: 38px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  font-size: 13px;
  color: #64748b;
  background-color: #ffffff;
  outline: none;
  cursor: pointer;
}

.year-select:focus {
  border-color: #2563eb;
}

.btn-export {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 8px 16px;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  background-color: #ffffff;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-export:hover {
  background-color: #f8fafc;
}

@media (max-width: 1024px) {
  .revenue-stats {
    grid-template-columns: 1fr;
  }

  .charts-row {
    grid-template-columns: 1fr;
  }
}
</style>
