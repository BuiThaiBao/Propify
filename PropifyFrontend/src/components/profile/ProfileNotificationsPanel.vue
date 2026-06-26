<template>
  <section class="bg-white rounded-xl shadow-sm p-6">
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3 mb-6">
      <div>
        <h2 class="text-xl font-bold text-slate-800">Thông báo</h2>
        <p class="text-sm text-slate-500 mt-1">{{ meta.unreadCount }} thông báo chưa đọc</p>
      </div>
      <button
        type="button"
        class="px-4 py-2 rounded-lg border border-sky-200 bg-sky-50 text-sky-600 text-sm font-medium hover:bg-sky-100 transition-colors disabled:opacity-50"
        :disabled="meta.unreadCount === 0 || isLoading"
        @click="markAllProfileNotificationsRead"
      >
        Đánh dấu tất cả là đã đọc
      </button>
    </div>

    <div v-if="isLoading" class="py-10 text-center text-sm text-slate-400">
      Đang tải thông báo...
    </div>

    <div v-else-if="notifications.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-400">
      Chưa có thông báo nào.
    </div>

    <div v-else class="space-y-3">
      <div
        v-for="item in notifications"
        :key="item.id"
        class="rounded-xl border px-4 py-4 transition-colors"
        :class="item.read_at ? 'border-slate-200 bg-white' : 'border-sky-200 bg-sky-50/60'"
      >
        <div class="flex items-start justify-between gap-3">
          <div class="min-w-0">
            <p class="text-sm font-semibold text-slate-800">{{ item.title }}</p>
            <p class="text-sm text-slate-600 mt-1">{{ item.content }}</p>
            <p class="text-xs text-slate-400 mt-2">{{ formatNotificationDate(item.created_at) }}</p>
          </div>
          <button
            v-if="!item.read_at"
            type="button"
            class="shrink-0 px-3 py-1.5 rounded-lg border border-sky-200 bg-white text-sky-600 text-xs font-medium hover:bg-sky-50 transition-colors"
            @click="markProfileNotificationRead(item)"
          >
            Đánh dấu đã đọc
          </button>
        </div>
      </div>

      <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between gap-3 text-sm text-slate-500">
        <p>Tổng {{ meta.total }} thông báo</p>
        <div class="flex items-center gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 w-8 h-8 flex items-center justify-center hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="meta.currentPage <= 1 || isLoading"
            @click="loadProfileNotifications(meta.currentPage - 1)"
          >
            ‹
          </button>
          <span>Trang {{ meta.currentPage }}/{{ meta.lastPage }}</span>
          <button
            type="button"
            class="rounded-lg border border-slate-200 w-8 h-8 flex items-center justify-center hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
            :disabled="meta.currentPage >= meta.lastPage || isLoading"
            @click="loadProfileNotifications(meta.currentPage + 1)"
          >
            ›
          </button>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from 'vue';
import {
  useProfileNotifications,
  useMarkNotificationRead,
  useMarkAllNotificationsRead,
} from '@/composables/useNotifications';

const currentPage = ref(1);

const { notifications, meta, isLoading } = useProfileNotifications(currentPage, { perPage: 10 });
const markReadMutation = useMarkNotificationRead();
const markAllReadMutation = useMarkAllNotificationsRead();

function formatNotificationDate(value) {
  if (!value) return '';
  return new Date(value).toLocaleString('vi-VN');
}

function loadProfileNotifications(page) {
  currentPage.value = page;
}

function markProfileNotificationRead(item) {
  markReadMutation.mutate(item.id);
}

function markAllProfileNotificationsRead() {
  markAllReadMutation.mutate();
}
</script>
