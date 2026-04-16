<template>
  <div class="flex h-[calc(100vh-70px)] bg-[#0f1117] font-[Inter,sans-serif]">
    <!-- Sidebar: Danh sách conversations -->
    <aside
      class="w-[340px] min-w-[340px] bg-[#161b27] border-r border-white/[0.06] flex flex-col overflow-hidden"
      :class="{ 'hidden md:flex': activeConversation && isMobile, 'max-md:hidden': activeConversation && isMobile }"
    >
      <div class="px-5 pt-5 pb-4 border-b border-white/[0.06]">
        <h2 class="flex items-center gap-2.5 text-[1.1rem] font-bold text-slate-200 m-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          Tin nhắn
          <span
            v-if="totalUnread > 0"
            class="bg-gradient-to-br from-indigo-500 to-violet-500 text-white text-[0.7rem] font-bold px-[7px] py-0.5 rounded-full min-w-[20px] text-center"
          >{{ totalUnread }}</span>
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
    <main
      class="flex-1 flex flex-col overflow-hidden bg-[#0f1117]"
      :class="isMobile && !activeConversation ? 'hidden' : 'flex'"
    >
      <!-- Placeholder khi chưa chọn conversation -->
      <div v-if="!activeConversation" class="flex-1 flex flex-col items-center justify-center gap-4 text-slate-500 text-center p-10">
        <div class="size-[100px] flex items-center justify-center bg-indigo-500/[0.08] rounded-full text-indigo-500">
          <svg width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <h3 class="text-[1.25rem] font-semibold text-slate-400 m-0">Chọn cuộc trò chuyện</h3>
        <p class="text-[0.9rem] m-0 max-w-[300px] leading-relaxed">Chọn một cuộc trò chuyện bên trái để bắt đầu nhắn tin</p>
      </div>

      <!-- Chat area -->
      <template v-else>
        <!-- Header -->
        <div class="flex items-center gap-3.5 px-5 py-3.5 border-b border-white/[0.06] bg-[#161b27]">
          <button
            v-if="isMobile"
            class="bg-transparent border-none text-slate-400 cursor-pointer p-1 flex items-center rounded-lg transition-colors hover:bg-white/[0.08]"
            @click="activeConversation = null"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <polyline points="15 18 9 12 15 6"/>
            </svg>
          </button>

          <div class="flex items-center gap-3 flex-1">
            <div class="size-10 rounded-full overflow-hidden bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center text-[0.85rem] font-bold text-white shrink-0">
              <img
                v-if="activeConversation.other_user?.avatar_url"
                :src="activeConversation.other_user.avatar_url"
                :alt="activeConversation.other_user.full_name"
                class="w-full h-full object-cover"
              />
              <span v-else>{{ getInitials(activeConversation.other_user?.full_name) }}</span>
            </div>
            <div>
              <div class="text-[0.95rem] font-semibold text-slate-200">{{ activeConversation.other_user?.full_name }}</div>
              <div v-if="isPartnerTyping" class="flex items-center gap-1 mt-0.5">
                <span class="typing-dot" /><span class="typing-dot" /><span class="typing-dot" />
                <span class="text-[0.75rem] text-slate-500 ml-0.5">đang nhập...</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Messages area -->
        <div
          class="flex-1 overflow-y-auto p-5 flex flex-col gap-1.5 scroll-smooth messages-scroll"
          ref="messagesContainer"
          @scroll="onScroll"
        >
          <!-- Load more button -->
          <div v-if="hasMore" class="flex justify-center mb-3">
            <button
              class="bg-indigo-500/[0.12] text-indigo-400 border border-indigo-500/20 px-[18px] py-1.5 rounded-full text-[0.8rem] cursor-pointer transition-all hover:bg-indigo-500/[0.22] disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loadingMessages"
              @click="loadMoreMessages"
            >
              <span v-if="loadingMessages">Đang tải...</span>
              <span v-else>↑ Tải thêm tin nhắn cũ</span>
            </button>
          </div>

          <!-- Loading skeleton -->
          <div v-if="loadingMessages && messages.length === 0" class="flex flex-col gap-3 p-2.5">
            <div
              v-for="i in 5"
              :key="i"
              class="h-10 rounded-2xl max-w-[60%] animate-[msgShimmer_1.5s_ease-in-out_infinite]"
              :class="i % 2 === 0 ? 'self-end bg-indigo-500/[0.08]' : 'self-start bg-white/[0.05]'"
            />
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

const { width } = useWindowSize();
const isMobile = computed(() => width.value < 768);

const messagesContainer = ref(null);

const isPartnerTyping = computed(() => typingUsers.size > 0);

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
  if (messagesContainer.value?.scrollTop === 0 && hasMore) {
    loadMoreMessages();
  }
}

watch(() => messages.length, (newLen, oldLen) => {
  if (newLen > oldLen) scrollToBottom();
});

onMounted(async () => {
  await loadConversations();
});

onUnmounted(() => {
  clearTimeout(typingTimeout);
});
</script>

<style scoped>
/* Scrollbar — không thể dùng Tailwind */
.messages-scroll::-webkit-scrollbar { width: 4px; }
.messages-scroll::-webkit-scrollbar-track { background: transparent; }
.messages-scroll::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
  border-radius: 99px;
}

/* Typing animation dots */
.typing-dot {
  display: inline-block;
  width: 5px;
  height: 5px;
  border-radius: 50%;
  background: #6366f1;
  animation: typingBounce 1.2s ease-in-out infinite;
}
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }

@keyframes typingBounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-4px); }
}

@keyframes msgShimmer {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}

/* Responsive sidebar */
@media (max-width: 767px) {
  aside { display: none; }
  aside.force-show { display: flex; }
}
</style>
