<template>
  <div class="overflow-x-auto rounded-xl border border-border">
    <table class="crud-table w-full border-collapse" :style="{ tableLayout: 'fixed' }">
      <thead>
        <tr class="bg-muted/30 border-b border-border">
          <th
            v-for="col in columns"
            :key="col.key"
            :class="[
              'px-3 py-3 text-xs font-bold text-muted-foreground uppercase tracking-wider',
              col.sortable ? 'cursor-pointer select-none hover:bg-muted/50 transition' : '',
              col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : 'text-left',
            ]"
            :style="col.width ? { width: col.width, minWidth: col.width } : {}"
            @click="col.sortable ? toggleSort(col.key) : null"
          >
            <span class="inline-flex items-center gap-1">
              {{ col.label }}
              <span v-if="col.sortable" class="inline-flex flex-col leading-none text-[10px] opacity-50">
                <span :class="sortBy === col.key && sortDir === 'asc' ? 'text-primary opacity-100' : ''">▲</span>
                <span :class="sortBy === col.key && sortDir === 'desc' ? 'text-primary opacity-100' : ''">▼</span>
              </span>
            </span>
          </th>
          <th v-if="$slots.actions" class="px-3 py-3 text-center whitespace-nowrap w-12"></th>
        </tr>
      </thead>
      <tbody>
        <!-- Loading via slot -->
        <tr v-if="loading && !rows?.length">
          <td :colspan="colspan" class="px-3 py-12">
            <LoadingState :message="loadingText" />
          </td>
        </tr>
        <!-- Empty -->
        <tr v-else-if="!rows || rows.length === 0">
          <td :colspan="colspan" class="px-3 py-12">
            <EmptyState :message="emptyText">
              <template v-if="$slots['empty-action']" #action>
                <slot name="empty-action" />
              </template>
            </EmptyState>
          </td>
        </tr>
        <!-- Rows -->
        <tr
          v-for="(row, rowIdx) in rows"
          :key="row.id ?? rowIdx"
          class="border-b border-border/50 hover:bg-muted/15 transition-colors"
          :class="{ 'cursor-pointer': $attrs.onRowClick }"
          @click="$attrs.onRowClick ? $emit('row-click', row) : null"
        >
          <td
            v-for="col in columns"
            :key="col.key"
            :class="[
              'px-3 py-3 text-sm text-foreground',
              col.align === 'right' ? 'text-right' : col.align === 'center' ? 'text-center' : '',
              col.nowrap !== false ? 'whitespace-nowrap' : '',
            ]"
          >
            <slot :name="`cell(${col.key})`" :row="row" :value="resolveValue(row, col.key)">
              {{ col.formatter ? col.formatter(resolveValue(row, col.key), row) : resolveValue(row, col.key) }}
            </slot>
          </td>
          <td v-if="$slots.actions" class="px-3 py-3 text-center">
            <slot name="actions" :row="row" />
          </td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import LoadingState from './LoadingState.vue'
import EmptyState from './EmptyState.vue'

const props = defineProps({
  columns: { type: Array, required: true },
  rows: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
  loadingText: { type: String, default: 'Đang tải dữ liệu...' },
  emptyText: { type: String, default: 'Không có dữ liệu.' },
  sortBy: { type: String, default: '' },
  sortDir: { type: String, default: '' },
})
const emit = defineEmits(['sort', 'row-click'])

const colspan = computed(() => props.columns.length + 1)

function resolveValue(row, key) {
  if (key.includes('.')) {
    return key.split('.').reduce((o, k) => (o ? o[k] : ''), row)
  }
  return row[key] ?? ''
}

function toggleSort(key) {
  if (props.sortBy === key) {
    emit('sort', { key, dir: props.sortDir === 'asc' ? 'desc' : 'asc' })
  } else {
    emit('sort', { key, dir: 'asc' })
  }
}
</script>
