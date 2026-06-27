<template>
  <button
    class="chat-fab"
    :title="isOpen ? 'Đóng chat' : 'Tin nhắn'"
    @click="$emit('toggle')"
  >
    <Transition name="icon-swap" mode="out-in">
      <svg v-if="isOpen" key="close" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
        <line x1="18" y1="6" x2="6" y2="18" />
        <line x1="6" y1="6" x2="18" y2="18" />
      </svg>
      <svg v-else key="chat" width="22" height="22" viewBox="0 0 24 24" fill="currentColor">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" opacity="0.9" />
      </svg>
    </Transition>

    <span
      v-if="!isOpen && unreadCount > 0"
      class="chat-badge"
    >
      {{ unreadCount > 9 ? '9+' : unreadCount }}
    </span>

    <span v-if="!isOpen && unreadCount > 0" class="chat-ring" />
  </button>
</template>

<script setup>
defineProps({
  isOpen: { type: Boolean, default: false },
  unreadCount: { type: Number, default: 0 },
});

defineEmits(['toggle']);
</script>

<style scoped>
.chat-fab {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  border: none;
  cursor: pointer;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  flex-shrink: 0;
  background: linear-gradient(135deg, #4f6bff 0%, #3b5de7 50%, #2d4fd8 100%);
  box-shadow:
    0 4px 16px rgba(79, 107, 255, 0.35),
    0 2px 8px rgba(79, 107, 255, 0.2),
    inset 0 1px 0 rgba(255, 255, 255, 0.2);
  transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.chat-fab:hover {
  transform: scale(1.08);
  box-shadow:
    0 8px 28px rgba(79, 107, 255, 0.45),
    0 4px 12px rgba(79, 107, 255, 0.25),
    inset 0 1px 0 rgba(255, 255, 255, 0.25);
}

.chat-fab:active {
  transform: scale(0.95);
  box-shadow:
    0 2px 8px rgba(79, 107, 255, 0.3),
    inset 0 1px 2px rgba(0, 0, 0, 0.1);
}

.chat-badge {
  position: absolute;
  top: -3px;
  right: -3px;
  min-width: 20px;
  height: 20px;
  background: linear-gradient(135deg, #ff4757, #e8303f);
  color: #fff;
  font-size: 0.65rem;
  font-weight: 700;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 0 4px;
  border: 2px solid #fff;
  box-shadow: 0 2px 6px rgba(255, 71, 87, 0.4);
}

.chat-ring {
  position: absolute;
  inset: -4px;
  border-radius: 50%;
  border: 2px solid rgba(79, 107, 255, 0.4);
  animation: cwPulseRing 2s ease-out infinite;
  pointer-events: none;
}

@keyframes cwPulseRing {
  0% { transform: scale(1); opacity: 0.8; }
  70% { transform: scale(1.35); opacity: 0; }
  100% { transform: scale(1.35); opacity: 0; }
}

/* Icon swap transition */
.icon-swap-enter-active,
.icon-swap-leave-active {
  transition: all 0.18s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.icon-swap-enter-from {
  opacity: 0;
  transform: rotate(-90deg) scale(0.6);
}
.icon-swap-leave-to {
  opacity: 0;
  transform: rotate(90deg) scale(0.6);
}
</style>
