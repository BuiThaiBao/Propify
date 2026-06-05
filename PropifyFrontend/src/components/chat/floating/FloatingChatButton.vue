<template>
  <button
    class="size-14 rounded-full bg-blue-600 border-none cursor-pointer text-white flex items-center justify-center relative shadow-lg shadow-blue-300/40 transition-all duration-200 shrink-0 hover:scale-[1.08] hover:shadow-xl hover:shadow-blue-300/50 active:scale-95"
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
      class="absolute -top-1 -right-1 min-w-5 h-5 bg-red-500 text-white text-[0.65rem] font-bold rounded-full flex items-center justify-center px-1 border-2 border-white"
    >
      {{ unreadCount > 9 ? '9+' : unreadCount }}
    </span>

    <span v-if="!isOpen && unreadCount > 0" class="absolute inset-[-4px] rounded-full border-2 border-blue-400/60 float-pulse pointer-events-none" />
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
.icon-swap-enter-active, .icon-swap-leave-active { transition: all 0.15s; }
.icon-swap-enter-from { opacity: 0; transform: rotate(-90deg) scale(0.7); }
.icon-swap-leave-to { opacity: 0; transform: rotate(90deg) scale(0.7); }

.float-pulse {
  animation: cwPulseRing 2s ease-out infinite;
}

@keyframes cwPulseRing {
  0% { transform: scale(1); opacity: 0.8; }
  70% { transform: scale(1.4); opacity: 0; }
  100% { transform: scale(1.4); opacity: 0; }
}
</style>
