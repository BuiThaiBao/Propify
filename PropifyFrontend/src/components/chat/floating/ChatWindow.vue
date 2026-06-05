<template>
  <Transition name="chat-pop">
    <div
      v-if="open"
      class="w-[360px] h-[500px] bg-white border border-gray-200 rounded-2xl flex flex-col overflow-hidden shadow-xl shadow-gray-200/60 transition-[width,height] duration-200"
      :class="{ 'w-[380px] h-[520px]': hasConversation }"
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
.chat-pop-enter-active { animation: cwPopIn 0.28s cubic-bezier(0.34, 1.56, 0.64, 1); }
.chat-pop-leave-active { animation: cwPopOut 0.2s ease-in; }

@keyframes cwPopIn {
  from { opacity: 0; transform: scale(0.7) translateY(20px); transform-origin: bottom right; }
  to { opacity: 1; transform: scale(1) translateY(0); transform-origin: bottom right; }
}

@keyframes cwPopOut {
  from { opacity: 1; transform: scale(1) translateY(0); transform-origin: bottom right; }
  to { opacity: 0; transform: scale(0.7) translateY(16px); transform-origin: bottom right; }
}
</style>
