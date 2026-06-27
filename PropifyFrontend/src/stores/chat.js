import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import chatService from '@/services/chatService';
import { getEcho } from '@/plugins/echo';
import { useAuthStore } from '@/stores/auth';
import { useMessageQueue } from '@/composables/useMessageQueue';
import { LRUCache } from '@/utils/LRUCache';

export const useChatStore = defineStore('chat', () => {
  const conversations = ref([]);
  const activeConversation = ref(null);
  const messages = ref([]);
  const loadingConversations = ref(false);
  const loadingMessages = ref(false);
  const sending = ref(false);
  const nextCursor = ref(null);
  const hasMore = ref(false);
  const typingUsers = ref(new Set());
  const isChatVisible = ref(false);
  const popupOpen = ref(false);
  const groupMembers = ref([]);
  const loadingMembers = ref(false);
  const { queue, isOnline, enqueue, remove, setupListeners } = useMessageQueue();

  const channels = new Map();
  const messageCache = new LRUCache(20);
  const totalUnread = computed(() =>
    conversations.value.reduce((sum, item) => sum + (item.unread_count ?? 0), 0),
  );
  const activeDisplayName = computed(() => {
    const conversation = activeConversation.value;
    if (!conversation) return '';
    if (conversation.type === 'group') return conversation.group?.name ?? 'Nhóm chat';
    return conversation.other_user?.full_name ?? 'Người dùng';
  });
  const activeDisplayAvatar = computed(() => {
    const conversation = activeConversation.value;
    if (!conversation) return null;
    return conversation.type === 'group'
      ? conversation.group?.avatar_url ?? null
      : conversation.other_user?.avatar_url ?? null;
  });
  const isGroupConversation = computed(() => activeConversation.value?.type === 'group');
  const myRole = computed(() => activeConversation.value?.group?.my_role ?? null);
  const isGroupAdmin = computed(() => myRole.value === 'admin');

  let loadingPromise = null;
  let conversationsLoaded = false;
  let openingConversationId = null;
  let queueListenersInitialized = false;

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
      // ignore
    }
  }

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

  async function loadConversations(force = false) {
    if (conversationsLoaded && !force) return;
    if (loadingPromise) return loadingPromise;

    loadingConversations.value = true;
    loadingPromise = (async () => {
      try {
        const res = await chatService.getConversations();
        conversations.value = res.data.data ?? [];
        conversations.value.forEach((conversation) => subscribeChannel(conversation.id));
        conversationsLoaded = true;
      } finally {
        loadingConversations.value = false;
        loadingPromise = null;
      }
    })();

    return loadingPromise;
  }

  async function openConversationWith(otherUserId, listingId = null) {
    const res = await chatService.getOrCreateConversation(otherUserId, listingId);
    const conversation = res.data.data;
    const existing = conversations.value.find((item) => item.id === conversation.id);

    if (!existing) {
      conversations.value.unshift(conversation);
      subscribeChannel(conversation.id);
    }

    await openConversation(conversation);
    return conversation;
  }

  async function createGroup(name, memberIds, avatarUrl = null) {
    const res = await chatService.createGroup(name, memberIds, avatarUrl);
    const conversation = res.data.data;
    conversations.value.unshift(conversation);
    subscribeChannel(conversation.id);
    await openConversation(conversation);
    return conversation;
  }

  async function openConversation(conversation) {
    if (openingConversationId === conversation.id) return;
    openingConversationId = conversation.id;

    activeConversation.value = conversation;
    typingUsers.value = new Set();
    groupMembers.value = [];

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

    const localConversation = conversations.value.find((item) => item.id === conversation.id);
    if (localConversation) localConversation.unread_count = 0;
    if (conversation.type === 'group') {
      await loadGroupMembers(conversation.id);
    }

    openingConversationId = null;
  }

  async function loadGroupMembers(conversationId) {
    loadingMembers.value = true;
    try {
      const res = await chatService.getGroupMembers(conversationId);
      groupMembers.value = res.data?.data ?? [];
    } finally {
      loadingMembers.value = false;
    }
  }

  async function addGroupMembers(userIds) {
    if (!activeConversation.value) return;
    const res = await chatService.addGroupMembers(activeConversation.value.id, userIds);
    activeConversation.value = res.data.data;
    await loadGroupMembers(activeConversation.value.id);
    await loadConversations(true);
  }

  async function removeGroupMember(userId) {
    if (!activeConversation.value) return;
    const res = await chatService.removeGroupMember(activeConversation.value.id, userId);
    activeConversation.value = res.data.data;
    groupMembers.value = groupMembers.value.filter((member) => member.id !== userId);
    await loadConversations(true);
  }

  async function transferGroupAdmin(userId) {
    if (!activeConversation.value) return;
    const res = await chatService.transferGroupAdmin(activeConversation.value.id, userId);
    activeConversation.value = res.data.data;
    await loadGroupMembers(activeConversation.value.id);
    await loadConversations(true);
    return activeConversation.value;
  }

  async function leaveGroup() {
    if (!activeConversation.value) return;
    const conversationId = activeConversation.value.id;
    await chatService.leaveGroup(conversationId);
    conversations.value = conversations.value.filter((item) => item.id !== conversationId);
    activeConversation.value = null;
    groupMembers.value = [];
  }

  async function updateGroup(data) {
    if (!activeConversation.value) return;
    const res = await chatService.updateGroup(activeConversation.value.id, data);
    const updated = res.data.data;
    activeConversation.value = updated;
    const index = conversations.value.findIndex((item) => item.id === updated.id);
    if (index !== -1) conversations.value[index] = updated;
    return updated;
  }

  function buildOptimisticMessage(body, status = 'sending', id = `temp-${Date.now()}`) {
    const authStore = useAuthStore();

    return {
      id,
      conversation_id: activeConversation.value?.id,
      sender: {
        id: authStore.user.id,
        full_name: authStore.user.full_name,
        avatar_url: authStore.user.avatar_url,
      },
      type: 'text',
      body: body.trim(),
      file_url: null,
      created_at: new Date().toISOString(),
      _status: status,
    };
  }

  function saveCache(conversationId) {
    if (!conversationId) return;
    messageCache.set(conversationId, {
      msgs: [...messages.value],
      cursor: nextCursor.value,
      hasMore: hasMore.value,
    });
  }

  function replaceMessageStatus(messageId, status) {
    const index = messages.value.findIndex((message) => message.id === messageId);
    if (index !== -1) {
      messages.value[index] = { ...messages.value[index], _status: status };
      saveCache(activeConversation.value?.id ?? messages.value[index].conversation_id);
    }
  }

  async function flushQueuedMessage(item) {
    const res = await chatService.sendTextMessage(item.conversationId, item.body);
    const realMsg = res.data.data;

    const index = messages.value.findIndex((message) => message.id === item.localId);
    if (index !== -1) {
      messages.value[index] = { ...realMsg, _status: 'sent' };
    }

    updateConversationLastMessage(item.conversationId, realMsg);
    saveCache(item.conversationId);
    remove(item.id);
  }

  function ensureQueueListeners() {
    if (queueListenersInitialized) return;
    setupListeners(flushQueuedMessage);
    queueListenersInitialized = true;
  }

  function subscribeChannel(conversationId) {
    if (channels.has(conversationId)) return;

    const echo = getEcho();
    if (!echo) return;

    const authStore = useAuthStore();

    const channel = echo
      .private(`conversation.${conversationId}`)
      .listen('.message.sent', (event) => {
        const incomingMsg = event.message;

        if (incomingMsg.sender?.id === authStore.user?.id) return;

        const isActive = activeConversation.value?.id === conversationId && isChatVisible.value;

        if (isActive) {
          const exists = messages.value.some((message) => message.id === incomingMsg.id);
          if (!exists) {
            messages.value.push({ ...incomingMsg, _status: 'received' });
            saveCache(conversationId);
          }
          updateConversationLastMessage(conversationId, incomingMsg);
          markAsRead(conversationId);
          return;
        }

        updateConversationLastMessage(conversationId, incomingMsg);
        const conversation = conversations.value.find((item) => item.id === conversationId);
        if (conversation) conversation.unread_count = (conversation.unread_count ?? 0) + 1;

        playNotificationSound();

        const senderName = incomingMsg.sender?.full_name ?? 'Ai đó';
        const preview = incomingMsg.type === 'image'
          ? '📷 Đã gửi ảnh'
          : incomingMsg.type === 'file'
            ? '📎 Đã gửi tệp'
            : (incomingMsg.body ?? '');
        showBrowserNotification(senderName, preview);
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
      saveCache(conversationId);
    } finally {
      loadingMessages.value = false;
    }
  }

  async function loadMoreMessages() {
    if (!hasMore.value || !nextCursor.value || !activeConversation.value) return;
    await loadMessages(activeConversation.value.id, nextCursor.value);
  }

  async function sendMessage(body) {
    if (!activeConversation.value || !body.trim()) return;

    ensureQueueListeners();

    const tempId = `temp-${Date.now()}`;
    const initialStatus = isOnline.value ? 'sending' : 'queued';
    const optimisticMsg = buildOptimisticMessage(body, initialStatus, tempId);
    messages.value.push(optimisticMsg);
    updateConversationLastMessage(activeConversation.value.id, optimisticMsg);
    saveCache(activeConversation.value.id);

    if (!isOnline.value) {
      enqueue(activeConversation.value.id, body.trim(), { localId: tempId });
      return;
    }

    sending.value = true;
    try {
      const res = await chatService.sendTextMessage(activeConversation.value.id, body.trim());
      const realMsg = res.data.data;

      const index = messages.value.findIndex((message) => message.id === tempId);
      if (index !== -1) {
        messages.value[index] = { ...realMsg, _status: 'sent' };
      }

      updateConversationLastMessage(activeConversation.value.id, realMsg);
      saveCache(activeConversation.value.id);
    } catch {
      replaceMessageStatus(tempId, 'error');
    } finally {
      sending.value = false;
    }
  }

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

  function unsubscribeActive() {
    activeConversation.value = null;
    groupMembers.value = [];
  }

  const readTimestamps = new Map();
  async function markAsRead(conversationId) {
    const now = Date.now();
    const lastRead = readTimestamps.get(conversationId) ?? 0;
    if (now - lastRead < 3000) return;
    readTimestamps.set(conversationId, now);

    const conversation = conversations.value.find((item) => item.id === conversationId);
    if (conversation) conversation.unread_count = 0;

    try {
      await chatService.markAsRead(conversationId);
    } catch {
      // ignore
    }
  }

  function updateConversationLastMessage(conversationId, msg) {
    const conversation = conversations.value.find((item) => item.id === conversationId);
    if (!conversation) return;

    conversation.last_message = {
      body: msg.body,
      type: msg.type,
      sender_id: msg.sender?.id ?? msg.sender_id,
      sender_name: msg.sender?.full_name ?? msg.sender_name,
      created_at: msg.created_at,
      metadata: msg.metadata ?? null,
    };

    const index = conversations.value.indexOf(conversation);
    if (index > 0) {
      conversations.value.splice(index, 1);
      conversations.value.unshift(conversation);
    }
  }

  function reset() {
    const echo = getEcho();
    if (echo) {
      channels.forEach((_, conversationId) => {
        echo.leave(`conversation.${conversationId}`);
      });
    }

    channels.clear();
    messageCache.clear();
    queue.value = [];
    conversationsLoaded = false;
    conversations.value = [];
    activeConversation.value = null;
    messages.value = [];
    groupMembers.value = [];
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
    popupOpen,
    queue,
    isOnline,
    groupMembers,
    loadingMembers,
    activeDisplayName,
    activeDisplayAvatar,
    isGroupConversation,
    myRole,
    isGroupAdmin,
    loadConversations,
    openConversationWith,
    createGroup,
    openConversation,
    loadGroupMembers,
    addGroupMembers,
    removeGroupMember,
    transferGroupAdmin,
    leaveGroup,
    updateGroup,
    loadMoreMessages,
    sendMessage,
    sendTypingIndicator,
    unsubscribeActive,
    markAsRead,
    reset,
  };
});
