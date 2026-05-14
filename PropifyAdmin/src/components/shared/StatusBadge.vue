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
  approved: { class: 'bg-success/10 text-success', default: 'Đã duyệt' },
  pending: { class: 'bg-warning/10 text-warning', default: 'Chờ duyệt' },
  rejected: { class: 'bg-destructive/10 text-destructive', default: 'Từ chối' },
  locked: { class: 'bg-muted text-muted-foreground', default: 'Đã khóa' },
}
</script>

<template>
  <span
    class="inline-flex items-center whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium"
    :class="config[status]?.class"
  >
    {{ label || config[status]?.default }}
  </span>
</template>
