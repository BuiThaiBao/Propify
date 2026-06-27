<template>
  <div
    class="msg-row"
    :class="[
      isSystem ? 'msg-system' : '',
      isMine ? 'msg-row-mine' : 'msg-row-other',
    ]"
  >
    <component v-if="isSystem" :is="renderer" :message="message" :is-mine="isMine" />

    <template v-else>
      <!-- Avatar bên trái nếu là tin nhắn của người khác -->
      <div
        v-if="!isMine"
        class="msg-avatar"
        :class="compact ? 'msg-avatar-sm' : ''"
      >
        <img
          v-if="message.sender?.avatar_url"
          :src="message.sender.avatar_url"
          :alt="message.sender?.full_name"
          class="msg-avatar-img"
        />
        <span v-else class="msg-avatar-fallback">{{ getInitials(message.sender?.full_name) }}</span>
      </div>

      <div class="msg-content" :class="compact ? 'msg-content-sm' : ''">
        <component :is="renderer" :message="message" :is-mine="isMine" />

        <div class="msg-meta" :class="isMine ? 'msg-meta-mine' : 'msg-meta-other'">
          <span class="msg-time">{{ formatTime(message.created_at) }}</span>

          <!-- Trạng thái gửi -->
          <span v-if="isMine" class="msg-status">
            <svg v-if="message._status === 'queued'" class="msg-status-icon msg-status-queued" :width="10" :height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <path d="M12 6v6l4 2" />
              <circle cx="12" cy="12" r="10" />
            </svg>
            <svg v-else-if="message._status === 'sending'" class="msg-status-icon msg-status-sending" :width="10" :height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4" />
            </svg>
            <svg v-else-if="message._status === 'error' || message._status === 'permanently_failed'" class="msg-status-icon msg-status-error" :width="10" :height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <circle cx="12" cy="12" r="10" />
              <line x1="12" y1="8" x2="12" y2="12" />
              <line x1="12" y1="16" x2="12.01" y2="16" />
            </svg>
            <svg v-else class="msg-status-icon msg-status-sent" :width="10" :height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
              <polyline points="20 6 9 17 4 12" />
            </svg>
          </span>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useChatFormatters } from '@/composables/useChatFormatters';
import { getMessageRenderer } from '../message-renderers';

const props = defineProps({
  message: { type: Object, required: true },
  isMine: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
});

const { initials: getInitials, formatTime } = useChatFormatters();
const renderer = computed(() => getMessageRenderer(props.message.type));
const isSystem = computed(() => props.message.type === 'system');
</script>

<style scoped>
.msg-row {
  display: flex;
  align-items: flex-end;
  gap: 6px;
  max-width: 82%;
  animation: msgIn 0.2s ease-out;
}

.msg-row-mine {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.msg-row-other {
  align-self: flex-start;
}

.msg-system {
  align-self: stretch;
  max-width: 100%;
}

/* Avatar */
.msg-avatar {
  width: 28px;
  height: 28px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #e8ecf4, #d5dce8);
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  margin-bottom: 14px;
}

.msg-avatar-sm {
  width: 24px;
  height: 24px;
}

.msg-avatar-img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.msg-avatar-fallback {
  font-size: 0.6rem;
  font-weight: 700;
  color: #5a6a8a;
}

.msg-content {
  display: flex;
  flex-direction: column;
  max-width: 220px;
}

.msg-content-sm {
  max-width: 200px;
}

/* Meta: time + status */
.msg-meta {
  display: flex;
  align-items: center;
  gap: 3px;
  margin-top: 3px;
  padding: 0 4px;
}

.msg-meta-mine {
  justify-content: flex-end;
}

.msg-meta-other {
  justify-content: flex-start;
}

.msg-time {
  font-size: 0.65rem;
  color: #b0b0c0;
  line-height: 1;
}

.msg-status {
  display: inline-flex;
  align-items: center;
  line-height: 1;
}

.msg-status-icon {
  display: block;
}

.msg-status-queued { color: #f59e0b; }
.msg-status-sending { color: #b0b0c0; }
.msg-status-sent { color: #4f6bff; }
.msg-status-error { color: #ff4757; }

@keyframes msgIn {
  from {
    opacity: 0;
    transform: translateY(6px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
</style>
