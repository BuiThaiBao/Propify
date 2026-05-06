<template>
  <div
    v-if="open"
    class="fixed inset-0 z-[1200] flex items-center justify-center bg-slate-900/45 px-4"
    @click.self="$emit('cancel')"
  >
    <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
      <h3 class="text-lg font-bold text-slate-800">{{ title }}</h3>
      <p class="mt-2 text-sm leading-6 text-slate-600">{{ message }}</p>

      <div class="mt-6 flex items-center justify-end gap-3">
        <button
          type="button"
          class="rounded-lg border border-slate-200 px-4 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="$emit('cancel')"
        >
          {{ cancelText }}
        </button>
        <button
          type="button"
          class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="loading"
          @click="$emit('confirm')"
        >
          {{ loading ? loadingText : confirmText }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
  open: {
    type: Boolean,
    default: false,
  },
  title: {
    type: String,
    default: 'Xác nhận thao tác',
  },
  message: {
    type: String,
    default: 'Bạn có chắc chắn muốn tiếp tục?',
  },
  confirmText: {
    type: String,
    default: 'Xác nhận',
  },
  cancelText: {
    type: String,
    default: 'Hủy',
  },
  loadingText: {
    type: String,
    default: 'Đang xử lý...',
  },
  loading: {
    type: Boolean,
    default: false,
  },
});

defineEmits(['confirm', 'cancel']);
</script>
