<template>
  <Teleport to="body">
    <Transition name="crud-modal">
      <div
        v-if="open"
        class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/45 px-4"
        @click.self="closeable ? $emit('close') : null"
      >
        <div
          class="w-full bg-card rounded-2xl shadow-2xl"
          :class="maxWidthClass"
          role="dialog"
          :aria-modal="open"
        >
          <div v-if="$slots.title || title" class="flex items-center justify-between border-b border-border px-6 py-4">
            <h3 class="text-lg font-bold text-foreground">
              <slot name="title">{{ title }}</slot>
            </h3>
            <button
              v-if="closeable"
              type="button"
              class="rounded-lg p-1 text-muted-foreground hover:bg-muted hover:text-foreground transition"
              @click="$emit('close')"
            >
              <X class="h-5 w-5" />
            </button>
          </div>
          <div class="px-6 py-4">
            <slot />
          </div>
          <div v-if="$slots.footer" class="flex justify-end gap-3 border-t border-border px-6 py-4">
            <slot name="footer" />
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { computed } from 'vue'
import { X } from 'lucide-vue-next'

const props = defineProps({
  open: { type: Boolean, default: false },
  title: { type: String, default: '' },
  maxWidth: { type: String, default: 'md' },
  closeable: { type: Boolean, default: true },
})
defineEmits(['close'])

const maxWidthClass = computed(() => ({
  sm: 'max-w-sm',
  md: 'max-w-md',
  lg: 'max-w-lg',
  xl: 'max-w-xl',
  '2xl': 'max-w-2xl',
}[props.maxWidth] || 'max-w-md'))
</script>

<style scoped>
.crud-modal-enter-active { transition: opacity 0.2s ease; }
.crud-modal-leave-active { transition: opacity 0.15s ease; }
.crud-modal-enter-from,
.crud-modal-leave-to { opacity: 0; }
.crud-modal-enter-active > div,
.crud-modal-leave-active > div {
  transition: transform 0.2s ease;
}
.crud-modal-enter-from > div { transform: scale(0.95) translateY(8px); }
.crud-modal-leave-to > div { transform: scale(0.95) translateY(8px); }
</style>
