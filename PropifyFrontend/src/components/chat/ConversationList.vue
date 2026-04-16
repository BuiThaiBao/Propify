<template>
  <div class="conversation-list">
    <!-- Loading state -->
    <div v-if="loading" class="conv-loading">
      <div v-for="i in 5" :key="i" class="conv-skeleton">
        <div class="skel-avatar" />
        <div class="skel-content">
          <div class="skel-name" />
          <div class="skel-preview" />
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="conversations.length === 0" class="conv-empty">
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      <p>Chưa có cuộc trò chuyện nào</p>
    </div>

    <!-- List -->
    <button
      v-for="conv in conversations"
      :key="conv.id"
      class="conv-item"
      :class="{ 'conv-active': conv.id === activeId }"
      @click="$emit('select', conv)"
    >
      <!-- Avatar -->
      <div class="conv-avatar">
        <img
          v-if="conv.other_user?.avatar_url"
          :src="conv.other_user.avatar_url"
          :alt="conv.other_user?.full_name"
        />
        <span v-else>{{ getInitials(conv.other_user?.full_name) }}</span>
      </div>

      <!-- Info -->
      <div class="conv-info">
        <div class="conv-top">
          <span class="conv-name">{{ conv.other_user?.full_name ?? 'Người dùng' }}</span>
          <span class="conv-time">{{ formatTime(conv.last_message?.created_at) }}</span>
        </div>
        <div class="conv-bottom">
          <span class="conv-preview" :class="{ 'conv-unread-preview': conv.unread_count > 0 }">
            {{ getPreview(conv.last_message) }}
          </span>
          <span v-if="conv.unread_count > 0" class="conv-unread">
            {{ conv.unread_count > 9 ? '9+' : conv.unread_count }}
          </span>
        </div>
      </div>
    </button>
  </div>
</template>

<script setup>
import dayjs from 'dayjs';
import relativeTime from 'dayjs/plugin/relativeTime';
import 'dayjs/locale/vi';

dayjs.extend(relativeTime);
dayjs.locale('vi');

defineProps({
  conversations: { type: Array, default: () => [] },
  activeId: { type: Number, default: null },
  loading: { type: Boolean, default: false },
});

defineEmits(['select']);

function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2);
}

function formatTime(isoString) {
  if (!isoString) return '';
  return dayjs(isoString).fromNow(true);
}

function getPreview(lastMessage) {
  if (!lastMessage) return 'Chưa có tin nhắn';
  if (lastMessage.type === 'image') return '📷 Hình ảnh';
  if (lastMessage.type === 'file') return '📎 Tệp đính kèm';
  return lastMessage.body?.length > 40
    ? lastMessage.body.slice(0, 40) + '...'
    : lastMessage.body ?? '';
}
</script>

<style scoped>
.conversation-list {
  flex: 1;
  overflow-y: auto;
}

.conversation-list::-webkit-scrollbar {
  width: 3px;
}

.conversation-list::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.08);
  border-radius: 99px;
}

/* ===== ITEM ===== */
.conv-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  width: 100%;
  background: none;
  border: none;
  cursor: pointer;
  text-align: left;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
  transition: background 0.15s;
  position: relative;
}

.conv-item:hover {
  background: rgba(255, 255, 255, 0.04);
}

.conv-active {
  background: rgba(99, 102, 241, 0.1) !important;
  border-left: 3px solid #6366f1;
  padding-left: 17px;
}

/* ===== AVATAR ===== */
.conv-avatar {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.9rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
}

.conv-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ===== INFO ===== */
.conv-info {
  flex: 1;
  min-width: 0;
}

.conv-top {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
  margin-bottom: 4px;
}

.conv-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #e2e8f0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.conv-time {
  font-size: 0.72rem;
  color: #475569;
  white-space: nowrap;
  flex-shrink: 0;
}

.conv-bottom {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 8px;
}

.conv-preview {
  font-size: 0.8rem;
  color: #64748b;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  flex: 1;
}

.conv-unread-preview {
  color: #94a3b8;
  font-weight: 500;
}

.conv-unread {
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
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
  flex-shrink: 0;
}

/* ===== EMPTY ===== */
.conv-empty {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 12px;
  padding: 50px 20px;
  color: #475569;
  text-align: center;
}

.conv-empty p {
  font-size: 0.85rem;
  margin: 0;
}

/* ===== SKELETON ===== */
.conv-loading {
  display: flex;
  flex-direction: column;
}

.conv-skeleton {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 14px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.04);
}

.skel-avatar {
  width: 46px;
  height: 46px;
  border-radius: 50%;
  background: rgba(255, 255, 255, 0.06);
  flex-shrink: 0;
  animation: pulse 1.5s ease-in-out infinite;
}

.skel-content {
  flex: 1;
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.skel-name {
  height: 12px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.06);
  width: 60%;
  animation: pulse 1.5s ease-in-out infinite;
}

.skel-preview {
  height: 10px;
  border-radius: 6px;
  background: rgba(255, 255, 255, 0.04);
  width: 80%;
  animation: pulse 1.5s ease-in-out infinite 0.2s;
}

@keyframes pulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}
</style>
