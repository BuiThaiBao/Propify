<template>
  <form class="flex items-end gap-2.5 px-[18px] py-3.5 bg-[#161b27] border-t border-white/[0.06]" @submit.prevent="handleSubmit">
    <!-- Attach button -->
    <button
      type="button"
      class="bg-transparent border-none text-slate-500 cursor-pointer p-2 rounded-[10px] flex items-center justify-center transition-all duration-200 shrink-0 mb-0.5 hover:bg-white/[0.06] hover:text-slate-400"
      title="Đính kèm file"
      @click="onAttach"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66l-9.2 9.19a2 2 0 0 1-2.83-2.83l8.49-8.48"/>
      </svg>
    </button>

    <!-- Text input -->
    <div
      class="flex-1 bg-[#0f1117] border border-white/[0.08] rounded-[20px] px-4 py-2.5 transition-all duration-200 focus-within:border-indigo-500/50 focus-within:shadow-[0_0_0_3px_rgba(99,102,241,0.08)]"
    >
      <textarea
        ref="inputRef"
        v-model="inputText"
        class="w-full bg-transparent border-none outline-none text-slate-200 text-[0.9rem] font-[inherit] leading-relaxed resize-none max-h-[120px] overflow-hidden block [scrollbar-width:none]"
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
      class="bg-indigo-500/15 border border-indigo-500/20 text-indigo-500 cursor-pointer p-[9px] rounded-xl flex items-center justify-center transition-all duration-200 shrink-0 mb-0.5 disabled:opacity-35 disabled:cursor-not-allowed"
      :class="{ 'bg-gradient-to-br from-indigo-600 to-violet-700 !border-transparent !text-white shadow-[0_4px_15px_rgba(99,102,241,0.35)] scale-105 hover:shadow-[0_6px_20px_rgba(99,102,241,0.5)] hover:scale-[1.08]': inputText.trim().length > 0 }"
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
  typingTimer = setTimeout(() => {
    typingTimer = null;
  }, 2000);
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
/* Ẩn scrollbar webkit — không thể dùng Tailwind */
textarea::-webkit-scrollbar { display: none; }
</style>
