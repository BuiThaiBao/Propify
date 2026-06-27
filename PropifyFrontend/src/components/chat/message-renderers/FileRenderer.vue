<template>
  <template v-if="isVideo">
    <video
      :src="message.file_url"
      class="max-w-[260px] max-h-[260px] rounded-2xl object-cover shadow-sm"
      controls
      playsinline
      preload="metadata"
    ></video>
  </template>
  <a
    v-else
    class="flex items-center gap-2 px-4 py-2.5 rounded-2xl no-underline text-[0.85rem] font-medium transition-opacity hover:opacity-85 shadow-sm"
    :class="isMine ? 'bg-[#deefff] text-gray-800' : 'bg-slate-100 text-gray-700 border border-gray-200'"
    :href="message.file_url"
    target="_blank"
    rel="noopener"
  >
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
      <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
      <polyline points="14 2 14 8 20 8" />
    </svg>
    <span>{{ fileName }}</span>
  </a>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  message: { type: Object, required: true },
  isMine: { type: Boolean, default: false },
});

const isVideo = computed(() => {
  const mime = props.message?.metadata?.mime_type || '';
  return mime.startsWith('video/');
});

const fileName = computed(() => {
  return props.message?.metadata?.file_name || 'Tệp đính kèm';
});
</script>
