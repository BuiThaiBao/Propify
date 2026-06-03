import api from "./api";

const notificationService = {
  list(params = {}) {
    return api.get("/v1/notifications", { params });
  },

  getNotifications(params = {}) {
    return this.list(params);
  },

  unreadCount() {
    return api.get("/v1/notifications/unread-count");
  },

  getUnreadCount() {
    return this.unreadCount();
  },

  markAsRead(id) {
    return api.patch(`/v1/notifications/${id}/read`);
  },

  markAllAsRead() {
    return api.patch("/v1/notifications/read-all");
  },
};

export default notificationService;
