<template>
  <div class="bubble-wrapper" :class="isMine ? 'mine' : 'theirs'">
    <!-- Avatar (chỉ hiển thị cho message của người khác) -->
    <div v-if="!isMine" class="bubble-avatar">
      <img
        v-if="message.sender?.avatar_url"
        :src="message.sender.avatar_url"
        :alt="message.sender?.full_name"
      />
      <span v-else>{{ getInitials(message.sender?.full_name) }}</span>
    </div>

    <!-- Bubble -->
    <div class="bubble-content">
      <!-- Image message -->
      <template v-if="message.type === 'image'">
        <a :href="message.file_url" target="_blank" rel="noopener">
          <img class="bubble-image" :src="message.file_url" alt="Hình ảnh" loading="lazy" />
        </a>
      </template>

      <!-- File message -->
      <template v-else-if="message.type === 'file'">
        <a class="bubble-file" :href="message.file_url" target="_blank" rel="noopener">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
          <span>Tệp đính kèm</span>
        </a>
      </template>

      <!-- Text message -->
      <template v-else>
        <p class="bubble-text">{{ message.body }}</p>
      </template>

      <!-- Footer: time + status -->
      <div class="bubble-footer">
        <span class="bubble-time">{{ formatTime(message.created_at) }}</span>

        <!-- Status indicator (chỉ cho message của mình) -->
        <span v-if="isMine" class="bubble-status">
          <!-- Sending -->
          <svg v-if="message._status === 'sending'" class="status-icon spin" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/>
          </svg>
          <!-- Error -->
          <svg v-else-if="message._status === 'error'" class="status-icon error" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <!-- Sent / received -->
          <svg v-else class="status-icon sent" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <polyline points="20 6 9 17 4 12"/>
          </svg>
        </span>
      </div>
    </div>
  </div>
</template>

<script setup>
import dayjs from 'dayjs';
import 'dayjs/locale/vi';

dayjs.locale('vi');

defineProps({
  message: { type: Object, required: true },
  isMine: { type: Boolean, default: false },
});

function getInitials(name) {
  if (!name) return '?';
  return name.split(' ').map((w) => w[0]).join('').toUpperCase().slice(0, 2);
}

function formatTime(isoString) {
  if (!isoString) return '';
  return dayjs(isoString).format('HH:mm');
}
</script>

<style scoped>
.bubble-wrapper {
  display: flex;
  align-items: flex-end;
  gap: 8px;
  max-width: 75%;
  animation: fadeSlideUp 0.2s ease-out;
}

.mine {
  align-self: flex-end;
  flex-direction: row-reverse;
}

.theirs {
  align-self: flex-start;
}

@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* ===== AVATAR ===== */
.bubble-avatar {
  width: 30px;
  height: 30px;
  border-radius: 50%;
  overflow: hidden;
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.7rem;
  font-weight: 700;
  color: white;
  flex-shrink: 0;
  margin-bottom: 2px;
}

.bubble-avatar img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

/* ===== BUBBLE ===== */
.bubble-content {
  display: flex;
  flex-direction: column;
}

.bubble-text {
  margin: 0;
  padding: 10px 14px;
  border-radius: 18px;
  font-size: 0.9rem;
  line-height: 1.5;
  word-break: break-word;
  white-space: pre-wrap;
}

.mine .bubble-text {
  background: linear-gradient(135deg, #4f46e5, #6d28d9);
  color: white;
  border-bottom-right-radius: 4px;
}

.theirs .bubble-text {
  background: #1e293b;
  color: #e2e8f0;
  border-bottom-left-radius: 4px;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

/* ===== IMAGE ===== */
.bubble-image {
  max-width: 240px;
  max-height: 240px;
  border-radius: 14px;
  object-fit: cover;
  cursor: pointer;
  transition: opacity 0.2s;
}

.bubble-image:hover {
  opacity: 0.9;
}

/* ===== FILE ===== */
.bubble-file {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 10px 14px;
  border-radius: 14px;
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 500;
  transition: opacity 0.2s;
}

.mine .bubble-file {
  background: linear-gradient(135deg, #4f46e5, #6d28d9);
  color: white;
}

.theirs .bubble-file {
  background: #1e293b;
  color: #94a3b8;
  border: 1px solid rgba(255, 255, 255, 0.05);
}

.bubble-file:hover {
  opacity: 0.85;
}

/* ===== FOOTER ===== */
.bubble-footer {
  display: flex;
  align-items: center;
  gap: 4px;
  margin-top: 3px;
  padding: 0 4px;
}

.mine .bubble-footer {
  justify-content: flex-end;
}

.bubble-time {
  font-size: 0.68rem;
  color: #475569;
}

/* ===== STATUS ===== */
.status-icon {
  display: block;
}

.sent {
  color: #818cf8;
}

.error {
  color: #f87171;
}

.spin {
  color: #64748b;
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to   { transform: rotate(360deg); }
}

/* Error retry hint */
.mine .bubble-content:has(.error) .bubble-text {
  opacity: 0.7;
  border: 1px solid rgba(248, 113, 113, 0.3);
}
</style>
