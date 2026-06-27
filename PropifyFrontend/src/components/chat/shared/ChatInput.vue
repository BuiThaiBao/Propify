<template>
  <form
    class="ci-root"
    :class="compact ? 'ci-compact' : ''"
    @submit.prevent="handleSubmit"
  >
    <button
      type="button"
      class="ci-attach-btn"
      :class="{ 'ci-attach-uploading': uploading }"
      :title="uploading ? 'Đang tải...' : 'Đính kèm file'"
      :disabled="uploading"
      @click="onAttach"
    >
      <svg v-if="!uploading" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48" />
      </svg>
      <svg v-else class="animate-spin" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
        <path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4" />
      </svg>
    </button>
    <input
      ref="fileInputRef"
      type="file"
      accept="image/*,video/mp4,video/quicktime,video/x-msvideo,.pdf,.doc,.docx,.xls,.xlsx,.txt"
      class="hidden"
      @change="onFileChange"
    />

    <EmojiPicker
      :open="emojiOpen"
      @toggle="emojiOpen = !emojiOpen"
      @select="insertEmoji"
    />

    <div class="ci-input-wrap">
      <textarea
        ref="inputRef"
        v-model="inputText"
        class="ci-input"
        :class="compact ? 'ci-input-compact' : ''"
        :placeholder="compact ? 'Aa' : 'Nhập tin nhắn...'"
        rows="1"
        :disabled="disabled"
        @keydown.enter.exact.prevent="handleSubmit"
        @keydown.enter.shift.exact="newLine"
        @input="onInput"
      />
    </div>

    <button
      type="submit"
      class="ci-send-btn"
      :class="{ 'ci-send-active': !!inputText.trim() }"
      :disabled="disabled || !inputText.trim()"
    >
      <svg :width="compact ? 18 : 17" :height="compact ? 18 : 17" viewBox="0 0 24 24" fill="none" :stroke="inputText.trim() ? 'currentColor' : 'currentColor'" :stroke-width="compact ? 2 : 2.2">
        <line x1="22" y1="2" x2="11" y2="13" />
        <polygon points="22 2 15 22 11 13 2 9 22 2" />
      </svg>
    </button>
  </form>
</template>

<script setup>
import { nextTick, ref, onMounted, onUnmounted } from 'vue';
import EmojiPicker from './EmojiPicker.vue';
import chatUploadService from '@/services/chatUploadService';

const props = defineProps({
  disabled: { type: Boolean, default: false },
  compact: { type: Boolean, default: false },
});

const emit = defineEmits(['send', 'typing', 'file-uploading', 'file-error']);

const inputText = ref('');
const inputRef = ref(null);
const emojiOpen = ref(false);
const fileInputRef = ref(null);
const uploading = ref(false);
const pendingFile = ref(null);

let typingTimer = null;

function onFileChange(event) {
  const file = event.target.files?.[0];
  if (!file) return;
  event.target.value = '';

  // Validate size ngay khi chọn file
  if (file.size > MAX_FILE_SIZE) {
    const mb = MAX_FILE_SIZE / 1024 / 1024;
    emit('file-error', `File vượt quá ${mb}MB. Vui lòng chọn file nhỏ hơn.`);
    return;
  }

  const isImage = file.type.startsWith('image/');
  const prefix = isImage ? '🖼️ ' : '📎 ';
  inputText.value = `${prefix}${file.name}`;
  pendingFile.value = file;
  nextTick(() => inputRef.value?.focus());
}

const MAX_FILE_SIZE = 30 * 1024 * 1024; // 30MB

async function uploadAndSend(file) {
  const isImage = file.type.startsWith('image/');
  const type = isImage ? 'image' : 'file';

  uploading.value = true;
  emit('file-uploading', true);
  try {
    const { public_url, file_name, file_size, mime_type } = await chatUploadService.upload(file, type);
    const meta = { file_name, file_size, mime_type };
    emit('send', public_url, type, meta);
    inputText.value = '';
    resetHeight();
  } catch (err) {
    emit('file-error', err?.message || 'Upload file thất bại');
  } finally {
    uploading.value = false;
    emit('file-uploading', false);
    nextTick(() => inputRef.value?.focus());
  }
}

function onAttach() {
  fileInputRef.value?.click();
}

function handleSubmit() {
  if (pendingFile.value) {
    uploadAndSend(pendingFile.value);
    pendingFile.value = null;
    return;
  }

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

function onInput() {
  autoResize();
  if (typingTimer) return;
  emit('typing');
  typingTimer = setTimeout(() => {
    typingTimer = null;
  }, 2000);
}

function autoResize() {
  const el = inputRef.value;
  if (!el) return;
  const maxHeight = props.compact ? 80 : 120;
  el.style.height = '0';
  el.style.height = `${Math.min(el.scrollHeight, maxHeight)}px`;
}

function resetHeight() {
  const el = inputRef.value;
  if (el) el.style.height = 'auto';
}

function insertEmoji(emoji) {
  const el = inputRef.value;
  if (!el) {
    inputText.value += emoji;
    return;
  }
  const start = el.selectionStart;
  const end = el.selectionEnd;
  const text = inputText.value;
  inputText.value = text.slice(0, start) + emoji + text.slice(end);
  nextTick(() => {
    el.focus();
    el.selectionStart = el.selectionEnd = start + emoji.length;
    autoResize();
  });
}

function onDocClick(e) {
  if (emojiOpen.value) {
    const el = document.querySelector('.ci-root');
    if (el && !el.contains(e.target)) {
      emojiOpen.value = false;
    }
  }
}

onMounted(() => document.addEventListener('click', onDocClick));
onUnmounted(() => document.removeEventListener('click', onDocClick));
</script>

<style scoped>
.ci-root {
  display: flex;
  align-items: flex-end;
  padding: 10px 12px 10px 14px;
  background: #fff;
  border-top: 1px solid rgba(0, 0, 0, 0.04);
  gap: 8px;
}

.ci-compact {
  padding: 8px 12px 8px 12px;
  gap: 6px;
}

.ci-attach-btn {
  padding: 6px;
  border-radius: 12px;
  border: none;
  background: transparent;
  color: #b0b0c0;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.15s ease;
  flex-shrink: 0;
  margin-bottom: 2px;
}

.ci-attach-btn:hover {
  background: rgba(0, 0, 0, 0.04);
  color: #6b6b80;
}

.ci-input-wrap {
  flex: 1;
  display: flex;
  align-items: flex-end;
  background: #f3f4f8;
  border: 1px solid transparent;
  border-radius: 22px;
  transition: all 0.2s ease;
}

.ci-input-wrap:focus-within {
  background: #fff;
  border-color: #4f6bff;
  box-shadow: 0 0 0 3px rgba(79, 107, 255, 0.12);
}

.ci-input {
  flex: 1;
  background: transparent;
  border: none;
  outline: none;
  color: #1a1a2e;
  font-family: inherit;
  line-height: 1.5;
  resize: none;
  overflow: hidden;
  display: block;
  padding: 9px 14px;
  font-size: 0.85rem;
  max-height: 80px;
}

.ci-input::placeholder {
  color: #b0b0c0;
}

.ci-input-compact {
  padding: 7px 14px;
  font-size: 0.85rem;
  max-height: 80px;
}

.ci-input::-webkit-scrollbar {
  display: none;
}

.ci-send-btn {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 50%;
  border: none;
  background: #e8e8f0;
  color: #b0b0c0;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.34, 1.56, 0.64, 1);
  flex-shrink: 0;
  margin-bottom: 1px;
}

.ci-send-btn:disabled {
  cursor: not-allowed;
  opacity: 0.5;
}

.ci-send-active {
  background: linear-gradient(135deg, #4f6bff, #3b5de7);
  color: #fff;
  box-shadow: 0 3px 10px rgba(79, 107, 255, 0.3);
}

.ci-send-active:hover {
  transform: scale(1.08);
  box-shadow: 0 5px 16px rgba(79, 107, 255, 0.4);
}

.ci-send-active:active {
  transform: scale(0.92);
}
</style>
