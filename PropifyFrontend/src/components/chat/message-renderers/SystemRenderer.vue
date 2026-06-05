<template>
  <div class="flex justify-center my-2">
    <span class="text-[0.72rem] text-gray-400 bg-gray-100 px-3 py-1 rounded-full">
      {{ message.body }}
    </span>
  </div>
</template>

<script setup>
defineProps({
  message: { type: Object, required: true },
});
</script>
