<script setup>
import { Clock } from 'lucide-vue-next'
import StatusBadge from '@/components/shared/StatusBadge.vue'

const activities = [
  {
    id: 1,
    title: 'Căn hộ 3PN Vinhomes Central Park',
    user: 'Nguyễn Văn A',
    time: '5 phút trước',
    status: 'pending',
    statusText: 'Chờ duyệt',
  },
  {
    id: 2,
    title: 'Nhà phố Quận 2 - 120m²',
    user: 'Trần Thị B',
    time: '15 phút trước',
    status: 'success',
    statusText: 'Đã duyệt',
  },
  {
    id: 3,
    title: 'Đất nền Long An giá rẻ',
    user: 'Lê Văn C',
    time: '1 giờ trước',
    status: 'danger',
    statusText: 'Từ chối',
  },
  {
    id: 4,
    title: 'Gói VIP 30 ngày - Premium',
    user: 'Phạm Văn D',
    time: '2 giờ trước',
    status: 'success',
    statusText: 'Đã duyệt',
  },
  {
    id: 5,
    title: 'Đăng ký tài khoản mới giới',
    user: 'Hoàng Thị E',
    time: '3 giờ trước',
    status: 'pending',
    statusText: 'Chờ duyệt',
  },
]
</script>

<template>
  <div class="activity-card">
    <h3 class="activity-title">Hoạt động gần đây</h3>

    <div class="activity-list">
      <div
        v-for="item in activities"
        :key="item.id"
        class="activity-item"
      >
        <div class="activity-icon">
          <Clock :size="14" class="text-slate-400" />
        </div>
        <div class="activity-content">
          <p class="activity-name">{{ item.title }}</p>
          <p class="activity-meta">{{ item.user }} · {{ item.time }}</p>
        </div>
        <StatusBadge :text="item.statusText" :variant="item.status" />
      </div>
    </div>
  </div>
</template>

<style scoped>
.activity-card {
  background-color: #ffffff;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 20px;
}

.activity-title {
  font-size: 16px;
  font-weight: 600;
  color: #0f172a;
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
  gap: 10px;
}

.activity-icon {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background-color: #f8fafc;
  border: 1px solid #e2e8f0;
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
  font-size: 13px;
  font-weight: 500;
  color: #0f172a;
  margin: 0 0 2px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.activity-meta {
  font-size: 12px;
  color: #94a3b8;
  margin: 0;
}
</style>
