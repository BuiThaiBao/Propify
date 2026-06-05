<template>
  <div
    class="animate-[fadeSlideUp_0.2s_ease-out]"
    :class="isSystem
      ? 'self-stretch'
      : [compact ? 'flex items-end gap-1.5 max-w-[82%]' : 'flex items-end gap-2 max-w-[75%]', isMine ? 'self-end flex-row-reverse' : 'self-start']"
  >
    <component v-if="isSystem" :is="renderer" :message="message" :is-mine="isMine" />

    <div
      v-else-if="!isMine"
      class="rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center font-bold text-white shrink-0 shadow-sm"
      :class="compact ? 'size-[26px] text-[0.62rem] mb-0' : 'size-8 text-[0.7rem] mb-0.5'"
    >
      <img v-if="message.sender?.avatar_url" :src="message.sender.avatar_url" :alt="message.sender?.full_name" class="w-full h-full object-cover" />
      <span v-else>{{ getInitials(message.sender?.full_name) }}</span>
    </div>

    <div class="flex flex-col" :class="compact ? 'max-w-[220px]' : ''">
      <component :is="renderer" :message="message" :is-mine="isMine" />

      <div
        class="flex items-center mt-1"
        :class="[compact ? 'gap-[3px] px-[3px]' : 'gap-1 px-1', isMine ? 'justify-end' : 'justify-start']"
      >
        <span class="text-gray-400" :class="compact ? 'text-[0.65rem]' : 'text-[0.68rem]'">{{ formatTime(message.created_at) }}</span>
        <span v-if="isMine" class="block">
          <svg v-if="message._status === 'queued'" class="text-amber-500" :width="compact ? 10 : 11" :height="compact ? 10 : 11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <path d="M12 6v6l4 2" />
            <circle cx="12" cy="12" r="10" />
          </svg>
          <svg v-else-if="message._status === 'sending'" class="text-gray-400 animate-spin" :width="compact ? 10 : 11" :height="compact ? 10 : 11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4" />
          </svg>
          <svg v-else-if="message._status === 'error' || message._status === 'permanently_failed'" class="text-red-500" :width="compact ? 10 : 11" :height="compact ? 10 : 11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <circle cx="12" cy="12" r="10" />
            <line x1="12" y1="8" x2="12" y2="12" />
            <line x1="12" y1="16" x2="12.01" y2="16" />
          </svg>
          <svg v-else class="text-blue-500" :width="compact ? 10 : 11" :height="compact ? 10 : 11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
            <polyline points="20 6 9 17 4 12" />
          </svg>
        </span>
      </div>
    </div>
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
@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(6px); }
  to { opacity: 1; transform: translateY(0); }
}
</style>
