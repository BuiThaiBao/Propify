<template>
  <div class="form-page">
    <!-- Page header -->
    <div class="form-page-header">
      <button class="back-btn" @click="router.push({ name: 'Packages' })" id="back-btn-create">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M19 12H5M12 5l-7 7 7 7" />
        </svg>
        Quay lại
      </button>
      <div class="form-page-title-wrap">
        <h1 class="form-page-title">Thêm gói tin mới</h1>
        <p class="form-page-subtitle">Tạo mới cấu hình một gói dịch vụ cho người dùng</p>
      </div>
    </div>

    <!-- Alert: Error -->
    <div v-if="apiError" class="alert alert--error" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      {{ apiError }}
    </div>

    <!-- Alert: Success -->
    <div v-if="success" class="alert alert--success" role="alert">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
      Tạo gói tin thành công! Đang chuyển hướng...
    </div>

    <form @submit.prevent="handleSubmit" class="form-body" novalidate>
      <!-- Section 1: Thông tin cơ bản -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-number">1</div>
          <div>
            <h2 class="form-card-title">Thông tin cơ bản</h2>
            <p class="form-card-desc">Tên, định danh và nhãn hiển thị của gói tin</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="field-grid field-grid--2">
            <div class="field">
              <label class="field-label" for="pkg-name">Tên gói <span class="required">*</span></label>
              <input
                id="pkg-name"
                v-model="form.name"
                class="field-input"
                :class="{ 'field-input--error': errors.name }"
                placeholder="Ví dụ: Gói Cơ bản"
              />
              <span v-if="errors.name" class="field-error">{{ errors.name }}</span>
            </div>
            <div class="field">
              <label class="field-label" for="pkg-slug">Định danh (Slug) <span class="required">*</span></label>
              <input
                id="pkg-slug"
                v-model="form.slug"
                class="field-input"
                :class="{ 'field-input--error': errors.slug }"
                placeholder="Ví dụ: basic, standard, premium"
              />
              <span v-if="errors.slug" class="field-error">{{ errors.slug }}</span>
            </div>
            <div class="field">
              <label class="field-label" for="pkg-badge">Nhãn (Badge)</label>
              <input
                id="pkg-badge"
                v-model="form.badge"
                class="field-input"
                placeholder="HOT, VIP, BEST..."
              />
              <span class="field-hint">Nhãn hiển thị trên card gói tin</span>
            </div>
            <div class="field">
              <label class="field-label" for="pkg-color">Màu chủ đạo</label>
              <div class="color-input-wrap">
                <input
                  id="pkg-color"
                  v-model="form.color"
                  class="field-input"
                  placeholder="#3b82f6"
                />
                <input
                  type="color"
                  :value="form.color || '#3b82f6'"
                  class="color-picker"
                  @input="form.color = $event.target.value"
                />
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 2: Thông số phân phối -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-number">2</div>
          <div>
            <h2 class="form-card-title">Thông số phân phối & Xếp hạng</h2>
            <p class="form-card-desc">Quyết định mức độ ưu tiên hiển thị và đặc quyền của gói</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="field-grid field-grid--3">
            <div class="field">
              <label class="field-label" for="pkg-quota">Lượt hiển thị/ngày <span class="required">*</span></label>
              <input
                id="pkg-quota"
                v-model.number="form.daily_quota"
                type="number"
                min="0"
                class="field-input"
                :class="{ 'field-input--error': errors.daily_quota }"
                placeholder="100"
              />
              <span v-if="errors.daily_quota" class="field-error">{{ errors.daily_quota }}</span>
              <span v-else class="field-hint">Số lần tin đăng được phân phối mỗi ngày</span>
            </div>
            <div class="field">
              <label class="field-label" for="pkg-priority">Tầng ưu tiên <span class="required">*</span></label>
              <input
                id="pkg-priority"
                v-model.number="form.priority"
                type="number"
                min="1"
                class="field-input"
                :class="{ 'field-input--error': errors.priority }"
                placeholder="1"
              />
              <span v-if="errors.priority" class="field-error">{{ errors.priority }}</span>
              <span v-else class="field-hint">1 = Thấp nhất, càng cao càng ưu tiên</span>
            </div>
            <div class="field">
              <label class="field-label" for="pkg-multiplier">Hệ số điểm <span class="required">*</span></label>
              <input
                id="pkg-multiplier"
                v-model.number="form.multiplier"
                type="number"
                step="0.1"
                min="1"
                class="field-input"
                :class="{ 'field-input--error': errors.multiplier }"
                placeholder="1.0"
              />
              <span v-if="errors.multiplier" class="field-error">{{ errors.multiplier }}</span>
              <span v-else class="field-hint">Nhân tố tăng điểm hiển thị</span>
            </div>
            <div class="field">
              <label class="field-label" for="pkg-decay">Tốc độ tụt hạng <span class="required">*</span></label>
              <input
                id="pkg-decay"
                v-model.number="form.decay_rate"
                type="number"
                step="0.001"
                min="0"
                max="1"
                class="field-input"
                :class="{ 'field-input--error': errors.decay_rate }"
                placeholder="0.005"
              />
              <span v-if="errors.decay_rate" class="field-error">{{ errors.decay_rate }}</span>
              <span v-else class="field-hint">Tốc độ giảm điểm theo thời gian (0 - 1)</span>
            </div>
          </div>
        </div>
      </div>

      <!-- Section 3: Bảng giá -->
      <div class="form-card">
        <div class="form-card-header">
          <div class="form-card-number">3</div>
          <div>
            <h2 class="form-card-title">Cấu hình bảng giá</h2>
            <p class="form-card-desc">Nhập giá gốc 1 ngày — hệ thống tự tính giá các thời hạn khác</p>
          </div>
        </div>
        <div class="form-card-body">
          <div class="pricing-layout">
            <!-- Price per day -->
            <div class="pricing-left">
              <div class="field">
                <label class="field-label" for="pkg-price">Giá 1 ngày (VNĐ) <span class="required">*</span></label>
                <input
                  id="pkg-price"
                  v-model.number="form.price"
                  type="number"
                  min="0"
                  class="field-input field-input--lg"
                  :class="{ 'field-input--error': errors.price }"
                  placeholder="50000"
                />
                <span v-if="errors.price" class="field-error">{{ errors.price }}</span>
                <span v-else class="field-hint">Giá bằng 0 = gói miễn phí</span>
              </div>

              <div v-if="form.price > 0" class="price-preview-simple">
                <div class="price-preview-row" v-for="days in [7, 30, 90]" :key="days">
                  <span>{{ days }} ngày</span>
                  <strong>{{ Number(form.price * days).toLocaleString('vi-VN') }}₫</strong>
                </div>
              </div>
            </div>

            <!-- Duration table -->
            <div class="pricing-right">
              <div class="duration-panel">
                <div class="duration-panel-header">
                  <span>Bảng giá theo thời hạn</span>
                  <span v-if="errors.active_durations" class="field-error">{{ errors.active_durations }}</span>
                </div>

                <div class="duration-add-row">
                  <input
                    v-model.number="newDuration"
                    type="number"
                    min="1"
                    max="3650"
                    class="field-input"
                    placeholder="Số ngày (1-3650)"
                    @keydown.enter.prevent="addDuration"
                    id="new-duration-input"
                  />
                  <button type="button" class="btn-add-duration" @click="addDuration" id="add-duration-btn">
                    + Thêm
                  </button>
                </div>

                <div v-if="visibleDurations.length === 0" class="duration-empty">
                  Chưa có thời hạn nào — hãy thêm bên trên
                </div>

                <div v-else class="duration-table-wrap">
                  <table class="duration-table">
                    <thead>
                      <tr>
                        <th>Bật</th>
                        <th>Thời hạn</th>
                        <th class="text-right">Thành tiền</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="days in visibleDurations" :key="days">
                        <td>
                          <input
                            type="checkbox"
                            :id="`dur-${days}`"
                            v-model="form.active_durations"
                            :value="days"
                            class="check"
                          />
                        </td>
                        <td>
                          <label :for="`dur-${days}`" class="dur-label">{{ days }} ngày</label>
                        </td>
                        <td class="text-right">
                          <span v-if="form.active_durations.includes(days)" class="dur-price">
                            {{ Number((form.price || 0) * days).toLocaleString('vi-VN') }}₫
                          </span>
                          <span v-else class="dur-off">Tạm tắt</span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Actions -->
      <div class="form-actions">
        <button type="button" class="btn-cancel" @click="router.push({ name: 'Packages' })" id="cancel-create-btn">
          Hủy bỏ
        </button>
        <button type="submit" class="btn-submit" :disabled="loading" id="submit-create-btn">
          <span v-if="loading" class="btn-spinner"></span>
          {{ loading ? 'Đang lưu...' : 'Tạo gói tin' }}
        </button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { usePackageApi } from '@/composables/usePackageApi'
import { normalizeDurationDays } from '@/utils/packageFormatters'

const router = useRouter()
const {
  createPackage,
  fetchDurationOptions,
  createDurationOption,
  loading,
  error: apiError,
} = usePackageApi()

const success = ref(false)
const newDuration = ref(null)
const durationOptions = ref([])

const form = reactive({
  name: '',
  slug: '',
  priority: null,
  multiplier: null,
  daily_quota: null,
  decay_rate: null,
  badge: '',
  color: '',
  price: null,
  active_durations: [],
})

const errors = reactive({})

const sortedDurations = computed(() => normalizeDurationDays(form.active_durations))

const visibleDurations = computed(() =>
  normalizeDurationDays([
    ...durationOptions.value.map((o) => Number(o.days)),
    ...form.active_durations.map((d) => Number(d)),
  ]),
)

onMounted(async () => {
  try {
    const res = await fetchDurationOptions()
    durationOptions.value = res?.data || []
  } catch {
    durationOptions.value = []
  }
})

async function addDuration() {
  const days = Number(newDuration.value)
  errors.active_durations = ''

  if (!Number.isInteger(days) || days < 1 || days > 3650) {
    errors.active_durations = 'Số ngày phải từ 1 đến 3650'
    return
  }

  if (!durationOptions.value.some((o) => Number(o.days) === days)) {
    const res = await createDurationOption({ days })
    if (res?.data) durationOptions.value.push(res.data)
  }

  if (!form.active_durations.includes(days)) {
    form.active_durations.push(days)
  }

  newDuration.value = null
}

function validate() {
  let isValid = true
  Object.keys(errors).forEach((k) => (errors[k] = ''))

  if (!form.name) { errors.name = 'Vui lòng nhập tên gói'; isValid = false }
  if (!form.slug) { errors.slug = 'Vui lòng nhập định danh gói'; isValid = false }
  if (form.priority == null || form.priority < 1) { errors.priority = 'Độ ưu tiên phải ≥ 1'; isValid = false }
  if (form.multiplier == null || form.multiplier < 1) { errors.multiplier = 'Hệ số điểm phải ≥ 1'; isValid = false }
  if (form.daily_quota == null || form.daily_quota < 0) { errors.daily_quota = 'Quota không hợp lệ'; isValid = false }
  if (form.decay_rate == null || form.decay_rate < 0 || form.decay_rate > 1) { errors.decay_rate = 'Decay rate phải từ 0 đến 1'; isValid = false }
  if (form.price == null || form.price < 0) { errors.price = 'Vui lòng nhập giá'; isValid = false }
  if (sortedDurations.value.length === 0) { errors.active_durations = 'Vui lòng thêm ít nhất một thời hạn'; isValid = false }

  return isValid
}

async function handleSubmit() {
  if (!validate()) return
  success.value = false

  try {
    await createPackage({
      name: form.name,
      slug: form.slug,
      priority: Number(form.priority),
      multiplier: Number(form.multiplier),
      daily_quota: Number(form.daily_quota),
      decay_rate: Number(form.decay_rate),
      price: Number(form.price),
      badge: form.badge || null,
      color: form.color || null,
      active_durations: sortedDurations.value,
    })

    success.value = true
    setTimeout(() => router.push({ name: 'Packages' }), 1000)
  } catch {
    // apiError handled by composable
  }
}
</script>

<style scoped>
.form-page {
  max-width: 900px;
  margin: 0 auto;
}

/* Page header */
.form-page-header {
  display: flex;
  align-items: center;
  gap: 16px;
  margin-bottom: 24px;
}

.back-btn {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  height: 36px;
  padding: 0 14px;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  background: #fff;
  color: #475569;
  font-size: 13px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  white-space: nowrap;
}

.back-btn:hover {
  border-color: #3b82f6;
  color: #3b82f6;
  background: #eff6ff;
}

.form-page-title { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; }
.form-page-subtitle { margin: 3px 0 0; font-size: 13px; color: #64748b; }

/* Alert */
.alert {
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 16px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  margin-bottom: 16px;
}

.alert--error { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; }
.alert--success { background: #f0fdf4; border: 1px solid #bbf7d0; color: #16a34a; }

/* Form body */
.form-body { display: flex; flex-direction: column; gap: 20px; }

/* Form card */
.form-card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
}

.form-card-header {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 18px 20px;
  border-bottom: 1px solid #f1f5f9;
  background: #f8fafc;
}

.form-card-number {
  display: flex;
  align-items: center;
  justify-content: center;
  width: 32px;
  height: 32px;
  border-radius: 50%;
  background: #3b82f6;
  color: #fff;
  font-size: 14px;
  font-weight: 800;
  flex-shrink: 0;
}

.form-card-title { margin: 0; font-size: 15px; font-weight: 700; color: #0f172a; }
.form-card-desc { margin: 3px 0 0; font-size: 12px; color: #64748b; }

.form-card-body { padding: 20px; }

/* Field grid */
.field-grid { display: grid; gap: 18px; }
.field-grid--2 { grid-template-columns: 1fr 1fr; }
.field-grid--3 { grid-template-columns: repeat(3, 1fr); }

/* Field */
.field { display: flex; flex-direction: column; gap: 6px; }
.field-label { font-size: 13px; font-weight: 600; color: #374151; }
.required { color: #ef4444; }

.field-input {
  height: 40px;
  padding: 0 12px;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  background: #fff;
  font-size: 14px;
  color: #1e293b;
  outline: none;
  transition: border-color 0.15s, box-shadow 0.15s;
  width: 100%;
  box-sizing: border-box;
}

.field-input:focus {
  border-color: #3b82f6;
  box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.field-input--error { border-color: #ef4444; }
.field-input--error:focus { box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1); }
.field-input--lg { height: 48px; font-size: 16px; font-weight: 600; }

.field-error { font-size: 12px; color: #ef4444; }
.field-hint { font-size: 12px; color: #94a3b8; }

/* Color input */
.color-input-wrap {
  display: flex;
  align-items: center;
  gap: 8px;
}

.color-picker {
  width: 40px;
  height: 40px;
  border: 1px solid #e2e8f0;
  border-radius: 9px;
  padding: 2px;
  cursor: pointer;
  background: #fff;
  flex-shrink: 0;
}

/* Pricing layout */
.pricing-layout {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 24px;
  align-items: start;
}

.price-preview-simple {
  margin-top: 14px;
  background: #f8fafc;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  overflow: hidden;
}

.price-preview-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 14px;
  border-bottom: 1px solid #f1f5f9;
  font-size: 13px;
  color: #475569;
}

.price-preview-row:last-child { border-bottom: none; }
.price-preview-row strong { color: #1d4ed8; font-weight: 700; }

/* Duration panel */
.duration-panel {
  border: 1px solid #e2e8f0;
  border-radius: 12px;
  overflow: hidden;
  background: #fff;
}

.duration-panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 12px 16px;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
  font-size: 13px;
  font-weight: 700;
  color: #374151;
}

.duration-add-row {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 16px;
  border-bottom: 1px solid #f1f5f9;
}

.duration-add-row .field-input { flex: 1; }

.btn-add-duration {
  height: 40px;
  padding: 0 16px;
  border: 1px solid #3b82f6;
  border-radius: 9px;
  background: #eff6ff;
  color: #1d4ed8;
  font-size: 13px;
  font-weight: 700;
  cursor: pointer;
  white-space: nowrap;
  transition: all 0.15s;
}

.btn-add-duration:hover { background: #dbeafe; }

.duration-empty {
  padding: 24px;
  text-align: center;
  font-size: 13px;
  color: #94a3b8;
}

.duration-table-wrap { overflow-x: auto; }

.duration-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}

.duration-table thead th {
  padding: 8px 14px;
  text-align: left;
  font-size: 11px;
  font-weight: 700;
  text-transform: uppercase;
  color: #64748b;
  background: #f8fafc;
  border-bottom: 1px solid #e2e8f0;
}

.duration-table tbody tr {
  border-bottom: 1px solid #f1f5f9;
  transition: background 0.1s;
}

.duration-table tbody tr:last-child { border-bottom: none; }
.duration-table tbody tr:hover { background: #f8fafc; }
.duration-table td { padding: 10px 14px; }

.text-right { text-align: right; }

.check { width: 16px; height: 16px; cursor: pointer; accent-color: #3b82f6; }

.dur-label { cursor: pointer; color: #374151; font-weight: 500; }
.dur-price { font-weight: 700; color: #1d4ed8; }
.dur-off { font-size: 12px; color: #94a3b8; font-style: italic; }

/* Form actions */
.form-actions {
  display: flex;
  align-items: center;
  justify-content: flex-end;
  gap: 12px;
  padding: 20px 0 8px;
}

.btn-cancel {
  height: 42px;
  padding: 0 20px;
  border: 1px solid #e2e8f0;
  border-radius: 10px;
  background: #fff;
  color: #475569;
  font-size: 14px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
}

.btn-cancel:hover { border-color: #94a3b8; background: #f8fafc; }

.btn-submit {
  display: inline-flex;
  align-items: center;
  gap: 8px;
  height: 42px;
  padding: 0 24px;
  border: none;
  border-radius: 10px;
  background: linear-gradient(135deg, #3b82f6, #1d4ed8);
  color: #fff;
  font-size: 14px;
  font-weight: 700;
  cursor: pointer;
  box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  transition: all 0.15s;
}

.btn-submit:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(59, 130, 246, 0.4); }
.btn-submit:disabled { opacity: 0.6; cursor: not-allowed; }

.btn-spinner {
  width: 16px;
  height: 16px;
  border: 2px solid rgba(255,255,255,0.4);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

@media (max-width: 768px) {
  .field-grid--2, .field-grid--3 { grid-template-columns: 1fr; }
  .pricing-layout { grid-template-columns: 1fr; }
  .form-page-header { flex-wrap: wrap; }
}
</style>
