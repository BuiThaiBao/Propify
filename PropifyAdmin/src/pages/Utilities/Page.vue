<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import {
  Check,
  ChevronLeft,
  ChevronRight,
  MoreHorizontal,
  Plus,
  X,
  Eye,
  EyeOff,
  Edit3,
  Trash2,
} from 'lucide-vue-next'
import PageHeader from '@/components/shared/PageHeader.vue'
import { amenityService } from '@/services/amenityService'

const amenities = ref([])
const loading = ref(false)
const saving = ref(false)
const error = ref('')
const activeFilter = ref('all')
const page = ref(1)
const perPage = ref(25)
const toast = ref({ show: false, message: '', type: 'success' })

/* ── Dropdown menu per row ── */
const openMenuId = ref(null)

function toggleMenu(id) {
  openMenuId.value = openMenuId.value === id ? null : id
}

function closeMenus() {
  openMenuId.value = null
}

/* ── Modal ── */
const modal = reactive({
  open: false,
  mode: 'create', // 'create' | 'edit'
  amenity: null,
})

const form = reactive({
  name: '',
  description: '',
  is_active: true,
})

/* ── Filters ── */
const filters = computed(() => [
  { value: 'all', label: 'Tất cả', count: amenities.value.length },
  {
    value: 'active',
    label: 'Kích hoạt',
    count: amenities.value.filter((a) => a.is_active !== false && a.is_active !== 0).length,
  },
  {
    value: 'hidden',
    label: 'Đã ẩn',
    count: amenities.value.filter((a) => a.is_active === false || a.is_active === 0).length,
  },
])

const filteredAmenities = computed(() => {
  return amenities.value.filter((amenity) => {
    if (activeFilter.value === 'active') return amenity.is_active !== false && amenity.is_active !== 0
    if (activeFilter.value === 'hidden') return amenity.is_active === false || amenity.is_active === 0
    return true
  })
})

const pagedAmenities = computed(() => {
  const start = (page.value - 1) * perPage.value
  return filteredAmenities.value.slice(start, start + perPage.value)
})

const totalPages = computed(() =>
  Math.max(1, Math.ceil(filteredAmenities.value.length / perPage.value)),
)

onMounted(loadAmenities)

async function loadAmenities() {
  loading.value = true
  error.value = ''
  try {
    const response = await amenityService.getAmenities()
    amenities.value = response.data?.data || []
    page.value = 1
  } catch (err) {
    error.value = err.response?.data?.message || 'Không thể tải danh sách tiện ích.'
    amenities.value = []
  } finally {
    loading.value = false
  }
}

function openCreateModal() {
  modal.mode = 'create'
  modal.amenity = null
  form.name = ''
  form.description = ''
  form.is_active = true
  modal.open = true
}

function openEditModal(amenity) {
  closeMenus()
  modal.mode = 'edit'
  modal.amenity = amenity
  form.name = amenity.name || ''
  form.description = amenity.description || ''
  form.is_active = amenity.is_active !== false && amenity.is_active !== 0
  modal.open = true
}

function closeModal() {
  if (saving.value) return
  modal.open = false
}

async function submitForm() {
  if (!form.name.trim()) {
    error.value = 'Tên tiện ích là bắt buộc.'
    return
  }

  saving.value = true
  error.value = ''

  const payload = {
    name: form.name.trim(),
    description: form.description.trim() || null,
    is_active: form.is_active,
  }

  try {
    if (modal.mode === 'edit' && modal.amenity) {
      await amenityService.updateAmenity(modal.amenity.id, payload)
      showToast('Cập nhật tiện ích thành công!')
    } else {
      await amenityService.createAmenity(payload)
      showToast('Thêm tiện ích mới thành công!')
    }
    modal.open = false
    await loadAmenities()
  } catch (err) {
    const validationErrors = err.response?.data?.errors
    error.value = validationErrors
      ? Object.values(validationErrors).flat().join(' ')
      : err.response?.data?.message || 'Không thể lưu tiện ích.'
    showToast(error.value, 'error')
  } finally {
    saving.value = false
  }
}

function setFilter(value) {
  activeFilter.value = value
  page.value = 1
}

function changePage(p) {
  page.value = Math.min(Math.max(p, 1), totalPages.value)
}

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => {
    toast.value.show = false
  }, 3000)
}
</script>

<template>
  <div class="utilities-page" @click="closeMenus">
    <!-- Toast -->
    <Transition name="toast">
      <div v-if="toast.show" class="toast" :class="`toast--${toast.type}`">
        <Check v-if="toast.type === 'success'" :size="16" />
        <X v-else :size="16" />
        <span>{{ toast.message }}</span>
      </div>
    </Transition>

    <!-- Header -->
    <div class="page-top">
      <div>
        <h1 class="page-title">Tiện ích tin đăng</h1>
        <p class="page-desc">Quản lý các tiện ích hiển thị cho bất động sản</p>
      </div>
      <button class="btn-add" id="btn-add-amenity" type="button" @click="openCreateModal">
        <Plus :size="16" />
        Thêm tiện ích
      </button>
    </div>

    <!-- Filter tabs -->
    <div class="filter-bar">
      <div class="filter-tabs">
        <button
          v-for="f in filters"
          :key="f.value"
          type="button"
          class="filter-tab"
          :class="{ 'filter-tab--active': activeFilter === f.value }"
          @click="setFilter(f.value)"
        >
          <span v-if="f.value === 'active'" class="dot dot--green"></span>
          <span v-if="f.value === 'hidden'" class="dot dot--red"></span>
          {{ f.label }} {{ f.count }}
        </button>
      </div>
    </div>

    <!-- Error -->
    <p v-if="error" class="alert">{{ error }}</p>

    <!-- Table -->
    <section class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th class="col-id">ID</th>
            <th class="col-name">Tiện ích</th>
            <th class="col-desc">Mô tả</th>
            <th class="col-status">Trạng thái</th>
            <th class="col-actions"></th>
          </tr>
        </thead>
        <tbody>
          <!-- Loading -->
          <template v-if="loading">
            <tr v-for="n in 5" :key="`sk-${n}`">
              <td><div class="skel skel--id"></div></td>
              <td><div class="skel skel--text"></div></td>
              <td><div class="skel skel--text-long"></div></td>
              <td><div class="skel skel--pill"></div></td>
              <td><div class="skel skel--dot"></div></td>
            </tr>
          </template>

          <!-- Empty -->
          <tr v-else-if="pagedAmenities.length === 0">
            <td colspan="5" class="empty-cell">Không có tiện ích nào phù hợp.</td>
          </tr>

          <!-- Rows -->
          <tr
            v-for="amenity in pagedAmenities"
            v-else
            :key="amenity.id"
            class="row"
          >
            <td class="cell-id">{{ amenity.id }}</td>
            <td class="cell-name">{{ amenity.name }}</td>
            <td class="cell-desc">{{ amenity.description || '—' }}</td>
            <td>
              <span
                class="status-badge"
                :class="amenity.is_active !== false && amenity.is_active !== 0 ? 'status--active' : 'status--hidden'"
              >
                <Eye v-if="amenity.is_active !== false && amenity.is_active !== 0" :size="13" />
                <EyeOff v-else :size="13" />
                {{ amenity.is_active !== false && amenity.is_active !== 0 ? 'Đang hiển thị' : 'Đã ẩn' }}
              </span>
            </td>
            <td class="cell-actions">
              <div class="menu-anchor">
                <button
                  class="btn-dots"
                  type="button"
                  @click.stop="toggleMenu(amenity.id)"
                >
                  <MoreHorizontal :size="18" />
                </button>
                <Transition name="menu">
                  <div v-if="openMenuId === amenity.id" class="dropdown-menu">
                    <button class="menu-item" @click="openEditModal(amenity)">
                      <Edit3 :size="14" />
                      Chỉnh sửa
                    </button>
                    <button class="menu-item menu-item--danger">
                      <Trash2 :size="14" />
                      Xoá
                    </button>
                  </div>
                </Transition>
              </div>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Footer / Pagination -->
      <footer class="table-footer">
        <span class="footer-text">Tất cả {{ filteredAmenities.length }} dòng</span>
        <div class="pager">
          <button class="pager-btn" :disabled="page <= 1" @click="changePage(page - 1)">
            <ChevronLeft :size="16" />
          </button>
          <span class="pager-current">{{ page }}</span>
          <button class="pager-btn" :disabled="page >= totalPages" @click="changePage(page + 1)">
            <ChevronRight :size="16" />
          </button>
          <select v-model.number="perPage" class="pager-select" @change="page = 1">
            <option :value="10">10</option>
            <option :value="25">25</option>
            <option :value="50">50</option>
          </select>
        </div>
      </footer>
    </section>

    <!-- ────── Modal / Popup ────── -->
    <Transition name="overlay">
      <div v-if="modal.open" class="modal-overlay" @click.self="closeModal">
        <Transition name="popup" appear>
          <div class="modal-box">
            <!-- Header -->
            <div class="modal-header">
              <h2>{{ modal.mode === 'edit' ? 'Chỉnh sửa tiện ích' : 'Thêm mới tiện ích' }}</h2>
              <button class="modal-close" type="button" @click="closeModal">
                <X :size="20" />
              </button>
            </div>

            <!-- Body -->
            <form class="modal-body" @submit.prevent="submitForm">
              <label class="field">
                <span class="field-label">Tên tiện ích <b>*</b></span>
                <input
                  v-model.trim="form.name"
                  type="text"
                  class="field-input"
                  maxlength="255"
                  placeholder="Nhập thông tin"
                />
              </label>

              <label class="field">
                <span class="field-label">Mô tả</span>
                <input
                  v-model.trim="form.description"
                  type="text"
                  class="field-input"
                  maxlength="500"
                  placeholder="Nhập thông tin"
                />
              </label>

              <div class="field-row">
                <span class="field-label">Trạng thái</span>
                <label class="toggle" for="status-toggle">
                  <input
                    id="status-toggle"
                    v-model="form.is_active"
                    type="checkbox"
                    class="toggle-input"
                  />
                  <span class="toggle-track"></span>
                </label>
              </div>

              <div class="modal-actions">
                <button class="btn-cancel" type="button" :disabled="saving" @click="closeModal">
                  Quay lại
                </button>
                <button class="btn-submit" type="submit" :disabled="saving">
                  <template v-if="saving">
                    <span class="spinner"></span>
                    Đang lưu...
                  </template>
                  <template v-else>
                    {{ modal.mode === 'edit' ? 'Lưu thay đổi' : 'Thêm mới' }}
                  </template>
                </button>
              </div>
            </form>
          </div>
        </Transition>
      </div>
    </Transition>
  </div>
</template>

<style scoped>
/* ══════════════════════════════
   Variables
   ══════════════════════════════ */
.utilities-page {
  --primary: #18a8e6;
  --primary-light: #e8f7fd;
  --primary-dark: #1291c9;
  --text: #1e293b;
  --text-secondary: #64748b;
  --text-muted: #94a3b8;
  --border: #e8edf3;
  --bg-page: #f8fafc;
  --bg-card: #ffffff;
  --radius: 12px;
  --radius-sm: 8px;
  --shadow-sm: 0 1px 3px rgba(15, 23, 42, 0.04);
  --shadow-md: 0 4px 20px rgba(15, 23, 42, 0.06);
  min-height: calc(100vh - 96px);
}

/* ══════════════════════════════
   Header
   ══════════════════════════════ */
.page-top {
  display: flex;
  align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 24px;
}

.page-title {
  margin: 0;
  font-size: 24px;
  font-weight: 800;
  color: var(--text);
  line-height: 1.3;
}

.page-desc {
  margin: 4px 0 0;
  font-size: 14px;
  color: var(--text-secondary);
}

.btn-add {
  display: inline-flex;
  align-items: center;
  gap: 7px;
  padding: 10px 20px;
  border: none;
  border-radius: 24px;
  background: var(--primary);
  color: #fff;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  white-space: nowrap;
  transition: background 0.2s, box-shadow 0.2s, transform 0.15s;
  box-shadow: 0 2px 10px rgba(24, 168, 230, 0.25);
}

.btn-add:hover {
  background: var(--primary-dark);
  box-shadow: 0 4px 16px rgba(24, 168, 230, 0.35);
  transform: translateY(-1px);
}

/* ══════════════════════════════
   Filter tabs
   ══════════════════════════════ */
.filter-bar {
  margin-bottom: 20px;
}

.filter-tabs {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-wrap: wrap;
}

.filter-tab {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 36px;
  padding: 0 16px;
  border: 1px solid var(--border);
  border-radius: 20px;
  background: var(--bg-card);
  color: var(--text-secondary);
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.2s;
}

.filter-tab:hover {
  border-color: #cbd5e1;
}

.filter-tab--active {
  background: var(--primary-light);
  border-color: #b5e4f7;
  color: var(--primary);
}

.dot {
  width: 7px;
  height: 7px;
  border-radius: 50%;
  flex-shrink: 0;
}

.dot--green {
  background: #22c55e;
}

.dot--red {
  background: #ef4444;
}

/* ══════════════════════════════
   Alert
   ══════════════════════════════ */
.alert {
  margin: 0 0 16px;
  padding: 12px 16px;
  border-radius: var(--radius-sm);
  background: #fef2f2;
  border: 1px solid #fecdd3;
  color: #be123c;
  font-size: 14px;
  font-weight: 600;
}

/* ══════════════════════════════
   Table
   ══════════════════════════════ */
.table-wrap {
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.data-table {
  width: 100%;
  border-collapse: collapse;
}

.data-table th,
.data-table td {
  text-align: left;
  vertical-align: middle;
  padding: 0 20px;
  font-size: 14px;
}

.data-table th {
  height: 48px;
  background: #fafbfc;
  color: var(--text-secondary);
  font-size: 13px;
  font-weight: 700;
  border-bottom: 1px solid var(--border);
}

.data-table td {
  height: 64px;
  border-bottom: 1px solid #f3f5f8;
  color: var(--text);
}

.data-table tbody tr:last-child td {
  border-bottom: none;
}

.data-table tbody tr {
  transition: background 0.15s;
}

.data-table tbody tr:hover {
  background: #f8fbfe;
}

.col-id { width: 90px; }
.col-name { width: 180px; }
.col-desc { }
.col-status { width: 170px; }
.col-actions { width: 60px; text-align: center; }

.cell-id {
  color: var(--primary);
  font-weight: 700;
}

.cell-name {
  font-weight: 600;
  color: var(--text);
}

.cell-desc {
  color: var(--text-secondary);
}

/* ── Status badge ── */
.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 14px;
  border-radius: 20px;
  font-size: 13px;
  font-weight: 600;
  white-space: nowrap;
}

.status--active {
  background: #ecfdf5;
  color: #16a34a;
}

.status--hidden {
  background: #fef2f2;
  color: #dc2626;
}

/* ── Dots menu ── */
.cell-actions {
  text-align: center;
  position: relative;
}

.menu-anchor {
  position: relative;
  display: inline-block;
}

.btn-dots {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}

.btn-dots:hover {
  background: #f1f5f9;
  color: var(--text-secondary);
}

.dropdown-menu {
  position: absolute;
  right: 0;
  top: calc(100% + 4px);
  z-index: 30;
  min-width: 160px;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: 0 8px 30px rgba(15, 23, 42, 0.1);
  padding: 6px;
  display: flex;
  flex-direction: column;
}

.menu-item {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 10px 14px;
  border: none;
  border-radius: var(--radius-sm);
  background: transparent;
  color: var(--text);
  font-size: 14px;
  font-weight: 500;
  cursor: pointer;
  transition: background 0.12s;
}

.menu-item:hover {
  background: #f8fafc;
}

.menu-item--danger {
  color: #ef4444;
}

.menu-item--danger:hover {
  background: #fef2f2;
}

.menu-enter-active {
  animation: menu-in 0.18s ease;
}

.menu-leave-active {
  animation: menu-out 0.12s ease forwards;
}

@keyframes menu-in {
  from { opacity: 0; transform: translateY(-6px) scale(0.96); }
  to { opacity: 1; transform: translateY(0) scale(1); }
}

@keyframes menu-out {
  to { opacity: 0; transform: translateY(-4px) scale(0.96); }
}

/* ── Skeleton ── */
.skel {
  border-radius: 6px;
  background: linear-gradient(90deg, #f1f5f9 25%, #e8edf3 50%, #f1f5f9 75%);
  background-size: 200% 100%;
  animation: shimmer 1.4s infinite;
}

.skel--id { width: 48px; height: 18px; }
.skel--text { width: 100px; height: 18px; }
.skel--text-long { width: 180px; height: 18px; }
.skel--pill { width: 110px; height: 26px; border-radius: 20px; }
.skel--dot { width: 26px; height: 26px; border-radius: 50%; margin: 0 auto; }

@keyframes shimmer {
  to { background-position: -200% 0; }
}

/* ── Empty ── */
.empty-cell {
  text-align: center !important;
  padding: 48px 20px !important;
  color: var(--text-muted);
  height: auto !important;
}

/* ══════════════════════════════
   Table footer / pagination
   ══════════════════════════════ */
.table-footer {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 16px;
  height: 56px;
  padding: 0 20px;
  border-top: 1px solid #f3f5f8;
}

.footer-text {
  font-size: 13px;
  color: var(--text-muted);
  margin-right: auto;
}

.pager {
  display: flex;
  align-items: center;
  gap: 8px;
}

.pager-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 34px;
  height: 34px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  background: var(--bg-card);
  color: var(--text-secondary);
  cursor: pointer;
  transition: all 0.15s;
}

.pager-btn:hover:not(:disabled) {
  background: #f8fafc;
  border-color: #cbd5e1;
}

.pager-btn:disabled {
  opacity: 0.35;
  cursor: not-allowed;
}

.pager-current {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 34px;
  height: 34px;
  padding: 0 10px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 14px;
  font-weight: 700;
  color: var(--text);
}

.pager-select {
  height: 34px;
  padding: 0 8px;
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  font-size: 13px;
  color: var(--text-secondary);
  background: var(--bg-card);
  cursor: pointer;
}

/* ══════════════════════════════
   Modal / Popup
   ══════════════════════════════ */
.modal-overlay {
  position: fixed;
  inset: 0;
  z-index: 60;
  display: flex;
  align-items: center;
  justify-content: center;
  background: rgba(15, 23, 42, 0.35);
  backdrop-filter: blur(3px);
}

.modal-box {
  width: min(460px, 92vw);
  background: var(--bg-card);
  border-radius: 20px;
  box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
  overflow: hidden;
}

.modal-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 24px 28px 0;
}

.modal-header h2 {
  margin: 0;
  font-size: 20px;
  font-weight: 800;
  color: var(--text);
}

.modal-close {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border: none;
  border-radius: 10px;
  background: transparent;
  color: var(--text-muted);
  cursor: pointer;
  transition: background 0.15s, color 0.15s;
}

.modal-close:hover {
  background: #f1f5f9;
  color: var(--text-secondary);
}

.modal-body {
  display: flex;
  flex-direction: column;
  gap: 20px;
  padding: 24px 28px 28px;
}

/* ── Form fields ── */
.field {
  display: flex;
  flex-direction: column;
  gap: 8px;
}

.field-label {
  font-size: 14px;
  font-weight: 600;
  color: var(--text);
}

.field-label b {
  color: #ef4444;
}

.field-input {
  width: 100%;
  height: 46px;
  padding: 0 14px;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  font-size: 14px;
  color: var(--text);
  background: var(--bg-card);
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s;
}

.field-input::placeholder {
  color: var(--text-muted);
}

.field-input:focus {
  border-color: var(--primary);
  box-shadow: 0 0 0 3px rgba(24, 168, 230, 0.1);
}

/* ── Toggle row ── */
.field-row {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 4px 0;
  border-top: 1px solid #f3f5f8;
  padding-top: 16px;
}

.toggle {
  position: relative;
  cursor: pointer;
}

.toggle-input {
  position: absolute;
  opacity: 0;
  width: 0;
  height: 0;
}

.toggle-track {
  display: block;
  width: 44px;
  height: 24px;
  border-radius: 12px;
  background: #cbd5e1;
  position: relative;
  transition: background 0.25s;
}

.toggle-track::after {
  content: '';
  position: absolute;
  top: 3px;
  left: 3px;
  width: 18px;
  height: 18px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0, 0, 0, 0.15);
  transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
}

.toggle-input:checked + .toggle-track {
  background: var(--primary);
}

.toggle-input:checked + .toggle-track::after {
  transform: translateX(20px);
}

/* ── Modal action buttons ── */
.modal-actions {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 12px;
  margin-top: 8px;
}

.btn-cancel {
  height: 46px;
  border: 1.5px solid var(--primary);
  border-radius: 10px;
  background: var(--bg-card);
  color: var(--primary);
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.15s;
}

.btn-cancel:hover {
  background: var(--primary-light);
}

.btn-cancel:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

.btn-submit {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  height: 46px;
  border: none;
  border-radius: 10px;
  background: var(--primary);
  color: #fff;
  font-size: 15px;
  font-weight: 700;
  cursor: pointer;
  transition: background 0.2s, box-shadow 0.2s;
}

.btn-submit:hover {
  background: var(--primary-dark);
  box-shadow: 0 4px 14px rgba(24, 168, 230, 0.3);
}

.btn-submit:disabled {
  opacity: 0.6;
  cursor: not-allowed;
}

.spinner {
  display: inline-block;
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.6s linear infinite;
}

@keyframes spin {
  to { transform: rotate(360deg); }
}

/* ── Modal transitions ── */
.overlay-enter-active {
  transition: opacity 0.25s ease;
}

.overlay-leave-active {
  transition: opacity 0.2s ease;
}

.overlay-enter-from,
.overlay-leave-to {
  opacity: 0;
}

.popup-enter-active {
  animation: popup-in 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.popup-leave-active {
  animation: popup-out 0.2s ease forwards;
}

@keyframes popup-in {
  from {
    opacity: 0;
    transform: scale(0.9) translateY(16px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}

@keyframes popup-out {
  to {
    opacity: 0;
    transform: scale(0.95) translateY(8px);
  }
}

/* ══════════════════════════════
   Toast
   ══════════════════════════════ */
.toast {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 100;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 14px 20px;
  border-radius: var(--radius);
  font-size: 14px;
  font-weight: 600;
  color: #fff;
  box-shadow: 0 8px 30px rgba(0, 0, 0, 0.12);
}

.toast--success {
  background: #16a34a;
}

.toast--error {
  background: #dc2626;
}

.toast-enter-active {
  animation: toast-in 0.35s ease;
}

.toast-leave-active {
  animation: toast-out 0.25s ease forwards;
}

@keyframes toast-in {
  from { opacity: 0; transform: translateX(30px); }
  to { opacity: 1; transform: translateX(0); }
}

@keyframes toast-out {
  to { opacity: 0; transform: translateX(30px); }
}

/* ══════════════════════════════
   Responsive
   ══════════════════════════════ */
@media (max-width: 768px) {
  .page-top {
    flex-direction: column;
    gap: 16px;
  }

  .data-table {
    min-width: 640px;
  }

  .table-wrap {
    overflow-x: auto;
  }

  .modal-actions {
    grid-template-columns: 1fr;
  }

  .table-footer {
    flex-direction: column;
    height: auto;
    padding: 12px 16px;
    gap: 10px;
  }
}
</style>
