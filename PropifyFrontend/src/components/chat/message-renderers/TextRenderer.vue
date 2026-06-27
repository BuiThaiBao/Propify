<template>
  <div
    class="msg-bubble"
    :class="[
      isMine ? 'msg-mine' : 'msg-other',
      message._status === 'error' ? 'msg-error' : '',
    ]"
  >
    {{ message.body }}
  </div>
</template>

<script setup>
defineProps({
  message: { type: Object, required: true },
  isMine: { type: Boolean, default: false },
});
</script>

<style scoped>
.msg-bubble {
  margin: 0;
  padding: 10px 16px;
  font-size: 0.875rem;
  line-height: 1.55;
  word-break: break-word;
  white-space: pre-wrap;
  max-width: 100%;
  position: relative;
}

/* Mine: xanh dương nhạt, bo 3 góc + 1 góc nhọn phải dưới */
.msg-mine {
  background: linear-gradient(135deg, #4f6bff 0%, #3b5de7 100%);
  color: #fff;
  border-radius: 18px 18px 4px 18px;
  box-shadow: 0 2px 8px rgba(79, 107, 255, 0.25);
}

/* Other: xám nhạt, bo 3 góc + 1 góc nhọn trái dưới */
.msg-other {
  background: #f0f2f5;
  color: #1a1a2e;
  border-radius: 18px 18px 18px 4px;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
}

.msg-error {
  opacity: 0.65;
  box-shadow: 0 0 0 1px rgba(255, 71, 87, 0.3);
}
</style>
