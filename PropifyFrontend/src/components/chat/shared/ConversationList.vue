<template>
  <div class="flex-1 overflow-y-auto conv-scroll bg-white">
    <div v-if="loading" class="flex flex-col">
      <div
        v-for="i in 5"
        :key="i"
        class="flex items-center border-b border-gray-100"
        :class="compact ? 'gap-2.5 px-3.5 py-2.5' : 'gap-3 px-4 py-3'"
      >
        <div
          class="rounded-full bg-gray-200 shrink-0 animate-[convPulse_1.4s_ease-in-out_infinite]"
          :class="compact ? 'size-[42px]' : 'size-11'"
        />
        <div class="flex-1 flex flex-col" :class="compact ? 'gap-[7px]' : 'gap-2'">
          <div class="rounded-full bg-gray-200 animate-[convPulse_1.4s_ease-in-out_infinite]" :class="compact ? 'h-2.5 w-[55%]' : 'h-3 w-3/5'" />
          <div class="rounded-full bg-gray-100 animate-[convPulse_1.4s_ease-in-out_infinite_0.2s]" :class="compact ? 'h-2.5 w-[75%]' : 'h-2.5 w-4/5'" />
        </div>
      </div>
    </div>

    <div
      v-else-if="conversations.length === 0"
      class="flex flex-col items-center justify-center text-gray-400 text-center"
      :class="compact ? 'gap-2.5 h-full p-5' : 'gap-3 py-16 px-6'"
    >
      <div :class="compact ? '' : 'size-16 rounded-full bg-blue-50 flex items-center justify-center'">
        <svg :width="compact ? 36 : 28" :height="compact ? 36 : 28" viewBox="0 0 24 24" fill="none" :stroke="compact ? '#93c5fd' : '#3b82f6'" :stroke-width="compact ? '1.3' : '1.5'">
          <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
        </svg>
      </div>
      <p class="m-0 text-gray-500" :class="compact ? 'text-[0.82rem]' : 'text-[0.85rem]'">Chưa có cuộc trò chuyện nào</p>
    </div>

    <button
      v-for="conv in conversations"
      :key="conv.id"
      class="flex items-center w-full bg-white border-none border-b border-gray-100 cursor-pointer text-left transition-all duration-150 hover:bg-slate-50 relative"
      :class="[
        compact ? 'gap-2.5 px-3.5 py-3' : 'gap-3 px-4 py-3.5',
        conv.id === activeId
          ? compact
            ? 'bg-blue-50 !border-l-[3px] border-l-blue-500 !pl-[11px] hover:bg-blue-50'
            : 'bg-blue-50 !border-l-[3px] border-l-blue-500 !pl-[13px] hover:bg-blue-50'
          : '',
      ]"
      @click="$emit('select', conv)"
    >
      <GroupAvatar
        v-if="conv.type === 'group'"
        :avatar-url="conv.group?.avatar_url"
        :members="groupMembers(conv)"
        :size="compact ? 'md' : 'md'"
      />
      <div
        v-else
        class="rounded-full overflow-hidden bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center font-bold text-white shrink-0 shadow-sm"
        :class="compact ? 'size-[42px] text-[0.8rem]' : 'size-11 text-sm'"
      >
        <img v-if="conv.other_user?.avatar_url" :src="conv.other_user.avatar_url" :alt="conv.other_user?.full_name" class="w-full h-full object-cover" />
        <span v-else>{{ getInitials(conv.other_user?.full_name) }}</span>
      </div>

      <div class="flex-1 min-w-0">
        <div class="flex justify-between items-center" :class="compact ? 'gap-1 mb-[3px]' : 'gap-2 mb-0.5'">
          <span class="font-semibold text-gray-900 truncate" :class="compact ? 'text-[0.85rem]' : 'text-[0.875rem]'">
            {{ conv.type === 'group' ? (conv.group?.name ?? 'Nhóm chat') : (conv.other_user?.full_name ?? 'Người dùng') }}
          </span>
          <span class="text-gray-400 whitespace-nowrap shrink-0" :class="compact ? 'text-[0.68rem]' : 'text-[0.7rem]'">
            {{ formatTime(conv.last_message?.created_at) }}
          </span>
        </div>
        <div class="flex justify-between items-center" :class="compact ? 'gap-1.5' : 'gap-2'">
          <span
            class="truncate flex-1"
            :class="[compact ? 'text-[0.78rem]' : 'text-[0.8rem]', conv.unread_count > 0 ? 'text-gray-800 font-medium' : 'text-gray-500']"
          >
            {{ getPreview(conv.last_message) }}
          </span>
          <span v-if="conv.type === 'group'" class="text-[0.65rem] text-gray-400 shrink-0">
            {{ conv.group?.member_count ?? 0 }} người
          </span>
          <span
            v-if="conv.unread_count > 0"
            class="bg-blue-500 text-white font-bold rounded-full flex items-center justify-center shrink-0"
            :class="compact ? 'text-[0.62rem] min-w-[16px] h-4 px-[3px]' : 'text-[0.65rem] min-w-[18px] h-[18px] px-1'"
          >
            {{ conv.unread_count > 9 ? '9+' : conv.unread_count }}
          </span>
        </div>
      </div>
    </button>
  </div>
</template>

<script setup>
import { useChatFormatters } from '@/composables/useChatFormatters';
import GroupAvatar from '@/components/chat/group/GroupAvatar.vue';

const { initials: getInitials, relTime: formatTime, preview: getPreview } = useChatFormatters();

function groupMembers(conversation) {
  return conversation.group?.members ?? conversation.members ?? [];
}

defineProps({
  conversations: { type: Array, default: () => [] },
  activeId: { type: Number, default: null },
  loading: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
});

defineEmits(['select']);
</script>

<style scoped>
.conv-scroll::-webkit-scrollbar { width: 4px; }
.conv-scroll::-webkit-scrollbar-thumb { background: rgba(0, 0, 0, 0.1); border-radius: 99px; }

@keyframes convPulse {
  0%, 100% { opacity: 0.5; }
  50% { opacity: 1; }
}
</style>
