<template>
  <div class="ep-root" @click.stop>
    <Transition name="ep-pop">
      <div v-if="open" class="ep-panel">
        <div class="ep-grid">
          <button
            v-for="emoji in emojiList"
            :key="emoji"
            type="button"
            class="ep-item"
            :title="emoji"
            @click.stop.prevent="select(emoji)"
          >
            {{ emoji }}
          </button>
        </div>
      </div>
    </Transition>

    <button
      type="button"
      class="ep-btn"
      :class="{ 'ep-btn-active': open }"
      title="Chọn emoji"
      @click="$emit('toggle')"
    >
      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
        <circle cx="12" cy="12" r="10" />
        <path d="M8 14s1.5 2 4 2 4-2 4-2" />
        <line x1="9" y1="9" x2="9.01" y2="9" />
        <line x1="15" y1="9" x2="15.01" y2="9" />
      </svg>
    </button>
  </div>
</template>

<script setup>
defineProps({
  open: { type: Boolean, default: false },
});

const emit = defineEmits(['toggle', 'select']);

const emojiList = [
  '😀', '😃', '😄', '😁', '😅', '😂', '🤣', '😊',
  '😇', '🙂', '😉', '😌', '😍', '🥰', '😘', '😗',
  '😋', '😛', '😜', '🤪', '😝', '🤑', '🤗', '🤭',
  '🤔', '🤐', '😐', '😑', '😶', '😏', '😒', '🙄',
  '😬', '😮', '😯', '😲', '😳', '🥺', '😢', '😭',
  '😤', '😡', '🤬', '😈', '👿', '💀', '☠️', '💩',
  '👍', '👎', '👊', '✊', '🤛', '🤜', '👏', '🙌',
  '🤲', '🤝', '🙏', '✌️', '🤟', '🤘', '👌', '❤️',
  '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '💔',
  '🔥', '⭐', '✨', '💯', '🎉', '🎊', '🎈', '💪',
];

function select(emoji) {
  emit('select', emoji);
}
</script>

<style scoped>
.ep-root {
  position: relative;
  display: flex;
}

.ep-btn {
  width: 32px;
  height: 32px;
  border-radius: 10px;
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

.ep-btn:hover {
  background: rgba(0, 0, 0, 0.04);
  color: #6b6b80;
}

.ep-btn-active {
  background: rgba(79, 107, 255, 0.1);
  color: #4f6bff;
}

.ep-panel {
  position: absolute;
  bottom: calc(100% + 8px);
  left: 0;
  width: 300px;
  max-height: 220px;
  overflow-y: auto;
  background: #fff;
  border: 1px solid rgba(0, 0, 0, 0.06);
  border-radius: 16px;
  padding: 10px;
  box-shadow:
    0 8px 30px rgba(0, 0, 0, 0.12),
    0 2px 8px rgba(0, 0, 0, 0.04);
  z-index: 99999;
}

.ep-grid {
  display: grid;
  grid-template-columns: repeat(8, 1fr);
  gap: 2px;
}

.ep-item {
  width: 100%;
  aspect-ratio: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.35rem;
  border: none;
  background: transparent;
  border-radius: 8px;
  cursor: pointer;
  transition: background 0.1s ease;
  padding: 0;
}

.ep-item:hover {
  background: rgba(0, 0, 0, 0.06);
  transform: scale(1.15);
}

/* Scroll */
.ep-panel::-webkit-scrollbar { width: 4px; }
.ep-panel::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.1); border-radius: 99px; }

/* Transition */
.ep-pop-enter-active { transition: all 0.18s cubic-bezier(0.34, 1.56, 0.64, 1); }
.ep-pop-leave-active { transition: all 0.12s ease-in; }
.ep-pop-enter-from { opacity: 0; transform: translateY(8px) scale(0.92); }
.ep-pop-leave-to { opacity: 0; transform: translateY(4px) scale(0.95); }
</style>
