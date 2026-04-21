<template>
  <form
    class="flex items-end gap-2.5 px-3.5 py-3 bg-white border-t border-gray-100"
    @submit.prevent="handleSubmit"
  >
    <!-- Attach button -->
    <button
      type="button"
      class="p-2 rounded-xl text-gray-400 bg-transparent border-none cursor-pointer flex items-center justify-center transition-all shrink-0 mb-0.5 hover:bg-gray-100 hover:text-gray-600"
      title="Đính kèm file"
      @click="onAttach"
    >
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
      </svg>
    </button>

    <!-- Text input wrapper -->
    <div class="flex-1 flex items-end bg-gray-50 border border-gray-200 rounded-2xl px-4 py-2 transition-all focus-within:border-blue-400 focus-within:ring-2 focus-within:ring-blue-100 focus-within:bg-white">
      <textarea
        ref="inputRef"
        v-model="inputText"
        class="flex-1 bg-transparent border-none outline-none text-gray-800 text-[0.875rem] font-[inherit] leading-relaxed resize-none max-h-[120px] overflow-hidden block [scrollbar-width:none] placeholder-gray-400"
        style="scrollbar-width: none;"
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
      class="size-9 rounded-xl flex items-center justify-center transition-all shrink-0 mb-0.5 disabled:opacity-40 disabled:cursor-not-allowed border-none cursor-pointer"
      :class="inputText.trim()
        ? 'bg-blue-600 text-white shadow-md shadow-blue-200 hover:bg-blue-700 scale-100 hover:scale-105'
        : 'bg-gray-100 text-gray-400'"
      :disabled="disabled || !inputText.trim()"
    >
      <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2">
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

function onInput() {
  autoResize();
  if (typingTimer) return;
  emit('typing');
  typingTimer = setTimeout(() => { typingTimer = null; }, 2000);
}

function autoResize() {
  const el = inputRef.value;
  if (!el) return;
  el.style.height = '0';
  el.style.height = Math.min(el.scrollHeight, 120) + 'px';
}

function resetHeight() {
  const el = inputRef.value;
  if (el) el.style.height = 'auto';
}

function onAttach() {
  alert('Tính năng đính kèm sắp ra mắt!');
}
</script>

<style scoped>
textarea::-webkit-scrollbar { display: none; }
</style>
