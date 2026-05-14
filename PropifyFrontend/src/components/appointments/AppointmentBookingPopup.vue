<template>
  <Teleport to="body">
    <Transition name="popup-fade">
      <div v-if="visible" class="popup-overlay" @click.self="close">
        <div class="popup-container">
          <!-- Header -->
          <div class="popup-header">
            <div class="popup-header-left">
              <svg xmlns="http://www.w3.org/2000/svg" class="header-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              <h2>Đặt lịch xem nhà</h2>
            </div>
            <button class="close-btn" @click="close">
              <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          </div>

          <!-- Loading -->
          <div v-if="loading" class="popup-loading">
            <div class="spinner"></div>
            <p>Đang tải lịch hẹn...</p>
          </div>

          <!-- Error -->
          <div v-else-if="errorMsg" class="popup-error">
            <p>{{ errorMsg }}</p>
            <button class="retry-btn" @click="fetchSlots">Thử lại</button>
          </div>

          <!-- Content -->
          <div v-else class="popup-body">
            <!-- Viewing type -->
            <div class="view-type-card">
              <div class="view-type-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
              </div>
              <div>
                <p class="view-type-title">Xem trực tiếp</p>
                <p class="view-type-desc">Gặp trực tiếp người đăng tin để xem nhà đất</p>
              </div>
            </div>

            <!-- Date selector -->
            <div class="section-label">Chọn ngày</div>
            <div class="date-selector">
              <button class="date-nav-btn" @click="scrollDates(-1)" :disabled="dateScrollIndex <= 0">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
              </button>
              <div class="date-list">
                <button
                  v-for="(dateItem, idx) in visibleDates"
                  :key="dateItem.date"
                  class="date-item"
                  :class="{ active: selectedDateIndex === (dateScrollIndex + idx), today: dateItem.isToday }"
                  @click="selectDate(dateScrollIndex + idx)"
                >
                  <span class="date-day-label">{{ dateItem.dayLabel }}</span>
                  <span class="date-number">{{ dateItem.dayNumber }}</span>
                  <span class="date-month">Tháng {{ dateItem.month }}</span>
                </button>
              </div>
              <button class="date-nav-btn" @click="scrollDates(1)" :disabled="dateScrollIndex + maxVisibleDates >= allDates.length">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
              </button>
            </div>

            <!-- Time selector -->
            <div class="section-label">Chọn giờ</div>
            <div v-if="selectedDateSlots.length === 0" class="no-slots">Không có khung giờ nào cho ngày này</div>
            <div v-else class="time-selector">
              <button
                v-for="slot in selectedDateSlots"
                :key="slot.id"
                class="time-item"
                :class="{ active: selectedSlotId === slot.id }"
                @click="selectedSlotId = slot.id"
              >
                {{ formatTime(slot.start_time) }} - {{ formatTime(slot.end_time) }}
              </button>
            </div>

            <!-- Contact form -->
            <div class="section-label">Vui lòng cung cấp thông tin liên hệ của bạn</div>
            <div class="form-fields">
              <div class="input-group" :class="{ 'has-error': nameError }">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#94a3b8"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" /></svg>
                <input v-model="form.full_name" type="text" placeholder="Họ và tên *" @blur="validateName" />
              </div>
              <p v-if="nameError" class="field-error">{{ nameError }}</p>
              <div class="input-group" :class="{ 'has-error': phoneError }">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#94a3b8"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" /></svg>
                <input v-model="form.phone" type="tel" placeholder="Số điện thoại *" @blur="validatePhone(true)" />
              </div>
              <p v-if="phoneError" class="field-error">{{ phoneError }}</p>
              <div class="input-group" :class="{ 'has-error': emailError }">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#94a3b8"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" /></svg>
                <input v-model="form.email" type="email" placeholder="Email" @blur="validateEmail(true)" />
              </div>
              <p v-if="emailError" class="field-error">{{ emailError }}</p>
              <div class="input-group textarea-group">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#94a3b8"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                <textarea v-model="form.note" placeholder="Ghi chú" maxlength="1000" rows="3"></textarea>
                <span class="char-count">{{ form.note.length }}/1000</span>
              </div>
            </div>

            <!-- Submit button -->
            <button
              class="submit-btn"
              :disabled="!canSubmit || submitting"
              @click="showConfirm"
            >
              <svg v-if="!submitting" xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              <div v-if="submitting" class="spinner-sm"></div>
              {{ submitting ? 'Đang xử lý...' : 'Đặt lịch ngay' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Confirm Dialog -->
    <Transition name="popup-fade">
      <div v-if="confirmVisible" class="dialog-overlay" @click.self="confirmVisible = false">
        <div class="dialog-box">
          <button class="dialog-close" @click="confirmVisible = false">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
          <div class="dialog-icon confirm-icon">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#0ea5e9" stroke-width="1.5"><rect x="3" y="4" width="18" height="18" rx="3" /><path d="M3 10h18" /><path d="M8 2v4M16 2v4" /><path stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M9 15l2 2 4-4" /></svg>
          </div>
          <h3 class="dialog-title">Xác nhận lịch hẹn</h3>
          <p class="dialog-desc">Bạn có chắc chắn muốn đặt lịch hẹn?</p>
          <div class="dialog-actions">
            <button class="dialog-btn-cancel" @click="confirmVisible = false">Quay lại</button>
            <button class="dialog-btn-confirm" :disabled="submitting" @click="doSubmit">
              <div v-if="submitting" class="spinner-sm"></div>
              {{ submitting ? 'Đang xử lý...' : 'Xác nhận' }}
            </button>
          </div>
        </div>
      </div>
    </Transition>

    <!-- Result Notification -->
    <Transition name="popup-fade">
      <div v-if="resultVisible" class="dialog-overlay">
        <div class="dialog-box">
          <button v-if="!resultSuccess" class="dialog-close" @click="resultVisible = false">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
          </button>
          <div class="dialog-icon" :class="resultSuccess ? 'success-icon' : 'error-icon'">
            <svg v-if="resultSuccess" xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M8 12l3 3 5-5" /></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="48" height="48" fill="none" viewBox="0 0 24 24" stroke="#ef4444" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10" /><path d="M15 9l-6 6M9 9l6 6" /></svg>
          </div>
          <h3 class="dialog-title">{{ resultSuccess ? 'Đặt lịch thành công!' : 'Đặt lịch thất bại' }}</h3>
          <p v-if="!resultSuccess" class="dialog-desc">{{ resultMsg }}</p>
          <div v-if="!resultSuccess" class="dialog-actions">
            <button class="dialog-btn-confirm" @click="resultVisible = false">Đóng</button>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch } from 'vue';
import appointmentService from '@/services/appointmentService';

const props = defineProps({
  visible: { type: Boolean, default: false },
  listingId: { type: [Number, String], required: true },
});

const emit = defineEmits(['close', 'success']);

const loading = ref(false);
const errorMsg = ref('');
const slotsData = ref([]);
const selectedDateIndex = ref(0);
const selectedSlotId = ref(null);
const dateScrollIndex = ref(0);
const maxVisibleDates = 5;
const submitting = ref(false);
const confirmVisible = ref(false);
const resultVisible = ref(false);
const resultSuccess = ref(false);
const resultMsg = ref('');
const nameError = ref('');
const phoneError = ref('');
const emailError = ref('');
const phoneTouched = ref(false);
const emailTouched = ref(false);
let phoneValidateTimer = null;
let emailValidateTimer = null;

const form = ref({
  full_name: '',
  phone: '',
  email: '',
  note: '',
});

const dayLabels = ['Chủ nhật', 'Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7'];

const allDates = computed(() => {
  const today = new Date();
  today.setHours(0, 0, 0, 0);
  return slotsData.value.map(item => {
    const d = new Date(item.date + 'T00:00:00');
    const isToday = d.getTime() === today.getTime();
    return {
      date: item.date,
      slots: item.slots,
      dayLabel: isToday ? 'Hôm nay' : dayLabels[d.getDay()],
      dayNumber: d.getDate(),
      month: d.getMonth() + 1,
      isToday,
    };
  });
});

const visibleDates = computed(() => {
  return allDates.value.slice(dateScrollIndex.value, dateScrollIndex.value + maxVisibleDates);
});

const selectedDateSlots = computed(() => {
  if (selectedDateIndex.value < 0 || selectedDateIndex.value >= allDates.value.length) return [];
  return allDates.value[selectedDateIndex.value]?.slots || [];
});

function validateName() {
  nameError.value = form.value.full_name.trim() ? '' : 'Vui lòng nhập họ và tên.';
}

function validatePhone(force = false) {
  if (!phoneTouched.value && !force) return;
  const digits = form.value.phone.replace(/\D/g, '');
  phoneError.value = digits.length === 10 ? '' : 'Số điện thoại phải đủ 10 chữ số.';
}

function validateEmail(force = false) {
  if (!emailTouched.value && !force) return;
  const email = form.value.email.trim();
  if (!email) {
    emailError.value = '';
    return;
  }
  emailError.value = /^[^@\s]+@gmail\.com$/i.test(email) ? '' : 'Email phải có đuôi @gmail.com.';
}

function scrollDates(dir) {
  const newIdx = dateScrollIndex.value + dir;
  if (newIdx >= 0 && newIdx + maxVisibleDates <= allDates.value.length) {
    dateScrollIndex.value = newIdx;
  }
}

function selectDate(idx) {
  selectedDateIndex.value = idx;
  selectedSlotId.value = null;
}

function formatTime(time) {
  if (!time) return '';
  return time.substring(0, 5);
}

const canSubmit = computed(() => {
  return selectedSlotId.value && form.value.full_name.trim() && form.value.phone.trim();
});

async function fetchSlots() {
  loading.value = true;
  errorMsg.value = '';
  slotsData.value = [];
  selectedDateIndex.value = 0;
  selectedSlotId.value = null;
  dateScrollIndex.value = 0;
  try {
    const res = await appointmentService.getAppointmentSlots(props.listingId);
    slotsData.value = res.data.data || [];
    if (slotsData.value.length === 0) {
      errorMsg.value = 'Chưa có lịch hẹn nào cho bài đăng này.';
    }
  } catch (err) {
    const msg = err.response?.data?.message || 'Không thể tải lịch hẹn.';
    errorMsg.value = msg;
  } finally {
    loading.value = false;
  }
}

function showConfirm() {
  if (!canSubmit.value) return;
  confirmVisible.value = true;
}

async function doSubmit() {
  if (submitting.value) return;
  submitting.value = true;
  try {
    const selectedDate = allDates.value[selectedDateIndex.value]?.date;
    const res = await appointmentService.createBooking({
      slot_id: selectedSlotId.value,
      date: selectedDate,
      full_name: form.value.full_name,
      phone: form.value.phone,
      email: form.value.email || undefined,
      note: form.value.note || undefined,
    });
    confirmVisible.value = false;
    resultSuccess.value = true;
    resultMsg.value = '';
    resultVisible.value = true;
    // Tự tắt popup sau 1 giây khi thành công
    setTimeout(() => {
      resultVisible.value = false;
      emit('success', res.data);
      close();
    }, 1000);
  } catch (err) {
    confirmVisible.value = false;
    resultSuccess.value = false;
    resultMsg.value = err.response?.data?.message || 'Đặt lịch thất bại, vui lòng thử lại.';
    resultVisible.value = true;
  } finally {
    submitting.value = false;
  }
}

function close() {
  emit('close');
}

watch(() => props.visible, (val) => {
  if (val) {
    fetchSlots();
    form.value = { full_name: '', phone: '', email: '', note: '' };
    nameError.value = '';
    phoneError.value = '';
    emailError.value = '';
    phoneTouched.value = false;
    emailTouched.value = false;
    if (phoneValidateTimer) clearTimeout(phoneValidateTimer);
    if (emailValidateTimer) clearTimeout(emailValidateTimer);
  }
});

watch(() => form.value.phone, () => {
  phoneTouched.value = true;
  if (phoneValidateTimer) clearTimeout(phoneValidateTimer);
  phoneValidateTimer = setTimeout(() => validatePhone(), 350);
});

watch(() => form.value.email, () => {
  emailTouched.value = true;
  if (emailValidateTimer) clearTimeout(emailValidateTimer);
  emailValidateTimer = setTimeout(() => validateEmail(), 350);
});
</script>

<style scoped>
.popup-overlay {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.45);
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}
.popup-container {
  background: #fff; border-radius: 20px; width: 100%; max-width: 460px;
  max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  animation: slideUp 0.3s ease;
}
@keyframes slideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

.popup-header {
  display: flex; align-items: center; justify-content: space-between;
  padding: 20px 24px 16px; border-bottom: 1px solid #f1f5f9;
}
.popup-header-left { display: flex; align-items: center; gap: 10px; }
.popup-header-left h2 { font-size: 18px; font-weight: 700; color: #1e293b; margin: 0; }
.header-icon { width: 22px; height: 22px; color: #0ea5e9; }
.close-btn {
  background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;
  border-radius: 8px; transition: all 0.2s;
}
.close-btn:hover { background: #f1f5f9; color: #475569; }

.popup-body { padding: 16px 24px 24px; }

.popup-loading, .popup-error {
  padding: 48px 24px; text-align: center; color: #64748b;
}
.spinner {
  width: 36px; height: 36px; border: 3px solid #e2e8f0; border-top-color: #0ea5e9;
  border-radius: 50%; animation: spin 0.8s linear infinite; margin: 0 auto 12px;
}
.spinner-sm {
  width: 18px; height: 18px; border: 2px solid rgba(255,255,255,0.3); border-top-color: #fff;
  border-radius: 50%; animation: spin 0.8s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }
.retry-btn {
  margin-top: 12px; padding: 8px 20px; border-radius: 10px; border: 1px solid #e2e8f0;
  background: #fff; color: #0ea5e9; font-weight: 600; cursor: pointer;
}

.view-type-card {
  display: flex; align-items: center; gap: 12px;
  padding: 14px 16px; border-radius: 12px; border: 2px solid #0ea5e9;
  background: #f0f9ff; margin-bottom: 20px;
}
.view-type-icon {
  width: 36px; height: 36px; border-radius: 10px; background: #0ea5e9;
  display: flex; align-items: center; justify-content: center; flex-shrink: 0;
}
.view-type-icon svg { color: #fff; }
.view-type-title { font-weight: 700; font-size: 14px; color: #1e293b; margin: 0; }
.view-type-desc { font-size: 12px; color: #64748b; margin: 2px 0 0; }

.section-label { font-size: 14px; font-weight: 700; color: #1e293b; margin-bottom: 10px; }

.date-selector { display: flex; align-items: center; gap: 6px; margin-bottom: 20px; }
.date-nav-btn {
  background: none; border: none; cursor: pointer; color: #94a3b8; padding: 4px;
  border-radius: 8px; transition: 0.2s; flex-shrink: 0;
}
.date-nav-btn:hover:not(:disabled) { background: #f1f5f9; color: #475569; }
.date-nav-btn:disabled { opacity: 0.3; cursor: default; }
.date-list { display: flex; gap: 8px; flex: 1; overflow: hidden; }
.date-item {
  flex: 1; min-width: 0; display: flex; flex-direction: column; align-items: center;
  padding: 10px 4px; border-radius: 14px; border: 2px solid #e2e8f0;
  background: #fff; cursor: pointer; transition: all 0.2s; gap: 2px;
}
.date-item:hover { border-color: #0ea5e9; background: #f0f9ff; }
.date-item.active {
  background: #0ea5e9; border-color: #0ea5e9; color: #fff;
}
.date-item.active .date-day-label,
.date-item.active .date-number,
.date-item.active .date-month { color: #fff; }
.date-day-label { font-size: 11px; color: #64748b; font-weight: 500; }
.date-number { font-size: 20px; font-weight: 800; color: #1e293b; }
.date-month { font-size: 11px; color: #94a3b8; }

.time-selector { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 20px; }
.time-item {
  padding: 8px 16px; border-radius: 10px; border: 2px solid #e2e8f0;
  background: #fff; font-size: 13px; font-weight: 600; color: #475569;
  cursor: pointer; transition: all 0.2s;
}
.time-item:hover { border-color: #0ea5e9; color: #0ea5e9; }
.time-item.active { background: #0ea5e9; border-color: #0ea5e9; color: #fff; }
.no-slots {
  padding: 16px; text-align: center; color: #94a3b8; font-size: 13px;
  background: #f8fafc; border-radius: 10px; margin-bottom: 20px;
}

.form-fields { display: flex; flex-direction: column; gap: 12px; margin-bottom: 20px; }
.input-group {
  display: flex; align-items: center; gap: 10px;
  border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 0 14px;
  transition: border-color 0.2s; background: #fff;
}
.input-group:focus-within { border-color: #0ea5e9; }
.input-group.has-error { border-color: #ef4444; }
.input-group svg { flex-shrink: 0; }
.input-group input, .input-group textarea {
  flex: 1; border: none; outline: none; padding: 12px 0;
  font-size: 14px; color: #1e293b; background: transparent; font-family: inherit;
}
.input-group input::placeholder, .input-group textarea::placeholder { color: #94a3b8; }
.field-error { margin: -6px 0 2px; font-size: 12px; color: #ef4444; }
.textarea-group { align-items: flex-start; position: relative; }
.textarea-group svg { margin-top: 12px; }
.textarea-group textarea { resize: none; min-height: 60px; }
.char-count {
  position: absolute; bottom: 8px; right: 14px;
  font-size: 11px; color: #94a3b8;
}

.submit-btn {
  width: 100%; padding: 14px; border-radius: 12px; border: none;
  background: linear-gradient(135deg, #0ea5e9, #38bdf8);
  color: #fff; font-size: 15px; font-weight: 700; cursor: pointer;
  display: flex; align-items: center; justify-content: center; gap: 8px;
  transition: all 0.2s; box-shadow: 0 4px 14px rgba(14,165,233,0.3);
}
.submit-btn:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(14,165,233,0.4); }
.submit-btn:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.popup-fade-enter-active, .popup-fade-leave-active { transition: opacity 0.25s; }
.popup-fade-enter-from, .popup-fade-leave-to { opacity: 0; }

.popup-container::-webkit-scrollbar { width: 6px; }
.popup-container::-webkit-scrollbar-track { background: transparent; }
.popup-container::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }

/* Confirm / Result Dialog */
.dialog-overlay {
  position: fixed; inset: 0; z-index: 10000;
  background: rgba(0,0,0,0.5);
  display: flex; align-items: center; justify-content: center;
  padding: 16px;
}
.dialog-box {
  background: #fff; border-radius: 20px; padding: 32px 28px 24px;
  width: 100%; max-width: 380px; text-align: center;
  box-shadow: 0 20px 60px rgba(0,0,0,0.2);
  position: relative;
  animation: slideUp 0.25s ease;
}
.dialog-close {
  position: absolute; top: 16px; right: 16px;
  background: none; border: none; cursor: pointer; color: #94a3b8;
  padding: 4px; border-radius: 8px; transition: 0.2s;
}
.dialog-close:hover { background: #f1f5f9; color: #475569; }
.dialog-icon { margin: 0 auto 16px; display: flex; justify-content: center; }
.dialog-title {
  font-size: 18px; font-weight: 700; color: #1e293b; margin: 0 0 8px;
}
.dialog-desc {
  font-size: 14px; color: #64748b; margin: 0 0 24px; line-height: 1.5;
}
.dialog-actions {
  display: flex; gap: 12px;
}
.dialog-btn-cancel {
  flex: 1; padding: 12px; border-radius: 12px;
  border: 2px solid #0ea5e9; background: #fff;
  color: #0ea5e9; font-size: 15px; font-weight: 700;
  cursor: pointer; transition: all 0.2s;
}
.dialog-btn-cancel:hover { background: #f0f9ff; }
.dialog-btn-confirm {
  flex: 1; padding: 12px; border-radius: 12px; border: none;
  background: linear-gradient(135deg, #0ea5e9, #38bdf8);
  color: #fff; font-size: 15px; font-weight: 700;
  cursor: pointer; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 8px;
}
.dialog-btn-confirm:hover { transform: translateY(-1px); box-shadow: 0 4px 14px rgba(14,165,233,0.3); }
.dialog-btn-confirm:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }
.success-icon { color: #22c55e; }
.error-icon { color: #ef4444; }
</style>
