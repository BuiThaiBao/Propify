import api from "./api";

const notificationService = {
  getNotifications(params = {}) {
    return api.get("/v1/notifications", { params });
  },

  getUnreadCount() {
    return api.get("/v1/notifications/unread-count");
  },

  markAsRead(notificationId) {
    return api.post(`/v1/notifications/${notificationId}/read`);
  },

  markAllAsRead() {
    return api.post("/v1/notifications/read-all");
  },
};

export default notificationService;
