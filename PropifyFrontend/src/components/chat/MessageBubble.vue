<template>
  <div
    class="flex items-end gap-2 max-w-[75%] animate-[fadeSlideUp_0.2s_ease-out]"
    :class="isMine ? 'self-end flex-row-reverse' : 'self-start'"
  >
    <!-- Avatar (chỉ hiển thị cho message của người khác) -->
    <div
      v-if="!isMine"
      class="size-[30px] rounded-full overflow-hidden bg-gradient-to-br from-indigo-600 to-violet-700 flex items-center justify-center text-[0.7rem] font-bold text-white shrink-0 mb-0.5"
    >
      <img
        v-if="message.sender?.avatar_url"
        :src="message.sender.avatar_url"
        :alt="message.sender?.full_name"
        class="w-full h-full object-cover"
      />
      <span v-else>{{ getInitials(message.sender?.full_name) }}</span>
    </div>

    <!-- Bubble -->
    <div class="flex flex-col">
      <!-- Image message -->
      <template v-if="message.type === 'image'">
        <a :href="message.file_url" target="_blank" rel="noopener">
          <img
            class="max-w-[240px] max-h-[240px] rounded-[14px] object-cover cursor-pointer transition-opacity hover:opacity-90"
            :src="message.file_url"
            alt="Hình ảnh"
            loading="lazy"
          />
        </a>
      </template>

      <!-- File message -->
      <template v-else-if="message.type === 'file'">
        <a
          class="flex items-center gap-2 px-3.5 py-2.5 rounded-[14px] no-underline text-[0.85rem] font-medium transition-opacity hover:opacity-85"
          :class="isMine
            ? 'bg-gradient-to-br from-indigo-600 to-purple-800 text-white'
            : 'bg-[#1e293b] text-slate-400 border border-white/5'"
          :href="message.file_url"
          target="_blank"
          rel="noopener"
        >
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
            <polyline points="14 2 14 8 20 8"/>
          </svg>
          <span>Tệp đính kèm</span>
        </a>
      </template>

      <!-- Text message -->
      <template v-else>
        <p
          class="m-0 px-3.5 py-2.5 rounded-[18px] text-[0.9rem] leading-relaxed break-words whitespace-pre-wrap"
          :class="isMine
            ? 'bg-gradient-to-br from-indigo-600 to-purple-800 text-white rounded-br-[4px]'
            : 'bg-[#1e293b] text-slate-200 rounded-bl-[4px] border border-white/5'"
        >{{ message.body }}</p>
      </template>

      <!-- Footer: time + status -->
      <div
        class="flex items-center gap-1 mt-1 px-1"
        :class="isMine ? 'justify-end' : 'justify-start'"
      >
        <span class="text-[0.68rem] text-slate-600">{{ formatTime(message.created_at) }}</span>

        <span v-if="isMine" class="block">
          <!-- Sending -->
          <svg
            v-if="message._status === 'sending'"
            class="text-slate-500 animate-spin"
            width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
          >
            <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4"/>
          </svg>
          <!-- Error -->
          <svg
            v-else-if="message._status === 'error'"
            class="text-red-400"
            width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
          >
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          <!-- Sent -->
          <svg
            v-else
            class="text-indigo-400"
            width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"
          >
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
/* Chỉ giữ @keyframes — không thể dùng Tailwind cho keyframes */
@keyframes fadeSlideUp {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Complex :has() selector — không thể dùng Tailwind */
.flex-row-reverse .flex-col:has(.text-red-400) p {
  opacity: 0.7;
  border: 1px solid rgba(248, 113, 113, 0.3);
}
</style>
