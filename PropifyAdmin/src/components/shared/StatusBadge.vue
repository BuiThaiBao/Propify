<script setup>
defineProps({
  status: {
    type: String,
    required: true,
    // 'approved' | 'pending' | 'rejected' | 'locked'
  },
  label: {
    type: String,
    default: '',
  },
})

const config = {
  approved: { bg: 'badge-success', text: 'badge-success-text', default: 'Đã duyệt' },
  pending: { bg: 'badge-warning', text: 'badge-warning-text', default: 'Chờ duyệt' },
  rejected: { bg: 'badge-danger', text: 'badge-danger-text', default: 'Từ chối' },
  locked: { bg: 'badge-muted', text: 'badge-muted-text', default: 'Đã khóa' },
}
</script>

<template>
  <span
    class="status-badge"
    :class="[config[status]?.bg, config[status]?.text]"
  >
    {{ label || config[status]?.default }}
  </span>
</template>

<style scoped>
.status-badge {
  display: inline-flex;
  align-items: center;
  padding: 4px 10px;
  border-radius: 9999px;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
}

.badge-success { background-color: hsl(var(--success) / 0.1); }
.badge-success-text { color: hsl(var(--success)); }

.badge-warning { background-color: hsl(var(--warning) / 0.1); }
.badge-warning-text { color: hsl(var(--warning)); }

.badge-danger { background-color: hsl(var(--destructive) / 0.1); }
.badge-danger-text { color: hsl(var(--destructive)); }

.badge-muted { background-color: hsl(var(--muted)); }
.badge-muted-text { color: hsl(var(--muted-foreground)); }
</style>
