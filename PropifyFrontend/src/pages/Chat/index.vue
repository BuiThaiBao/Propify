<template>
  <div class="flex h-[calc(100vh-70px)] bg-gray-50 font-[Inter,sans-serif]">
    <!-- Sidebar -->
    <aside class="w-[320px] min-w-[320px] bg-white border-r border-gray-200 flex flex-col overflow-hidden"
      :class="{ 'max-md:hidden': activeConversation && isMobile }">
      <div class="px-5 py-4 border-b border-gray-100">
        <h2 class="flex items-center gap-2.5 text-lg font-bold text-gray-900 m-0">
          <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
          Tin nhắn
          <span v-if="totalUnread > 0" class="bg-blue-500 text-white text-[0.7rem] font-bold px-2 py-0.5 rounded-full min-w-[20px] text-center">
            {{ totalUnread }}
          </span>
        </h2>
      </div>
      <ConversationList :conversations="conversations" :active-id="activeConversation?.id" :loading="loadingConversations" @select="openConversation" />
    </aside>

    <!-- Main -->
    <main class="flex-1 flex flex-col overflow-hidden" :class="isMobile && !activeConversation ? 'hidden' : 'flex'">
      <!-- Empty state -->
      <div v-if="!activeConversation" class="flex-1 flex flex-col items-center justify-center gap-4 text-gray-400 text-center p-10">
        <div class="size-24 rounded-full bg-blue-50 flex items-center justify-center">
          <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="#93c5fd" stroke-width="1.2">
            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
          </svg>
        </div>
        <h3 class="text-xl font-semibold text-gray-500 m-0">Chọn cuộc trò chuyện</h3>
        <p class="text-sm m-0 max-w-xs leading-relaxed text-gray-400">Chọn một cuộc trò chuyện bên trái để bắt đầu nhắn tin</p>
      </div>

      <!-- Chat area -->
      <template v-else>
        <!-- Header -->
        <div class="flex items-center gap-3 px-5 py-3.5 bg-white border-b border-gray-100 shadow-sm">
          <button v-if="isMobile" class="bg-transparent border-none text-gray-500 cursor-pointer p-1 rounded-lg hover:bg-gray-100 flex items-center" @click="activeConversation = null">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="15 18 9 12 15 6"/></svg>
          </button>
          <div class="size-10 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-sm">
            <img v-if="activeConversation.other_user?.avatar_url" :src="activeConversation.other_user.avatar_url" :alt="activeConversation.other_user.full_name" class="w-full h-full object-cover" />
            <span v-else>{{ getInitials(activeConversation.other_user?.full_name) }}</span>
          </div>
          <div class="flex-1">
            <div class="text-[0.95rem] font-semibold text-gray-900">{{ activeConversation.other_user?.full_name }}</div>
            <div v-if="isPartnerTyping" class="flex items-center gap-1 mt-0.5">
              <span class="typing-dot"/><span class="typing-dot"/><span class="typing-dot"/>
              <span class="text-[0.72rem] text-blue-500 ml-0.5">đang nhập...</span>
            </div>
            <div v-else class="text-[0.72rem] text-green-500 font-medium">Đang hoạt động</div>
          </div>
        </div>

        <!-- Messages -->
        <div class="flex-1 overflow-y-auto px-5 py-4 flex flex-col gap-1.5 messages-scroll bg-gray-50" ref="messagesContainer" @scroll="onScroll">
          <div v-if="hasMore" class="flex justify-center mb-2">
            <button class="text-[0.78rem] text-blue-600 bg-blue-50 border border-blue-100 px-4 py-1.5 rounded-full cursor-pointer hover:bg-blue-100 transition-all disabled:opacity-50" :disabled="loadingMessages" @click="loadMoreMessages">
              {{ loadingMessages ? 'Đang tải...' : '↑ Tải thêm tin nhắn cũ' }}
            </button>
          </div>
          <div v-if="loadingMessages && messages.length === 0" class="flex flex-col gap-3 p-2">
            <div v-for="i in 5" :key="i" class="h-10 rounded-2xl max-w-[55%] animate-[msgShimmer_1.4s_ease-in-out_infinite]"
              :class="i % 2 === 0 ? 'self-end bg-blue-100' : 'self-start bg-gray-200'" />
          </div>
          <MessageBubble v-for="msg in messages" :key="msg.id" :message="msg" :is-mine="msg.sender?.id === currentUserId" />
        </div>

        <ChatInput :disabled="sending" @send="handleSend" @typing="handleTyping" />
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
const { conversations, activeConversation, messages, loadingConversations, loadingMessages, sending, hasMore, typingUsers, totalUnread, loadConversations, openConversation, loadMoreMessages, sendMessage, sendTypingIndicator } = chatStore;
const currentUserId = computed(() => authStore.user?.id);
const { width } = useWindowSize();
const isMobile = computed(() => width.value < 768);
const messagesContainer = ref(null);
const isPartnerTyping = computed(() => typingUsers.size > 0);

function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2);
}
async function handleSend(body) { await sendMessage(body); scrollToBottom(); }
let typingTimeout = null;
function handleTyping() { sendTypingIndicator(); clearTimeout(typingTimeout); typingTimeout = setTimeout(() => {}, 3000); }
function scrollToBottom(smooth = true) {
  nextTick(() => { if (messagesContainer.value) messagesContainer.value.scrollTo({ top: messagesContainer.value.scrollHeight, behavior: smooth ? 'smooth' : 'instant' }); });
}
function onScroll() { if (messagesContainer.value?.scrollTop === 0 && hasMore) loadMoreMessages(); }
watch(() => messages.length, (n, o) => { if (n > o) scrollToBottom(); });
onMounted(async () => { if (conversations.length === 0) await loadConversations(); });
onUnmounted(() => { clearTimeout(typingTimeout); });
</script>

<style scoped>
.messages-scroll::-webkit-scrollbar { width: 4px; }
.messages-scroll::-webkit-scrollbar-track { background: transparent; }
.messages-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 99px; }
.typing-dot { display: inline-block; width: 4px; height: 4px; border-radius: 50%; background: #3b82f6; animation: typingBounce 1.2s ease-in-out infinite; }
.typing-dot:nth-child(2) { animation-delay: 0.2s; }
.typing-dot:nth-child(3) { animation-delay: 0.4s; }
@keyframes typingBounce { 0%, 60%, 100% { transform: translateY(0); } 30% { transform: translateY(-4px); } }
@keyframes msgShimmer { 0%, 100% { opacity: 0.5; } 50% { opacity: 1; } }
@media (max-width: 767px) { aside { display: none; } }
</style>
