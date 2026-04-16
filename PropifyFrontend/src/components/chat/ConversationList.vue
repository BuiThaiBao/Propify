<template>
  <div class="flex-1 overflow-y-auto conv-list-scroll">
    <!-- Loading state -->
    <div v-if="loading" class="flex flex-col">
      <div
        v-for="i in 5"
        :key="i"
        class="flex items-center gap-3 px-5 py-3.5 border-b border-white/[0.04]"
      >
        <div class="size-[46px] rounded-full bg-white/[0.06] shrink-0 animate-[convPulse_1.5s_ease-in-out_infinite]" />
        <div class="flex-1 flex flex-col gap-2">
          <div class="h-3 rounded-md bg-white/[0.06] w-3/5 animate-[convPulse_1.5s_ease-in-out_infinite]" />
          <div class="h-2.5 rounded-md bg-white/[0.04] w-4/5 animate-[convPulse_1.5s_ease-in-out_infinite_0.2s]" />
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div
      v-else-if="conversations.length === 0"
      class="flex flex-col items-center justify-center gap-3 py-[50px] px-5 text-slate-600 text-center"
    >
      <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
        <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
      </svg>
      <p class="text-[0.85rem] m-0">Chưa có cuộc trò chuyện nào</p>
    </div>

    <!-- List -->
    <button
      v-for="conv in conversations"
      :key="conv.id"
      class="flex items-center gap-3 px-5 py-3.5 w-full bg-transparent border-none border-b border-white/[0.04] cursor-pointer text-left transition-colors duration-150 relative hover:bg-white/[0.04]"
      :class="conv.id === activeId
        ? 'bg-indigo-500/10 !border-l-[3px] border-l-indigo-500 !pl-[17px]'
        : ''"
      @click="$emit('select', conv)"
    >
      <!-- Avatar -->
      <div
        class="size-[46px] rounded-full overflow-hidden bg-gradient-to-br from-indigo-600 to-violet-700 flex items-center justify-center text-[0.9rem] font-bold text-white shrink-0"
      >
        <img
          v-if="conv.other_user?.avatar_url"
          :src="conv.other_user.avatar_url"
          :alt="conv.other_user?.full_name"
          class="w-full h-full object-cover"
        />
        <span v-else>{{ getInitials(conv.other_user?.full_name) }}</span>
      </div>

      <!-- Info -->
      <div class="flex-1 min-w-0">
        <div class="flex justify-between items-center gap-2 mb-1">
          <span class="text-[0.9rem] font-semibold text-slate-200 whitespace-nowrap overflow-hidden text-ellipsis">
            {{ conv.other_user?.full_name ?? 'Người dùng' }}
          </span>
          <span class="text-[0.72rem] text-slate-600 whitespace-nowrap shrink-0">
            {{ formatTime(conv.last_message?.created_at) }}
          </span>
        </div>
        <div class="flex justify-between items-center gap-2">
          <span
            class="text-[0.8rem] text-slate-500 whitespace-nowrap overflow-hidden text-ellipsis flex-1"
            :class="{ 'text-slate-400 font-medium': conv.unread_count > 0 }"
          >
            {{ getPreview(conv.last_message) }}
          </span>
          <span
            v-if="conv.unread_count > 0"
            class="bg-gradient-to-br from-indigo-500 to-violet-500 text-white text-[0.65rem] font-bold min-w-[18px] h-[18px] rounded-full flex items-center justify-center px-1 shrink-0"
          >
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
/* Scrollbar — không thể dùng Tailwind cho ::-webkit-scrollbar */
.conv-list-scroll::-webkit-scrollbar { width: 3px; }
.conv-list-scroll::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.08);
  border-radius: 99px;
}

/* @keyframes — bắt buộc dùng CSS */
@keyframes convPulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}
</style>
