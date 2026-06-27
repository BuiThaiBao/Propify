import api from './api';

/**
 * chatService — Wrapper các API call cho tính năng chat.
 * Tất cả response trả về raw axios response để store tự xử lý.
 */
const chatService = {
  /**
   * Lấy danh sách conversations của user hiện tại.
   */
  getConversations() {
    return api.get('/v1/chat/conversations');
  },

  /**
   * Lấy hoặc tạo conversation với user khác (idempotent).
   * @param {number} otherUserId
   * @param {number|null} listingId
   */
  getOrCreateConversation(otherUserId, listingId = null) {
    return api.post('/v1/chat/conversations', {
      other_user_id: otherUserId,
      listing_id: listingId,
    });
  },

  /**
   * Lấy messages theo cursor pagination.
   * @param {number} conversationId
   * @param {string|null} cursor  - null = lấy trang đầu (latest messages)
   */
  getMessages(conversationId, cursor = null) {
    return api.get(`/v1/chat/conversations/${conversationId}/messages`, {
      params: cursor ? { cursor } : {},
    });
  },

  /**
   * Gửi text message.
   * @param {number} conversationId
   * @param {string} body
   */
  sendTextMessage(conversationId, body) {
    return api.post(`/v1/chat/conversations/${conversationId}/messages`, {
      type: 'text',
      body,
    });
  },

  /**
   * Gửi message dạng ảnh hoặc file.
   * @param {number} conversationId
   * @param {'image'|'file'} type
   * @param {string} fileUrl
   */
  sendFileMessage(conversationId, type, fileUrl, metadata = null) {
    const payload = { type, file_url: fileUrl };
    if (metadata) {
      payload.metadata = metadata;
    }
    return api.post(`/v1/chat/conversations/${conversationId}/messages`, payload);
  },

  /**
   * Đánh dấu tất cả messages trong conversation là đã đọc.
   * @param {number} conversationId
   */
  markAsRead(conversationId) {
    return api.post(`/v1/chat/conversations/${conversationId}/read`);
  },

  /**
   * Tìm user theo số điện thoại để bắt đầu chat.
   * @param {string} phone
   */
  searchUserByPhone(phone) {
    return api.get('/v1/user/search', { params: { phone } });
  },

  createGroup(name, memberIds, avatarUrl = null) {
    return api.post('/v1/chat/groups', {
      name,
      member_ids: memberIds,
      avatar_url: avatarUrl,
    });
  },

  updateGroup(conversationId, data) {
    return api.put(`/v1/chat/groups/${conversationId}`, data);
  },

  getGroupMembers(conversationId) {
    return api.get(`/v1/chat/groups/${conversationId}/members`);
  },

  addGroupMembers(conversationId, userIds) {
    return api.post(`/v1/chat/groups/${conversationId}/members`, { user_ids: userIds });
  },

  removeGroupMember(conversationId, userId) {
    return api.delete(`/v1/chat/groups/${conversationId}/members/${userId}`);
  },

  transferGroupAdmin(conversationId, userId) {
    return api.post(`/v1/chat/groups/${conversationId}/members/${userId}/transfer-admin`);
  },

  leaveGroup(conversationId) {
    return api.post(`/v1/chat/groups/${conversationId}/leave`);
  },
};

export default chatService;
