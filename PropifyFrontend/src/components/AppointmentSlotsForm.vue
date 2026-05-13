<template>
  <section class="rounded-[14px] border border-[#e6edf5] bg-white p-[14px] shadow-[0_1px_2px_rgba(15,23,42,0.03)]">
    <button
      type="button"
      class="flex w-full items-center justify-between gap-3 rounded-[10px] text-[#0f172a] transition hover:text-[#0284c7]"
      @click="isOpen = !isOpen"
    >
      <span class="inline-flex items-center gap-2 text-[13px] font-bold">
        <svg class="h-5 w-5 shrink-0 text-[#0ea5e9]" viewBox="0 0 24 24" fill="none" aria-hidden="true">
          <rect x="4" y="5" width="16" height="16" rx="3" stroke="currentColor" stroke-width="2" />
          <path d="M8 3v4M16 3v4M4 10h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" />
        </svg>
        <span>Đặt lịch xem nhà</span>
      </span>
      <span
        class="h-[9px] w-[9px] rotate-45 border-b-2 border-r-2 border-[#0f172a] transition"
        :class="{ 'rotate-[225deg]': isOpen }"
        aria-hidden="true"
      ></span>
    </button>

    <div v-if="isOpen" class="mt-4 grid gap-[14px]">
      <div class="grid gap-3">
        <div
          v-for="(row, rowIndex) in appointmentRows"
          :key="rowIndex"
          class="rounded-[10px] border border-[#d9e6f4] bg-[#f8fbff] p-[14px]"
        >
          <div class="grid items-end gap-[14px] md:grid-cols-[minmax(210px,250px)_1fr_42px]">
            <div class="relative">
              <label class="appointment-label required">Chọn thứ</label>
              <button
                type="button"
                class="mt-[10px] flex h-11 w-full items-center justify-between gap-2.5 rounded-[10px] border border-[#dbe7f4] bg-[#f3f7fc] px-[14px] text-left text-sm text-[#0f172a] outline-none transition focus:border-[#38bdf8] focus:bg-white focus:shadow-[0_0_0_3px_rgba(56,189,248,0.14)]"
                @click="dropdownOpenIndex = dropdownOpenIndex === rowIndex ? -1 : rowIndex"
                @blur="dropdownOpenIndex = -1"
              >
                <span v-if="(row.selected_days || []).length === 0" class="text-[#94a3b8]">Chọn thứ</span>
                <span v-else class="truncate">{{ getSelectedDaysLabel(row.selected_days || []) }}</span>
                <span class="h-[9px] w-[9px] rotate-45 border-b-2 border-r-2 border-[#0f172a]" aria-hidden="true"></span>
              </button>

              <div
                v-if="dropdownOpenIndex === rowIndex"
                class="absolute left-0 right-0 top-[calc(100%+6px)] z-20 flex max-h-[220px] flex-col overflow-y-auto rounded-xl border border-[#dbe7f4] bg-white shadow-[0_16px_32px_rgba(15,23,42,0.12)]"
                @mousedown.prevent
              >
                <label
                  v-for="day in dayOfWeekOptions"
                  :key="day.value"
                  class="flex cursor-pointer items-center justify-between gap-2.5 px-3 py-2.5 text-[13px] text-[#0f172a] transition hover:bg-[#f0f9ff]"
                >
                  <span>{{ day.label }}</span>
                  <input
                    type="checkbox"
                    class="h-4 w-4 accent-[#0ea5e9]"
                    :checked="(row.selected_days || []).includes(day.value)"
                    @change="(e) => toggleDay(rowIndex, day.value, e.target.checked)"
                  />
                </label>
              </div>

              <p v-if="touchedRows[rowIndex] && (row.selected_days || []).length === 0" class="field-error">
                Vui lòng chọn thứ
              </p>
            </div>

            <div>
              <label class="appointment-label">Khung giờ</label>
              <div class="mt-[10px] flex flex-wrap items-center gap-2.5">
                <input
                  v-model="row.start_time"
                  type="time"
                  class="h-11 w-36 rounded-[10px] border border-[#dbe7f4] bg-[#f3f7fc] px-[14px] text-sm text-[#0f172a] outline-none transition focus:border-[#38bdf8] focus:bg-white focus:shadow-[0_0_0_3px_rgba(56,189,248,0.14)]"
                  @change="syncTimeFields(rowIndex); validateAll()"
                />
                <span class="text-sm text-[#64748b]">-</span>
                <input
                  v-model="row.end_time"
                  type="time"
                  class="h-11 w-36 rounded-[10px] border border-[#dbe7f4] bg-[#f3f7fc] px-[14px] text-sm text-[#0f172a] outline-none transition focus:border-[#38bdf8] focus:bg-white focus:shadow-[0_0_0_3px_rgba(56,189,248,0.14)]"
                  @change="syncTimeFields(rowIndex); validateAll()"
                />
              </div>
            </div>

            <button
              type="button"
              class="delete-slot-btn inline-flex h-[42px] w-[42px] items-center justify-center justify-self-end rounded-full border border-[#fda4af] bg-[#fff8f8] text-[#ef4444] transition hover:-translate-y-px hover:bg-[#fff1f2] md:justify-self-auto"
              title="Xóa khung giờ này"
              @click="removeRow(rowIndex)"
            >
              <span aria-hidden="true"></span>
            </button>
          </div>
        </div>
      </div>

      <div
        v-if="duplicateError"
        class="rounded-[10px] border border-[#fecaca] bg-[#fef2f2] px-3 py-2.5 text-xs font-medium text-[#dc2626]"
      >
        {{ duplicateError }}
      </div>

      <button
        type="button"
        class="min-h-[50px] w-full rounded-[10px] border-2 border-dashed border-[#bfd2e8] bg-[#fbfdff] text-sm font-semibold text-[#475569] transition hover:border-[#7cc5f3] hover:bg-[#f0f9ff] hover:text-[#0284c7]"
        @click="addRow"
      >
        + Thêm khung giờ khác
      </button>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue';

const isOpen = ref(true);
const dropdownOpenIndex = ref(-1);
const appointmentRows = ref([
  { start_time: '08:00', end_time: '09:00', selected_days: [] },
]);

const touchedRows = ref([false]);
const duplicateError = ref('');

const dayOfWeekOptions = [
  { label: 'Thứ 2', value: 1 },
  { label: 'Thứ 3', value: 2 },
  { label: 'Thứ 4', value: 3 },
  { label: 'Thứ 5', value: 4 },
  { label: 'Thứ 6', value: 5 },
  { label: 'Thứ 7', value: 6 },
  { label: 'CN', value: 7 },
];

const getDayLabel = (dayOfWeek) => dayOfWeekOptions.find((d) => d.value === dayOfWeek)?.label || 'N/A';

const formatTimeForDisplay = (value) => {
  if (!value) return '';

  const match = String(value).trim().match(/^(\d{1,2}):(\d{2})/);
  if (!match) return String(value).trim();

  const hours24 = Number(match[1]);
  const minutes = match[2];
  const suffix = hours24 >= 12 ? 'PM' : 'AM';
  const hours12 = hours24 % 12 || 12;

  return `${String(hours12).padStart(2, '0')}:${minutes} ${suffix}`;
};

const normalizeTimeValue = (value) => {
  if (!value) return '';
  const text = String(value).trim();
  const match = text.match(/^(\d{1,2}):(\d{2})/);
  if (!match) return text;
  const hours = String(Number(match[1])).padStart(2, '0');
  return `${hours}:${match[2]}`;
};

const syncTimeFields = (rowIndex) => {
  const row = appointmentRows.value[rowIndex];
  if (!row) return;
  row.start_time = normalizeTimeValue(row.start_time);
  row.end_time = normalizeTimeValue(row.end_time);
};

const getSelectedDaysLabel = (selectedDays) => {
  if (selectedDays.length === 0) return '';
  if (selectedDays.length === dayOfWeekOptions.length) return 'Tất cả các thứ';
  return selectedDays.map((day) => getDayLabel(day)).join(', ');
};

const addRow = () => {
  appointmentRows.value.push({ start_time: '08:00', end_time: '09:00', selected_days: [] });
  touchedRows.value.push(false);
};

const removeRow = (index) => {
  appointmentRows.value.splice(index, 1);
  touchedRows.value.splice(index, 1);
  validateAll();
};

const toggleDay = (rowIndex, dayValue, isChecked) => {
  touchedRows.value[rowIndex] = true;
  const row = appointmentRows.value[rowIndex];
  if (!row.selected_days) {
    row.selected_days = [];
  }

  if (isChecked) {
    if (!row.selected_days.includes(dayValue)) {
      row.selected_days.push(dayValue);
      row.selected_days.sort((a, b) => a - b);
    }
  } else {
    const idx = row.selected_days.indexOf(dayValue);
    if (idx !== -1) {
      row.selected_days.splice(idx, 1);
    }
  }
  duplicateError.value = '';
};

const validateAll = () => {
  duplicateError.value = '';

  for (let i = 0; i < appointmentRows.value.length; i += 1) {
    touchedRows.value[i] = true;
  }

  const hasTimeOverlap = (startA, endA, startB, endB) => startA < endB && endA > startB;

  for (let r = 0; r < appointmentRows.value.length; r += 1) {
    const row = appointmentRows.value[r];
    const startTime = normalizeTimeValue(row.start_time);
    const endTime = normalizeTimeValue(row.end_time);
    row.start_time = startTime;
    row.end_time = endTime;

    if ((row.selected_days || []).length === 0 || !startTime || !endTime || startTime >= endTime) {
      duplicateError.value = 'Vui lòng điền đủ thông tin cần thiết';
      return false;
    }

    for (let u = r + 1; u < appointmentRows.value.length; u += 1) {
      const other = appointmentRows.value[u];
      const otherStartTime = normalizeTimeValue(other.start_time);
      const otherEndTime = normalizeTimeValue(other.end_time);
      const commonDays = (row.selected_days || []).filter((day) => (other.selected_days || []).includes(day));

      if (commonDays.length > 0 && hasTimeOverlap(startTime, endTime, otherStartTime, otherEndTime)) {
        const commonDaysStr = commonDays.map((day) => getDayLabel(day)).join(', ');
        const displayStart = formatTimeForDisplay(startTime);
        const displayEnd = formatTimeForDisplay(endTime);
        const displayOtherStart = formatTimeForDisplay(otherStartTime);
        const displayOtherEnd = formatTimeForDisplay(otherEndTime);

        duplicateError.value = startTime === otherStartTime && endTime === otherEndTime
          ? `Khung giờ ${displayStart} - ${displayEnd} bị trùng ở ${commonDaysStr}.`
          : `Khung giờ ${displayStart} - ${displayEnd} bị trùng với ${displayOtherStart} - ${displayOtherEnd} ở ${commonDaysStr}.`;
        return false;
      }
    }
  }

  return true;
};

const getFormData = () => {
  const out = [];
  appointmentRows.value.forEach((row) => {
    const startTime = normalizeTimeValue(row.start_time);
    const endTime = normalizeTimeValue(row.end_time);
    if (!startTime || !endTime || (row.selected_days || []).length === 0) return;

    (row.selected_days || []).forEach((day) => {
      out.push({
        day_of_week: day,
        start_time: startTime,
        end_time: endTime,
      });
    });
  });
  return out;
};

const isValid = computed(() => !duplicateError.value && getFormData().length > 0);

const resetForm = () => {
  appointmentRows.value = [
    { start_time: '08:00', end_time: '09:00', selected_days: [] },
  ];
  touchedRows.value = [false];
  duplicateError.value = '';
};

defineExpose({ getFormData, isValid, validateAll, appointmentRows, isOpen, resetForm, duplicateError });
</script>

<style scoped>
.appointment-card {
  border: 1px solid #e6edf5;
  border-radius: 14px;
  background: #fff;
  padding: 14px;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
}

.appointment-toggle {
  width: 100%;
  min-height: 58px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  border-radius: 10px;
  background: #f8fbff;
  padding: 0 14px;
  color: #0f172a;
  transition: background 0.18s ease, border-color 0.18s ease;
}

.appointment-toggle:hover {
  background: #f2f7fd;
}

.appointment-title {
  display: inline-flex;
  align-items: center;
  gap: 9px;
  font-size: 13px;
  font-weight: 700;
}

.appointment-icon {
  position: relative;
  width: 20px;
  height: 20px;
  border-radius: 5px;
  background: linear-gradient(180deg, #dbeafe 0%, #ede9fe 100%);
  border: 1px solid #c7d2fe;
}

.appointment-icon::before {
  content: "";
  position: absolute;
  inset: 0 0 auto;
  height: 5px;
  border-radius: 5px 5px 0 0;
  background: #3b82f6;
}

.appointment-icon span {
  position: absolute;
  left: 5px;
  right: 5px;
  top: 9px;
  height: 6px;
  background:
    linear-gradient(#94a3b8 0 0) 0 0 / 3px 3px no-repeat,
    linear-gradient(#94a3b8 0 0) 7px 0 / 3px 3px no-repeat,
    linear-gradient(#94a3b8 0 0) 0 5px / 3px 3px no-repeat,
    linear-gradient(#94a3b8 0 0) 7px 5px / 3px 3px no-repeat;
}

.appointment-info {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 16px;
  height: 16px;
  border-radius: 4px;
  background: #93c5fd;
  color: #fff;
  font-size: 11px;
  font-weight: 700;
}

.chevron {
  width: 8px;
  height: 8px;
  border-right: 2px solid #0f172a;
  border-bottom: 2px solid #0f172a;
  transform: rotate(45deg);
  transition: transform 0.18s ease;
  flex: 0 0 auto;
}

.chevron-open {
  transform: rotate(225deg);
}

.chevron.small {
  width: 7px;
  height: 7px;
  border-width: 1.8px;
  border-color: #0f172a;
}

.appointment-body {
  margin-top: 16px;
  display: grid;
  gap: 14px;
}

.slot-list {
  display: grid;
  gap: 12px;
}

.slot-row {
  border: 1px solid #d9e6f4;
  border-radius: 10px;
  background: #f8fbff;
  padding: 14px;
}

.slot-grid {
  display: grid;
  grid-template-columns: minmax(210px, 250px) 1fr 42px;
  gap: 14px;
  align-items: end;
}

.day-picker {
  position: relative;
}

.appointment-label {
  display: inline-block;
  font-size: 12px;
  font-weight: 600;
  color: #0f172a;
}

.appointment-label.required::after {
  content: " *";
  color: #ef4444;
  font-weight: 700;
}

.day-trigger,
.time-input {
  height: 44px;
  border: 1px solid #dbe7f4;
  border-radius: 10px;
  background: #f3f7fc;
  color: #0f172a;
  font-size: 14px;
  outline: none;
  transition: border-color 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.day-trigger {
  margin-top: 10px;
  width: 100%;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 0 14px;
  text-align: left;
}

.day-trigger:focus,
.time-input:focus {
  border-color: #38bdf8;
  box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.14);
  background: #fff;
}

.placeholder {
  color: #94a3b8;
}

.selected-days {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.day-dropdown {
  position: absolute;
  left: 0;
  right: 0;
  top: calc(100% + 6px);
  z-index: 20;
  display: flex;
  max-height: 220px;
  flex-direction: column;
  overflow-y: auto;
  border: 1px solid #dbe7f4;
  border-radius: 12px;
  background: #fff;
  box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
}

.day-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 10px;
  padding: 10px 12px;
  color: #0f172a;
  font-size: 13px;
  cursor: pointer;
  transition: background 0.15s ease;
}

.day-option:hover {
  background: #f0f9ff;
}

.day-option input[type="checkbox"] {
  width: 16px;
  height: 16px;
  accent-color: #0ea5e9;
  cursor: pointer;
}

.time-row {
  margin-top: 10px;
  display: flex;
  align-items: center;
  gap: 10px;
}

.time-input {
  width: 144px;
  padding: 0 14px;
}

.time-separator {
  color: #64748b;
  font-size: 14px;
}

.appointment-error {
  border: 1px solid #fecaca;
  border-radius: 10px;
  background: #fef2f2;
  padding: 10px 12px;
  color: #dc2626;
  font-size: 12px;
  font-weight: 500;
}

.add-slot-btn {
  width: 100%;
  min-height: 50px;
  border: 2px dashed #bfd2e8;
  border-radius: 10px;
  background: #fbfdff;
  color: #475569;
  font-size: 14px;
  font-weight: 600;
  transition: border-color 0.18s ease, background 0.18s ease, color 0.18s ease;
}

.add-slot-btn:hover {
  border-color: #7cc5f3;
  background: #f0f9ff;
  color: #0284c7;
}

.field-error {
  margin-top: 5px;
  color: #ef4444;
  font-size: 11px;
  font-weight: 500;
}

.delete-slot-btn span {
  position: relative;
  width: 16px;
  height: 16px;
  display: block;
}

.delete-slot-btn span::before,
.delete-slot-btn span::after {
  content: "";
  position: absolute;
  left: 50%;
  top: 50%;
  width: 16px;
  height: 2px;
  border-radius: 999px;
  background: currentColor;
  transform-origin: center;
}

.delete-slot-btn span::before {
  transform: translate(-50%, -50%) rotate(45deg);
}

.delete-slot-btn span::after {
  transform: translate(-50%, -50%) rotate(-45deg);
}

@media (max-width: 720px) {
  .slot-grid {
    grid-template-columns: 1fr;
  }

  .delete-slot-btn {
    justify-self: end;
  }

  .time-row {
    flex-wrap: wrap;
  }

  .time-input {
    flex: 1 1 130px;
  }
}
</style>
