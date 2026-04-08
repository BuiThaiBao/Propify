<script setup>
defineProps({
  open: Boolean,
  title: String,
  description: String,
  confirmText: { type: String, default: 'Xác nhận' },
  variant: { type: String, default: 'default' }, // 'default' | 'destructive'
})

const emit = defineEmits(['close', 'confirm'])
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div v-if="open" class="modal-overlay" @click.self="emit('close')">
        <div class="modal-dialog">
          <div class="modal-header">
            <h3 class="modal-title">{{ title }}</h3>
            <p class="modal-desc">{{ description }}</p>
          </div>
          <div class="modal-footer">
            <button class="btn-cancel" @click="emit('close')">Hủy</button>
            <button
              class="btn-confirm"
              :class="variant === 'destructive' ? 'btn-destructive' : 'btn-primary gradient-primary'"
              @click="emit('confirm')"
            >
              {{ confirmText }}
            </button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 100;
  background-color: hsl(0 0% 0% / 0.5);
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 16px;
}

.modal-dialog {
  background-color: hsl(var(--card));
  border-radius: 16px;
  padding: 24px;
  width: 100%;
  max-width: 440px;
  box-shadow: 0 20px 60px hsl(220 20% 10% / 0.2);
}

.modal-header {
  margin-bottom: 20px;
}

.modal-title {
  font-size: 18px;
  font-weight: 600;
  color: hsl(var(--foreground));
  margin: 0 0 8px 0;
}

.modal-desc {
  font-size: 14px;
  color: hsl(var(--muted-foreground));
  margin: 0;
}

.modal-footer {
  display: flex;
  justify-content: flex-end;
  gap: 8px;
}

.btn-cancel {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  border: 1px solid hsl(var(--border));
  background: hsl(var(--card));
  color: hsl(var(--foreground));
  cursor: pointer;
  transition: background-color 0.15s;
}

.btn-cancel:hover {
  background-color: hsl(var(--muted));
}

.btn-confirm {
  padding: 8px 16px;
  border-radius: 8px;
  font-size: 14px;
  font-weight: 500;
  border: none;
  cursor: pointer;
  color: white;
  transition: opacity 0.15s;
}

.btn-confirm:hover {
  opacity: 0.9;
}

.btn-primary { background: var(--gradient-primary); }

.btn-destructive {
  background-color: hsl(var(--destructive));
}

/* Transition */
.modal-enter-active, .modal-leave-active {
  transition: opacity 0.2s ease;
}
.modal-enter-from, .modal-leave-to {
  opacity: 0;
}
.modal-enter-active .modal-dialog,
.modal-leave-active .modal-dialog {
  transition: transform 0.2s ease;
}
.modal-enter-from .modal-dialog {
  transform: scale(0.95) translateY(8px);
}
.modal-leave-to .modal-dialog {
  transform: scale(0.95) translateY(8px);
}
</style>
