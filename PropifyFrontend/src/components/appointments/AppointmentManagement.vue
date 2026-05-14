<template>
  <div>
    <h2 class="text-xl font-bold text-slate-800 mb-5">Đặt lịch xem nhà</h2>

    <!-- Tab header -->
    <div class="flex gap-2 mb-5">
      <button :class="['px-5 py-2 rounded-full text-sm font-semibold transition-all', activeView === 'my' ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-md shadow-sky-500/20' : 'bg-white text-slate-600 border border-slate-200 hover:border-sky-300 hover:text-sky-500']" @click="switchView('my')">Lịch của bạn</button>
      <button :class="['px-5 py-2 rounded-full text-sm font-semibold transition-all', activeView === 'received' ? 'bg-gradient-to-r from-sky-500 to-sky-600 text-white shadow-md shadow-sky-500/20' : 'bg-white text-slate-600 border border-slate-200 hover:border-sky-300 hover:text-sky-500']" @click="switchView('received')">Lịch của khách</button>
    </div>

    <!-- Status filter pills -->
    <div class="flex flex-wrap gap-2 mb-5">
      <button v-for="f in statusFilters" :key="f.value" :class="['inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-full text-xs font-semibold transition-all border', activeFilter === f.value ? 'bg-sky-50 border-sky-400 text-sky-600' : 'bg-white border-slate-200 text-slate-500 hover:border-sky-300 hover:text-sky-500']" @click="activeFilter = f.value">
        <span v-if="f.color" :class="['w-2 h-2 rounded-full', f.color]"></span>
        {{ f.label }} <span class="text-[10px] opacity-70">{{ countByStatus(f.value) }}</span>
      </button>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center justify-center py-16 text-slate-400">
      <div class="w-8 h-8 border-3 border-sky-500 border-t-transparent rounded-full animate-spin mb-3"></div>
      <p class="text-sm">Đang tải dữ liệu...</p>
    </div>

    <!-- Empty -->
    <div v-else-if="filteredBookings.length === 0" class="text-center py-16">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-16 h-16 mx-auto text-slate-200 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.2"><rect x="3" y="4" width="18" height="18" rx="2" /><path d="M16 2v4M8 2v4M3 10h18" /></svg>
      <p class="text-slate-400 text-sm">Không có lịch hẹn nào.</p>
    </div>

    <!-- Booking list -->
    <div v-else class="flex flex-col gap-3">
      <div v-for="booking in filteredBookings" :key="booking.id" class="bg-white border border-slate-200 rounded-xl p-5 hover:shadow-md hover:border-slate-300 transition-all">
        <div class="flex items-start justify-between gap-4">
          <div class="flex-1 min-w-0">
            <div class="flex items-center flex-wrap gap-2 mb-2">
              <h3 class="font-bold text-slate-800 text-[0.95rem]">
                <router-link :to="`/listings/${booking.listing_id}`" class="hover:text-sky-600 transition-colors">
                  {{ booking.listing_title || 'Bài đăng #' + booking.listing_id }}
                </router-link>
              </h3>
              <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold', statusBadge(booking.status).class]">{{ statusBadge(booking.status).label }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-500 mb-2">
              <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg> {{ booking.full_name }}</span>
              <span v-if="booking.phone" class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg> {{ booking.phone }}</span>
              <span v-if="booking.email" class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 01-2.06 0L2 7"/></svg> {{ booking.email }}</span>
            </div>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-slate-600 mb-1">
              <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/></svg> {{ formatDate(booking.meet_time) }}</span>
              <span class="inline-flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg> {{ formatTime(booking.start_time) }} - {{ formatTime(booking.end_time) }}</span>
            </div>
            <p v-if="booking.note" class="text-xs text-slate-400 mt-1 italic">{{ getNoteLabel(booking) }}: {{ getDisplayNote(booking) }}</p>
          </div>
          <div class="flex items-center gap-2 shrink-0">
            <template v-if="activeView === 'received' && booking.status === 'PENDING'">
              <button class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-sky-500 text-white text-xs font-semibold hover:bg-sky-600 transition-all shadow-sm" :disabled="actionLoading === booking.id" @click="handleApprove(booking)">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg> Xác nhận
              </button>
              <button class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-slate-200 text-slate-600 text-xs font-semibold hover:border-rose-300 hover:text-rose-500 transition-all" :disabled="actionLoading === booking.id" @click="handleReject(booking)">
                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg> Từ chối
              </button>
            </template>
            <button v-if="booking.status === 'APPROVED' || (activeView === 'my' && booking.status === 'PENDING')" class="inline-flex items-center gap-1.5 px-4 py-2 rounded-lg bg-white border border-rose-200 text-rose-500 text-xs font-semibold hover:bg-rose-50 hover:border-rose-300 transition-all" :disabled="actionLoading === booking.id" @click="handleCancel(booking)">
              <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path d="M18 6L6 18M6 6l12 12"/></svg> Hủy lịch
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- ===== POPUPS ===== -->
    <Teleport to="body">
      <!-- Xác nhận -->
      <Transition name="fade">
        <div v-if="approveVisible" class="dlg-overlay" @click.self="approveVisible = false">
          <div class="dlg-box">
            <button class="dlg-x" @click="approveVisible = false">&times;</button>
            <div class="dlg-icon"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#0ea5e9" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M3 10h18M8 2v4M16 2v4"/><path stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 15l2 2 4-4"/></svg></div>
            <h3 class="dlg-title">Xác nhận lịch hẹn</h3>
            <p class="dlg-desc">Bạn có chắc chắn muốn xác nhận lịch hẹn?</p>
            <div class="dlg-btns">
              <button class="btn-outline" @click="approveVisible = false">Quay lại</button>
              <button class="btn-primary" :disabled="dlgLoading" @click="doApprove">{{ dlgLoading ? 'Đang xử lý...' : 'Xác nhận' }}</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Từ chối -->
      <Transition name="fade">
        <div v-if="rejectVisible" class="dlg-overlay" @click.self="rejectVisible = false">
          <div class="dlg-box">
            <button class="dlg-x" @click="rejectVisible = false">&times;</button>
            <div class="dlg-icon"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#0ea5e9" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M3 10h18M8 2v4M16 2v4"/><circle cx="17" cy="17" r="5" fill="#ef4444" stroke="#ef4444"/><path stroke="#fff" stroke-width="2" stroke-linecap="round" d="M15.5 15.5l3 3M18.5 15.5l-3 3"/></svg></div>
            <h3 class="dlg-title">Từ chối lịch hẹn</h3>
            <p class="dlg-desc">Bạn có chắc chắn muốn từ chối lịch hẹn?</p>
            <select v-model="dlgReason" class="dlg-select"><option value="" disabled>Chọn lý do *</option><option v-for="r in rejectReasons" :key="r" :value="r">{{ r }}</option></select>
            <textarea v-if="dlgReason === 'Khác'" v-model="dlgCustomReason" class="dlg-textarea" rows="3" maxlength="255" placeholder="Nhập lý do cụ thể"></textarea>
            <div class="dlg-btns">
              <button class="btn-outline" @click="rejectVisible = false">Quay lại</button>
              <button class="btn-primary" :disabled="!canSubmitReason || dlgLoading" @click="doReject">{{ dlgLoading ? 'Đang xử lý...' : 'Từ chối' }}</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Hủy lịch -->
      <Transition name="fade">
        <div v-if="cancelVisible" class="dlg-overlay" @click.self="cancelVisible = false">
          <div class="dlg-box">
            <button class="dlg-x" @click="cancelVisible = false">&times;</button>
            <div class="dlg-icon"><svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#0ea5e9" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="3"/><path d="M3 10h18M8 2v4M16 2v4"/><circle cx="17" cy="17" r="5" fill="#ef4444" stroke="#ef4444"/><path stroke="#fff" stroke-width="2" stroke-linecap="round" d="M15.5 15.5l3 3M18.5 15.5l-3 3"/></svg></div>
            <h3 class="dlg-title">Hủy lịch hẹn</h3>
            <p class="dlg-desc">Bạn có chắc chắn muốn hủy lịch hẹn?</p>
            <select v-model="dlgReason" class="dlg-select"><option value="" disabled>Chọn lý do *</option><option v-for="r in cancelReasons" :key="r" :value="r">{{ r }}</option></select>
            <textarea v-if="dlgReason === 'Khác'" v-model="dlgCustomReason" class="dlg-textarea" rows="3" maxlength="255" placeholder="Nhập lý do cụ thể"></textarea>
            <div class="dlg-btns">
              <button class="btn-outline" @click="cancelVisible = false">Quay lại</button>
              <button class="btn-danger" :disabled="!canSubmitReason || dlgLoading" @click="doCancel">{{ dlgLoading ? 'Đang xử lý...' : 'Hủy lịch hẹn' }}</button>
            </div>
          </div>
        </div>
      </Transition>

      <!-- Toast -->
      <Transition name="fade">
        <div v-if="toastVisible" class="dlg-overlay" style="z-index:10001">
          <div class="dlg-box" style="padding:32px 28px">
            <svg v-if="toastOk" xmlns="http://www.w3.org/2000/svg" width="48" height="48" class="mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M8 12l3 3 5-5"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="48" height="48" class="mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="2"><circle cx="12" cy="12" r="10"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 9l-6 6M9 9l6 6"/></svg>
            <h3 class="dlg-title">{{ toastTitle }}</h3>
            <p v-if="toastMsg" class="dlg-desc" style="margin-bottom:0">{{ toastMsg }}</p>
          </div>
        </div>
      </Transition>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import appointmentService from '@/services/appointmentService';

const activeView = ref('my');
const activeFilter = ref('ALL');
const loading = ref(false);
const bookings = ref([]);
const actionLoading = ref(null);

const approveVisible = ref(false);
const rejectVisible = ref(false);
const cancelVisible = ref(false);
const dlgReason = ref('');
const dlgCustomReason = ref('');
const dlgLoading = ref(false);
const dlgBookingId = ref(null);

const toastVisible = ref(false);
const toastOk = ref(true);
const toastTitle = ref('');
const toastMsg = ref('');

const rejectReasons = [
  'Trùng lịch đột xuất',
  'Căn hộ đã được chốt cọc',
  'Căn hộ đang bảo trì/sửa chữa',
  'Thời gian hẹn không phù hợp',
  'Khác',
];
const cancelReasons = [
  'Thay đổi kế hoạch',
  'Việc bận đột xuất',
  'Lý do thời tiết/Sức khỏe',
  'Nhầm lẫn thông tin',
  'Khác',
];

const statusFilters = [
  { value: 'ALL', label: 'Tất cả', color: '' },
  { value: 'PENDING', label: 'Chờ xử lý', color: 'bg-amber-400' },
  { value: 'APPROVED', label: 'Đã xác nhận', color: 'bg-sky-400' },
  { value: 'COMPLETED', label: 'Hoàn thành', color: 'bg-emerald-400' },
  { value: 'CANCELLED_BY_POSTER', label: 'Chủ nhà hủy', color: 'bg-rose-400' },
  { value: 'CANCELLED_BY_VIEWER', label: 'Khách hủy', color: 'bg-slate-400' },
  { value: 'EXPIRED', label: 'Quá hạn', color: 'bg-slate-300' },
];

const filteredBookings = computed(() => {
  if (activeFilter.value === 'ALL') return bookings.value;
  return bookings.value.filter(b => b.status === activeFilter.value);
});

const canSubmitReason = computed(() => {
  if (!dlgReason.value) return false;
  if (dlgReason.value !== 'Khác') return true;
  return dlgCustomReason.value.trim().length > 0;
});

function countByStatus(s) {
  if (s === 'ALL') return bookings.value.length;
  return bookings.value.filter(b => b.status === s).length;
}

function statusBadge(s) {
  return { 
    PENDING: { label: 'Chờ xử lý', class: 'bg-amber-100 text-amber-700' }, 
    APPROVED: { label: 'Đã xác nhận', class: 'bg-sky-100 text-sky-700' }, 
    COMPLETED: { label: 'Hoàn thành', class: 'bg-emerald-100 text-emerald-700' }, 
    CANCELLED_BY_POSTER: { label: 'Chủ nhà hủy', class: 'bg-rose-100 text-rose-700' },
    CANCELLED_BY_VIEWER: { label: 'Khách hủy', class: 'bg-slate-100 text-slate-600' },
    EXPIRED: { label: 'Quá thời gian xác nhận', class: 'bg-slate-100 text-slate-500' }
  }[s] || { label: s, class: 'bg-slate-100 text-slate-600' };
}

function formatDate(t) { if (!t) return ''; const d = new Date(t); return `${String(d.getDate()).padStart(2,'0')}/${String(d.getMonth()+1).padStart(2,'0')}/${d.getFullYear()}`; }
function formatTime(t) { return t ? t.substring(0,5) : ''; }

function getNoteLabel(b) {
  if (!b.note) return '';
  if (b.note.includes('[Chủ nhà từ chối]')) return 'Lý do từ chối';
  if (b.note.includes('hủy]')) return 'Lý do hủy';
  return 'Ghi chú';
}
function getDisplayNote(b) { if (!b.note) return ''; const m = b.note.match(/\[.*?\]\s*(.*)/); return m ? m[1] : b.note; }

async function fetchBookings() {
  loading.value = true;
  try { const r = activeView.value === 'my' ? await appointmentService.getMyBookings() : await appointmentService.getReceivedBookings(); bookings.value = r.data.data || []; }
  catch { bookings.value = []; }
  finally { loading.value = false; }
}

function switchView(v) { if (activeView.value === v) return; activeView.value = v; activeFilter.value = 'ALL'; fetchBookings(); }

function handleApprove(b) { dlgBookingId.value = b.id; approveVisible.value = true; }
function handleReject(b) { dlgBookingId.value = b.id; dlgReason.value = ''; dlgCustomReason.value = ''; rejectVisible.value = true; }
function handleCancel(b) { dlgBookingId.value = b.id; dlgReason.value = ''; dlgCustomReason.value = ''; cancelVisible.value = true; }

function getFinalReason() {
  return dlgReason.value === 'Khác' ? dlgCustomReason.value.trim() : dlgReason.value;
}

async function doApprove() {
  dlgLoading.value = true;
  try { await appointmentService.updateBookingStatus(dlgBookingId.value, 'APPROVED'); approveVisible.value = false; toast(true, 'Xác nhận thành công!'); await fetchBookings(); }
  catch (e) { approveVisible.value = false; toast(false, 'Thất bại', e.response?.data?.message || 'Không thể xác nhận.'); }
  finally { dlgLoading.value = false; }
}

async function doReject() {
  if (!canSubmitReason.value) return; dlgLoading.value = true;
  try { await appointmentService.updateBookingStatus(dlgBookingId.value, 'CANCELLED_BY_POSTER', getFinalReason()); rejectVisible.value = false; toast(true, 'Đã từ chối lịch hẹn'); await fetchBookings(); }
  catch (e) { rejectVisible.value = false; toast(false, 'Thất bại', e.response?.data?.message || 'Có lỗi xảy ra.'); }
  finally { dlgLoading.value = false; }
}

async function doCancel() {
  if (!canSubmitReason.value) return; dlgLoading.value = true;
  try { await appointmentService.cancelBooking(dlgBookingId.value, getFinalReason()); cancelVisible.value = false; toast(true, 'Đã hủy lịch hẹn'); await fetchBookings(); }
  catch (e) { cancelVisible.value = false; toast(false, 'Thất bại', e.response?.data?.message || 'Có lỗi xảy ra.'); }
  finally { dlgLoading.value = false; }
}

function toast(ok, title, msg = '') { toastOk.value = ok; toastTitle.value = title; toastMsg.value = msg; toastVisible.value = true; setTimeout(() => { toastVisible.value = false; }, ok ? 1200 : 3000); }

onMounted(() => { fetchBookings(); });
</script>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.2s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.dlg-overlay { position: fixed; inset: 0; z-index: 10000; background: rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center; padding: 16px; }
.dlg-box { background: #fff; border-radius: 20px; padding: 28px 24px 22px; width: 100%; max-width: 380px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.18); position: relative; animation: slideUp 0.25s ease; }
.dlg-x { position: absolute; top: 12px; right: 14px; background: none; border: none; cursor: pointer; font-size: 22px; color: #94a3b8; line-height: 1; }
.dlg-x:hover { color: #475569; }
.dlg-icon { margin: 0 auto 14px; display: flex; justify-content: center; }
.dlg-title { font-size: 17px; font-weight: 700; color: #1e293b; margin: 0 0 6px; }
.dlg-desc { font-size: 13.5px; color: #64748b; margin: 0 0 18px; }
.dlg-select { width: 100%; padding: 11px 36px 11px 14px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 13.5px; color: #475569; outline: none; cursor: pointer; appearance: none; background: #fff url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 24 24' fill='none' stroke='%2394a3b8' stroke-width='2'%3E%3Cpath d='m6 9 6 6 6-6'/%3E%3C/svg%3E") no-repeat right 14px center; transition: border-color 0.2s; margin-bottom: 18px; }
.dlg-select:focus { border-color: #0ea5e9; }
.dlg-textarea { width: 100%; padding: 11px 12px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-size: 13.5px; color: #475569; outline: none; resize: vertical; min-height: 76px; margin: -6px 0 18px; transition: border-color 0.2s; }
.dlg-textarea:focus { border-color: #0ea5e9; }
.dlg-btns { display: flex; gap: 12px; }
.btn-outline { flex: 1; padding: 11px; border-radius: 12px; border: 2px solid #0ea5e9; background: #fff; color: #0ea5e9; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.btn-outline:hover { background: #f0f9ff; }
.btn-primary { flex: 1; padding: 11px; border-radius: 12px; border: none; background: linear-gradient(135deg, #0ea5e9, #38bdf8); color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.btn-primary:hover { box-shadow: 0 4px 14px rgba(14,165,233,0.3); }
.btn-primary:disabled, .btn-danger:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-danger { flex: 1; padding: 11px; border-radius: 12px; border: none; background: linear-gradient(135deg, #ef4444, #f87171); color: #fff; font-size: 14px; font-weight: 700; cursor: pointer; transition: 0.2s; }
.btn-danger:hover { box-shadow: 0 4px 14px rgba(239,68,68,0.3); }
</style>
