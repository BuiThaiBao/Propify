<script setup>
import { onMounted, reactive, ref, watch } from 'vue'
import { History, Search, X } from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import auditLogService from '@/services/auditLogService'

const logs = ref([])
const loading = ref(false)
const error = ref('')
const selectedLog = ref(null)
const pagination = reactive({
  currentPage: 1,
  lastPage: 1,
  perPage: 20,
  total: 0,
})
const filters = reactive({
  action: '',
  actorId: '',
  auditableType: '',
  auditableId: '',
  fromDate: '',
  toDate: '',
})
const quickActionFilters = [
  { label: 'Tất cả', value: '' },
  { label: 'Cập nhật hồ sơ', value: 'user.profile.updated' },
]

let searchTimer = null

onMounted(() => {
  loadLogs()
})

watch(
  () => ({ ...filters }),
  () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => loadLogs(1), 300)
  },
  { deep: true },
)

async function loadLogs(page = 1) {
  loading.value = true
  error.value = ''

  try {
    const response = await auditLogService.getAuditLogs({
      page,
      per_page: pagination.perPage,
      action: filters.action || undefined,
      actor_id: filters.actorId || undefined,
      auditable_type: filters.auditableType || undefined,
      auditable_id: filters.auditableId || undefined,
      from_date: filters.fromDate || undefined,
      to_date: filters.toDate || undefined,
    })

    logs.value = response?.data?.data || []
    const meta = response?.data?.meta || {}
    pagination.currentPage = Number(meta.current_page || 1)
    pagination.lastPage = Number(meta.last_page || 1)
    pagination.perPage = Number(meta.per_page || 20)
    pagination.total = Number(meta.total || 0)
  } catch (err) {
    logs.value = []
    error.value = err?.response?.data?.message || 'Không thể tải audit logs.'
  } finally {
    loading.value = false
  }
}

function formatDate(value) {
  if (!value) return '--'
  const date = new Date(value)
  return Number.isNaN(date.getTime()) ? '--' : date.toLocaleString('vi-VN')
}

function formatTarget(log) {
  const shortType = log.auditable_type?.split('\\').pop() || log.auditable_type || '--'
  return `${shortType} #${log.auditable_id ?? '--'}`
}

function formatChanges(changes) {
  const entries = Object.entries(changes || {})
  if (!entries.length) return 'Không có thay đổi'

  return entries
    .map(([field, change]) => `${field}: ${change?.old ?? '∅'} → ${change?.new ?? '∅'}`)
    .join(', ')
}

function openDetail(log) {
  selectedLog.value = log
}

function closeDetail() {
  selectedLog.value = null
}

function formatPrettyJson(value) {
  return JSON.stringify(value || {}, null, 2)
}

function applyQuickActionFilter(action) {
  filters.action = action
}

function resetFilters() {
  filters.action = ''
  filters.actorId = ''
  filters.auditableType = ''
  filters.auditableId = ''
  filters.fromDate = ''
  filters.toDate = ''
}
</script>

<template>
  <div>
    <PageHeader title="Audit logs" description="Theo dõi lịch sử thay đổi trong hệ thống">
      <template #actions>
        <div class="summary-pill">
          <History :size="16" />
          {{ pagination.total }} bản ghi
        </div>
      </template>
    </PageHeader>

    <section class="quick-filter-bar">
      <div class="quick-filter-group">
        <button
          v-for="filter in quickActionFilters"
          :key="filter.value || 'all'"
          type="button"
          class="quick-filter-button"
          :class="{ active: filters.action === filter.value }"
          @click="applyQuickActionFilter(filter.value)"
        >
          {{ filter.label }}
        </button>
      </div>

      <button type="button" class="reset-button" @click="resetFilters">Reset bộ lọc</button>
    </section>

    <section class="filter-panel">
      <div class="search-wrap">
        <Search :size="18" class="search-icon" />
        <input
          v-model.trim="filters.action"
          class="search-input"
          placeholder="Lọc theo action..."
        />
      </div>
      <input v-model.trim="filters.actorId" class="filter-input" placeholder="Actor ID" />
      <input
        v-model.trim="filters.auditableType"
        class="filter-input"
        placeholder="Auditable type"
      />
      <input v-model.trim="filters.auditableId" class="filter-input" placeholder="Auditable ID" />
      <input v-model="filters.fromDate" type="date" class="filter-input" aria-label="Từ ngày" />
      <input v-model="filters.toDate" type="date" class="filter-input" aria-label="Đến ngày" />
    </section>

    <div v-if="loading && logs.length === 0" class="state-text">Đang tải dữ liệu...</div>
    <div v-else-if="error" class="state-text state-error">{{ error }}</div>
    <div v-else-if="logs.length === 0" class="state-text">Chưa có audit log phù hợp.</div>

    <div v-else class="table-wrap">
      <div class="table-scroll">
        <table class="data-table">
          <thead>
            <tr class="table-head-row">
              <th class="th">Thời gian</th>
              <th class="th">Người thao tác</th>
              <th class="th">Hành động</th>
              <th class="th">Đối tượng</th>
              <th class="th">Thay đổi</th>
            </tr>
          </thead>
          <tbody>
            <tr
              v-for="log in logs"
              :key="log.id"
              class="table-row clickable-row"
              @click="openDetail(log)"
            >
              <td class="td td-muted">{{ formatDate(log.created_at) }}</td>
              <td class="td">
                <div class="actor-name">{{ log.actor?.full_name || 'Hệ thống' }}</div>
                <div class="actor-email">{{ log.actor?.email || '--' }}</div>
              </td>
              <td class="td">
                <span class="action-badge">{{ log.action }}</span>
              </td>
              <td class="td">{{ formatTarget(log) }}</td>
              <td class="td changes-cell">{{ formatChanges(log.changes) }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <div v-if="pagination.lastPage > 1" class="pagination">
      <button :disabled="pagination.currentPage <= 1" @click="loadLogs(pagination.currentPage - 1)">
        Trước
      </button>
      <span>Trang {{ pagination.currentPage }} / {{ pagination.lastPage }}</span>
      <button
        :disabled="pagination.currentPage >= pagination.lastPage"
        @click="loadLogs(pagination.currentPage + 1)"
      >
        Sau
      </button>
    </div>

    <div v-if="selectedLog" class="detail-backdrop" @click.self="closeDetail">
      <aside class="detail-panel" aria-modal="true" role="dialog">
        <div class="detail-header">
          <div>
            <div class="detail-kicker">Audit log #{{ selectedLog.id }}</div>
            <h2>{{ selectedLog.action }}</h2>
          </div>
          <button class="icon-button" aria-label="Đóng" @click="closeDetail">
            <X :size="18" />
          </button>
        </div>

        <div class="detail-grid">
          <div>
            <span>Thời gian</span>
            <strong>{{ formatDate(selectedLog.created_at) }}</strong>
          </div>
          <div>
            <span>Người thao tác</span>
            <strong>{{ selectedLog.actor?.full_name || 'Hệ thống' }}</strong>
          </div>
          <div>
            <span>Email</span>
            <strong>{{ selectedLog.actor?.email || '--' }}</strong>
          </div>
          <div>
            <span>Đối tượng</span>
            <strong>{{ formatTarget(selectedLog) }}</strong>
          </div>
        </div>

        <section class="detail-section">
          <h3>Thay đổi</h3>
          <div v-if="Object.keys(selectedLog.changes || {}).length" class="change-list">
            <div v-for="(change, field) in selectedLog.changes" :key="field" class="change-item">
              <span>{{ field }}</span>
              <strong>{{ change?.old ?? '∅' }} → {{ change?.new ?? '∅' }}</strong>
            </div>
          </div>
          <div v-else class="empty-detail">Không có thay đổi.</div>
        </section>

        <section class="detail-section">
          <h3>Metadata</h3>
          <pre>{{ formatPrettyJson(selectedLog.metadata) }}</pre>
        </section>
      </aside>
    </div>
  </div>
</template>

<style scoped>
.summary-pill {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  border-radius: 999px;
  background: #eff6ff;
  color: #2563eb;
  font-size: 13px;
  font-weight: 700;
  padding: 8px 12px;
}
.filter-panel {
  display: grid;
  grid-template-columns: minmax(260px, 1.4fr) repeat(5, minmax(140px, 0.6fr));
  gap: 12px;
  margin-bottom: 24px;
}
.quick-filter-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 14px;
}
.quick-filter-group {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}
.quick-filter-button,
.reset-button {
  height: 38px;
  border: 1px solid #e2e8f0;
  border-radius: 999px;
  background: #fff;
  color: #334155;
  font-size: 13px;
  font-weight: 700;
  padding: 0 14px;
  cursor: pointer;
}
.quick-filter-button.active {
  border-color: #2563eb;
  background: #eff6ff;
  color: #2563eb;
}
.reset-button {
  border-radius: 12px;
}
.reset-button:hover,
.quick-filter-button:hover {
  border-color: #93c5fd;
}
.search-wrap {
  position: relative;
}
.search-icon {
  position: absolute;
  left: 14px;
  top: 50%;
  transform: translateY(-50%);
  color: #64748b;
}
.search-input,
.filter-input {
  width: 100%;
  height: 44px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  background: #fff;
  color: #0f172a;
  outline: none;
  font-size: 14px;
  padding: 0 14px;
}
.search-input {
  padding-left: 44px;
}
.search-input:focus,
.filter-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.12);
}
.table-wrap {
  overflow: hidden;
  border: 1px solid #e2e8f0;
  border-radius: 16px;
  background: #fff;
}
.table-scroll {
  overflow-x: auto;
}
.data-table {
  width: 100%;
  border-collapse: collapse;
}
.table-head-row {
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}
.th {
  padding: 14px 18px;
  text-align: left;
  font-size: 12px;
  font-weight: 700;
  color: #64748b;
  text-transform: uppercase;
}
.table-row {
  border-bottom: 1px solid #e2e8f0;
}
.clickable-row {
  cursor: pointer;
}
.clickable-row:hover {
  background: #f8fafc;
}
.table-row:last-child {
  border-bottom: 0;
}
.td {
  padding: 16px 18px;
  vertical-align: top;
  font-size: 14px;
  color: #0f172a;
}
.td-muted,
.actor-email {
  color: #64748b;
}
.actor-name {
  font-weight: 700;
}
.actor-email {
  margin-top: 2px;
  font-size: 12px;
}
.action-badge {
  display: inline-flex;
  border-radius: 999px;
  background: #eef2ff;
  color: #4338ca;
  font-size: 12px;
  font-weight: 700;
  padding: 5px 10px;
}
.changes-cell {
  min-width: 280px;
  line-height: 1.55;
}
.state-text {
  padding: 24px 0;
  color: #64748b;
}
.state-error {
  color: #dc2626;
}
.pagination {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  margin-top: 18px;
}
.pagination button {
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  padding: 8px 12px;
  cursor: pointer;
}
.pagination button:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}
.detail-backdrop {
  position: fixed;
  inset: 0;
  z-index: 40;
  display: flex;
  justify-content: flex-end;
  background: rgba(15, 23, 42, 0.4);
}
.detail-panel {
  width: min(520px, 100%);
  height: 100%;
  overflow-y: auto;
  background: #fff;
  box-shadow: -12px 0 30px rgba(15, 23, 42, 0.18);
  padding: 24px;
}
.detail-header {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 24px;
}
.detail-kicker {
  margin-bottom: 6px;
  color: #64748b;
  font-size: 12px;
  font-weight: 700;
  text-transform: uppercase;
}
.detail-header h2 {
  margin: 0;
  font-size: 22px;
}
.icon-button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  cursor: pointer;
}
.detail-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 14px;
  margin-bottom: 24px;
}
.detail-grid div {
  display: grid;
  gap: 4px;
}
.detail-grid span {
  color: #64748b;
  font-size: 12px;
}
.detail-section + .detail-section {
  margin-top: 24px;
}
.detail-section h3 {
  margin: 0 0 12px;
  font-size: 15px;
}
.change-list {
  display: grid;
  gap: 10px;
}
.change-item {
  display: grid;
  gap: 4px;
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  padding: 12px;
}
.change-item span {
  color: #64748b;
  font-size: 12px;
}
.empty-detail {
  color: #64748b;
}
pre {
  overflow-x: auto;
  margin: 0;
  border-radius: 12px;
  background: #0f172a;
  color: #e2e8f0;
  font-size: 12px;
  line-height: 1.5;
  padding: 16px;
}
@media (max-width: 900px) {
  .quick-filter-bar {
    align-items: stretch;
    flex-direction: column;
  }
  .filter-panel {
    grid-template-columns: 1fr;
  }
  .detail-grid {
    grid-template-columns: 1fr;
  }
}
</style>
