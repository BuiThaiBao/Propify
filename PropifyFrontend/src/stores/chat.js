import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import chatService from '@/services/chatService';
import { getEcho } from '@/plugins/echo';
import { useAuthStore } from '@/stores/auth';

export const useChatStore = defineStore('chat', () => {
  // ============ STATE ============
  const conversations = ref([]);
  const activeConversation = ref(null);
  const messages = ref([]);
  const loadingConversations = ref(false);
  const loadingMessages = ref(false);
  const sending = ref(false);
  const nextCursor = ref(null);
  const hasMore = ref(false);
  const typingUsers = ref(new Set());
  /** FloatingChat widget đang mở và hiển thị conversation không */
  const isChatVisible = ref(false);

  /**
   * Map<conversationId, EchoChannel> — subscribe tất cả conversations.
   * Mỗi channel handle cả notification lẫn hiển thị message (nếu đang active).
   */
  const channels = new Map();

  // ============ GETTERS ============
  const totalUnread = computed(() =>
    conversations.value.reduce((sum, c) => sum + (c.unread_count ?? 0), 0),
  );

  // ============ NOTIFICATION HELPERS ============

  /** Beep ngắn bằng Web Audio API — không cần file âm thanh */
  function playNotificationSound() {
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const osc = ctx.createOscillator();
      const gain = ctx.createGain();
      osc.connect(gain);
      gain.connect(ctx.destination);
      osc.type = 'sine';
      osc.frequency.setValueAtTime(880, ctx.currentTime);
      osc.frequency.setValueAtTime(660, ctx.currentTime + 0.08);
      gain.gain.setValueAtTime(0.25, ctx.currentTime);
      gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.3);
      osc.start(ctx.currentTime);
      osc.stop(ctx.currentTime + 0.3);
    } catch {
      // browser block hoặc không hỗ trợ
    }
  }

  /** Hiển thị OS notification. Xin quyền lần đầu tự động. */
  async function showBrowserNotification(senderName, body) {
    if (!('Notification' in window)) return;
    if (Notification.permission === 'default') {
      await Notification.requestPermission();
    }
    if (Notification.permission === 'granted') {
      const n = new Notification(`Tin nhắn từ ${senderName}`, {
        body: body?.slice(0, 80) ?? 'Đã gửi một tin nhắn',
        icon: '/favicon.ico',
        silent: true,
        tag: 'chat-message',
      });
      setTimeout(() => n.close(), 4000);
    }
  }

  // ============ ACTIONS ============

  /** Flag + promise lock — ngăn gọi API trùng hoàn toàn */
  let _loadingPromise = null;
  let _conversationsLoaded = false;

  /**
   * Tải danh sách conversations và subscribe WebSocket cho tất cả.
   * - Nếu đã load rồi → skip (trừ khi force=true)
   * - Nếu đang load → trả về promise đang chạy
   */
  async function loadConversations(force = false) {
    if (_conversationsLoaded && !force) return;
    if (_loadingPromise) return _loadingPromise;

    loadingConversations.value = true;
    _loadingPromise = (async () => {
      try {
        const res = await chatService.getConversations();
        conversations.value = res.data.data ?? [];
        conversations.value.forEach((c) => subscribeChannel(c.id));
        _conversationsLoaded = true;
      } finally {
        loadingConversations.value = false;
        _loadingPromise = null;
      }
    })();

    return _loadingPromise;
  }

  /**
   * Lấy hoặc tạo conversation với user, rồi mở nó.
   */
  async function openConversationWith(otherUserId, listingId = null) {
    const res = await chatService.getOrCreateConversation(otherUserId, listingId);
    const conversation = res.data.data;

    const existing = conversations.value.find((c) => c.id === conversation.id);
    if (!existing) {
      conversations.value.unshift(conversation);
      subscribeChannel(conversation.id); // subscribe conversation mới
    }

    await openConversation(conversation);
    return conversation;
  }

  /**
   * Cache tin nhắn per conversation để không phải gọi API lại mỗi lần chuyển.
   * Map<conversationId, { msgs: [], cursor: string|null, hasMore: boolean }>
   */
  const messageCache = new Map();

  /** Lock mở conversation — ngăn gọi trùng cùng conversation */
  let _openingConvId = null;

  /**
   * Mở một conversation — dùng cache nếu có, nếu không thì tải từ API.
   * Deduplicate: skip nếu đang mở cùng conversation.
   */
  async function openConversation(conversation) {
    if (_openingConvId === conversation.id) return; // đang mở rồi
    _openingConvId = conversation.id;

    activeConversation.value = conversation;
    typingUsers.value = new Set();

    const cached = messageCache.get(conversation.id);
    if (cached) {
      messages.value = cached.msgs;
      nextCursor.value = cached.cursor;
      hasMore.value = cached.hasMore;
    } else {
      messages.value = [];
      nextCursor.value = null;
      hasMore.value = false;
      await loadMessages(conversation.id);
    }
    // Server tự markAsRead khi loadMessages (trang đầu) → chỉ reset UI
    const conv = conversations.value.find((c) => c.id === conversation.id);
    if (conv) conv.unread_count = 0;
    _openingConvId = null;
  }

  /**
   * Subscribe WebSocket channel cho một conversation.
   * Gọi một lần duy nhất per conversation — handle cả notification + hiển thị.
   */
  function subscribeChannel(conversationId) {
    if (channels.has(conversationId)) return; // đã subscribe rồi

    const echo = getEcho();
    if (!echo) return;

    const authStore = useAuthStore();

    const channel = echo
      .private(`conversation.${conversationId}`)
      .listen('.message.sent', (event) => {
        const incomingMsg = event.message;

        // Bỏ qua message của chính mình (đã có qua optimistic UI)
        if (incomingMsg.sender?.id === authStore.user?.id) return;

        const isActivConv = activeConversation.value?.id === conversationId && isChatVisible.value;

        if (isActivConv) {
          // Đang xem conversation này — thêm vào UI nếu chưa có
          const exists = messages.value.some((m) => m.id === incomingMsg.id);
          if (!exists) {
            messages.value.push({ ...incomingMsg, _status: 'received' });
            saveCache(conversationId);
          }
          updateConversationLastMessage(conversationId, incomingMsg);
          markAsRead(conversationId);
        } else {
          // Không xem → tăng unread + thông báo + sound
          updateConversationLastMessage(conversationId, incomingMsg);
          const conv = conversations.value.find((c) => c.id === conversationId);
          if (conv) conv.unread_count = (conv.unread_count ?? 0) + 1;

          playNotificationSound();

          const senderName = incomingMsg.sender?.full_name ?? 'Ai đó';
          const preview = incomingMsg.type === 'image'
            ? '📷 Đã gửi ảnh'
            : incomingMsg.type === 'file'
              ? '📎 Đã gửi tệp'
              : (incomingMsg.body ?? '');
          showBrowserNotification(senderName, preview);
        }
      })
      .listenForWhisper('typing', (event) => {
        if (activeConversation.value?.id !== conversationId) return;
        typingUsers.value = new Set([...typingUsers.value, event.user_id]);
        setTimeout(() => {
          typingUsers.value.delete(event.user_id);
          typingUsers.value = new Set(typingUsers.value);
        }, 3000);
      });

    channels.set(conversationId, channel);
  }

  /**
   * Tải messages với cursor pagination.
   */
  async function loadMessages(conversationId, cursor = null) {
    loadingMessages.value = true;
    try {
      const res = await chatService.getMessages(conversationId, cursor);
      const data = res.data;
      const fetched = (data.data ?? []).reverse();

      if (cursor) {
        messages.value = [...fetched, ...messages.value];
      } else {
        messages.value = fetched;
      }

      nextCursor.value = data.meta?.next_cursor ?? null;
      hasMore.value = data.meta?.has_more ?? false;

      // Lưu vào cache
      saveCache(conversationId);
    } finally {
      loadingMessages.value = false;
    }
  }

  /** Lưu state tin nhắn hiện tại vào cache */
  function saveCache(convId) {
    messageCache.set(convId, {
      msgs: [...messages.value],
      cursor: nextCursor.value,
      hasMore: hasMore.value,
    });
  }

  /**
   * Load thêm messages cũ (infinite scroll lên trên).
   */
  async function loadMoreMessages() {
    if (!hasMore.value || !nextCursor.value || !activeConversation.value) return;
    await loadMessages(activeConversation.value.id, nextCursor.value);
  }

  /**
   * Gửi tin nhắn với Optimistic UI.
   */
  async function sendMessage(body) {
    if (!activeConversation.value || !body.trim()) return;

    const authStore = useAuthStore();
    const tempId = `temp-${Date.now()}`;

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
      _status: 'sending',
    };
    messages.value.push(optimisticMsg);

    sending.value = true;
    try {
      const res = await chatService.sendTextMessage(activeConversation.value.id, body.trim());
      const realMsg = res.data.data;

      const idx = messages.value.findIndex((m) => m.id === tempId);
      if (idx !== -1) {
        messages.value[idx] = { ...realMsg, _status: 'sent' };
      }

      updateConversationLastMessage(activeConversation.value.id, realMsg);
      saveCache(activeConversation.value.id);
    } catch {
      const idx = messages.value.findIndex((m) => m.id === tempId);
      if (idx !== -1) {
        messages.value[idx]._status = 'error';
      }
    } finally {
      sending.value = false;
    }
  }

  /**
   * Whisper typing event.
   */
  function sendTypingIndicator() {
    const authStore = useAuthStore();
    if (!activeConversation.value) return;
    const channel = channels.get(activeConversation.value.id);
    if (!channel || !authStore.user) return;

    channel.whisper('typing', {
      user_id: authStore.user.id,
      user_name: authStore.user.full_name,
    });
  }

  /**
   * Hủy subscribe conversation đang active (giữ lại các channel khác).
   * @deprecated Giờ dùng channels Map, không cần unsubscribe từng cái
   */
  function unsubscribeActive() {
    activeConversation.value = null;
  }

  /**
   * Đánh dấu đã đọc — throttle 3s per conversation.
   */
  const _readTimestamps = new Map();
  async function markAsRead(conversationId) {
    const now = Date.now();
    const lastRead = _readTimestamps.get(conversationId) ?? 0;
    if (now - lastRead < 3000) return; // skip nếu vừa gọi < 3s trước
    _readTimestamps.set(conversationId, now);

    const conv = conversations.value.find((c) => c.id === conversationId);
    if (conv) conv.unread_count = 0;
    try {
      await chatService.markAsRead(conversationId);
    } catch {
      // silent fail
    }
  }

  /**
   * Cập nhật last_message và đưa conversation lên đầu list.
   */
  function updateConversationLastMessage(conversationId, msg) {
    const conv = conversations.value.find((c) => c.id === conversationId);
    if (conv) {
      conv.last_message = {
        body: msg.body,
        type: msg.type,
        sender_id: msg.sender?.id ?? msg.sender_id,
        sender_name: msg.sender?.full_name ?? msg.sender_name,
        created_at: msg.created_at,
      };
      const idx = conversations.value.indexOf(conv);
      if (idx > 0) {
        conversations.value.splice(idx, 1);
        conversations.value.unshift(conv);
      }
    }
  }

  /**
   * Reset toàn bộ state và unsubscribe tất cả channels (khi logout).
   */
  function reset() {
    const echo = getEcho();
    if (echo) {
      channels.forEach((_, convId) => {
        echo.leave(`conversation.${convId}`);
      });
    }
    channels.clear();
    messageCache.clear();
    _conversationsLoaded = false;
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
    isChatVisible,
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
