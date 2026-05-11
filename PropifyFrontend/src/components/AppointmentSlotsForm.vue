<template>
  <section class="rounded border border-slate-200 bg-white p-4">
    <button
      type="button"
      class="flex w-full items-center justify-between rounded bg-slate-50 px-4 py-3 hover:bg-slate-100 transition"
      @click="isOpen = !isOpen"
    >
      <span class="inline-flex items-center gap-2">
        <span class="text-lg">📅</span>
        <h2 class="font-semibold">Đặt lịch xem nhà</h2>
        <span class="text-sm opacity-70">ℹ️</span>
      </span>
      <span class="text-xl transition" :class="{ 'rotate-180': isOpen }">⌃</span>
    </button>

    <div v-if="isOpen" class="mt-4 space-y-4">
      <!-- Rows: one time slot per row, multiple days can be selected -->
      <div class="space-y-4">
        <div
          v-for="(row, rowIndex) in appointmentRows"
          :key="rowIndex"
          class="rounded border border-slate-200 bg-slate-50 p-3"
        >
          <div class="flex flex-col gap-4">
            <!-- Day Selection + Time Input (same row) -->
            <div class="flex items-end gap-3">
              <!-- Day Selection - Multi-select Dropdown -->
              <div class="relative w-56">
                <label class="text-xs font-semibold text-slate-700">Chọn thứ <span class="text-red-500">*</span></label>
                <button
                  type="button"
                  @click="dropdownOpenIndex = dropdownOpenIndex === rowIndex ? -1 : rowIndex"
                  @blur="dropdownOpenIndex = -1"
                  class="input mt-2 w-full text-left flex items-center justify-between"
                >
                  <span v-if="(row.selected_days || []).length === 0" class="text-slate-400 text-sm">Chọn thứ</span>
                  <span v-else class="text-slate-900 text-sm">{{ getSelectedDaysLabel(row.selected_days || []) }}</span>
                  <span class="text-slate-500">▾</span>
                </button>
                
                <div
                  v-if="dropdownOpenIndex === rowIndex"
                  class="day-dropdown absolute top-full left-0 right-0 mt-1 bg-white border border-slate-200 rounded-lg shadow-lg z-10"
                  @mousedown.prevent
                >
                  <label v-for="day in dayOfWeekOptions" :key="day.value" class="day-option">
                    <span>{{ day.label }}</span>
                    <input
                      type="checkbox"
                      :checked="(row.selected_days || []).includes(day.value)"
                      @change="(e) => toggleDay(rowIndex, day.value, e.target.checked)"
                    />
                  </label>
                </div>

                <p v-if="touchedRows[rowIndex] && (row.selected_days || []).length === 0" class="field-error mt-1 text-xs">Chọn thứ</p>
              </div>

              <!-- Time Input -->
              <div class="flex-1">
                <label class="text-xs font-semibold text-slate-700">Khung giờ</label>
                <div class="mt-2 flex items-center gap-2">
                  <input v-model="row.start_time" type="time" @change="syncTimeFields(rowIndex); validateAll()" class="w-32 px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent" />
                  <span class="text-sm text-slate-500">–</span>
                  <input v-model="row.end_time" type="time" @change="syncTimeFields(rowIndex); validateAll()" class="w-32 px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent" />
                </div>
              </div>

              <!-- Delete Button -->
              <button
                type="button"
                class="h-9 w-9 rounded-full border border-red-300 bg-red-50 text-red-600 flex items-center justify-center hover:bg-red-100 flex-shrink-0"
                @click="removeRow(rowIndex)"
                title="Xóa khung giờ này"
              >
                ✕
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="duplicateError" class="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700">❌ {{ duplicateError }}</div>

      <div class="mt-2">
        <button
          type="button"
          class="w-full rounded border-2 border-dashed border-slate-300 bg-slate-50 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
          @click="addRow"
        >
          + Thêm khung giờ khác
        </button>
      </div>
    </div>
  </section>
</template>

<style scoped>
.day-dropdown {
  display: flex;
  flex-direction: column;
  max-height: 200px;
  overflow-y: auto;
}

.day-option {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0.5rem 0.75rem;
  cursor: pointer;
  transition: background-color 0.15s;
}

.day-option:hover {
  background-color: #f1f5f9;
}

.day-option input[type="checkbox"] {
  width: 1rem;
  height: 1rem;
  cursor: pointer;
}

.input {
  display: flex;
  align-items: center;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  border: 1px solid #cbd5e1;
  border-radius: 0.5rem;
  transition: all 0.2s;
}

.input:focus {
  outline: none;
  ring: 2px;
  border-color: transparent;
}

.field-error {
  font-size: 0.875rem;
  color: #dc2626;
}
</style>

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
  return selectedDays.map(d => getDayLabel(d)).join(', ');
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
      row.selected_days.sort((a, b) => a - b); // Keep sorted
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
  
  // Mark all rows as touched
  for (let i = 0; i < appointmentRows.value.length; i++) {
    touchedRows.value[i] = true;
  }
  
  const hasTimeOverlap = (startA, endA, startB, endB) => {
    return startA < endB && endA > startB;
  };

  // Check each row
  for (let r = 0; r < appointmentRows.value.length; r++) {
    const row = appointmentRows.value[r];
    const startTime = normalizeTimeValue(row.start_time);
    const endTime = normalizeTimeValue(row.end_time);
    row.start_time = startTime;
    row.end_time = endTime;
    
    // Must select at least 1 day
    if ((row.selected_days || []).length === 0) {
      duplicateError.value = 'Vui lòng điền đủ thông tin cần thiết';
      return false;
    }
    
    // Validate time
    if (!startTime || !endTime) {
      duplicateError.value = 'Vui lòng điền đủ thông tin cần thiết';
      return false;
    }
    
    if (startTime >= endTime) {
      duplicateError.value = 'Vui lòng điền đủ thông tin cần thiết';
      return false;
    }
    
    // Check for time overlaps on shared days across rows
    for (let u = r + 1; u < appointmentRows.value.length; u++) {
      const other = appointmentRows.value[u];
      const otherStartTime = normalizeTimeValue(other.start_time);
      const otherEndTime = normalizeTimeValue(other.end_time);
      
      // Find common days
      const commonDays = (row.selected_days || []).filter((d) => (other.selected_days || []).includes(d));
      
      if (commonDays.length > 0) {
        // Check if times overlap
        if (hasTimeOverlap(startTime, endTime, otherStartTime, otherEndTime)) {
          const commonDaysStr = commonDays.map(d => getDayLabel(d)).join(', ');
          const displayStart = formatTimeForDisplay(startTime);
          const displayEnd = formatTimeForDisplay(endTime);
          const displayOtherStart = formatTimeForDisplay(otherStartTime);
          const displayOtherEnd = formatTimeForDisplay(otherEndTime);

          if (startTime === otherStartTime && endTime === otherEndTime) {
            duplicateError.value = `Khung giờ ${displayStart} - ${displayEnd} bị trùng ở ${commonDaysStr}.`;
          } else {
            duplicateError.value = `Khung giờ ${displayStart} - ${displayEnd} bị trùng với ${displayOtherStart} - ${displayOtherEnd} ở ${commonDaysStr}.`;
          }
          return false;
        }
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
    
    // Create an entry for each selected day
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
