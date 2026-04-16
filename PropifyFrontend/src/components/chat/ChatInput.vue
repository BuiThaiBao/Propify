<template>
  <form class="chat-input-form" @submit.prevent="handleSubmit">
    <!-- Attach button (placeholder — integrate with Cloudinary) -->
    <button type="button" class="btn-attach" title="Đính kèm file" @click="onAttach">
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
      </svg>
    </button>

    <!-- Text input -->
    <div class="input-wrapper">
      <textarea
        ref="inputRef"
        v-model="inputText"
        class="message-input"
        placeholder="Nhập tin nhắn..."
        rows="1"
        :disabled="disabled"
        @keydown.enter.exact.prevent="handleSubmit"
        @keydown.enter.shift.exact="newLine"
        @input="onInput"
      />
    </div>

    <!-- Send button -->
    <button
      type="submit"
      class="btn-send"
      :class="{ 'btn-send-active': inputText.trim().length > 0 }"
      :disabled="disabled || !inputText.trim()"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <line x1="22" y1="2" x2="11" y2="13"/>
        <polygon points="22 2 15 22 11 13 2 9 22 2"/>
      </svg>
    </button>
  </form>
</template>

<script setup>
import { ref, nextTick } from 'vue';

const props = defineProps({
  disabled: { type: Boolean, default: false },
});

const emit = defineEmits(['send', 'typing']);

const inputText = ref('');
const inputRef = ref(null);

let typingTimer = null;

/**
 * Gửi message: emit 'send' rồi reset input.
 */
function handleSubmit() {
  const text = inputText.value.trim();
  if (!text || props.disabled) return;

  emit('send', text);
  inputText.value = '';
  resetHeight();
  nextTick(() => inputRef.value?.focus());
}

function newLine() {
  inputText.value += '\n';
  autoResize();
}

/**
 * Debounced typing indicator — emit mỗi lần user gõ, throtled 2s.
 */
function onInput() {
  autoResize();
  if (typingTimer) return; // đang trong window 2s → không emit lại
  emit('typing');
  typingTimer = setTimeout(() => {
    typingTimer = null;
  }, 2000);
}

/**
 * Auto-resize textarea theo nội dung (max 5 dòng).
 */
function autoResize() {
  const el = inputRef.value;
  if (!el) return;
  el.style.height = 'auto';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function resetHeight() {
  const el = inputRef.value;
  if (el) {
    el.style.height = 'auto';
  }
}

function onAttach() {
  // TODO: Tích hợp Cloudinary upload
  alert('Tính năng đính kèm sắp ra mắt!');
}
</script>

<style scoped>
.chat-input-form {
  display: flex;
  align-items: flex-end;
  gap: 10px;
  padding: 14px 18px;
  background: #161b27;
  border-top: 1px solid rgba(255, 255, 255, 0.06);
}

/* ===== ATTACH BUTTON ===== */
.btn-attach {
  background: none;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 8px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-bottom: 2px;
}

.btn-attach:hover {
  background: rgba(255, 255, 255, 0.06);
  color: #94a3b8;
}

/* ===== INPUT ===== */
.input-wrapper {
  flex: 1;
  background: #0f1117;
  border: 1px solid rgba(255, 255, 255, 0.08);
  border-radius: 20px;
  padding: 10px 16px;
  transition: border-color 0.2s;
}

.input-wrapper:focus-within {
  border-color: rgba(99, 102, 241, 0.5);
  box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08);
}

.message-input {
  width: 100%;
  background: none;
  border: none;
  outline: none;
  color: #e2e8f0;
  font-size: 0.9rem;
  font-family: 'Inter', sans-serif;
  line-height: 1.5;
  resize: none;
  max-height: 120px;
  overflow-y: auto;
}

.message-input::placeholder {
  color: #475569;
}

.message-input:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.message-input::-webkit-scrollbar {
  width: 2px;
}

.message-input::-webkit-scrollbar-thumb {
  background: rgba(255, 255, 255, 0.1);
}

/* ===== SEND BUTTON ===== */
.btn-send {
  background: rgba(99, 102, 241, 0.15);
  border: 1px solid rgba(99, 102, 241, 0.2);
  color: #6366f1;
  cursor: pointer;
  padding: 9px;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.2s;
  flex-shrink: 0;
  margin-bottom: 2px;
}

.btn-send:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.btn-send-active {
  background: linear-gradient(135deg, #4f46e5, #7c3aed);
  border-color: transparent;
  color: white;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.35);
  transform: scale(1.05);
}

.btn-send-active:hover:not(:disabled) {
  box-shadow: 0 6px 20px rgba(99, 102, 241, 0.5);
  transform: scale(1.08);
}
</style>
