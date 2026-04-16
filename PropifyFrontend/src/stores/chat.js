import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import chatService from '@/services/chatService';
import { getEcho } from '@/plugins/echo';
import { useAuthStore } from '@/stores/auth';

export const useChatStore = defineStore('chat', () => {
  // ============ STATE ============
  /** @type {import('vue').Ref<Array>} */
  const conversations = ref([]);
  /** @type {import('vue').Ref<Object|null>} Conversation đang mở */
  const activeConversation = ref(null);
  /** @type {import('vue').Ref<Array>} Messages của conversation đang mở */
  const messages = ref([]);
  const loadingConversations = ref(false);
  const loadingMessages = ref(false);
  const sending = ref(false);
  /** Cursor cho infinite scroll */
  const nextCursor = ref(null);
  const hasMore = ref(false);
  /** Typing indicators: Set of user IDs đang nhập */
  const typingUsers = ref(new Set());
  /** Channel subscription hiện tại */
  let activeChannel = null;

  // ============ GETTERS ============
  const totalUnread = computed(() =>
    conversations.value.reduce((sum, c) => sum + (c.unread_count ?? 0), 0),
  );

  // ============ ACTIONS ============

  /**
   * Tải danh sách conversations.
   */
  async function loadConversations() {
    loadingConversations.value = true;
    try {
      const res = await chatService.getConversations();
      conversations.value = res.data.data ?? [];
    } finally {
      loadingConversations.value = false;
    }
  }

  /**
   * Lấy hoặc tạo conversation với user, rồi mở nó.
   * @param {number} otherUserId
   * @param {number|null} listingId
   */
  async function openConversationWith(otherUserId, listingId = null) {
    const res = await chatService.getOrCreateConversation(otherUserId, listingId);
    const conversation = res.data.data;

    // Thêm vào list nếu chưa có
    const existing = conversations.value.find((c) => c.id === conversation.id);
    if (!existing) {
      conversations.value.unshift(conversation);
    }

    await openConversation(conversation);
    return conversation;
  }

  /**
   * Mở một conversation cụ thể — tải messages + subscribe WebSocket.
   * @param {Object} conversation
   */
  async function openConversation(conversation) {
    // Unsubscribe conversation cũ
    unsubscribeActive();

    activeConversation.value = conversation;
    messages.value = [];
    nextCursor.value = null;
    hasMore.value = false;
    typingUsers.value = new Set();

    await loadMessages(conversation.id);
    subscribeToConversation(conversation.id);
    markAsRead(conversation.id);
  }

  /**
   * Tải messages với cursor pagination. newest-first từ server → đảo ngược để hiển thị.
   */
  async function loadMessages(conversationId, cursor = null) {
    loadingMessages.value = true;
    try {
      const res = await chatService.getMessages(conversationId, cursor);
      const data = res.data;
      const fetched = (data.data ?? []).reverse(); // đảo: newest first → oldest first

      if (cursor) {
        // Load more: prepend to top
        messages.value = [...fetched, ...messages.value];
      } else {
        messages.value = fetched;
      }

      nextCursor.value = data.meta?.next_cursor ?? null;
      hasMore.value = data.meta?.has_more ?? false;
    } finally {
      loadingMessages.value = false;
    }
  }

  /**
   * Load thêm messages cũ hơn (infinite scroll lên trên).
   */
  async function loadMoreMessages() {
    if (!hasMore.value || !nextCursor.value || !activeConversation.value) return;
    await loadMessages(activeConversation.value.id, nextCursor.value);
  }

  /**
   * Gửi tin nhắn với **Optimistic UI**:
   * 1. Thêm message giả (status: 'sending') ngay vào UI
   * 2. Gọi API
   * 3. Replace bằng message thật khi API trả về
   * 4. Nếu lỗi: đánh dấu 'error'
   */
  async function sendMessage(body) {
    if (!activeConversation.value || !body.trim()) return;

    const authStore = useAuthStore();
    const tempId = `temp-${Date.now()}`;

    // 1. Optimistic: thêm message tạm vào UI ngay
    const optimisticMsg = {
      id: tempId,
      conversation_id: activeConversation.value.id,
      sender: {
        id: authStore.user.id,
        full_name: authStore.user.full_name,
        avatar_url: authStore.user.avatar_url,
      },
      type: 'text',
      body: body.trim(),
      file_url: null,
      created_at: new Date().toISOString(),
      _status: 'sending', // chỉ dùng trong FE
    };
    messages.value.push(optimisticMsg);

    sending.value = true;
    try {
      // 2. Gọi API
      const res = await chatService.sendTextMessage(activeConversation.value.id, body.trim());
      const realMsg = res.data.data;

      // 3. Replace optimistic → real message
      const idx = messages.value.findIndex((m) => m.id === tempId);
      if (idx !== -1) {
        messages.value[idx] = { ...realMsg, _status: 'sent' };
      }

      // Cập nhật last_message trong conversation list
      updateConversationLastMessage(activeConversation.value.id, realMsg);
    } catch {
      // 4. Đánh dấu lỗi
      const idx = messages.value.findIndex((m) => m.id === tempId);
      if (idx !== -1) {
        messages.value[idx]._status = 'error';
      }
    } finally {
      sending.value = false;
    }
  }

  /**
   * Subscribe WebSocket channel của conversation.
   * - Lắng nghe event 'message.sent' → thêm message vào UI (nếu không phải của mình)
   * - Lắng nghe whisper 'typing' → hiển thị typing indicator
   */
  function subscribeToConversation(conversationId) {
    const echo = getEcho();
    if (!echo) return;

    const authStore = useAuthStore();

    activeChannel = echo
      .private(`conversation.${conversationId}`)
      .listen('.message.sent', (event) => {
        const incomingMsg = event.message;

        // Không thêm nếu đã có (tránh duplicate với optimistic UI)
        const alreadyExists = messages.value.some(
          (m) => m.id === incomingMsg.id,
        );
        if (alreadyExists) return;

        // Chỉ thêm message từ người khác (của mình đã có qua optimistic)
        if (incomingMsg.sender?.id !== authStore.user?.id) {
          messages.value.push({ ...incomingMsg, _status: 'received' });
          updateConversationLastMessage(conversationId, incomingMsg);

          // Đánh dấu đã đọc nếu conversation đang active
          if (activeConversation.value?.id === conversationId) {
            markAsRead(conversationId);
          }
        }
      })
      .listenForWhisper('typing', (event) => {
        // Hiển thị typing 3 giây rồi tắt
        typingUsers.value = new Set([...typingUsers.value, event.user_id]);
        setTimeout(() => {
          typingUsers.value.delete(event.user_id);
          typingUsers.value = new Set(typingUsers.value);
        }, 3000);
      });
  }

  /**
   * Whisper typing event tới partner (không qua server, peer-to-peer qua Reverb).
   */
  function sendTypingIndicator() {
    const authStore = useAuthStore();
    if (!activeChannel || !authStore.user) return;

    activeChannel.whisper('typing', {
      user_id: authStore.user.id,
      user_name: authStore.user.full_name,
    });
  }

  /**
   * Hủy subscribe channel hiện tại.
   */
  function unsubscribeActive() {
    if (activeChannel) {
      activeChannel.stopListening('.message.sent');
      activeChannel = null;
    }
  }

  /**
   * Đánh dấu đã đọc — cập nhật local state + gọi API.
   */
  async function markAsRead(conversationId) {
    // Reset unread count trong list
    const conv = conversations.value.find((c) => c.id === conversationId);
    if (conv) conv.unread_count = 0;

    try {
      await chatService.markAsRead(conversationId);
    } catch {
      // Không hiển thị lỗi, silent fail
    }
  }

  /**
   * Cập nhật last_message trong conversation list khi có tin nhắn mới.
   */
  function updateConversationLastMessage(conversationId, msg) {
    const conv = conversations.value.find((c) => c.id === conversationId);
    if (conv) {
      conv.last_message = {
        body: msg.body,
        type: msg.type,
        created_at: msg.created_at,
      };
      // Đưa conversation lên đầu list
      const idx = conversations.value.indexOf(conv);
      if (idx > 0) {
        conversations.value.splice(idx, 1);
        conversations.value.unshift(conv);
      }
    }
  }

  /**
   * Reset toàn bộ state (khi logout).
   */
  function reset() {
    unsubscribeActive();
    conversations.value = [];
    activeConversation.value = null;
    messages.value = [];
    nextCursor.value = null;
    hasMore.value = false;
    typingUsers.value = new Set();
  }

  return {
    conversations,
    activeConversation,
    messages,
    loadingConversations,
    loadingMessages,
    sending,
    nextCursor,
    hasMore,
    typingUsers,
    totalUnread,
    loadConversations,
    openConversationWith,
    openConversation,
    loadMoreMessages,
    sendMessage,
    sendTypingIndicator,
    unsubscribeActive,
    markAsRead,
    reset,
  };
});
