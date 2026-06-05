<template>
  <p
    class="m-0 px-4 py-2.5 rounded-2xl text-[0.875rem] leading-relaxed break-words whitespace-pre-wrap shadow-sm"
    :class="[
      isMine ? 'bg-[#deefff] text-gray-800 rounded-br-sm' : 'bg-slate-100 text-gray-800 rounded-bl-sm',
      message._status === 'error' ? 'opacity-60 border border-red-400/30' : '',
    ]"
  >
    {{ message.body }}
  </p>
</template>

<script setup>
defineProps({
  message: { type: Object, required: true },
  isMine: { type: Boolean, default: false },
});
</script>
