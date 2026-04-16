<template>
  <div class="chat-page">
    <!-- Sidebar: Danh sách conversations -->
    <aside class="chat-sidebar" :class="{ 'sidebar-hidden': activeConversation && isMobile }">
      <div class="sidebar-header">
        <h2 class="sidebar-title">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          Tin nhắn
          <span v-if="totalUnread > 0" class="unread-badge">{{ totalUnread }}</span>
        </h2>
      </div>

      <ConversationList
        :conversations="conversations"
        :active-id="activeConversation?.id"
        :loading="loadingConversations"
        @select="openConversation"
      />
    </aside>

    <!-- Main: Khung chat -->
    <main class="chat-main" :class="{ 'main-visible': activeConversation || !isMobile }">
      <!-- Placeholder khi chưa chọn conversation -->
      <div v-if="!activeConversation" class="chat-empty">
        <div class="empty-icon">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <h3>Chọn cuộc trò chuyện</h3>
        <p>Chọn một cuộc trò chuyện bên trái để bắt đầu nhắn tin</p>
      </div>

      <!-- Chat area -->
      <template v-else>
        <!-- Header -->
        <div class="chat-header">
          <button v-if="isMobile" class="btn-back" @click="activeConversation = null">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
          </button>
          <div class="chat-partner-info">
            <div class="partner-avatar">
              <img
                v-if="activeConversation.other_user?.avatar_url"
                :src="activeConversation.other_user.avatar_url"
                :alt="activeConversation.other_user.full_name"
              />
              <span v-else>{{ getInitials(activeConversation.other_user?.full_name) }}</span>
            </div>
            <div>
              <div class="partner-name">{{ activeConversation.other_user?.full_name }}</div>
              <div v-if="isPartnerTyping" class="typing-indicator">
                <span class="dot" /><span class="dot" /><span class="dot" />
                <span class="typing-text">đang nhập...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Messages area -->
        <div class="messages-container" ref="messagesContainer" @scroll="onScroll">
          <!-- Load more button -->
          <div v-if="hasMore" class="load-more-wrapper">
            <button class="btn-load-more" :disabled="loadingMessages" @click="loadMoreMessages">
              <span v-if="loadingMessages">Đang tải...</span>
              <span v-else>↑ Tải thêm tin nhắn cũ</span>
            </button>
          </div>

          <!-- Loading skeleton -->
          <div v-if="loadingMessages && messages.length === 0" class="messages-loading">
            <div v-for="i in 5" :key="i" class="skeleton-msg" :class="i % 2 === 0 ? 'skeleton-right' : 'skeleton-left'" />
          </div>

          <!-- Message list -->
          <MessageBubble
            v-for="msg in messages"
            :key="msg.id"
            :message="msg"
            :is-mine="msg.sender?.id === currentUserId"
          />
        </div>

        <!-- Chat input -->
        <ChatInput
          :disabled="sending"
          @send="handleSend"
          @typing="handleTyping"
        />
      </template>
    </main>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue';
import { useWindowSize } from '@vueuse/core';
import { useChatStore } from '@/stores/chat';
import { useAuthStore } from '@/stores/auth';
import ConversationList from '@/components/chat/ConversationList.vue';
import MessageBubble from '@/components/chat/MessageBubble.vue';
import ChatInput from '@/components/chat/ChatInput.vue';

// ============ Stores ============
const chatStore = useChatStore();
const authStore = useAuthStore();

const {
  conversations,
  activeConversation,
  messages,
  loadingConversations,
  loadingMessages,
  sending,
  hasMore,
  typingUsers,
  totalUnread,
  loadConversations,
  openConversation,
  loadMoreMessages,
  sendMessage,
  sendTypingIndicator,
} = chatStore;

const currentUserId = computed(() => authStore.user?.id);

// ============ Responsive ============
const { width } = useWindowSize();
const isMobile = computed(() => width.value < 768);

// ============ Refs ============
const messagesContainer = ref(null);

// ============ Typing ============
const isPartnerTyping = computed(() => typingUsers.size > 0);

// ============ Methods ============
function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2);
}

async function handleSend(body) {
  await sendMessage(body);
  scrollToBottom();
}

let typingTimeout = null;
function handleTyping() {
  sendTypingIndicator();
  clearTimeout(typingTimeout);
  typingTimeout = setTimeout(() => {}, 3000);
}

function scrollToBottom(smooth = true) {
  nextTick(() => {
    if (messagesContainer.value) {
      messagesContainer.value.scrollTo({
        top: messagesContainer.value.scrollHeight,
        behavior: smooth ? 'smooth' : 'instant',
      });
    }
  });
}

function onScroll() {
  // Infinite scroll — khi scroll lên gần top thì load thêm messages cũ
  if (messagesContainer.value?.scrollTop === 0 && hasMore) {
    loadMoreMessages();
  }
}

// Auto scroll khi có message mới
watch(() => messages.length, (newLen, oldLen) => {
  if (newLen > oldLen) {
    scrollToBottom();
  }
});

// ============ Lifecycle ============
onMounted(async () => {
  await loadConversations();
});

onUnmounted(() => {
  clearTimeout(typingTimeout);
});
</script>

<style scoped>
.chat-page {
  display: flex;
  height: calc(100vh - 70px);
  background: #0f1117;
  font-family: 'Inter', sans-serif;
}

/* ===== SIDEBAR ===== */
.chat-sidebar {
  width: 340px;
  min-width: 340px;
  background: #161b27;
  border-right: 1px solid rgba(255, 255, 255, 0.06);
  display: flex;
  flex-direction: column;
  overflow: hidden;
}

.sidebar-header {
  padding: 20px 20px 16px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
}

.sidebar-title {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: 1.1rem;
  font-weight: 700;
  color: #e2e8f0;
  margin: 0;
}

.unread-badge {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  color: white;
  font-size: 0.7rem;
  font-weight: 700;
  padding: 2px 7px;
  border-radius: 99px;
  min-width: 20px;
  text-align: center;
}

/* ===== MAIN CHAT ===== */
.chat-main {
  flex: 1;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  background: #0f1117;
}

/* ===== EMPTY STATE ===== */
.chat-empty {
  flex: 1;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 16px;
  color: #64748b;
  text-align: center;
  padding: 40px;
}

.empty-icon {
  width: 100px;
  height: 100px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(99, 102, 241, 0.08);
  border-radius: 50%;
  color: #6366f1;
}

.chat-empty h3 {
  font-size: 1.25rem;
  font-weight: 600;
  color: #94a3b8;
  margin: 0;
}

.chat-empty p {
  font-size: 0.9rem;
  margin: 0;
  max-width: 300px;
  line-height: 1.6;
}

/* ===== CHAT HEADER ===== */
.chat-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.06);
  background: #161b27;
}

.btn-back {
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 4px;
  display: flex;
  align-items: center;
  border-radius: 8px;
  transition: background 0.2s;
}

.btn-back:hover {
  background: rgba(255, 255, 255, 0.08);
}

.chat-partner-info {
  display: flex;
  align-items: center;
  gap: 12px;
  flex: 1;
}

.partner-avatar {
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.partner-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.partner-name {
  font-size: 0.95rem;
  font-weight: 600;
  color: #e2e8f0;
}

/* ===== TYPING INDICATOR ===== */
.typing-indicator {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 2px;
}

.dot {
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #6366f1;
  animation: bounce 1.2s ease-in-out infinite;
}

.dot:nth-child(2) { animation-delay: 0.2s; }
.dot:nth-child(3) { animation-delay: 0.4s; }

.typing-text {
  font-size: 0.75rem;
  color: #64748b;
  margin-left: 2px;
}

@keyframes bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-4px); }
}

/* ===== MESSAGES ===== */
.messages-container {
  flex: 1;
  overflow-y: auto;
  padding: 20px;
  display: flex;
  flex-direction: column;
  gap: 6px;
  scroll-behavior: smooth;
}

.messages-container::-webkit-scrollbar {
  width: 4px;
}

.messages-container::-webkit-scrollbar-track {
  background: transparent;
}

.messages-container::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 99px;
}

/* ===== LOAD MORE ===== */
.load-more-wrapper {
  display: flex;
  justify-content: center;
  margin-bottom: 12px;
}

.btn-load-more {
  background: rgba(99, 102, 241, 0.12);
  color: #818cf8;
  border: 1px solid rgba(99, 102, 241, 0.2);
  padding: 6px 18px;
  border-radius: 99px;
  font-size: 0.8rem;
  cursor: pointer;
  transition: all 0.2s;
}

.btn-load-more:hover:not(:disabled) {
  background: rgba(99, 102, 241, 0.22);
}

.btn-load-more:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* ===== SKELETON ===== */
.messages-loading {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 10px;
}

.skeleton-msg {
  height: 40px;
  border-radius: 16px;
  background: rgba(255, 255, 255, 0.05);
  animation: shimmer 1.5s ease-in-out infinite;
  max-width: 60%;
}

.skeleton-right {
  align-self: flex-end;
  background: rgba(99, 102, 241, 0.08);
}

.skeleton-left {
  align-self: flex-start;
}

@keyframes shimmer {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

/* ===== RESPONSIVE ===== */
@media (max-width: 767px) {
  .sidebar-hidden {
    display: none;
  }

  .chat-main {
    display: none;
  }

  .main-visible {
    display: flex;
  }

  .chat-sidebar {
    width: 100%;
    min-width: 100%;
  }
}
</style>
