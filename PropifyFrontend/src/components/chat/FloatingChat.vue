<template>
  <Teleport to="body">
    <div v-if="authStore.isAuthenticated" class="fixed bottom-6 right-6 z-[9999] flex flex-col items-end gap-3 font-[Inter,-apple-system,sans-serif]">

      <!-- ============== CHAT WINDOW ============== -->
      <Transition name="chat-pop">
        <div
          v-if="isOpen"
          class="w-[340px] h-[480px] bg-[#161b27] border border-white/[0.08] rounded-[18px] flex flex-col overflow-hidden shadow-[0_20px_60px_rgba(0,0,0,0.6),0_0_0_1px_rgba(255,255,255,0.05)] transition-[width,height] duration-200"
          :class="{ 'w-[380px] h-[520px]': activeConversation }"
        >
          <!-- HEADER -->
          <div class="flex items-center justify-between px-3.5 py-[14px] pb-3 bg-gradient-to-br from-[#1e1b4b] to-[#2d1b69] border-b border-white/[0.06] shrink-0">
            <div class="flex items-center gap-2.5 min-w-0">
              <!-- Back button -->
              <button
                v-if="activeConversation"
                class="size-7 rounded-full bg-transparent border-none text-slate-400 cursor-pointer flex items-center justify-center transition-all duration-150 hover:bg-white/[0.08] hover:text-slate-200 mr-0.5"
                @click="closeConversation"
                title="Quay lại"
              >
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              </button>

              <!-- Title: Conversation -->
              <div v-if="activeConversation" class="flex items-center gap-2.5 min-w-0">
                <div class="size-[34px] rounded-full overflow-hidden bg-gradient-to-br from-indigo-600 to-violet-700 flex items-center justify-center text-[0.75rem] font-bold text-white shrink-0">
                  <img v-if="activeConversation.other_user?.avatar_url" :src="activeConversation.other_user.avatar_url" class="w-full h-full object-cover" />
                  <span v-else>{{ initials(activeConversation.other_user?.full_name) }}</span>
                </div>
                <div>
                  <div class="text-[0.88rem] font-semibold text-slate-200 whitespace-nowrap overflow-hidden text-ellipsis max-w-[160px]">
                    {{ activeConversation.other_user?.full_name }}
                  </div>
                  <div v-if="isTyping" class="flex items-center gap-[3px] mt-[1px]">
                    <span class="cw-dot"/><span class="cw-dot"/><span class="cw-dot"/>
                    <span class="text-[0.7rem] text-slate-500">đang nhập...</span>
                  </div>
                </div>
              </div>

              <!-- Title: Inbox -->
              <span v-else class="flex items-center gap-2 text-[0.95rem] font-bold text-slate-200">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Tin nhắn
                <span v-if="totalUnread > 0" class="bg-red-500 text-white text-[0.65rem] font-bold min-w-[18px] h-[18px] rounded-full flex items-center justify-center px-1">
                  {{ totalUnread > 9 ? '9+' : totalUnread }}
                </span>
              </span>
            </div>

            <div class="flex gap-1">
              <button class="size-7 rounded-full bg-transparent border-none text-slate-400 cursor-pointer flex items-center justify-center transition-all duration-150 hover:bg-white/[0.08] hover:text-slate-200" @click="isOpen = false" title="Thu nhỏ">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
              <button class="size-7 rounded-full bg-transparent border-none text-slate-400 cursor-pointer flex items-center justify-center transition-all duration-150 hover:bg-white/[0.08] hover:text-red-400" @click="isOpen = false" title="Đóng">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>

          <!-- BODY: conversation list -->
          <div v-if="!activeConversation" class="flex-1 overflow-y-auto cw-body-scroll">
            <!-- Loading skeleton -->
            <template v-if="loadingConversations">
              <div v-for="i in 5" :key="i" class="flex items-center gap-2.5 px-3.5 py-2.5">
                <div class="size-[42px] rounded-full bg-white/[0.06] shrink-0 animate-[cwPulse_1.4s_ease-in-out_infinite]" />
                <div class="flex-1 flex flex-col gap-[7px]">
                  <div class="h-2.5 rounded-[5px] bg-white/[0.05] w-[55%] animate-[cwPulse_1.4s_ease-in-out_infinite]" />
                  <div class="h-2.5 rounded-[5px] bg-white/[0.05] w-[75%] animate-[cwPulse_1.4s_ease-in-out_infinite_0.15s]" />
                </div>
              </div>
            </template>

            <!-- Empty -->
            <div v-else-if="conversations.length === 0" class="flex flex-col items-center justify-center gap-2.5 h-full text-slate-600 text-center p-5">
              <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <p class="text-[0.82rem] m-0">Chưa có cuộc trò chuyện nào</p>
            </div>

            <!-- List -->
            <button
              v-for="conv in conversations"
              :key="conv.id"
              class="flex items-center gap-2.5 px-3.5 py-2.5 w-full bg-transparent border-none border-b border-white/[0.03] cursor-pointer text-left transition-colors duration-150 hover:bg-white/[0.04]"
              @click="selectConversation(conv)"
            >
              <div class="size-[42px] rounded-full overflow-hidden bg-gradient-to-br from-indigo-600 to-violet-700 flex items-center justify-center text-[0.8rem] font-bold text-white shrink-0">
                <img v-if="conv.other_user?.avatar_url" :src="conv.other_user.avatar_url" class="w-full h-full object-cover" />
                <span v-else>{{ initials(conv.other_user?.full_name) }}</span>
              </div>
              <div class="flex-1 min-w-0">
                <div class="flex justify-between items-center gap-1 mb-[3px]">
                  <span class="text-[0.85rem] font-semibold text-slate-200 whitespace-nowrap overflow-hidden text-ellipsis">{{ conv.other_user?.full_name ?? 'Người dùng' }}</span>
                  <span class="text-[0.68rem] text-slate-600 shrink-0">{{ relTime(conv.last_message?.created_at) }}</span>
                </div>
                <div class="flex justify-between items-center gap-1.5">
                  <span class="text-[0.78rem] whitespace-nowrap overflow-hidden text-ellipsis flex-1" :class="conv.unread_count > 0 ? 'text-slate-400 font-medium' : 'text-slate-500'">
                    {{ preview(conv.last_message) }}
                  </span>
                  <span v-if="conv.unread_count > 0" class="bg-indigo-500 text-white text-[0.62rem] font-bold min-w-[16px] h-4 rounded-full flex items-center justify-center px-[3px] shrink-0">
                    {{ conv.unread_count > 9 ? '9+' : conv.unread_count }}
                  </span>
                </div>
              </div>
            </button>
          </div>

          <!-- BODY: messages -->
          <div
            v-else
            class="flex-1 overflow-y-auto px-3 pt-3 pb-1.5 flex flex-col gap-1 bg-[#0f1117] cw-msg-scroll"
            ref="msgContainer"
            @scroll="onScroll"
          >
            <!-- Load more -->
            <button
              v-if="hasMore"
              class="self-center mb-2 bg-indigo-500/[0.12] text-indigo-400 border border-indigo-500/20 px-3.5 py-1 rounded-full text-[0.75rem] cursor-pointer transition-all hover:bg-indigo-500/[0.22] disabled:opacity-50 disabled:cursor-not-allowed"
              :disabled="loadingMessages"
              @click="loadMoreMessages"
            >
              {{ loadingMessages ? 'Đang tải...' : '↑ Tin nhắn cũ hơn' }}
            </button>

            <!-- Skeleton -->
            <div v-if="loadingMessages && messages.length === 0" class="flex flex-col gap-2.5">
              <div
                v-for="i in 4"
                :key="i"
                class="h-[34px] rounded-[14px] max-w-[60%] animate-[cwPulse_1.4s_ease-in-out_infinite]"
                :class="i % 2 === 0 ? 'self-end bg-indigo-500/[0.08]' : 'self-start bg-white/[0.05]'"
              />
            </div>

            <!-- Messages -->
            <div
              v-for="msg in messages"
              :key="msg.id"
              class="flex items-end gap-1.5 max-w-[82%] cw-msg-fadein"
              :class="msg.sender?.id === currentUserId ? 'self-end flex-row-reverse' : 'self-start'"
            >
              <!-- Avatar (người khác) -->
              <div v-if="msg.sender?.id !== currentUserId" class="size-[26px] rounded-full overflow-hidden bg-gradient-to-br from-indigo-600 to-violet-700 flex items-center justify-center text-[0.62rem] font-bold text-white shrink-0">
                <img v-if="msg.sender?.avatar_url" :src="msg.sender.avatar_url" class="w-full h-full object-cover" />
                <span v-else>{{ initials(msg.sender?.full_name) }}</span>
              </div>

              <!-- Bubble -->
              <div class="flex flex-col">
                <!-- Image -->
                <a v-if="msg.type === 'image'" :href="msg.file_url" target="_blank">
                  <img class="max-w-[180px] max-h-[180px] rounded-xl object-cover" :src="msg.file_url" loading="lazy" />
                </a>
                <!-- File -->
                <a
                  v-else-if="msg.type === 'file'"
                  class="flex items-center gap-1.5 px-3 py-1.5 rounded-[14px] text-[0.82rem] no-underline"
                  :class="msg.sender?.id === currentUserId ? 'bg-gradient-to-br from-indigo-600 to-violet-700 text-white' : 'bg-[#1e293b] text-slate-400 border border-white/5'"
                  :href="msg.file_url"
                  target="_blank"
                >
                  📎 Tệp đính kèm
                </a>
                <!-- Text -->
                <div
                  v-else
                  class="px-3 py-2 rounded-[18px] text-[0.85rem] leading-relaxed break-words whitespace-pre-wrap max-w-[220px]"
                  :class="[
                    msg.sender?.id === currentUserId
                      ? 'bg-gradient-to-br from-indigo-600 to-purple-800 text-white rounded-br-[4px]'
                      : 'bg-[#1e293b] text-slate-200 rounded-bl-[4px] border border-white/5',
                    msg._status === 'error' ? 'opacity-60 !border border-red-400/30' : ''
                  ]"
                >{{ msg.body }}</div>

                <!-- Meta -->
                <div
                  class="flex items-center gap-[3px] mt-0.5 px-[3px]"
                  :class="msg.sender?.id === currentUserId ? 'justify-end' : 'justify-start'"
                >
                  <span class="text-[0.65rem] text-slate-600">{{ formatTime(msg.created_at) }}</span>
                  <span v-if="msg.sender?.id === currentUserId" class="block">
                    <svg v-if="msg._status === 'sending'" class="text-slate-500 animate-spin" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83"/></svg>
                    <svg v-else-if="msg._status === 'error'" class="text-red-400" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <svg v-else class="text-indigo-400" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- INPUT -->
          <div v-if="activeConversation" class="flex items-end gap-2 px-3 py-2.5 bg-[#161b27] border-t border-white/[0.06] shrink-0">
            <textarea
              ref="inputEl"
              v-model="inputText"
              class="flex-1 bg-[#0f1117] border border-white/[0.08] rounded-[20px] px-3.5 py-2 text-slate-200 text-[0.85rem] font-[inherit] resize-none outline-none max-h-[80px] overflow-hidden block leading-relaxed transition-colors duration-200 focus:border-indigo-500/40 cw-textarea-scroll"
              style="scrollbar-width: none;"
              placeholder="Aa"
              rows="1"
              @keydown.enter.exact.prevent="sendMsg"
              @input="onInputChange"
            />
            <button
              class="size-[34px] rounded-full flex items-center justify-center transition-all duration-200 shrink-0 mb-[1px] disabled:opacity-40 disabled:cursor-not-allowed"
              :class="inputText.trim()
                ? 'bg-gradient-to-br from-indigo-600 to-violet-700 border-transparent text-white shadow-[0_4px_12px_rgba(99,102,241,0.4)] scale-105'
                : 'bg-indigo-500/15 border border-indigo-500/20 text-indigo-500'"
              :disabled="!inputText.trim() || sending"
              @click="sendMsg"
            >
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
          </div>
        </div>
      </Transition>

      <!-- ============== FLOATING TOGGLE BUTTON ============== -->
      <button
        class="size-14 rounded-full bg-gradient-to-br from-indigo-600 to-violet-700 border-none cursor-pointer text-white flex items-center justify-center relative shadow-[0_8px_24px_rgba(99,102,241,0.5)] transition-all duration-200 shrink-0 hover:scale-[1.08] hover:shadow-[0_12px_32px_rgba(99,102,241,0.65)] active:scale-95"
        @click="toggleChat"
        :title="isOpen ? 'Đóng chat' : 'Tin nhắn'"
      >
        <Transition name="icon-swap" mode="out-in">
          <svg v-if="isOpen" key="close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          <svg v-else key="chat" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" opacity="0.9"/></svg>
        </Transition>

        <!-- Unread badge -->
        <span
          v-if="!isOpen && totalUnread > 0"
          class="absolute -top-1 -right-1 min-w-5 h-5 bg-red-500 text-white text-[0.65rem] font-bold rounded-full flex items-center justify-center px-1 border-2 border-[#0f1117]"
        >
          {{ totalUnread > 9 ? '9+' : totalUnread }}
        </span>

        <!-- Pulse ring -->
        <span v-if="!isOpen && totalUnread > 0" class="absolute inset-[-4px] rounded-full border-2 border-indigo-500/60 float-pulse pointer-events-none" />
      </button>

    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { storeToRefs } from 'pinia';
import { useChatStore } from '@/stores/chat';
import { useAuthStore } from '@/stores/auth';
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/vi';

dayjs.extend(relativeTime);
dayjs.locale('vi');

const authStore = useAuthStore();
const chatStore = useChatStore();

const {
  conversations, activeConversation, messages,
  loadingConversations, loadingMessages, sending,
  hasMore, typingUsers, totalUnread,
} = storeToRefs(chatStore);

const {
  loadConversations, openConversation, loadMoreMessages,
  sendMessage, sendTypingIndicator, unsubscribeActive,
} = chatStore;

const isOpen = ref(false);
const inputText = ref('');
const msgContainer = ref(null);
const inputEl = ref(null);
const currentUserId = computed(() => authStore.user?.id);
const isTyping = computed(() => typingUsers.value.size > 0);

function initials(name) {
  if (!name) return '?';
  return name.split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}
function relTime(iso) { return iso ? dayjs(iso).fromNow(true) : ''; }
function formatTime(iso) { return iso ? dayjs(iso).format('HH:mm') : ''; }
function preview(msg) {
  if (!msg) return 'Chưa có tin nhắn';
  if (msg.type === 'image') return '📷 Hình ảnh';
  if (msg.type === 'file') return '📎 Tệp đính kèm';
  const text = msg.body ?? '';
  return text.length > 35 ? text.slice(0, 35) + '...' : text;
}

function toggleChat() {
  isOpen.value = !isOpen.value;
  if (isOpen.value && conversations.value.length === 0) {
    safeLoadConversations();
  }
}

async function selectConversation(conv) {
  await openConversation(conv);
  nextTick(() => scrollBottom(false));
}

function closeConversation() {
  unsubscribeActive();
  activeConversation.value = null;
}

async function sendMsg() {
  const text = inputText.value.trim();
  if (!text || sending.value) return;
  inputText.value = '';
  resetInputHeight();
  await sendMessage(text);
  scrollBottom();
}

let typingTimer = null;
function onInputChange() {
  autoResize();
  if (typingTimer) return;
  sendTypingIndicator();
  typingTimer = setTimeout(() => { typingTimer = null; }, 2000);
}

function onScroll() {
  if (msgContainer.value?.scrollTop === 0 && hasMore.value) {
    loadMoreMessages();
  }
}

function scrollBottom(smooth = true) {
  nextTick(() => {
    if (msgContainer.value) {
      msgContainer.value.scrollTo({ top: msgContainer.value.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
    }
  });
}

function autoResize() {
  if (!inputEl.value) return;
  inputEl.value.style.height = '0';
  inputEl.value.style.height = Math.min(inputEl.value.scrollHeight, 80) + 'px';
}

function resetInputHeight() {
  if (inputEl.value) inputEl.value.style.height = 'auto';
}

async function safeLoadConversations() {
  try {
    await loadConversations();
  } catch (err) {
    console.warn('[FloatingChat] Không load được conversations:', err?.message ?? err);
  }
}

watch(() => messages.value.length, (n, o) => { if (n > o) scrollBottom(); });
watch(() => authStore.isAuthenticated, (auth) => {
  if (auth) safeLoadConversations();
}, { immediate: true });
</script>

<style scoped>
/* ── Scrollbars ─────────────────────────────────── */
.cw-body-scroll::-webkit-scrollbar { width: 3px; }
.cw-body-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 99px; }

.cw-msg-scroll::-webkit-scrollbar { width: 3px; }
.cw-msg-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 99px; }

.cw-textarea-scroll::-webkit-scrollbar { display: none; }

/* ── Typing dots ────────────────────────────────── */
.cw-dot {
  display: inline-block;
  width: 4px; height: 4px;
  border-radius: 50%;
  background: #818cf8;
  animation: cwBounce 1.2s ease-in-out infinite;
}
.cw-dot:nth-child(2) { animation-delay: 0.15s; }
.cw-dot:nth-child(3) { animation-delay: 0.3s; }

/* ── Pulse ring for float button ─────────────────── */
.float-pulse {
  animation: cwPulseRing 2s ease-out infinite;
}

/* ── Message fade-in ─────────────────────────────── */
.cw-msg-fadein {
  animation: cwFadeIn 0.18s ease-out;
}

/* ── Chat window pop transition ─────────────────── */
.chat-pop-enter-active { animation: cwPopIn 0.28s cubic-bezier(0.34, 1.56, 0.64, 1); }
.chat-pop-leave-active { animation: cwPopOut 0.2s ease-in; }

/* ── Icon swap transition ────────────────────────── */
.icon-swap-enter-active, .icon-swap-leave-active { transition: all 0.15s; }
.icon-swap-enter-from { opacity: 0; transform: rotate(-90deg) scale(0.7); }
.icon-swap-leave-to   { opacity: 0; transform: rotate(90deg) scale(0.7); }

/* ── @keyframes ─────────────────────────────────── */
@keyframes cwBounce {
  0%, 60%, 100% { transform: translateY(0); }
  30%           { transform: translateY(-3px); }
}

@keyframes cwPulseRing {
  0%   { transform: scale(1); opacity: 0.8; }
  70%  { transform: scale(1.4); opacity: 0; }
  100% { transform: scale(1.4); opacity: 0; }
}

@keyframes cwFadeIn {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}

@keyframes cwPopIn {
  from { opacity: 0; transform: scale(0.7) translateY(20px); transform-origin: bottom right; }
  to   { opacity: 1; transform: scale(1) translateY(0);      transform-origin: bottom right; }
}

@keyframes cwPopOut {
  from { opacity: 1; transform: scale(1) translateY(0);       transform-origin: bottom right; }
  to   { opacity: 0; transform: scale(0.7) translateY(16px);  transform-origin: bottom right; }
}

@keyframes cwPulse {
  0%, 100% { opacity: 0.4; }
  50%      { opacity: 0.9; }
}

/* ── Responsive ─────────────────────────────────── */
@media (max-width: 480px) {
  .fixed.bottom-6.right-6 { bottom: 16px; right: 16px; }
}
</style>
