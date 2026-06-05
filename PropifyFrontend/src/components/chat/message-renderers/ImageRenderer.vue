<template>
  <a :href="message.file_url" target="_blank" rel="noopener">
    <img
      class="max-w-[220px] max-h-[220px] rounded-2xl object-cover cursor-pointer transition-opacity hover:opacity-90 shadow-sm"
      :src="message.file_url"
      alt="Hình ảnh"
      loading="lazy"
    />
  </a>
</template>

<script setup>
defineProps({
  message: { type: Object, required: true },
  isMine: { type: Boolean, default: false },
});
</script>
