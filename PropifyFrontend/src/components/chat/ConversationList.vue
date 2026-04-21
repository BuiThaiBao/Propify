<template>
  <div class="flex-1 overflow-y-auto conv-scroll bg-white">
    <!-- Loading skeleton -->
    <div v-if="loading" class="flex flex-col">
      <div v-for="i in 5" :key="i" class="flex items-center gap-3 px-4 py-3 border-b border-gray-100">
        <div class="size-11 rounded-full bg-gray-200 shrink-0 animate-[convPulse_1.4s_ease-in-out_infinite]" />
        <div class="flex-1 flex flex-col gap-2">
          <div class="h-3 rounded-full bg-gray-200 w-3/5 animate-[convPulse_1.4s_ease-in-out_infinite]" />
          <div class="h-2.5 rounded-full bg-gray-100 w-4/5 animate-[convPulse_1.4s_ease-in-out_infinite_0.2s]" />
        </div>
      </div>
    </div>

    <!-- Empty state -->
    <div v-else-if="conversations.length === 0" class="flex flex-col items-center justify-center gap-3 py-16 px-6 text-gray-400 text-center">
      <div class="size-16 rounded-full bg-blue-50 flex items-center justify-center">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#3b82f6" stroke-width="1.5">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
        </svg>
      </div>
      <p class="text-[0.85rem] text-gray-500 m-0">Chưa có cuộc trò chuyện nào</p>
    </div>

    <!-- Conversation list -->
    <button
      v-for="conv in conversations"
      :key="conv.id"
      class="flex items-center gap-3 px-4 py-3.5 w-full bg-white border-none border-b border-gray-100 cursor-pointer text-left transition-all duration-150 hover:bg-slate-50 relative"
      :class="conv.id === activeId
        ? 'bg-blue-50 !border-l-[3px] border-l-blue-500 !pl-[13px] hover:bg-blue-50'
        : ''"
      @click="$emit('select', conv)"
    >
      <!-- Avatar -->
      <div class="size-11 rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center text-sm font-bold text-white shrink-0 shadow-sm">
        <img v-if="conv.other_user?.avatar_url" :src="conv.other_user.avatar_url" :alt="conv.other_user?.full_name" class="w-full h-full object-cover" />
        <span v-else>{{ getInitials(conv.other_user?.full_name) }}</span>
      </div>

      <!-- Info -->
      <div class="flex-1 min-w-0">
        <div class="flex justify-between items-center gap-2 mb-0.5">
          <span class="text-[0.875rem] font-semibold text-gray-900 truncate">
            {{ conv.other_user?.full_name ?? 'Người dùng' }}
          </span>
          <span class="text-[0.7rem] text-gray-400 whitespace-nowrap shrink-0">
            {{ formatTime(conv.last_message?.created_at) }}
          </span>
        </div>
        <div class="flex justify-between items-center gap-2">
          <span
            class="text-[0.8rem] text-gray-500 truncate flex-1"
            :class="{ 'text-gray-800 font-medium': conv.unread_count > 0 }"
          >
            {{ getPreview(conv.last_message) }}
          </span>
          <span
            v-if="conv.unread_count > 0"
            class="bg-blue-500 text-white text-[0.65rem] font-bold min-w-[18px] h-[18px] rounded-full flex items-center justify-center px-1 shrink-0"
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
.conv-scroll::-webkit-scrollbar { width: 4px; }
.conv-scroll::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 99px; }

@keyframes convPulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}
</style>
