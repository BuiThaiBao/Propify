<template>
  <div class="ch-header">
    <div class="ch-left">
      <!-- Nút back -->
      <button
        v-if="conversation"
        class="ch-icon-btn"
        title="Quay lại"
        @click="$emit('back')"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="15 18 9 12 15 6" />
        </svg>
      </button>

      <!-- Avatar + tên -->
      <template v-if="conversation">
        <GroupAvatar
          v-if="conversation.type === 'group'"
          :avatar-url="conversation.group?.avatar_url"
          :members="conversation.group?.members ?? []"
          size="sm"
        />
        <div v-else class="ch-avatar">
          <img
            v-if="conversation.other_user?.avatar_url"
            :src="conversation.other_user.avatar_url"
            class="ch-avatar-img"
          />
          <span v-else class="ch-avatar-fallback">{{ initials(conversation.other_user?.full_name) }}</span>
        </div>
        <div class="ch-user-info">
          <div class="ch-user-name">
            {{ conversation.type === 'group' ? (conversation.group?.name ?? 'Nhóm chat') : conversation.other_user?.full_name }}
          </div>
          <div v-if="isTyping" class="ch-typing">
            <span class="ch-dot" /><span class="ch-dot" /><span class="ch-dot" />
            <span>đang nhập...</span>
          </div>
        </div>
      </template>

      <!-- Title khi chưa chọn conversation -->
      <span v-else class="ch-title">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        </svg>
        Tin nhắn
        <span v-if="unreadCount > 0" class="ch-unread-badge">
          {{ unreadCount > 9 ? '9+' : unreadCount }}
        </span>
      </span>
    </div>

    <!-- Nút hành động bên phải -->
    <div class="ch-right">
      <button
        v-if="!conversation"
        class="ch-icon-btn"
        :class="{ 'ch-icon-btn-active': showSearch }"
        title="Tin nhắn mới"
        @click="$emit('toggle-search')"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="11" cy="11" r="8" />
          <line x1="21" y1="21" x2="16.65" y2="16.65" />
        </svg>
      </button>
      <button
        v-if="!conversation"
        class="ch-icon-btn"
        title="Tạo nhóm"
        @click="$emit('create-group')"
      >
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2" />
          <circle cx="9" cy="7" r="4" />
          <path d="M23 21v-2a4 4 0 0 0-3-3.87" />
          <path d="M16 3.13a4 4 0 0 1 0 7.75" />
        </svg>
      </button>
      <button
        v-if="conversation?.type === 'group'"
        class="ch-icon-btn"
        title="Thông tin nhóm"
        @click="$emit('show-group-info')"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10" />
          <path d="M12 16v-4" />
          <path d="M12 8h.01" />
        </svg>
      </button>
      <button class="ch-icon-btn" title="Thu nhỏ" @click="$emit('close')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
      </button>
      <button class="ch-icon-btn ch-close-btn" title="Đóng" @click="$emit('close')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="18" y1="6" x2="6" y2="18" />
          <line x1="6" y1="6" x2="18" y2="18" />
        </svg>
      </button>
    </div>
  </div>
</template>

<script setup>
import { useChatFormatters } from '@/composables/useChatFormatters';
import GroupAvatar from '@/components/chat/group/GroupAvatar.vue';

const { initials } = useChatFormatters();

defineProps({
  conversation: { type: Object, default: null },
  isTyping: { type: Boolean, default: false },
  unreadCount: { type: Number, default: 0 },
  showSearch: { type: Boolean, default: false },
});

defineEmits(['back', 'close', 'toggle-search', 'create-group', 'show-group-info']);
</script>

<style scoped>
.ch-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 14px;
  background: #fff;
  border-bottom: 1px solid rgba(0, 0, 0, 0.05);
  flex-shrink: 0;
}

.ch-left {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex: 1;
}

.ch-right {
  display: flex;
  gap: 2px;
  flex-shrink: 0;
}

.ch-icon-btn {
  width: 30px;
  height: 30px;
  border-radius: 10px;
  border: none;
  background: transparent;
  color: #8e8ea0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
}

.ch-icon-btn:hover {
  background: rgba(0, 0, 0, 0.05);
  color: #444;
}

.ch-icon-btn-active {
  background: rgba(79, 107, 255, 0.1);
  color: #4f6bff;
}

.ch-close-btn:hover {
  background: rgba(255, 71, 87, 0.1);
  color: #ff4757;
}

.ch-avatar {
  width: 34px;
  height: 34px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #e8ecf4, #d5dce8);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
}

.ch-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.ch-avatar-fallback {
  font-size: 0.75rem;
  font-weight: 700;
  color: #5a6a8a;
}

.ch-user-info {
  min-width: 0;
}

.ch-user-name {
  font-size: 0.9rem;
  font-weight: 600;
  color: #1a1a2e;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 160px;
  line-height: 1.3;
}

.ch-typing {
  display: flex;
  align-items: center;
  gap: 3px;
  margin-top: 1px;
}

.ch-typing span:last-child {
  font-size: 0.7rem;
  color: #8e8ea0;
}

.ch-title {
  display: flex;
  align-items: center;
  gap: 8px;
  font-size: 0.95rem;
  font-weight: 600;
  color: #1a1a2e;
}

.ch-unread-badge {
  background: linear-gradient(135deg, #ff4757, #e8303f);
  color: #fff;
  font-size: 0.6rem;
  font-weight: 700;
  min-width: 18px;
  height: 18px;
  border-radius: 9px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
}

/* Typing dots */
.ch-dot {
  display: inline-block;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #8e8ea0;
  animation: chBounce 1.2s ease-in-out infinite;
}

.ch-dot:nth-child(2) {
  animation-delay: 0.15s;
}

.ch-dot:nth-child(3) {
  animation-delay: 0.3s;
}

@keyframes chBounce {
  0%, 60%, 100% {
    transform: translateY(0);
  }
  30% {
    transform: translateY(-3px);
  }
}
</style>
