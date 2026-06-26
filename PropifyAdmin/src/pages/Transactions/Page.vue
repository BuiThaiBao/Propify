<script setup>
import { ref, onMounted, watch, computed } from 'vue'
import {
  Search,
  Download,
  Copy,
  Check,
  Receipt,
  DollarSign,
  CheckCircle,
  Clock,
  Loader2,
  X,
  ChevronLeft,
  ChevronRight,
} from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import StatCard from '@/components/shared/StatCard.vue'
import { DataTable, Pagination, Modal } from '@/components/crud'
import { useTransactionApi } from '@/composables/useTransactionApi'
import { usePackageApi } from '@/composables/usePackageApi'
import {
  formatTransactionAmount,
  formatTransactionDateTime,
  getTransactionPackageBadgeClass,
} from '@/utils/transactionFormatters'

const { fetchTransactions, fetchTransaction, storeNote, exportCsv, loading, error } =
  useTransactionApi()
const { fetchPackages } = usePackageApi()

// State quản lý danh sách & phân trang
const transactions = ref([])
const summary = ref({
  total_revenue: '0.00',
  counts: { SUCCESS: 0, PENDING: 0, FAILED: 0 },
})
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
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
    summary.value = res.summary || {
      total_revenue: '0.00',
      counts: { SUCCESS: 0, PENDING: 0, FAILED: 0 },
    }
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
watch(
  () => filters.value.search,
  (newVal) => {
    clearTimeout(debounceTimer)
    debounceTimer = setTimeout(() => {
      searchDebounced.value = newVal
    }, 400)
  },
)

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
  },
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
      admin: updatedNote.admin,
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

const tableColumns = [
  { key: 'code', label: 'Mã Giao Dịch', width: '130px' },
  { key: 'customer', label: 'Khách Hàng', width: '20%', nowrap: false },
  { key: 'package', label: 'Gói Tin', width: '15%' },
  { key: 'amount', label: 'Số Tiền', width: '12%' },
  { key: 'paymentMethod', label: 'Phương Thức', width: '110px' },
  { key: 'transactionDate', label: 'Ngày Giao Dịch', width: '15%' },
  { key: 'status', label: 'Trạng Thái', width: '12%' },
  { key: 'note', label: 'Ghi Chú Kế Toán', width: '20%', nowrap: false },
]

const normalizedTxs = computed(() =>
  transactions.value.map((tx) => ({
    id: tx.id,
    vnp_txn_ref: tx.vnp_txn_ref,
    code: tx.vnp_txn_ref || '#' + tx.id,
    full_name: tx.user?.full_name || 'Khách vãng lai',
    phone: tx.user?.phone || '-',
    email: tx.user?.email || '-',
    packageName: tx.package?.name || '-',
    packageSlug: tx.package?.slug,
    durationDays: tx.duration_days,
    amount: formatTransactionAmount(tx.amount),
    paymentMethod: tx.payment_method || 'VNPay',
    transactionDate: formatTransactionDateTime(tx.transaction_date),
    status: tx.status,
    statusLabel: tx.status === 'SUCCESS' ? 'Thành công' : tx.status === 'PENDING' ? 'Chờ xử lý' : tx.status === 'EXPIRED' ? 'Hết hạn thanh toán' : 'Thất bại',
    latestNote: tx.notes?.[0]?.note,
    latestNoteAuthor: tx.notes?.[0]?.admin?.full_name,
  }))
)

// Tính toán các trang hiển thị thông minh
const formatVND = (value) => formatTransactionAmount(value)
const formatDateTime = (value) => formatTransactionDateTime(value)
const getPackageBadgeClass = (slug) => getTransactionPackageBadgeClass(slug)

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
    <PageHeader
      title="Lịch sử giao dịch"
      description="Quản lý lịch sử nạp tiền đối soát và ghi chú kế toán nội bộ"
    >
      <template #actions>
        <button
          class="btn-export"
          @click="handleExport"
          :disabled="loading"
          id="export-transactions-btn"
        >
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
        :value="
          ((summary.counts.PENDING || 0) + (summary.counts.FAILED || 0)).toLocaleString() + ' GD'
        "
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
          <input
            id="filter-min-amount"
            v-model="filters.min_amount"
            type="number"
            placeholder="VND"
            class="form-input"
          />
        </div>

        <!-- Số tiền đến -->
        <div class="filter-item">
          <label for="filter-max-amount" class="filter-label">Số tiền tối đa</label>
          <input
            id="filter-max-amount"
            v-model="filters.max_amount"
            type="number"
            placeholder="VND"
            class="form-input"
          />
        </div>

        <!-- Nút Reset -->
        <div class="filter-item flex items-end">
          <button class="btn-reset w-full" @click="resetFilters">Xóa bộ lọc</button>
        </div>
      </div>
    </div>

    <!-- Data table -->
    <div class="bg-card border border-border/50 rounded-xl shadow-card overflow-hidden">
      <DataTable
        :columns="tableColumns"
        :rows="normalizedTxs"
        :loading="loading"
        loading-text="Đang tải lịch sử giao dịch..."
        empty-text="Không tìm thấy giao dịch nào"
      >
        <template #cell(code)="{ row }">
          <div class="flex items-center gap-1.5">
            <span class="font-semibold text-foreground font-mono text-xs">#{{ row.id }}</span>
            <button
              v-if="row.vnp_txn_ref"
              class="inline-flex items-center justify-center w-5 h-5 rounded text-muted-foreground hover:bg-muted cursor-pointer border-none"
              title="Copy mã tham chiếu"
              @click="copyToClipboard(row.vnp_txn_ref, row.id)"
            >
              <Check v-if="copiedId === row.id" :size="12" class="text-success" />
              <Copy v-else :size="12" />
            </button>
          </div>
          <div v-if="row.vnp_txn_ref" class="text-[10px] text-muted-foreground mt-0.5">Ref: {{ row.vnp_txn_ref }}</div>
        </template>
        <template #cell(customer)="{ row }">
          <div class="font-medium text-foreground text-sm">{{ row.full_name }}</div>
          <div class="text-xs text-muted-foreground mt-0.5">{{ row.phone }} | {{ row.email }}</div>
        </template>
        <template #cell(package)="{ row }">
          <span class="inline-block text-[11px] font-semibold px-2.5 py-0.5 rounded" :class="getPackageBadgeClass(row.packageSlug)">
            {{ row.packageName }}
          </span>
          <div class="text-[10px] text-muted-foreground mt-1">Thời hạn: {{ row.durationDays }} ngày</div>
        </template>
        <template #cell(amount)="{ value }">
          <span class="font-semibold text-foreground text-sm">{{ value }}</span>
        </template>
        <template #cell(status)="{ row }">
          <span class="px-2 py-0.5 rounded text-xs font-semibold"
            :class="{
              'bg-success/10 text-success': row.status === 'SUCCESS',
              'bg-warning/10 text-warning': row.status === 'PENDING',
              'bg-destructive/10 text-destructive': row.status === 'FAILED',
              'bg-muted text-muted-foreground': row.status === 'EXPIRED',
            }"
          >{{ row.statusLabel }}</span>
        </template>
        <template #cell(note)="{ row }">
          <div class="text-xs text-muted-foreground max-w-[200px] truncate" :title="row.latestNote">{{ row.latestNote || '-' }}</div>
          <div v-if="row.latestNoteAuthor" class="text-[10px] text-muted-foreground/75 mt-0.5">Bởi {{ row.latestNoteAuthor }}</div>
        </template>
        <template #actions="{ row }">
          <button class="inline-flex items-center justify-center w-8 h-8 rounded-lg text-muted-foreground hover:bg-muted hover:text-foreground transition cursor-pointer border-none" @click="openDetail(row.id)" title="Xem chi tiết & đối soát">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </template>
      </DataTable>

      <Pagination
        v-if="meta.total > 0"
        :current-page="meta.current_page"
        :last-page="meta.last_page"
        :total="meta.total"
        :per-page="meta.per_page"
        :loading="loading"
        @page-change="loadTransactions"
      />
    </div>

    <!-- Detail Modal -->
    <Modal :open="showDetailModal && !showConfirmNoteModal" title="" :closeable="!savingNote" max-width="2xl" @close="showDetailModal = false">
      <template v-if="selectedTx" #title>Chi tiết giao dịch #{{ selectedTx.id }}</template>
      <template v-if="selectedTx">
        <p class="text-xs text-muted-foreground mb-4">Ngày thực hiện: {{ formatDateTime(selectedTx.transaction_date) }}</p>
        <div class="grid grid-cols-2 gap-6 mb-6">
          <div class="bg-slate-50/50 border border-border p-4 rounded-xl">
            <h4 class="text-sm font-bold border-b border-border pb-1.5 mb-3">Thông tin giao dịch</h4>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between"><span class="text-muted-foreground">Khách hàng:</span><strong>{{ selectedTx.user?.full_name || 'Khách vãng lai' }}</strong></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Liên hệ:</span><span>{{ selectedTx.user?.phone || '-' }} | {{ selectedTx.user?.email || '-' }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Gói dịch vụ:</span><span class="font-semibold">{{ selectedTx.package?.name }} ({{ selectedTx.duration_days }} ngày)</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Tin đăng ID:</span><span>#{{ selectedTx.listing?.id || '-' }}</span></div>
              <div v-if="selectedTx.listing" class="flex justify-between"><span class="text-muted-foreground">Tiêu đề tin:</span><span class="truncate max-w-[200px] text-right" :title="selectedTx.listing?.title">{{ selectedTx.listing?.title }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Hạn gói tin:</span><span class="text-success font-medium">{{ formatDateTime(selectedTx.expires_at) }}</span></div>
            </div>
          </div>

          <div class="bg-slate-50/50 border border-border p-4 rounded-xl">
            <h4 class="text-sm font-bold border-b border-border pb-1.5 mb-3">Đối soát cổng VNPay</h4>
            <div class="space-y-3 text-sm">
              <div class="flex justify-between">
                <span class="text-muted-foreground">Trạng thái:</span>
                <span v-if="selectedTx.status === 'SUCCESS'" class="text-success font-semibold">Thành công</span>
                <span v-else-if="selectedTx.status === 'PENDING'" class="text-warning font-semibold">Chờ xử lý</span>
                <span v-else-if="selectedTx.status === 'EXPIRED'" class="text-muted-foreground">Hết hạn</span>
                <span v-else class="text-destructive font-semibold">Thất bại</span>
              </div>
              <div class="flex justify-between"><span class="text-muted-foreground">Số tiền:</span><strong>{{ formatVND(selectedTx.amount) }}</strong></div>
              <div class="flex justify-between items-center">
                <span class="text-muted-foreground">Mã Ref:</span>
                <span class="font-mono flex items-center gap-1">{{ selectedTx.vnp_txn_ref || '-' }}
                  <button v-if="selectedTx.vnp_txn_ref" class="inline-flex items-center justify-center w-5 h-5 rounded text-muted-foreground hover:bg-muted cursor-pointer border-none" @click="copyToClipboard(selectedTx.vnp_txn_ref, 'ref')">
                    <Check v-if="copiedId === 'ref'" :size="10" class="text-success" /><Copy v-else :size="10" />
                  </button>
                </span>
              </div>
              <div class="flex justify-between items-center">
                <span class="text-muted-foreground">Mã GD VNPay:</span>
                <span class="font-mono flex items-center gap-1">{{ selectedTx.vnp_transaction_no || '-' }}
                  <button v-if="selectedTx.vnp_transaction_no" class="inline-flex items-center justify-center w-5 h-5 rounded text-muted-foreground hover:bg-muted cursor-pointer border-none" @click="copyToClipboard(selectedTx.vnp_transaction_no, 'vnpno')">
                    <Check v-if="copiedId === 'vnpno'" :size="10" class="text-success" /><Copy v-else :size="10" />
                  </button>
                </span>
              </div>
              <div class="flex justify-between"><span class="text-muted-foreground">Ngân hàng:</span><span class="font-semibold">{{ selectedTx.vnp_bank_code || '-' }}</span></div>
              <div class="flex justify-between"><span class="text-muted-foreground">Mã phản hồi:</span><span :class="selectedTx.vnp_response_code === '00' ? 'text-success' : 'text-destructive'">{{ selectedTx.vnp_response_code || '-' }}</span></div>
            </div>
          </div>
        </div>

        <!-- Notes -->
        <div class="border-t border-border/50 pt-5">
          <h4 class="text-sm font-bold mb-4">Nhật ký đối soát & ghi chú kế toán</h4>

          <div v-if="selectedTx.notes && selectedTx.notes.length > 0" class="space-y-3 mb-4">
            <div v-for="note in selectedTx.notes" :key="note.id" class="flex gap-3">
              <div class="w-2 h-2 rounded-full bg-primary mt-1.5 shrink-0" />
              <div class="flex-1 bg-muted p-3 rounded-lg border border-border/50">
                <div class="flex justify-between items-start mb-1">
                  <span class="text-xs font-semibold text-foreground">{{ note.admin?.full_name || 'Admin' }}</span>
                  <span class="text-[10px] text-muted-foreground">{{ formatDateTime(note.created_at) }}</span>
                </div>
                <p class="text-xs text-muted-foreground m-0 whitespace-pre-wrap">{{ note.note }}</p>
              </div>
            </div>
          </div>

          <div v-else class="text-center py-6 bg-muted rounded-lg border border-border/50 border-dashed mb-4">
            <Clock :size="24" class="text-muted-foreground/40 mx-auto mb-2" />
            <p class="text-xs text-muted-foreground m-0">Chưa có nhật ký/ghi chú đối soát nào.</p>
          </div>

          <!-- Add note -->
          <div class="bg-muted/30 p-4 border border-border/50 rounded-xl">
            <label class="text-xs font-semibold text-foreground block mb-2">Thêm ghi chú đối soát mới</label>
            <textarea
              v-model="newNote"
              rows="3"
              placeholder="Nhập ghi chú..."
              class="w-full p-3 text-xs border border-border rounded-lg outline-none focus:border-primary resize-vertical bg-card box-border font-inherit"
            ></textarea>
            <div class="flex justify-end mt-3">
              <button
                class="inline-flex items-center gap-1 px-4 py-2 text-xs font-bold rounded-lg border-none bg-primary text-white cursor-pointer hover:opacity-90 transition disabled:opacity-50"
                :disabled="savingNote || !newNote.trim()"
                @click="confirmSaveNote"
              >
                <Loader2 v-if="savingNote" class="animate-spin" :size="12" />
                Lưu ghi chú
              </button>
            </div>
          </div>
        </div>
      </template>
    </Modal>

    <!-- Confirm save note -->
    <Modal :open="showConfirmNoteModal" title="Xác nhận ghi chú" max-width="sm" @close="showConfirmNoteModal = false">
      <p class="text-sm text-muted-foreground">Ghi chú sau khi lưu sẽ được thêm vào nhật ký audit trail và không thể xóa hay chỉnh sửa.</p>
      <template #footer>
        <button class="px-4 py-2 text-sm font-semibold rounded-lg border border-border bg-card text-foreground cursor-pointer hover:bg-muted transition disabled:opacity-50" :disabled="savingNote" @click="showConfirmNoteModal = false">Hủy</button>
        <button class="px-4 py-2 text-sm font-semibold rounded-lg border-none bg-primary text-white cursor-pointer hover:opacity-90 transition disabled:opacity-50 inline-flex items-center gap-1" :disabled="savingNote || !newNote.trim()" @click="saveNote">Xác nhận lưu</button>
      </template>
    </Modal>
  </div>
</template>

<style scoped>
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.filter-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
.filter-label { display: block; font-size: 12px; font-weight: 600; color: hsl(var(--muted-foreground)); margin-bottom: 6px; }
.form-input { width: 100%; height: 38px; padding: 0 12px; font-size: 13px; border: 1px solid hsl(var(--border)); border-radius: 8px; background-color: hsl(var(--card)); color: hsl(var(--foreground)); outline: none; box-sizing: border-box; }
.form-input:focus { border-color: hsl(var(--primary)); box-shadow: 0 0 0 2px hsl(var(--primary) / 0.15); }
.search-input-wrapper { position: relative; display: flex; align-items: center; }
.search-icon { position: absolute; left: 12px; color: hsl(var(--muted-foreground)); pointer-events: none; }
.search-input { padding-left: 36px; }
.btn-reset { height: 38px; padding: 0 16px; font-size: 13px; font-weight: 500; border: 1px solid hsl(var(--border)); border-radius: 8px; background-color: hsl(var(--muted)); color: hsl(var(--foreground)); cursor: pointer; }
.btn-export { display: flex; align-items: center; gap: 8px; padding: 9px 16px; font-size: 13px; font-weight: 600; border: 1px solid hsl(var(--border)); border-radius: 8px; background-color: hsl(var(--card)); color: hsl(var(--foreground)); cursor: pointer; }
@media (max-width: 1024px) { .stats-grid { grid-template-columns: 1fr; } .filter-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 640px) { .filter-grid { grid-template-columns: 1fr; } }
</style>
