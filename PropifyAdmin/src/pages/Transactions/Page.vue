<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import {
  Search,
  Download,
  Copy,
  Check,
  Eye,
  Trash2,
  Receipt,
  DollarSign,
  CheckCircle,
  AlertCircle,
  Clock,
  Loader2,
  Calendar,
  X,
  ChevronLeft,
  ChevronRight
} from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import ConfirmModal from '@/components/shared/ConfirmModal.vue'
import { useTransactionApi } from '@/composables/useTransactionApi'
import { usePackageApi } from '@/composables/usePackageApi'

const { fetchTransactions, fetchTransaction, storeNote, exportCsv, loading, error } = useTransactionApi()
const { fetchPackages } = usePackageApi()

// State quản lý danh sách & phân trang
const transactions = ref([])
const summary = ref({
  total_revenue: '0.00',
  counts: { SUCCESS: 0, PENDING: 0, FAILED: 0 }
})
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0
})

// State bộ lọc
const filters = ref({
  search: '',
  status: '',
  package_id: '',
  from_date: '',
  to_date: '',
  min_amount: '',
  max_amount: '',
})

// Danh sách các gói tin để hiển thị trong select filter
const packages = ref([])

// State UI
const searchDebounced = ref('')
let debounceTimer = null
const copiedId = ref(null) // ID của mã giao dịch vừa được copy để hiển thị tooltip check
const showDetailModal = ref(false)
const selectedTx = ref(null)
const newNote = ref('')
const savingNote = ref(false)
const showConfirmNoteModal = ref(false)

// Gọi API lấy danh sách packages
async function loadPackages() {
  try {
    const res = await fetchPackages()
    packages.value = res || []
  } catch (err) {
    console.error('Không thể tải danh sách gói tin:', err)
  }
}

// Gọi API lấy danh sách giao dịch
async function loadTransactions(page = 1) {
  try {
    const params = {
      page,
      per_page: meta.value.per_page,
      search: searchDebounced.value,
      status: filters.value.status || undefined,
      package_id: filters.value.package_id || undefined,
      from_date: filters.value.from_date || undefined,
      to_date: filters.value.to_date || undefined,
      min_amount: filters.value.min_amount || undefined,
      max_amount: filters.value.max_amount || undefined,
    }
    
    const res = await fetchTransactions(params)
    transactions.value = res.data || []
    summary.value = res.summary || { total_revenue: '0.00', counts: { SUCCESS: 0, PENDING: 0, FAILED: 0 } }
    meta.value = res.meta || { current_page: 1, last_page: 1, per_page: 10, total: 0 }
  } catch (err) {
    console.error('Lỗi tải danh sách giao dịch:', err)
  }
}

// Reset bộ lọc
function resetFilters() {
  filters.value = {
    search: '',
    status: '',
    package_id: '',
    from_date: '',
    to_date: '',
    min_amount: '',
    max_amount: '',
  }
  searchDebounced.value = ''
  loadTransactions(1)
}

// Watcher debounced cho ô tìm kiếm
watch(() => filters.value.search, (newVal) => {
  clearTimeout(debounceTimer)
  debounceTimer = setTimeout(() => {
    searchDebounced.value = newVal
  }, 400)
})

// Watcher cho các bộ lọc còn lại (tự động reload và reset về page 1 khi thay đổi)
watch(
  [
    searchDebounced,
    () => filters.value.status,
    () => filters.value.package_id,
    () => filters.value.from_date,
    () => filters.value.to_date,
    () => filters.value.min_amount,
    () => filters.value.max_amount,
  ],
  () => {
    loadTransactions(1)
  }
)

// Copy nhanh mã giao dịch vào clipboard
function copyToClipboard(text, id) {
  if (!text) return
  navigator.clipboard.writeText(text).then(() => {
    copiedId.value = id
    setTimeout(() => {
      if (copiedId.value === id) copiedId.value = null
    }, 1500)
  })
}

// Format số tiền sang định dạng VND
function formatVND(value) {
  if (value === undefined || value === null) return '0 đ'
  return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(value)
}

// Format ngày giờ
function formatDateTime(dateStr) {
  if (!dateStr) return '-'
  const date = new Date(dateStr)
  return date.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    timeZone: 'Asia/Ho_Chi_Minh'
  })
}

// Tải chi tiết giao dịch để xem trong modal
async function openDetail(id) {
  try {
    const data = await fetchTransaction(id)
    selectedTx.value = data
    newNote.value = ''
    showDetailModal.value = true
  } catch (err) {
    alert('Không thể tải chi tiết giao dịch: ' + (error.value || err.message))
  }
}

// Confirm ghi note
function confirmSaveNote() {
  if (!newNote.value.trim()) return
  showConfirmNoteModal.value = true
}

// Lưu note mới
async function saveNote() {
  if (!selectedTx.value || !newNote.value.trim()) return
  savingNote.value = true
  try {
    const updatedNote = await storeNote(selectedTx.value.id, newNote.value)
    
    // Thêm note mới vào danh sách notes của selectedTx để hiển thị ngay lập tức
    if (!selectedTx.value.notes) selectedTx.value.notes = []
    selectedTx.value.notes.unshift({
      id: updatedNote.id,
      note: updatedNote.note,
      created_at: updatedNote.created_at,
      admin: updatedNote.admin
    })
    
    newNote.value = ''
    showConfirmNoteModal.value = false
    
    // Reload danh sách ngầm để cập nhật cột note mới nhất ngoài bảng
    loadTransactions(meta.value.current_page)
  } catch (err) {
    alert('Không thể lưu ghi chú: ' + (error.value || err.message))
  } finally {
    savingNote.value = false
  }
}

// Tải file CSV
async function handleExport() {
  try {
    const params = {
      search: searchDebounced.value,
      status: filters.value.status || undefined,
      package_id: filters.value.package_id || undefined,
      from_date: filters.value.from_date || undefined,
      to_date: filters.value.to_date || undefined,
      min_amount: filters.value.min_amount || undefined,
      max_amount: filters.value.max_amount || undefined,
    }
    await exportCsv(params)
  } catch (err) {
    alert('Không thể xuất báo cáo: ' + (error.value || err.message))
  }
}

// Trả về class styling cho badge gói tin
function getPackageBadgeClass(slug) {
  const s = String(slug || '').toLowerCase()
  if (s === 'diamond') return 'bg-blue-50 text-blue-600 border border-blue-200'
  if (s === 'ruby') return 'bg-rose-50 text-rose-600 border border-rose-200/80'
  if (s === 'gold') return 'bg-amber-50 text-amber-600 border border-amber-200/80'
  return 'bg-slate-50 text-slate-600 border border-slate-200/60'
}

// Tính toán các trang hiển thị thông minh
const visiblePages = computed(() => {
  const current = meta.value.current_page
  const last = meta.value.last_page
  const pages = []
  
  if (last <= 7) {
    for (let i = 1; i <= last; i++) pages.push(i)
  } else {
    pages.push(1)
    if (current > 4) pages.push('...')
    
    const start = Math.max(2, current - 2)
    const end = Math.min(last - 1, current + 2)
    
    for (let i = start; i <= end; i++) {
      pages.push(i)
    }
    
    if (current < last - 3) pages.push('...')
    pages.push(last)
  }
  
  return pages
})

// Chạy khởi tạo
onMounted(() => {
  loadPackages()
  loadTransactions(1)
})
</script>

<template>
  <div>
    <PageHeader title="Lịch sử giao dịch" description="Quản lý lịch sử nạp tiền đối soát và ghi chú kế toán nội bộ">
      <template #actions>
        <button class="btn-export" @click="handleExport" :disabled="loading" id="export-transactions-btn">
          <Loader2 v-if="loading" class="animate-spin" :size="16" />
          <Download v-else :size="16" />
          Xuất báo cáo (CSV)
        </button>
      </template>
    </PageHeader>

    <!-- Thống kê nhanh ở trên -->
    <div class="stats-grid mb-6">
      <StatCard
        title="Tổng doanh thu (Thành công)"
        :value="formatVND(summary.total_revenue)"
        :icon="DollarSign"
        icon-color="bg-success/10 text-success"
      />
      <StatCard
        title="Giao dịch thành công"
        :value="summary.counts.SUCCESS.toLocaleString() + ' GD'"
        change="Đã kích hoạt dịch vụ"
        change-type="positive"
        :icon="CheckCircle"
        icon-color="bg-success/10 text-success"
      />
      <StatCard
        title="Giao dịch chờ/lỗi"
        :value="((summary.counts.PENDING || 0) + (summary.counts.FAILED || 0)).toLocaleString() + ' GD'"
        :change="`${summary.counts.PENDING || 0} Chờ xử lý | ${summary.counts.FAILED || 0} Thất bại`"
        change-type="neutral"
        :icon="Clock"
        icon-color="bg-warning/10 text-warning"
      />
    </div>

    <!-- Thanh lọc & tìm kiếm -->
    <div class="filter-panel bg-card border border-border/50 rounded-xl p-5 shadow-card mb-6">
      <div class="filter-grid">
        <!-- Tìm kiếm -->
        <div class="filter-item">
          <label for="filter-search" class="filter-label">Tìm kiếm</label>
          <div class="search-input-wrapper">
            <Search :size="16" class="search-icon" />
            <input
              id="filter-search"
              v-model="filters.search"
              type="text"
              placeholder="Mã GD, tên, email, sđt..."
              class="form-input search-input"
            />
          </div>
        </div>

        <!-- Trạng thái -->
        <div class="filter-item">
          <label for="filter-status" class="filter-label">Trạng thái</label>
          <select id="filter-status" v-model="filters.status" class="form-input">
            <option value="">Tất cả trạng thái</option>
            <option value="SUCCESS">Thành công</option>
            <option value="PENDING">Đang chờ</option>
            <option value="FAILED">Thất bại</option>
            <option value="EXPIRED">Hết hạn thanh toán</option>
          </select>
        </div>

        <!-- Gói tin -->
        <div class="filter-item">
          <label for="filter-package" class="filter-label">Gói tin đăng</label>
          <select id="filter-package" v-model="filters.package_id" class="form-input">
            <option value="">Tất cả gói tin</option>
            <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id">{{ pkg.name }}</option>
          </select>
        </div>

        <!-- Từ ngày -->
        <div class="filter-item">
          <label for="filter-from-date" class="filter-label">Từ ngày</label>
          <input id="filter-from-date" v-model="filters.from_date" type="date" class="form-input" />
        </div>

        <!-- Đến ngày -->
        <div class="filter-item">
          <label for="filter-to-date" class="filter-label">Đến ngày</label>
          <input id="filter-to-date" v-model="filters.to_date" type="date" class="form-input" />
        </div>

        <!-- Số tiền từ -->
        <div class="filter-item">
          <label for="filter-min-amount" class="filter-label">Số tiền tối thiểu</label>
          <input id="filter-min-amount" v-model="filters.min_amount" type="number" placeholder="VND" class="form-input" />
        </div>

        <!-- Số tiền đến -->
        <div class="filter-item">
          <label for="filter-max-amount" class="filter-label">Số tiền tối đa</label>
          <input id="filter-max-amount" v-model="filters.max_amount" type="number" placeholder="VND" class="form-input" />
        </div>

        <!-- Nút Reset -->
        <div class="filter-item flex items-end">
          <button class="btn-reset w-full" @click="resetFilters">
            Xóa bộ lọc
          </button>
        </div>
      </div>
    </div>

    <!-- Bảng dữ liệu -->
    <div class="table-container bg-card border border-border/50 rounded-xl shadow-card overflow-hidden">
      <!-- Loading State -->
      <div v-if="loading && transactions.length === 0" class="loading-state py-12">
        <Loader2 class="animate-spin text-primary mx-auto mb-4" :size="36" />
        <p class="text-muted-foreground text-sm">Đang tải lịch sử giao dịch...</p>
      </div>

      <!-- Empty State -->
      <div v-else-if="transactions.length === 0" class="empty-state py-12 text-center">
        <Receipt class="text-muted-foreground/30 mx-auto mb-4" :size="48" />
        <h3 class="text-lg font-medium text-foreground mb-1">Không tìm thấy giao dịch nào</h3>
        <p class="text-muted-foreground text-sm max-w-md mx-auto">
          Thử thay đổi từ khóa tìm kiếm hoặc điều chỉnh bộ lọc ngày/trạng thái.
        </p>
      </div>

      <!-- Bảng thực tế -->
      <div v-else class="overflow-x-auto">
        <table class="data-table">
          <thead>
            <tr>
              <th style="width: 130px;">Mã Giao Dịch</th>
              <th>Khách Hàng</th>
              <th>Gói Tin</th>
              <th>Số Tiền</th>
              <th style="width: 110px;">Phương Thức</th>
              <th>Ngày Giao Dịch</th>
              <th>Trạng Thái</th>
              <th>Ghi Chú Kế Toán</th>
              <th style="width: 80px; text-align: center;">Xem</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="tx in transactions" :key="tx.id" class="table-row">
              <!-- Mã GD -->
              <td class="font-mono text-xs">
                <div class="flex items-center gap-1.5">
                  <span class="font-semibold text-foreground">#{{ tx.id }}</span>
                  <button
                    v-if="tx.vnp_txn_ref"
                    class="btn-copy"
                    title="Copy mã tham chiếu"
                    @click="copyToClipboard(tx.vnp_txn_ref, tx.id)"
                  >
                    <Check v-if="copiedId === tx.id" :size="12" class="text-success" />
                    <Copy v-else :size="12" />
                  </button>
                </div>
                <div class="text-[10px] text-muted-foreground mt-0.5" v-if="tx.vnp_txn_ref">
                  Ref: {{ tx.vnp_txn_ref }}
                </div>
              </td>

              <!-- Khách hàng -->
              <td>
                <div class="font-medium text-foreground text-sm">{{ tx.user?.full_name || 'Khách vãng lai' }}</div>
                <div class="text-xs text-muted-foreground mt-0.5">
                  {{ tx.user?.phone || '-' }} | {{ tx.user?.email || '-' }}
                </div>
              </td>

              <!-- Gói tin -->
              <td>
                <span
                  class="inline-block text-[11px] font-semibold px-2.5 py-0.5 rounded"
                  :class="getPackageBadgeClass(tx.package?.slug)"
                >
                  {{ tx.package?.name || '-' }}
                </span>
                <div class="text-[10px] text-muted-foreground mt-1">Thời hạn: {{ tx.duration_days }} ngày</div>
              </td>

              <!-- Số tiền -->
              <td class="font-semibold text-foreground text-sm">
                {{ formatVND(tx.amount) }}
              </td>

              <!-- Phương thức -->
              <td>
                <span class="badge-payment">{{ tx.payment_method || 'VNPay' }}</span>
              </td>

              <!-- Ngày giao dịch -->
              <td class="text-xs text-muted-foreground">
                {{ formatDateTime(tx.transaction_date) }}
              </td>

              <!-- Trạng thái -->
              <td>
                <span
                  class="badge-status"
                  :class="{
                    'bg-success/10 text-success': tx.status === 'SUCCESS',
                    'bg-warning/10 text-warning': tx.status === 'PENDING',
                    'bg-destructive/10 text-destructive': tx.status === 'FAILED',
                    'bg-muted text-muted-foreground': tx.status === 'EXPIRED',
                  }"
                >
                  {{ tx.status === 'SUCCESS' ? 'Thành công' : tx.status === 'PENDING' ? 'Chờ xử lý' : tx.status === 'EXPIRED' ? 'Hết hạn thanh toán' : 'Thất bại' }}
                </span>
              </td>

              <!-- Ghi chú mới nhất -->
              <td>
                <div class="text-xs text-muted-foreground max-w-[200px] truncate" :title="tx.notes?.[0]?.note">
                  {{ tx.notes?.[0]?.note || '-' }}
                </div>
                <div class="text-[10px] text-muted-foreground/75 mt-0.5" v-if="tx.notes?.[0]">
                  Bởi {{ tx.notes[0].admin?.full_name }}
                </div>
              </td>

              <!-- Action -->
              <td style="text-align: center;">
                <button class="btn-icon" @click="openDetail(tx.id)" title="Xem chi tiết & đối soát">
                  <Eye :size="16" />
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Phân trang -->
      <div v-if="transactions.length > 0" class="pagination-bar border-t border-border/50 p-4 flex items-center justify-between">
        <div class="pagination-actions">
          <button
            class="page-btn"
            :disabled="meta.current_page === 1 || loading"
            @click="loadTransactions(meta.current_page - 1)"
            aria-label="Trang trước"
          >
            <ChevronLeft :size="18" />
          </button>
          <span class="page-summary">
            Trang {{ meta.current_page }} / {{ meta.last_page }}
          </span>
          <button
            class="page-btn"
            :disabled="meta.current_page === meta.last_page || loading"
            @click="loadTransactions(meta.current_page + 1)"
            aria-label="Trang sau"
          >
            <ChevronRight :size="18" />
          </button>
        </div>
        <p class="total-summary">
          Hiển thị tối đa {{ meta.per_page }} / {{ meta.total }} giao dịch
        </p>
      </div>
    </div>

    <!-- Modal Chi Tiết Giao Dịch -->
    <div v-if="showDetailModal && selectedTx" class="modal-backdrop" @click.self="showDetailModal = false">
      <div class="modal-content bg-card border border-border/50 rounded-2xl shadow-lg w-full max-w-3xl overflow-hidden">
        <!-- Header -->
        <div class="modal-header border-b border-border/50 p-5 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <div class="modal-icon gradient-primary">
              <Receipt :size="18" color="white" />
            </div>
            <div>
              <h3 class="text-lg font-bold text-foreground m-0">Chi tiết giao dịch #{{ selectedTx.id }}</h3>
              <p class="text-xs text-muted-foreground m-0 mt-0.5">Ngày thực hiện: {{ formatDateTime(selectedTx.transaction_date) }}</p>
            </div>
          </div>
          <button class="btn-close" @click="showDetailModal = false">
            <X :size="18" />
          </button>
        </div>

        <!-- Body -->
        <div class="modal-body p-6 overflow-y-auto max-h-[70vh]">
          <div class="grid grid-cols-2 gap-6 mb-6">
            <!-- Thông tin khách hàng & Bài đăng -->
            <div class="modal-card">
              <h4 class="modal-section-title">Thông tin giao dịch</h4>
              <div class="info-list">
                <div class="info-item">
                  <span class="info-label">Khách hàng:</span>
                  <span class="info-value font-medium">{{ selectedTx.user?.full_name || 'Khách vãng lai' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Liên hệ:</span>
                  <span class="info-value">{{ selectedTx.user?.phone || '-' }} | {{ selectedTx.user?.email || '-' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Gói dịch vụ:</span>
                  <div class="flex items-center gap-1.5 justify-end">
                    <span
                      class="text-[11px] font-semibold px-2 py-0.5 rounded"
                      :class="getPackageBadgeClass(selectedTx.package?.slug)"
                    >
                      {{ selectedTx.package?.name }}
                    </span>
                    <span class="info-value text-xs font-medium">({{ selectedTx.duration_days }} ngày)</span>
                  </div>
                </div>
                <div class="info-item">
                  <span class="info-label">Tin đăng ID:</span>
                  <span class="info-value">#{{ selectedTx.listing?.id || '-' }}</span>
                </div>
                <div class="info-item" v-if="selectedTx.listing">
                  <span class="info-label">Tiêu đề tin:</span>
                  <span class="info-value truncate block max-w-[200px]" :title="selectedTx.listing?.title">{{ selectedTx.listing?.title }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Hạn gói tin:</span>
                  <span class="info-value text-success font-medium">{{ formatDateTime(selectedTx.expires_at) }}</span>
                </div>
              </div>
            </div>

            <!-- Thông tin đối soát VNPay -->
            <div class="modal-card">
              <h4 class="modal-section-title">Đối soát cổng VNPay</h4>
              <div class="info-list">
                <div class="info-item">
                  <span class="info-label">Trạng thái:</span>
                  <span
                    class="badge-status text-xs"
                    :class="{
                      'bg-success/10 text-success': selectedTx.status === 'SUCCESS',
                      'bg-warning/10 text-warning': selectedTx.status === 'PENDING',
                      'bg-destructive/10 text-destructive': selectedTx.status === 'FAILED',
                      'bg-muted text-muted-foreground': selectedTx.status === 'EXPIRED',
                    }"
                  >
                    {{ selectedTx.status === 'SUCCESS' ? 'Thành công' : selectedTx.status === 'PENDING' ? 'Chờ xử lý' : selectedTx.status === 'EXPIRED' ? 'Hết hạn thanh toán' : 'Thất bại' }}
                  </span>
                </div>
                <div class="info-item">
                  <span class="info-label">Số tiền:</span>
                  <span class="info-value text-foreground font-bold">{{ formatVND(selectedTx.amount) }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Mã Ref hệ thống:</span>
                  <span class="info-value font-mono flex items-center gap-1.5">
                    {{ selectedTx.vnp_txn_ref || '-' }}
                    <button v-if="selectedTx.vnp_txn_ref" class="btn-copy-small" @click="copyToClipboard(selectedTx.vnp_txn_ref, 'ref')">
                      <Check v-if="copiedId === 'ref'" :size="10" class="text-success" />
                      <Copy v-else :size="10" />
                    </button>
                  </span>
                </div>
                <div class="info-item">
                  <span class="info-label">Mã GD VNPay:</span>
                  <span class="info-value font-mono flex items-center gap-1.5">
                    {{ selectedTx.vnp_transaction_no || '-' }}
                    <button v-if="selectedTx.vnp_transaction_no" class="btn-copy-small" @click="copyToClipboard(selectedTx.vnp_transaction_no, 'vnpno')">
                      <Check v-if="copiedId === 'vnpno'" :size="10" class="text-success" />
                      <Copy v-else :size="10" />
                    </button>
                  </span>
                </div>
                <div class="info-item">
                  <span class="info-label">Ngân hàng thanh toán:</span>
                  <span class="info-value font-semibold">{{ selectedTx.vnp_bank_code || '-' }}</span>
                </div>
                <div class="info-item">
                  <span class="info-label">Mã phản hồi:</span>
                  <span class="info-value" :class="selectedTx.vnp_response_code === '00' ? 'text-success' : 'text-destructive'">
                    {{ selectedTx.vnp_response_code || '-' }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Audit Trail Notes (Timeline) -->
          <div class="note-section border-t border-border/50 pt-5">
            <h4 class="modal-section-title mb-4">Nhật ký đối soát & ghi chú kế toán</h4>
            
            <div class="note-timeline mb-6" v-if="selectedTx.notes && selectedTx.notes.length > 0">
              <div v-for="note in selectedTx.notes" :key="note.id" class="timeline-item">
                <div class="timeline-marker"></div>
                <div class="timeline-content bg-muted p-3.5 rounded-lg border border-border/50">
                  <div class="flex justify-between items-start mb-1.5">
                    <span class="text-xs font-semibold text-foreground">{{ note.admin?.full_name || 'Admin' }}</span>
                    <span class="text-[10px] text-muted-foreground">{{ formatDateTime(note.created_at) }}</span>
                  </div>
                  <p class="text-xs text-muted-foreground m-0 whitespace-pre-wrap leading-relaxed">{{ note.note }}</p>
                </div>
              </div>
            </div>
            
            <div v-else class="text-center py-6 bg-muted rounded-lg border border-border/50 border-dashed mb-6">
              <Clock :size="24" class="text-muted-foreground/40 mx-auto mb-2" />
              <p class="text-xs text-muted-foreground m-0">Chưa có nhật ký/ghi chú đối soát nào.</p>
            </div>

            <!-- Thêm Note mới -->
            <div class="add-note-wrapper bg-muted/30 p-4 border border-border/50 rounded-xl">
              <label for="new-note-input" class="text-xs font-semibold text-foreground block mb-2">Thêm ghi chú đối soát mới</label>
              <textarea
                id="new-note-input"
                v-model="newNote"
                rows="3"
                placeholder="Nhập ghi chú (VD: Đối soát thành công, hoàn tiền thủ công ngày..., lý do lỗi cổng thanh toán...)"
                class="form-textarea w-full text-xs"
              ></textarea>
              <div class="flex justify-end mt-3">
                <button
                  class="btn-save-note"
                  :disabled="savingNote || !newNote.trim()"
                  @click="confirmSaveNote"
                >
                  <Loader2 v-if="savingNote" class="animate-spin mr-1.5" :size="12" />
                  Lưu ghi chú
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Confirm Modal khi lưu note -->
    <ConfirmModal
      :show="showConfirmNoteModal"
      title="Xác nhận ghi chú"
      message="Bạn có chắc chắn muốn lưu ghi chú đối soát này? Ghi chú sau khi lưu sẽ được thêm vào nhật ký audit trail và không thể xóa hay chỉnh sửa."
      confirm-text="Xác nhận lưu"
      cancel-text="Hủy"
      :loading="savingNote"
      @confirm="saveNote"
      @cancel="showConfirmNoteModal = false"
    />
  </div>
</template>

<style scoped>
.stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 20px;
}

/* Filter panel */
.filter-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
}

.filter-label {
  display: block;
  font-size: 12px;
  font-weight: 600;
  color: hsl(var(--muted-foreground));
  margin-bottom: 6px;
}

.form-input {
  width: 100%;
  height: 38px;
  padding: 0 12px;
  font-size: 13px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  outline: none;
  transition: border-color 0.15s ease, box-shadow 0.15s ease;
}

.form-input:focus {
  border-color: hsl(var(--primary));
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.15);
}

.search-input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.search-icon {
  position: absolute;
  left: 12px;
  color: hsl(var(--muted-foreground));
  pointer-events: none;
}

.search-input {
  padding-left: 36px;
}

.form-textarea {
  width: 100%;
  padding: 10px 12px;
  font-size: 13px;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  outline: none;
  resize: vertical;
}

.form-textarea:focus {
  border-color: hsl(var(--primary));
  box-shadow: 0 0 0 2px hsl(var(--primary) / 0.15);
}

/* Buttons */
.btn-export {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 9px 16px;
  font-size: 13px;
  font-weight: 600;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--card));
  color: hsl(var(--foreground));
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.btn-export:hover:not(:disabled) {
  background-color: hsl(var(--muted));
}

.btn-export:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.btn-reset {
  height: 38px;
  padding: 0 16px;
  font-size: 13px;
  font-weight: 500;
  border: 1px solid hsl(var(--border));
  border-radius: 8px;
  background-color: hsl(var(--muted));
  color: hsl(var(--foreground));
  cursor: pointer;
  transition: background-color 0.15s ease;
}

.btn-reset:hover {
  background-color: hsl(var(--border) / 0.8);
}

.pagination-actions {
  display: flex;
  align-items: center;
  gap: 10px;
}

.page-btn {
  width: 34px;
  height: 34px;
  border: 1px solid #dbe3ef;
  border-radius: 7px;
  background: #ffffff;
  color: #1e3a5f;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  cursor: pointer;
}

.page-btn:hover:not(:disabled) {
  background: #f8fafc;
}

.page-btn:disabled {
  cursor: not-allowed;
  opacity: 0.45;
}

.page-summary,
.total-summary {
  color: #64748b;
  font-size: 13px;
  margin: 0;
}


.btn-copy, .btn-copy-small {
  background: none;
  border: none;
  padding: 3px;
  border-radius: 4px;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}

.btn-copy:hover, .btn-copy-small:hover {
  background-color: hsl(var(--muted));
  color: hsl(var(--foreground));
}

.btn-icon {
  background: none;
  border: none;
  padding: 6px;
  border-radius: 6px;
  color: hsl(var(--primary));
  cursor: pointer;
  display: inline-flex;
}

.btn-icon:hover {
  background-color: hsl(var(--primary) / 0.1);
}

.btn-save-note {
  display: flex;
  align-items: center;
  padding: 8px 16px;
  font-size: 12px;
  font-weight: 600;
  border-radius: 6px;
  background-color: hsl(var(--primary));
  color: hsl(var(--primary-foreground));
  border: none;
  cursor: pointer;
  transition: opacity 0.15s;
}

.btn-save-note:hover:not(:disabled) {
  opacity: 0.9;
}

.btn-save-note:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Badges */
.badge-payment {
  font-size: 11px;
  font-weight: 600;
  background-color: hsl(var(--muted));
  color: hsl(var(--foreground));
  padding: 3px 8px;
  border-radius: 6px;
}

.badge-status {
  display: inline-flex;
  align-items: center;
  font-size: 11px;
  font-weight: 600;
  padding: 3px 8px;
  border-radius: 100px;
}

.badge-success {
  background-color: hsl(var(--success) / 0.1);
  color: hsl(var(--success));
}

.badge-warning {
  background-color: hsl(var(--warning) / 0.1);
  color: hsl(var(--warning));
}

.badge-destructive {
  background-color: hsl(var(--destructive) / 0.1);
  color: hsl(var(--destructive));
}

/* Data Table */
.data-table {
  width: 100%;
  border-collapse: collapse;
  text-align: left;
}

.data-table th {
  background-color: hsl(var(--muted) / 0.5);
  font-size: 11px;
  font-weight: 600;
  color: hsl(var(--muted-foreground));
  text-transform: uppercase;
  letter-spacing: 0.05em;
  padding: 14px 20px;
  border-bottom: 1px solid hsl(var(--border) / 0.5);
}

.data-table td {
  padding: 14px 20px;
  border-bottom: 1px solid hsl(var(--border) / 0.5);
  vertical-align: middle;
}

.table-row {
  transition: background-color 0.15s ease;
}

.table-row:hover {
  background-color: hsl(var(--muted) / 0.2);
}

/* Modal */
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(15, 23, 42, 0.4);
  z-index: 50;
  display: flex;
  align-items: center;
  justify-content: center;
  backdrop-filter: blur(4px);
  padding: 20px;
}

.modal-content {
  max-height: 90vh;
  display: flex;
  flex-direction: column;
}

.modal-icon {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
}

.btn-close {
  background: none;
  border: none;
  color: hsl(var(--muted-foreground));
  cursor: pointer;
  padding: 6px;
  border-radius: 6px;
  display: inline-flex;
}

.btn-close:hover {
  background-color: hsl(var(--muted));
  color: hsl(var(--foreground));
}

.modal-card {
  background-color: hsl(var(--muted) / 0.25);
  border: 1px solid hsl(var(--border) / 0.5);
  border-radius: 12px;
  padding: 16px;
}

.modal-section-title {
  font-size: 13px;
  font-weight: 700;
  color: hsl(var(--foreground));
  margin: 0 0 14px 0;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.info-list {
  display: flex;
  flex-direction: column;
  gap: 10px;
}

.info-item {
  display: flex;
  justify-content: space-between;
  font-size: 12px;
}

.info-label {
  color: hsl(var(--muted-foreground));
}

.info-value {
  color: hsl(var(--foreground));
  text-align: right;
}

/* Note timeline */
.note-timeline {
  display: flex;
  flex-direction: column;
  gap: 16px;
  position: relative;
  padding-left: 16px;
  border-left: 1px solid hsl(var(--border));
}

.timeline-item {
  position: relative;
}

.timeline-marker {
  position: absolute;
  left: -21px;
  top: 6px;
  width: 9px;
  height: 9px;
  border-radius: 50%;
  background-color: hsl(var(--primary));
  border: 2px solid hsl(var(--card));
}

@media (max-width: 1024px) {
  .stats-grid {
    grid-template-columns: 1fr;
  }
  .filter-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 640px) {
  .filter-grid {
    grid-template-columns: 1fr;
  }
}
</style>
