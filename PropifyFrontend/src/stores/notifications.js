import { defineStore } from "pinia";
import { computed, ref } from "vue";
import notificationService from "@/services/notificationService";

export const useNotificationStore = defineStore("notifications", () => {
  const recent = ref([]);
  const all = ref([]);
  const unreadCount = ref(0);
  const loadingRecent = ref(false);
  const loadingAll = ref(false);
  const pagination = ref({
    current_page: 1,
    last_page: 1,
    per_page: 10,
    total: 0,
  });
  let pollingTimer = null;

  const hasUnread = computed(() => unreadCount.value > 0);

  function normalizePayload(response) {
    return {
      items: response.data?.data || [],
      meta: response.data?.meta || {},
    };
  }

  async function fetchUnreadCount() {
    const response = await notificationService.unreadCount();
    unreadCount.value = Number(response.data?.data?.count || 0);
    return unreadCount.value;
  }

  async function fetchRecent() {
    loadingRecent.value = true;
    try {
      const response = await notificationService.list({ per_page: 5 });
      recent.value = normalizePayload(response).items;
      await fetchUnreadCount();
    } finally {
      loadingRecent.value = false;
    }
  }

  async function fetchAll({ page = 1, unread = false, append = false } = {}) {
    loadingAll.value = true;
    try {
      const response = await notificationService.list({
        page,
        per_page: 10,
        unread: unread ? 1 : undefined,
      });
      const payload = normalizePayload(response);
      all.value = append ? [...all.value, ...payload.items] : payload.items;
      pagination.value = {
        current_page: Number(payload.meta.current_page || page),
        last_page: Number(payload.meta.last_page || 1),
        per_page: Number(payload.meta.per_page || 10),
        total: Number(payload.meta.total || payload.items.length),
      };
      await fetchUnreadCount();
    } finally {
      loadingAll.value = false;
    }
  }

  function updateReadState(id, notification = null) {
    const apply = (item) => (item.id === id ? { ...item, ...(notification || {}), is_read: true, read_at: notification?.read_at || item.read_at || new Date().toISOString() } : item);
    recent.value = recent.value.map(apply);
    all.value = all.value.map(apply);
  }

  async function markAsRead(id) {
    const response = await notificationService.markAsRead(id);
    updateReadState(id, response.data?.data);
    unreadCount.value = Math.max(0, unreadCount.value - 1);
  }

  async function markAllAsRead() {
    await notificationService.markAllAsRead();
    unreadCount.value = 0;
    const markRead = (item) => ({ ...item, is_read: true, read_at: item.read_at || new Date().toISOString() });
    recent.value = recent.value.map(markRead);
    all.value = all.value.map(markRead);
  }

  function startPolling() {
    if (pollingTimer) return;
    fetchUnreadCount().catch(() => {});
    pollingTimer = window.setInterval(() => {
      fetchUnreadCount().catch(() => {});
    }, 30000);
  }

  function stopPolling() {
    if (pollingTimer) {
      window.clearInterval(pollingTimer);
      pollingTimer = null;
    }
  }

  function reset() {
    stopPolling();
    recent.value = [];
    all.value = [];
    unreadCount.value = 0;
    pagination.value = {
      current_page: 1,
      last_page: 1,
      per_page: 10,
      total: 0,
    };
  }

  return {
    recent,
    all,
    unreadCount,
    loadingRecent,
    loadingAll,
    pagination,
    hasUnread,
    fetchUnreadCount,
    fetchRecent,
    fetchAll,
    markAsRead,
    markAllAsRead,
    startPolling,
    stopPolling,
    reset,
  };
});
