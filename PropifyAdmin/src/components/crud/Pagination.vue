<template>
  <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-t border-border">
    <slot name="info">
      <p v-if="total !== null" class="text-xs text-muted-foreground">
        Hiển thị {{ showingFrom }}–{{ showingTo }} / {{ total }}
      </p>
    </slot>
    <div class="flex items-center gap-1">
      <button
        class="page-btn"
        :disabled="currentPage <= 1 || loading"
        @click="goTo(1)"
        title="Trang đầu"
      >
        <ChevronLeft :size="14" style="margin-right:-5px" /><ChevronLeft :size="14" />
      </button>
      <button
        class="page-btn"
        :disabled="currentPage <= 1 || loading"
        @click="goTo(currentPage - 1)"
        title="Trang trước"
      >
        <ChevronLeft :size="16" />
      </button>
      <template v-for="(p, i) in pageNumbers" :key="i">
        <span v-if="p === '...'" class="px-1 text-xs text-muted-foreground">…</span>
        <button
          v-else
          class="page-btn text-xs font-semibold"
          :class="{ active: p === currentPage }"
          @click="goTo(p)"
        >
          {{ p }}
        </button>
      </template>
      <button
        class="page-btn"
        :disabled="currentPage >= lastPage || loading"
        @click="goTo(currentPage + 1)"
        title="Trang sau"
      >
        <ChevronRight :size="16" />
      </button>
      <button
        class="page-btn"
        :disabled="currentPage >= lastPage || loading"
        @click="goTo(lastPage)"
        title="Trang cuối"
      >
        <ChevronRight :size="14" /><ChevronRight :size="14" style="margin-left:-5px" />
      </button>
      <span v-if="showSizeSelector" class="ml-2 flex items-center gap-1">
        <select
          :value="perPage"
          class="h-7 rounded border border-border px-1 text-xs outline-none bg-card"
          @change="onPerPageChange"
        >
          <option :value="10">10</option>
          <option :value="20">20</option>
          <option :value="50">50</option>
        </select>
      </span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { ChevronLeft, ChevronRight } from 'lucide-vue-next'

const props = defineProps({
  currentPage: { type: Number, required: true },
  lastPage: { type: Number, required: true },
  total: { type: Number, default: null },
  perPage: { type: Number, default: 10 },
  loading: { type: Boolean, default: false },
  showSizeSelector: { type: Boolean, default: false },
})
const emit = defineEmits(['page-change', 'per-page-change'])

const showingFrom = computed(() => props.total === 0 ? 0 : (props.currentPage - 1) * props.perPage + 1)
const showingTo = computed(() => Math.min(props.currentPage * props.perPage, props.total || 0))

const pageNumbers = computed(() => {
  const { currentPage, lastPage } = props
  const pages = []
  if (lastPage <= 7) {
    for (let i = 1; i <= lastPage; i++) pages.push(i)
  } else {
    pages.push(1)
    if (currentPage > 4) pages.push('...')
    const start = Math.max(2, currentPage - 2)
    const end = Math.min(lastPage - 1, currentPage + 2)
    for (let i = start; i <= end; i++) pages.push(i)
    if (currentPage < lastPage - 3) pages.push('...')
    pages.push(lastPage)
  }
  return pages
})

function goTo(page) {
  if (page < 1 || page > props.lastPage) return
  emit('page-change', page)
}

function onPerPageChange(e) {
  emit('per-page-change', Number(e.target.value))
}
</script>

<style scoped>
.page-btn {
  min-width: 32px;
  height: 32px;
  border: 1px solid hsl(var(--border));
  border-radius: 6px;
  background: hsl(var(--card));
  display: flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
  color: hsl(var(--foreground));
  transition: all 0.15s;
}
.page-btn:hover:not(:disabled) {
  background: hsl(var(--muted));
}
.page-btn.active {
  background: hsl(var(--primary));
  color: white;
  border-color: hsl(var(--primary));
}
.page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
</style>
