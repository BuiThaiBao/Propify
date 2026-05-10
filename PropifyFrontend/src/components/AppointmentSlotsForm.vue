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


      <!-- Rows: one day per row, multiple time slots horizontally -->
      <div class="space-y-4">
        <div
          v-for="(row, rowIndex) in appointmentRows"
          :key="rowIndex"
          class="rounded border border-slate-200 bg-slate-50 p-3"
        >
          <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
            <div class="sm:w-48">
              <label class="text-xs font-semibold text-slate-700">Chọn thứ <span class="text-red-500">*</span></label>
              <select
                v-model.number="row.day_of_week"
                @change="onDayChange(rowIndex)"
                class="mt-2 w-full px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent transition-colors"
              >
                <option value="">-- Chọn thứ --</option>
                <option v-for="day in getAvailableDays(rowIndex)" :key="day.value" :value="day.value">{{ day.label }}</option>
              </select>
            </div>

            <div class="flex-1">
              <label class="text-xs font-semibold text-slate-700">Khung giờ</label>
              <div class="mt-2 flex flex-col gap-2">
                <div v-for="(t, tIndex) in row.times" :key="tIndex" class="flex items-center gap-2">
                  <input v-model="t.start_time" type="time" @change="validateAll" class="w-36 px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent" />
                  <span class="text-sm text-slate-500">–</span>
                  <input v-model="t.end_time" type="time" @change="validateAll" class="w-36 px-3 py-2 text-sm border rounded-lg focus:outline-none focus:ring-2 focus:border-transparent" />
                  <button type="button" class="ml-2 h-8 w-8 rounded-full border border-red-300 bg-red-50 text-red-600 flex items-center justify-center" @click="removeTime(rowIndex, tIndex)">✕</button>
                </div>
              </div>
            </div>

            <div class="flex items-start">
              <button
                type="button"
                class="ml-auto inline-flex items-center gap-2 rounded-md border border-slate-300 bg-white px-3 py-1 text-sm hover:bg-slate-100"
                @click="addTime(rowIndex)"
              >
                <span class="text-lg leading-none">＋</span>
                <span>Thêm giờ</span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div v-if="duplicateError" class="rounded border border-red-300 bg-red-50 p-3 text-sm text-red-700">❌ {{ duplicateError }}</div>

      <div class="mt-2">
        <button
          type="button"
          class="w-full rounded border-2 border-dashed border-slate-300 bg-slate-50 py-2.5 text-sm font-medium text-slate-600"
          @click="addRow"
        >
          + Thêm khung giờ khác
        </button>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, computed } from 'vue';

const isOpen = ref(true);
const appointmentRows = ref([
  { day_of_week: '', times: [{ start_time: '08:00', end_time: '09:00' }] },
]);

const duplicateError = ref('');

const dayOfWeekOptions = [
  { label: 'Thứ 2 (T2)', value: 1 },
  { label: 'Thứ 3 (T3)', value: 2 },
  { label: 'Thứ 4 (T4)', value: 3 },
  { label: 'Thứ 5 (T5)', value: 4 },
  { label: 'Thứ 6 (T6)', value: 5 },
  { label: 'Thứ 7 (T7)', value: 6 },
  { label: 'Chủ nhật (CN)', value: 7 },
];

const usedDays = computed(() => appointmentRows.value.map((r) => r.day_of_week).filter(Boolean));

const getDayLabel = (dayOfWeek) => dayOfWeekOptions.find((d) => d.value === dayOfWeek)?.label || 'N/A';

const getAvailableDays = (rowIndex) => {
  const used = usedDays.value.slice();
  const current = appointmentRows.value[rowIndex]?.day_of_week;
  if (current) {
    const idx = used.indexOf(current);
    if (idx !== -1) used.splice(idx, 1);
  }
  return dayOfWeekOptions.filter((d) => !used.includes(d.value));
};

const addRow = () => {
  appointmentRows.value.push({ day_of_week: '', times: [{ start_time: '08:00', end_time: '09:00' }] });
};

const removeRow = (index) => {
  appointmentRows.value.splice(index, 1);
  validateAll();
};

const addTime = (rowIndex) => {
  appointmentRows.value[rowIndex].times.push({ start_time: '08:00', end_time: '09:00' });
};

const removeTime = (rowIndex, timeIndex) => {
  appointmentRows.value[rowIndex].times.splice(timeIndex, 1);
  validateAll();
};

const onDayChange = (rowIndex) => {
  duplicateError.value = '';
  const selected = appointmentRows.value[rowIndex].day_of_week;
  for (let i = 0; i < appointmentRows.value.length; i++) {
    if (i !== rowIndex && appointmentRows.value[i].day_of_week === selected && selected) {
      duplicateError.value = 'Thứ đã được chọn ở hàng khác.';
      appointmentRows.value[rowIndex].day_of_week = '';
      return;
    }
  }
};

const validateAll = () => {
  duplicateError.value = '';
  for (let r = 0; r < appointmentRows.value.length; r++) {
    const row = appointmentRows.value[r];
    for (let t = 0; t < row.times.length; t++) {
      const cur = row.times[t];
      if (!cur.start_time || !cur.end_time) continue;
      if (cur.start_time >= cur.end_time) {
        duplicateError.value = `Giờ kết thúc phải sau giờ bắt đầu (hàng ${r + 1}).`;
        return false;
      }
      for (let u = 0; u < row.times.length; u++) {
        if (u === t) continue;
        const o = row.times[u];
        if (!o.start_time || !o.end_time) continue;
        if (cur.start_time < o.end_time && cur.end_time > o.start_time) {
          duplicateError.value = `Khung giờ trùng trong cùng một thứ (hàng ${r + 1}).`;
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
    if (!row.day_of_week) return;
    row.times.forEach((t) => {
      if (t.start_time && t.end_time) out.push({ day_of_week: row.day_of_week, start_time: t.start_time, end_time: t.end_time });
    });
  });
  return out;
};

const isValid = computed(() => !duplicateError.value && getFormData().length > 0);

defineExpose({ getFormData, isValid, validateAll, appointmentRows, isOpen });

</script>
