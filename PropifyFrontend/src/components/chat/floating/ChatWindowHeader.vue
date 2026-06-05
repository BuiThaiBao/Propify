<template>
  <div class="flex items-center justify-between px-3.5 py-3 bg-gradient-to-r from-blue-600 to-blue-700 border-b border-blue-700/20 shrink-0">
    <div class="flex items-center gap-2.5 min-w-0">
      <button
        v-if="conversation"
        class="size-7 rounded-full bg-transparent border-none text-white/70 cursor-pointer flex items-center justify-center transition-all duration-150 hover:bg-white/20 hover:text-white mr-0.5"
        title="Quay lại"
        @click="$emit('back')"
      >
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <polyline points="15 18 9 12 15 6" />
        </svg>
      </button>

      <div v-if="conversation" class="flex items-center gap-2.5 min-w-0">
        <GroupAvatar
          v-if="conversation.type === 'group'"
          :avatar-url="conversation.group?.avatar_url"
          :members="conversation.group?.members ?? []"
          size="sm"
        />
        <div v-else class="size-[34px] rounded-full overflow-hidden bg-white/20 flex items-center justify-center text-[0.75rem] font-bold text-white shrink-0">
          <img v-if="conversation.other_user?.avatar_url" :src="conversation.other_user.avatar_url" class="w-full h-full object-cover" />
          <span v-else>{{ initials(conversation.other_user?.full_name) }}</span>
        </div>
        <div>
          <div class="text-[0.88rem] font-semibold text-white whitespace-nowrap overflow-hidden text-ellipsis max-w-[160px]">
            {{ conversation.type === 'group' ? (conversation.group?.name ?? 'Nhóm chat') : conversation.other_user?.full_name }}
          </div>
          <div v-if="isTyping" class="flex items-center gap-[3px] mt-[1px]">
            <span class="cw-dot" /><span class="cw-dot" /><span class="cw-dot" />
            <span class="text-[0.7rem] text-white/60">đang nhập...</span>
          </div>
        </div>
      </div>

      <span v-else class="flex items-center gap-2 text-[0.95rem] font-bold text-white">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        </svg>
        Tin nhắn
        <span v-if="unreadCount > 0" class="bg-red-500 text-white text-[0.65rem] font-bold min-w-[18px] h-[18px] rounded-full flex items-center justify-center px-1">
          {{ unreadCount > 9 ? '9+' : unreadCount }}
        </span>
      </span>
    </div>

    <div class="flex gap-1">
      <button
        v-if="!conversation"
        class="size-7 rounded-full bg-transparent border-none cursor-pointer flex items-center justify-center transition-all duration-150"
        :class="showSearch ? 'bg-white/20 text-white' : 'text-white/70 hover:bg-white/20 hover:text-white'"
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
        class="size-7 rounded-full bg-transparent border-none cursor-pointer flex items-center justify-center transition-all duration-150 text-white/70 hover:bg-white/20 hover:text-white"
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
        class="size-7 rounded-full bg-transparent border-none cursor-pointer flex items-center justify-center transition-all duration-150 text-white/70 hover:bg-white/20 hover:text-white"
        title="Thông tin nhóm"
        @click="$emit('show-group-info')"
      >
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="12" cy="12" r="10" />
          <path d="M12 16v-4" />
          <path d="M12 8h.01" />
        </svg>
      </button>
      <button class="size-7 rounded-full bg-transparent border-none text-white/70 cursor-pointer flex items-center justify-center transition-all duration-150 hover:bg-white/20 hover:text-white" title="Thu nhỏ" @click="$emit('close')">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <line x1="5" y1="12" x2="19" y2="12" />
        </svg>
      </button>
      <button class="size-7 rounded-full bg-transparent border-none text-white/70 cursor-pointer flex items-center justify-center transition-all duration-150 hover:bg-white/20 hover:text-white" title="Đóng" @click="$emit('close')">
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
.cw-dot {
  display: inline-block;
  width: 4px;
  height: 4px;
  border-radius: 50%;
  background: #ffffff;
  animation: cwBounce 1.2s ease-in-out infinite;
}

.cw-dot:nth-child(2) { animation-delay: 0.15s; }
.cw-dot:nth-child(3) { animation-delay: 0.3s; }

@keyframes cwBounce {
  0%, 60%, 100% { transform: translateY(0); }
  30% { transform: translateY(-3px); }
}
</style>
