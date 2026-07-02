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
import { Pagination, Modal } from '@/components/crud'
import { useTransactionApi } from '@/composables/useTransactionApi'
import { usePackageApi } from '@/composables/usePackageApi'
import {
  formatTransactionAmount,
  formatTransactionDateTime,
  getTransactionPackageBadgeClass,
} from '@/utils/transactionFormatters'

const { fetchTransactions, fetchTransaction, storeNote, exportReport, loading, error } =
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

// Tải file báo cáo (Excel/PDF)
async function handleExport(format) {
  try {
    const params = {
      search: searchDebounced.value,
      status: filters.value.status || undefined,
      package_id: filters.value.package_id || undefined,
      from_date: filters.value.from_date || undefined,
      to_date: filters.value.to_date || undefined,
    }
    await exportReport(format, params)
  } catch (err) {
    alert('Không thể xuất báo cáo: ' + (error.value || err.message))
  }
}


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
      <template #actions></template>
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


        <!-- Nút Reset -->
        <div class="filter-item flex items-end">
          <button class="btn-reset w-full" @click="resetFilters">Xóa bộ lọc</button>
        </div>
      </div>
    </div>

    <!-- Data table -->
    <div class="tx-table-wrap">
      <div class="tx-table-scroll">
        <table class="tx-table">
          <thead>
            <tr>
              <th class="col-code">Mã Giao Dịch</th>
              <th class="col-customer">Khách Hàng</th>
              <th class="col-package">Gói Tin</th>
              <th class="col-amount">Số Tiền</th>
              <th class="col-method">Phương Thức</th>
              <th class="col-date">Ngày Giao Dịch</th>
              <th class="sticky-right col-status">Trạng Thái</th>
              <th class="sticky-right sticky-action action-cell"></th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="loading">
              <td colspan="9" class="state-cell">Đang tải lịch sử giao dịch...</td>
            </tr>
            <tr v-else-if="error">
              <td colspan="9" class="state-cell error-cell">{{ error }}</td>
            </tr>
            <tr v-else-if="normalizedTxs.length === 0">
              <td colspan="9" class="state-cell">Không tìm thấy giao dịch nào</td>
            </tr>
            <tr
              v-else
              v-for="row in normalizedTxs"
              :key="row.id"
            >
              <td>
                <div class="flex items-center gap-1.5">
                  <span class="font-semibold text-foreground font-mono text-xs">{{ row.code }}</span>
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
               
              </td>
              <td>
                <div class="font-medium text-foreground text-sm">{{ row.full_name }}</div>
                <div class="text-xs text-muted-foreground mt-0.5">{{ row.phone }} | {{ row.email }}</div>
              </td>
              <td>
                <span class="inline-block text-[11px] font-semibold px-2.5 py-0.5 rounded" :class="getPackageBadgeClass(row.packageSlug)">
                  {{ row.packageName }}
                </span>
                <div class="text-[10px] text-muted-foreground mt-1">Thời hạn: {{ row.durationDays }} ngày</div>
              </td>
              <td>
                <span class="font-semibold text-foreground text-sm">{{ row.amount }}</span>
              </td>
              <td>{{ row.paymentMethod }}</td>
              <td>{{ row.transactionDate }}</td>
              <td class="sticky-right col-status">
                <span class="px-2 py-0.5 rounded text-xs font-semibold"
                  :class="{
                    'bg-success/10 text-success': row.status === 'SUCCESS',
                    'bg-warning/10 text-warning': row.status === 'PENDING',
                    'bg-destructive/10 text-destructive': row.status === 'FAILED',
                    'bg-muted text-muted-foreground': row.status === 'EXPIRED',
                  }"
                >{{ row.statusLabel }}</span>
              </td>
              <td class="sticky-right sticky-action action-cell">
                <button class="more-btn" @click="openDetail(row.id)" title="Xem chi tiết & đối soát">
                  <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div class="px-5 py-4 border-t border-[#e7edf5] bg-white">
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
.filter-grid { display: grid; grid-template-columns: 2fr 1.5fr 1.5fr 1fr 1fr auto; gap: 16px; }
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

.tx-table-wrap {
  background: #ffffff;
  border: 1px solid #e7edf5;
  border-radius: 8px;
  overflow: hidden;
}

.tx-table-scroll {
  max-width: 100%;
  overflow-x: auto;
  overflow-y: auto;
}

.tx-table {
  width: 100%;
  min-width: 1200px;
  border-collapse: separate;
  border-spacing: 0;
  color: #1b365d;
  font-size: 14px;
}

.tx-table th,
.tx-table td {
  min-height: 64px;
  padding: 14px 12px;
  border-bottom: 1px solid #f0f3f7;
  background: #ffffff;
  text-align: left;
  vertical-align: middle;
}

.tx-table th {
  height: 44px;
  min-height: 44px;
  position: sticky;
  top: 0;
  z-index: 5;
  background: #f1f3f6;
  color: #40536f;
  font-size: 13px;
  font-weight: 700;
  white-space: nowrap;
}

.tx-table tbody tr:nth-child(even) td {
  background: #f7f8fa;
}

.tx-table tbody tr:hover td {
  background: #f1f6ff;
}

.action-cell {
  width: 56px;
  min-width: 56px;
  max-width: 56px;
  text-align: center !important;
}

.state-cell {
  text-align: center !important;
  color: #64748b;
  padding: 40px !important;
}

.error-cell {
  color: #ef4444;
}

.sticky-right {
  position: sticky;
  z-index: 3;
  box-shadow: -1px 0 0 #e8edf3;
}

.sticky-action {
  right: 0;
}

.col-status {
  width: 130px;
  min-width: 130px;
  right: 56px;
}

thead .sticky-right {
  z-index: 8;
}

.more-btn {
  width: 32px;
  height: 32px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  border: none;
  border-radius: 6px;
  background: transparent;
  color: #17365f;
  cursor: pointer;
}

.more-btn:hover {
  background: #e8eef7;
}

.col-code { width: 130px; }
.col-customer { width: 20%; }
.col-package { width: 15%; }
.col-amount { width: 12%; }
.col-method { width: 110px; }
.col-date { width: 15%; }
.col-note { width: 20%; }
</style>

