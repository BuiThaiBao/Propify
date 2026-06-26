<template>
  <div class="flex flex-col items-center justify-center gap-4 px-6 py-12">
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-destructive/10">
      <AlertCircle class="h-6 w-6 text-destructive" />
    </div>
    <p class="text-sm text-destructive">{{ message }}</p>
    <BaseButton v-if="retryable" variant="secondary" size="sm" @click="$emit('retry')">
      Thử lại
    </BaseButton>
  </div>
</template>

<script setup>
import { AlertCircle } from 'lucide-vue-next'
import BaseButton from '@/components/ui/Button.vue'

defineProps({
  message: { type: String, default: 'Đã xảy ra lỗi. Vui lòng thử lại.' },
  retryable: { type: Boolean, default: true },
})
defineEmits(['retry'])
</script>
