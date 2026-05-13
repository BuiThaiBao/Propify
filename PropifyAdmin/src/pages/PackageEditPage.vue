<template>
  <div class="mx-auto max-w-4xl space-y-6">
    <div class="flex items-center justify-between gap-4">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Sửa gói tin</h1>
        <p class="mt-1 text-sm text-gray-500">Chỉnh sửa cấu hình gói tin hệ thống</p>
      </div>
      <Button variant="outline" @click="router.push({ name: 'Packages' })">
        Quay lại
      </Button>
    </div>

    <div v-if="loading && !packageLoaded" class="py-8 text-center text-gray-500">Đang tải thông tin...</div>
    <div v-if="fetchError" class="py-4 text-red-500">{{ fetchError }}</div>

    <div v-if="apiError" class="rounded-md border border-red-200 bg-red-50 p-4">
      <h3 class="text-sm font-medium text-red-800">Lỗi cập nhật gói tin</h3>
      <p class="mt-2 text-sm text-red-700">{{ apiError }}</p>
    </div>

    <div v-if="success" class="rounded-md border border-green-200 bg-green-50 p-4">
      <p class="text-sm font-medium text-green-800">Cập nhật gói tin thành công!</p>
    </div>

    <Card v-if="packageLoaded" class="overflow-hidden">
      <form @submit.prevent="handleSubmit">
        <section class="border-b border-gray-100 p-6">
          <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-900">1. Thông tin cơ bản</h3>
            <p class="text-sm text-gray-500">Tên gói và trạng thái hoạt động</p>
          </div>
          <div class="grid grid-cols-1 items-start gap-6 md:grid-cols-2">
            <Input
              v-model="form.name"
              label="Tên gói"
              required
              placeholder="Ví dụ: Gói Cơ bản"
              :error="errors.name"
            />
            <label class="mt-7 flex items-center rounded-lg border border-gray-100 bg-gray-50 p-3">
              <input
                v-model="form.is_active"
                type="checkbox"
                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span class="ml-2 text-sm font-medium text-gray-900">
                Kích hoạt gói tin, cho phép người dùng mua
              </span>
            </label>
          </div>
        </section>

        <section class="border-b border-gray-100 bg-gray-50/30 p-6">
          <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-900">2. Thông số phân phối và xếp hạng</h3>
            <p class="text-sm text-gray-500">Quyết định mức độ ưu tiên hiển thị và đặc quyền của gói</p>
          </div>
          <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
            <Input v-model.number="form.daily_quota" type="number" label="Lượt hiển thị/ngày" required min="0" :error="errors.daily_quota" />
            <Input v-model.number="form.priority" type="number" label="Tầng ưu tiên" required min="1" :error="errors.priority" />
            <Input v-model.number="form.multiplier" type="number" step="0.1" label="Hệ số điểm" required min="1" :error="errors.multiplier" />
            <Input v-model.number="form.decay_rate" type="number" step="0.001" label="Tốc độ tụt hạng" required min="0" max="1" :error="errors.decay_rate" />
            <Input v-model="form.badge" label="Nhãn hiển thị" placeholder="HOT, VIP..." />
            <Input v-model="form.color" label="Mã màu" placeholder="#FFD700" />
          </div>
        </section>

        <section class="border-b border-gray-100 p-6">
          <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-900">3. Cấu hình bảng giá</h3>
            <p class="text-sm text-gray-500">Nhập giá gốc 1 ngày. Hệ thống tự tính giá cho từng thời hạn đang bật.</p>
          </div>
          <div class="grid grid-cols-1 items-start gap-8 md:grid-cols-2">
            <Input
              v-model.number="form.price"
              type="number"
              label="Giá 1 ngày (VNĐ)"
              required
              min="0"
              placeholder="50000"
              :error="errors.price"
            />

            <div class="overflow-hidden rounded-xl border bg-white shadow-sm">
              <div class="border-b bg-gray-50 px-4 py-3 text-sm font-semibold text-gray-700">
                Bảng giá theo thời hạn
              </div>
              <div class="border-b bg-white px-4 py-3">
                <div class="flex gap-2">
                  <Input
                    v-model.number="newDuration"
                    type="number"
                    min="1"
                    max="3650"
                    placeholder="Số ngày"
                    :error="errors.active_durations"
                  />
                  <Button type="button" variant="outline" @click="addDuration">Thêm</Button>
                </div>
              </div>
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-white">
                  <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Thời hạn</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500">Thành tiền</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="days in visibleDurations" :key="days">
                    <td class="px-4 py-3 font-medium text-gray-800">
                      <label class="flex cursor-pointer items-center gap-3">
                        <input
                          v-model="form.active_durations"
                          type="checkbox"
                          :value="days"
                          class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                        />
                        {{ days }} ngày
                      </label>
                    </td>
                    <td class="px-4 py-3 text-right font-bold" :class="form.active_durations.includes(days) ? 'text-blue-600' : 'text-gray-300 italic'">
                      <template v-if="form.active_durations.includes(days)">
                        {{ Number((form.price || 0) * days).toLocaleString('vi-VN') }} ₫
                      </template>
                      <template v-else>
                        Tạm tắt
                      </template>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </section>

        <div class="flex items-center justify-end gap-3 rounded-b-lg bg-gray-50 px-6 py-4">
          <Button variant="outline" type="button" @click="router.push({ name: 'Packages' })">
            Hủy bỏ
          </Button>
          <Button type="submit" variant="primary" :loading="loading">
            Lưu thay đổi
          </Button>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import { usePackageApi } from '@/composables/usePackageApi'
import Card from '@/components/ui/Card.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'

const router = useRouter()
const route = useRoute()
const packageId = route.params.id

const {
  fetchPackage,
  updatePackage,
  fetchDurationOptions,
  createDurationOption,
  loading,
  error: apiError,
} = usePackageApi()

const success = ref(false)
const fetchError = ref('')
const packageLoaded = ref(false)
const newDuration = ref(null)
const durationOptions = ref([])

const form = reactive({
  name: '',
  priority: 1,
  multiplier: 1,
  daily_quota: 100,
  decay_rate: 0.05,
  badge: '',
  color: '',
  price: 50000,
  is_active: true,
  active_durations: [3, 7, 10, 15, 30],
})

const errors = reactive({})

const sortedDurations = computed(() => {
  return [...new Set(form.active_durations.map((days) => Number(days)))]
    .filter((days) => Number.isInteger(days) && days > 0)
    .sort((a, b) => a - b)
})

const visibleDurations = computed(() => {
  return [...new Set([
    ...durationOptions.value.map((option) => Number(option.days)),
    ...form.active_durations.map((days) => Number(days)),
  ])]
    .filter((days) => Number.isInteger(days) && days > 0)
    .sort((a, b) => a - b)
})

async function addDuration() {
  const days = Number(newDuration.value)
  errors.active_durations = ''

  if (!Number.isInteger(days) || days < 1 || days > 3650) {
    errors.active_durations = 'Số ngày phải từ 1 đến 3650'
    return
  }

  if (!durationOptions.value.some((option) => Number(option.days) === days)) {
    const response = await createDurationOption({ days })
    if (response?.data) {
      durationOptions.value.push(response.data)
    }
  }

  if (!form.active_durations.includes(days)) {
    form.active_durations.push(days)
  }

  newDuration.value = null
}

function validate() {
  let isValid = true
  Object.keys(errors).forEach((key) => {
    errors[key] = ''
  })

  if (!form.name) {
    errors.name = 'Vui lòng nhập tên gói'
    isValid = false
  }
  if (form.priority < 1) {
    errors.priority = 'Độ ưu tiên phải lớn hơn hoặc bằng 1'
    isValid = false
  }
  if (form.multiplier < 1) {
    errors.multiplier = 'Hệ số điểm phải lớn hơn hoặc bằng 1'
    isValid = false
  }
  if (form.daily_quota < 0) {
    errors.daily_quota = 'Quota không hợp lệ'
    isValid = false
  }
  if (form.decay_rate < 0 || form.decay_rate > 1) {
    errors.decay_rate = 'Tốc độ tụt hạng phải từ 0 đến 1'
    isValid = false
  }
  if (form.price < 0) {
    errors.price = 'Giá không thể âm'
    isValid = false
  }
  if (sortedDurations.value.length === 0) {
    errors.active_durations = 'Vui lòng thêm ít nhất một thời hạn'
    isValid = false
  }

  return isValid
}

async function handleSubmit() {
  if (!validate()) return

  success.value = false

  try {
    await updatePackage(packageId, {
      name: form.name,
      priority: Number(form.priority),
      multiplier: Number(form.multiplier),
      daily_quota: Number(form.daily_quota),
      decay_rate: Number(form.decay_rate),
      price: Number(form.price),
      badge: form.badge || null,
      color: form.color || null,
      is_active: Boolean(form.is_active),
      active_durations: sortedDurations.value,
    })

    success.value = true
    setTimeout(() => {
      router.push({ name: 'Packages' })
    }, 1000)
  } catch {
    // usePackageApi exposes apiError for the template.
  }
}

onMounted(async () => {
  try {
    const durationResponse = await fetchDurationOptions()
    durationOptions.value = durationResponse?.data || []

    const response = await fetchPackage(packageId)
    if (response?.data) {
      const data = response.data
      form.name = data.name
      form.priority = Number(data.priority)
      form.multiplier = Number(data.multiplier)
      form.daily_quota = Number(data.daily_quota)
      form.decay_rate = Number(data.decay_rate)
      form.badge = data.badge || ''
      form.color = data.color || ''
      form.price = Number(data.price || 0)
      form.is_active = Boolean(data.is_active)
      form.active_durations = (data.pricings || [])
        .filter((pricing) => pricing.is_active)
        .map((pricing) => Number(pricing.duration_days))

      packageLoaded.value = true
    }
  } catch (error) {
    fetchError.value = error.response?.data?.message || 'Không thể lấy thông tin gói tin'
  }
})
</script>
