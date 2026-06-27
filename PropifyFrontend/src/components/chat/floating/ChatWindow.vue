<template>
  <Transition name="chat-pop">
    <div
      v-if="open"
      class="chat-window-root"
      :class="{ 'chat-window-wide': hasConversation }"
    >
      <slot />
    </div>
  </Transition>
</template>

<script setup>
defineProps({
  open: { type: Boolean, required: true },
  hasConversation: { type: Boolean, default: false },
});
</script>

<style scoped>
.chat-window-root {
  width: 360px;
  height: 500px;
  background: #fff;
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
  box-shadow:
    0 16px 48px rgba(0, 0, 0, 0.12),
    0 4px 16px rgba(0, 0, 0, 0.06),
    inset 0 0 0 1px rgba(255, 255, 255, 0.5);
  backdrop-filter: blur(20px);
  transition:
    width 0.25s cubic-bezier(0.34, 1.56, 0.64, 1),
    height 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.chat-window-wide {
  width: 380px;
  height: 520px;
}

/* Animation */
.chat-pop-enter-active {
  animation: cwPopIn 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.chat-pop-leave-active {
  animation: cwPopOut 0.2s ease-in;
}

@keyframes cwPopIn {
  from {
    opacity: 0;
    transform: scale(0.7) translateY(24px);
    transform-origin: bottom right;
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
    transform-origin: bottom right;
  }
}

@keyframes cwPopOut {
  from {
    opacity: 1;
    transform: scale(1) translateY(0);
    transform-origin: bottom right;
  }
  to {
    opacity: 0;
    transform: scale(0.7) translateY(16px);
    transform-origin: bottom right;
  }
}
</style>
