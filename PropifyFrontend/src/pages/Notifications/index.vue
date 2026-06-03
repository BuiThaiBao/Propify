<template>
  <main class="min-h-screen bg-[#f4f8fc] pb-14 pt-24">
    <div class="mx-auto w-full max-w-[980px] px-4">
      <div class="flex flex-wrap items-center justify-between gap-3">
        <div>
          <p class="text-sm text-slate-500">Trang chủ / Thông báo</p>
          <h1 class="mt-2 text-2xl font-extrabold text-slate-900">Thông báo</h1>
        </div>
        <button
          type="button"
          class="rounded-xl border border-sky-200 bg-white px-4 py-2 text-sm font-semibold text-sky-600 transition hover:bg-sky-50 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="!notificationStore.unreadCount"
          @click="markAllAsRead"
        >
          Đánh dấu tất cả đã đọc
        </button>
      </div>

      <div class="mt-5 inline-flex rounded-xl border border-slate-200 bg-white p-1">
        <button
          type="button"
          :class="filter === 'all' ? activeFilterClass : inactiveFilterClass"
          @click="setFilter('all')"
        >
          Tất cả
        </button>
        <button
          type="button"
          :class="filter === 'unread' ? activeFilterClass : inactiveFilterClass"
          @click="setFilter('unread')"
        >
          Chưa đọc
        </button>
      </div>

      <section class="mt-5 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div v-if="notificationStore.loadingAll && !notificationStore.all.length" class="p-8 text-center text-sm text-slate-500">
          Đang tải thông báo...
        </div>
        <div v-else-if="!notificationStore.all.length" class="p-8 text-center text-sm text-slate-500">
          Chưa có thông báo nào.
        </div>
        <button
          v-for="notification in notificationStore.all"
          :key="notification.id"
          type="button"
          class="flex w-full gap-3 border-b border-slate-100 px-5 py-4 text-left transition last:border-b-0 hover:bg-sky-50/50"
          @click="openNotification(notification)"
        >
          <span
            class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full"
            :class="notification.is_read ? 'bg-slate-200' : 'bg-sky-500'"
          ></span>
          <span class="min-w-0 flex-1">
            <span class="block text-sm font-bold text-slate-800">{{ notification.title }}</span>
            <span class="mt-1 block text-sm leading-6 text-slate-600">{{ notification.message }}</span>
            <span class="mt-2 block text-xs text-slate-400">{{ formatDate(notification.created_at) }}</span>
          </span>
        </button>
      </section>

      <div v-if="canLoadMore" class="mt-5 flex justify-center">
        <button
          type="button"
          class="rounded-xl bg-sky-500 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-60"
          :disabled="notificationStore.loadingAll"
          @click="loadMore"
        >
          {{ notificationStore.loadingAll ? 'Đang tải...' : 'Xem thêm' }}
        </button>
      </div>
    </div>
  </main>
</template>

<script setup>
import { computed, onMounted, ref } from "vue";
import { useRouter } from "vue-router";
import { useNotificationStore } from "@/stores/notifications";

const router = useRouter();
const notificationStore = useNotificationStore();
const filter = ref("all");

const activeFilterClass = "rounded-lg bg-sky-500 px-4 py-2 text-sm font-semibold text-white";
const inactiveFilterClass = "rounded-lg px-4 py-2 text-sm font-semibold text-slate-500 hover:bg-slate-50";

const canLoadMore = computed(() =>
  notificationStore.pagination.current_page < notificationStore.pagination.last_page,
);

function formatDate(value) {
  if (!value) return "";
  return new Intl.DateTimeFormat("vi-VN", {
    hour: "2-digit",
    minute: "2-digit",
    day: "2-digit",
    month: "2-digit",
    year: "numeric",
  }).format(new Date(value));
}

async function loadPage(page = 1, append = false) {
  await notificationStore.fetchAll({
    page,
    unread: filter.value === "unread",
    append,
  });
}

async function setFilter(nextFilter) {
  if (filter.value === nextFilter) return;
  filter.value = nextFilter;
  await loadPage(1, false);
}

async function loadMore() {
  await loadPage(notificationStore.pagination.current_page + 1, true);
}

async function markAllAsRead() {
  await notificationStore.markAllAsRead();
  if (filter.value === "unread") {
    await loadPage(1, false);
  }
}

async function openNotification(notification) {
  if (!notification.is_read) {
    await notificationStore.markAsRead(notification.id);
  }

  const actionUrl = notification.data?.action_url;
  if (actionUrl) {
    router.push(actionUrl);
    return;
  }

  if (notification.data?.listing_id && notification.data?.status === "ACTIVE") {
    router.push(`/listings/${notification.data.listing_id}`);
    return;
  }

  router.push("/profile?tab=listings");
}

onMounted(() => {
  loadPage();
});
</script>
