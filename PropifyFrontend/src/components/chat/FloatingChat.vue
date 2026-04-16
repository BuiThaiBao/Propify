<template>
  <!-- Chỉ hiển thị khi user đã đăng nhập -->
  <Teleport to="body">
    <div v-if="authStore.isAuthenticated" class="floating-chat-root">

      <!-- ============== CHAT WINDOW ============== -->
      <Transition name="chat-pop">
        <div v-if="isOpen" class="chat-window" :class="{ 'window-expanded': activeConversation }">

          <!-- HEADER -->
          <div class="cw-header">
            <div class="cw-header-left">
              <!-- Back button khi đang xem messages -->
              <button v-if="activeConversation" class="btn-icon btn-back-conv" @click="closeConversation" title="Quay lại">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="15 18 9 12 15 6"/></svg>
              </button>

              <!-- Title -->
              <div v-if="activeConversation" class="cw-title-conv">
                <div class="cw-partner-avatar">
                  <img v-if="activeConversation.other_user?.avatar_url" :src="activeConversation.other_user.avatar_url" />
                  <span v-else>{{ initials(activeConversation.other_user?.full_name) }}</span>
                </div>
                <div>
                  <div class="cw-partner-name">{{ activeConversation.other_user?.full_name }}</div>
                  <div v-if="isTyping" class="cw-typing">
                    <span class="dot"/><span class="dot"/><span class="dot"/>
                    <span>đang nhập...</span>
                  </div>
                </div>
              </div>
              <span v-else class="cw-title-text">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
                Tin nhắn
                <span v-if="totalUnread > 0" class="cw-unread-title">{{ totalUnread > 9 ? '9+' : totalUnread }}</span>
              </span>
            </div>

            <div class="cw-header-right">
              <button class="btn-icon" @click="isOpen = false" title="Thu nhỏ">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="5" y1="12" x2="19" y2="12"/></svg>
              </button>
              <button class="btn-icon btn-close" @click="isOpen = false" title="Đóng">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
              </button>
            </div>
          </div>

          <!-- BODY: conversation list -->
          <div v-if="!activeConversation" class="cw-body">
            <!-- Loading -->
            <template v-if="loadingConversations">
              <div v-for="i in 5" :key="i" class="conv-skel">
                <div class="skel-av"/>
                <div class="skel-lines">
                  <div class="skel-line skel-name"/>
                  <div class="skel-line skel-preview"/>
                </div>
              </div>
            </template>

            <!-- Empty -->
            <div v-else-if="conversations.length === 0" class="cw-empty">
              <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.3"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
              <p>Chưa có cuộc trò chuyện nào</p>
            </div>

            <!-- List -->
            <button
              v-for="conv in conversations"
              :key="conv.id"
              class="conv-item"
              @click="selectConversation(conv)"
            >
              <div class="conv-av">
                <img v-if="conv.other_user?.avatar_url" :src="conv.other_user.avatar_url" />
                <span v-else>{{ initials(conv.other_user?.full_name) }}</span>
              </div>
              <div class="conv-info">
                <div class="conv-top">
                  <span class="conv-name">{{ conv.other_user?.full_name ?? 'Người dùng' }}</span>
                  <span class="conv-time">{{ relTime(conv.last_message?.created_at) }}</span>
                </div>
                <div class="conv-bottom">
                  <span class="conv-preview" :class="{ bold: conv.unread_count > 0 }">{{ preview(conv.last_message) }}</span>
                  <span v-if="conv.unread_count > 0" class="conv-badge">{{ conv.unread_count > 9 ? '9+' : conv.unread_count }}</span>
                </div>
              </div>
            </button>
          </div>

          <!-- BODY: messages -->
          <div v-else class="cw-messages" ref="msgContainer" @scroll="onScroll">
            <!-- Load more -->
            <button v-if="hasMore" class="btn-load-more" :disabled="loadingMessages" @click="loadMoreMessages">
              {{ loadingMessages ? 'Đang tải...' : '↑ Tin nhắn cũ hơn' }}
            </button>

            <!-- Skeleton loading -->
            <div v-if="loadingMessages && messages.length === 0" class="msgs-loading">
              <div v-for="i in 4" :key="i" class="msg-skel" :class="i % 2 === 0 ? 'right' : 'left'"/>
            </div>

            <!-- Messages -->
            <div
              v-for="msg in messages"
              :key="msg.id"
              class="msg-row"
              :class="msg.sender?.id === currentUserId ? 'msg-mine' : 'msg-theirs'"
            >
              <!-- Avatar (người khác) -->
              <div v-if="msg.sender?.id !== currentUserId" class="msg-av">
                <img v-if="msg.sender?.avatar_url" :src="msg.sender.avatar_url" />
                <span v-else>{{ initials(msg.sender?.full_name) }}</span>
              </div>

              <!-- Bubble -->
              <div class="msg-bubble-wrap">
                <!-- Image -->
                <a v-if="msg.type === 'image'" :href="msg.file_url" target="_blank">
                  <img class="msg-img" :src="msg.file_url" loading="lazy" />
                </a>
                <!-- File -->
                <a v-else-if="msg.type === 'file'" class="msg-file" :href="msg.file_url" target="_blank">
                  📎 Tệp đính kèm
                </a>
                <!-- Text -->
                <div v-else class="msg-bubble" :class="{ 'bubble-error': msg._status === 'error' }">
                  {{ msg.body }}
                </div>

                <!-- Status + time -->
                <div class="msg-meta">
                  <span class="msg-time">{{ formatTime(msg.created_at) }}</span>
                  <span v-if="msg.sender?.id === currentUserId" class="msg-status">
                    <svg v-if="msg._status === 'sending'" class="spin-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83"/></svg>
                    <svg v-else-if="msg._status === 'error'" class="err-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <svg v-else class="ok-icon" width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"/></svg>
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- INPUT -->
          <div v-if="activeConversation" class="cw-input-bar">
            <textarea
              ref="inputEl"
              v-model="inputText"
              class="cw-textarea"
              placeholder="Aa"
              rows="1"
              @keydown.enter.exact.prevent="sendMsg"
              @input="onInputChange"
            />
            <button class="btn-send-msg" :class="{ active: inputText.trim() }" :disabled="!inputText.trim() || sending" @click="sendMsg">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
            </button>
          </div>

        </div>
      </Transition>

      <!-- ============== FLOATING TOGGLE BUTTON ============== -->
      <button class="float-btn" @click="toggleChat" :title="isOpen ? 'Đóng chat' : 'Tin nhắn'">
        <!-- Đóng icon khi open -->
        <Transition name="icon-swap" mode="out-in">
          <svg v-if="isOpen" key="close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
          <svg v-else key="chat" width="22" height="22" viewBox="0 0 24 24" fill="currentColor"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" opacity="0.9"/></svg>
        </Transition>

        <!-- Unread badge -->
        <span v-if="!isOpen && totalUnread > 0" class="float-badge">
          {{ totalUnread > 9 ? '9+' : totalUnread }}
        </span>

        <!-- Pulse ring khi có tin nhắn mới -->
        <span v-if="!isOpen && totalUnread > 0" class="float-pulse"/>
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

// ============ Stores ============
const authStore = useAuthStore();
const chatStore = useChatStore();

// Dùng storeToRefs để giữ reactivity khi destructure reactive state
const {
  conversations, activeConversation, messages,
  loadingConversations, loadingMessages, sending,
  hasMore, typingUsers, totalUnread,
} = storeToRefs(chatStore);

// Actions không cần storeToRefs
const {
  loadConversations, openConversation, loadMoreMessages,
  sendMessage, sendTypingIndicator, unsubscribeActive,
} = chatStore;

// ============ State ============
const isOpen = ref(false);
const inputText = ref('');
const msgContainer = ref(null);
const inputEl = ref(null);
const currentUserId = computed(() => authStore.user?.id);
const isTyping = computed(() => typingUsers.value.size > 0);

// ============ Helpers ============
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

// ============ Actions ============
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
  inputEl.value.style.height = 'auto';
  inputEl.value.style.height = Math.min(inputEl.value.scrollHeight, 80) + 'px';
}

function resetInputHeight() {
  if (inputEl.value) inputEl.value.style.height = 'auto';
}

/**
 * Load conversations an toàn — lỗi API không crash widget.
 */
async function safeLoadConversations() {
  try {
    await loadConversations();
  } catch (err) {
    console.warn('[FloatingChat] Không load được conversations:', err?.message ?? err);
  }
}

// Auto scroll on new message
watch(() => messages.value.length, (n, o) => { if (n > o) scrollBottom(); });

// Load conversations khi user login
watch(() => authStore.isAuthenticated, (auth) => {
  if (auth) safeLoadConversations();
}, { immediate: true });

</script>

<style scoped>
/* ================================
   ROOT
================================ */
.floating-chat-root {
  position: fixed;
  bottom: 24px;
  right: 24px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  align-items: flex-end;
  gap: 12px;
  font-family: 'Inter', -apple-system, sans-serif;
}

/* ================================
   FLOAT BUTTON
================================ */
.float-btn {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border: none;
  cursor: pointer;
  color: white;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  box-shadow: 0 8px 24px rgba(99, 102, 241, 0.5);
  transition: transform 0.2s, box-shadow 0.2s;
  flex-shrink: 0;
}

.float-btn:hover {
  transform: scale(1.08);
  box-shadow: 0 12px 32px rgba(99, 102, 241, 0.65);
}

.float-btn:active {
  transform: scale(0.95);
}

/* Unread badge */
.float-badge {
  position: absolute;
  top: -4px;
  right: -4px;
  min-width: 20px;
  height: 20px;
  background: #ef4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  border-radius: 99px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid #0f1117;
}

/* Pulse ring */
.float-pulse {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid rgba(99, 102, 241, 0.6);
  animation: pulse-ring 2s ease-out infinite;
  pointer-events: none;
}

@keyframes pulse-ring {
  0%   { transform: scale(1); opacity: 0.8; }
  70%  { transform: scale(1.4); opacity: 0; }
  100% { transform: scale(1.4); opacity: 0; }
}

/* Icon swap transition */
.icon-swap-enter-active, .icon-swap-leave-active { transition: all 0.15s; }
.icon-swap-enter-from { opacity: 0; transform: rotate(-90deg) scale(0.7); }
.icon-swap-leave-to   { opacity: 0; transform: rotate(90deg)  scale(0.7); }

/* ================================
   CHAT WINDOW
================================ */
.chat-window {
  width: 340px;
  height: 480px;
  background: #161b27;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 18px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow: 0 20px 60px rgba(0, 0, 0, 0.6), 0 0 0 1px rgba(255,255,255,0.05);
  transition: width 0.2s, height 0.2s;
}

/* Pop animation */
.chat-pop-enter-active {
  animation: pop-in 0.28s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.chat-pop-leave-active {
  animation: pop-out 0.2s ease-in;
}

@keyframes pop-in {
  from { opacity: 0; transform: scale(0.7) translateY(20px); transform-origin: bottom right; }
  to   { opacity: 1; transform: scale(1)   translateY(0);    transform-origin: bottom right; }
}

@keyframes pop-out {
  from { opacity: 1; transform: scale(1)   translateY(0);    transform-origin: bottom right; }
  to   { opacity: 0; transform: scale(0.7) translateY(16px); transform-origin: bottom right; }
}

/* ================================
   HEADER
================================ */
.cw-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 14px 14px 12px;
  background: linear-gradient(135deg, #1e1b4b, #2d1b69);
  border-bottom: 1px solid rgba(255,255,255,0.06);
  flex-shrink: 0;
}

.cw-header-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.cw-title-text {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.95rem;
  font-weight: 700;
  color: #e2e8f0;
}

.cw-unread-title {
  background: #ef4444;
  color: white;
  font-size: 0.65rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 99px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}

.cw-title-conv {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.cw-partner-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.75rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.cw-partner-avatar img { width: 100%; height: 100%; object-fit: cover; }

.cw-partner-name {
  font-size: 0.88rem;
  font-weight: 600;
  color: #e2e8f0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 160px;
}

/* Typing indicator */
.cw-typing {
  display: flex;
  align-items: center;
  gap: 3px;
  margin-top: 1px;
}

.dot {
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #818cf8;
  animation: bounce 1.2s ease-in-out infinite;
}
.dot:nth-child(2) { animation-delay: 0.15s; }
.dot:nth-child(3) { animation-delay: 0.3s; }
.cw-typing span:last-child { font-size: 0.7rem; color: #64748b; }

@keyframes bounce {
  0%, 60%, 100% { transform: translateY(0); }
  30%           { transform: translateY(-3px); }
}

/* Header buttons */
.cw-header-right { display: flex; gap: 4px; }

.btn-icon {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  background: none;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s;
}

.btn-icon:hover { background: rgba(255,255,255,0.08); color: #e2e8f0; }
.btn-back-conv  { margin-right: 2px; }

/* ================================
   BODY — Conversation list
================================ */
.cw-body {
  flex: 1;
  overflow-y: auto;
}

.cw-body::-webkit-scrollbar { width: 3px; }
.cw-body::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 99px; }

/* Empty */
.cw-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 10px;
  height: 100%;
  color: #475569;
  text-align: center;
  padding: 20px;
}
.cw-empty p { font-size: 0.82rem; margin: 0; }

/* Conversation item */
.conv-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  width: 100%;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  border-bottom: 1px solid rgba(255,255,255,0.03);
  transition: background 0.15s;
}

.conv-item:hover { background: rgba(255,255,255,0.04); }

.conv-av {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.8rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}
.conv-av img { width: 100%; height: 100%; object-fit: cover; }

.conv-info { flex: 1; min-width: 0; }

.conv-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 4px;
  margin-bottom: 3px;
}

.conv-name {
  font-size: 0.85rem;
  font-weight: 600;
  color: #e2e8f0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conv-time { font-size: 0.68rem; color: #475569; flex-shrink: 0; }

.conv-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 6px;
}

.conv-preview {
  font-size: 0.78rem;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.conv-preview.bold { color: #94a3b8; font-weight: 500; }

.conv-badge {
  background: #6366f1;
  color: white;
  font-size: 0.62rem;
  font-weight: 700;
  min-width: 16px;
  height: 16px;
  border-radius: 99px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 3px;
  flex-shrink: 0;
}

/* Skeleton */
.conv-skel {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
}

.skel-av {
  width: 42px;
  height: 42px;
  border-radius: 50%;
  background: rgba(255,255,255,0.06);
  flex-shrink: 0;
  animation: skpulse 1.4s ease-in-out infinite;
}

.skel-lines { flex: 1; display: flex; flex-direction: column; gap: 7px; }

.skel-line {
  height: 10px;
  border-radius: 5px;
  background: rgba(255,255,255,0.05);
  animation: skpulse 1.4s ease-in-out infinite;
}

.skel-name { width: 55%; }
.skel-preview { width: 75%; animation-delay: 0.15s; }

@keyframes skpulse {
  0%, 100% { opacity: 0.4; }
  50%  { opacity: 0.9; }
}

/* ================================
   MESSAGES AREA
================================ */
.cw-messages {
  flex: 1;
  overflow-y: auto;
  padding: 12px 12px 6px;
  display: flex;
  flex-direction: column;
  gap: 4px;
  background: #0f1117;
}

.cw-messages::-webkit-scrollbar { width: 3px; }
.cw-messages::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.08); border-radius: 99px; }

/* Load more */
.btn-load-more {
  align-self: center;
  margin-bottom: 8px;
  background: rgba(99,102,241,0.12);
  color: #818cf8;
  border: 1px solid rgba(99,102,241,0.2);
  padding: 4px 14px;
  border-radius: 99px;
  font-size: 0.75rem;
  cursor: pointer;
  transition: all 0.2s;
}
.btn-load-more:hover:not(:disabled) { background: rgba(99,102,241,0.22); }
.btn-load-more:disabled { opacity: 0.5; cursor: not-allowed; }

/* Message skeleton */
.msgs-loading { display: flex; flex-direction: column; gap: 10px; }
.msg-skel {
  height: 34px;
  border-radius: 14px;
  max-width: 60%;
  background: rgba(255,255,255,0.05);
  animation: skpulse 1.4s ease-in-out infinite;
}
.msg-skel.right { align-self: flex-end; background: rgba(99,102,241,0.08); }
.msg-skel.left  { align-self: flex-start; }

/* Message rows */
.msg-row {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  max-width: 82%;
  animation: fadein 0.18s ease-out;
}

@keyframes fadein {
  from { opacity: 0; transform: translateY(6px); }
  to   { opacity: 1; transform: translateY(0); }
}

.msg-mine   { align-self: flex-end; flex-direction: row-reverse; }
.msg-theirs { align-self: flex-start; }

.msg-av {
  width: 26px;
  height: 26px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.62rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}
.msg-av img { width: 100%; height: 100%; object-fit: cover; }

.msg-bubble-wrap { display: flex; flex-direction: column; }

/* Bubbles */
.msg-bubble {
  padding: 8px 12px;
  border-radius: 18px;
  font-size: 0.85rem;
  line-height: 1.5;
  word-break: break-word;
  white-space: pre-wrap;
  max-width: 220px;
}

.msg-mine   .msg-bubble { background: linear-gradient(135deg, #4f46e5, #6d28d9); color: white; border-bottom-right-radius: 4px; }
.msg-theirs .msg-bubble { background: #1e293b; color: #e2e8f0; border-bottom-left-radius: 4px; border: 1px solid rgba(255,255,255,0.05); }

.bubble-error { opacity: 0.6; border: 1px solid rgba(248,113,113,0.3) !important; }

.msg-img { max-width: 180px; max-height: 180px; border-radius: 12px; object-fit: cover; display: block; }

.msg-file {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 7px 12px;
  border-radius: 14px;
  color: #94a3b8;
  font-size: 0.82rem;
  text-decoration: none;
  background: #1e293b;
  border: 1px solid rgba(255,255,255,0.05);
}

/* Meta */
.msg-meta {
  display: flex;
  align-items: center;
  gap: 3px;
  margin-top: 2px;
  padding: 0 3px;
}
.msg-mine .msg-meta   { justify-content: flex-end; }
.msg-theirs .msg-meta { justify-content: flex-start; }

.msg-time { font-size: 0.65rem; color: #475569; }

.msg-status { display: block; }
.ok-icon    { color: #818cf8; }
.err-icon   { color: #f87171; }
.spin-icon  { color: #64748b; animation: spin 1s linear infinite; }

@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* ================================
   INPUT BAR
================================ */
.cw-input-bar {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  padding: 10px 12px;
  background: #161b27;
  border-top: 1px solid rgba(255,255,255,0.06);
  flex-shrink: 0;
}

.cw-textarea {
  flex: 1;
  background: #0f1117;
  border: 1px solid rgba(255,255,255,0.08);
  border-radius: 20px;
  padding: 8px 14px;
  color: #e2e8f0;
  font-size: 0.85rem;
  font-family: inherit;
  resize: none;
  outline: none;
  max-height: 80px;
  overflow-y: auto;
  transition: border-color 0.2s;
  line-height: 1.5;
}

.cw-textarea::placeholder { color: #475569; }
.cw-textarea:focus { border-color: rgba(99,102,241,0.4); }

.btn-send-msg {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  background: rgba(99,102,241,0.15);
  border: 1px solid rgba(99,102,241,0.2);
  color: #6366f1;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-bottom: 1px;
}

.btn-send-msg.active {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 12px rgba(99,102,241,0.4);
  transform: scale(1.05);
}

.btn-send-msg:disabled { opacity: 0.4; cursor: not-allowed; transform: none; }

/* ================================
   RESPONSIVE
================================ */
@media (max-width: 480px) {
  .floating-chat-root { bottom: 16px; right: 16px; }
  .chat-window { width: calc(100vw - 32px); height: 70vh; border-radius: 14px; }
  .float-btn { width: 50px; height: 50px; }
}
</style>
