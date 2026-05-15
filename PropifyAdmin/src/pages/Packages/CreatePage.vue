<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Thêm Gói Tin Mới</h1>
        <p class="mt-1 text-sm text-gray-500">Tạo mới cấu hình một gói tin cho người dùng hệ thống</p>
      </div>
      <Button variant="outline" @click="$router.push({ name: 'Packages' })">
        Quay lại
      </Button>
    </div>

    <!-- Error Alert -->
    <div v-if="apiError" class="p-4 rounded-md bg-red-50 border border-red-200">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Lỗi tạo gói tin</h3>
          <div class="mt-2 text-sm text-red-700">
            <p>{{ apiError }}</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Success Alert -->
    <div v-if="success" class="p-4 rounded-md bg-green-50 border border-green-200">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <p class="text-sm font-medium text-green-800">Tạo gói tin thành công!</p>
        </div>
        <div class="ml-auto pl-3">
          <div class="-mx-1.5 -my-1.5">
            <button @click="$router.push({ name: 'Packages' })" class="inline-flex bg-green-50 rounded-md p-1.5 text-green-500 hover:bg-green-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-green-50 focus:ring-green-600">
              <span class="sr-only">Xem danh sách</span>
              <span class="text-sm font-medium">Xem danh sách &rarr;</span>
            </button>
          </div>
        </div>
      </div>
    </div>

    <Card class="overflow-hidden">
      <form @submit.prevent="handleSubmit">
        
        <!-- Section 1: Thông tin cơ bản -->
        <div class="p-6 border-b border-gray-100">
          <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-900">1. Thông tin cơ bản</h3>
            <p class="text-sm text-gray-500">Tên và định danh hiển thị của gói tin</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <Input
              v-model="form.name"
              label="Tên gói"
              required
              placeholder="Ví dụ: Gói Cơ bản"
              :error="errors.name"
            />
            <Input
              v-model="form.slug"
              label="Định danh (Slug)"
              required
              placeholder="Ví dụ: basic"
              :error="errors.slug"
            />
          </div>
        </div>

        <!-- Section 2: Thông số kỹ thuật & Xếp hạng -->
        <div class="p-6 border-b border-gray-100 bg-gray-50/30">
          <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-900">2. Thông số phân phối & Xếp hạng</h3>
            <p class="text-sm text-gray-500">Quyết định mức độ ưu tiên hiển thị và đặc quyền của gói</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <Input
              v-model.number="form.daily_quota"
              type="number"
              label="Lượt hiển thị/ngày (Quota)"
              required
              min="0"
              placeholder="100"
              :error="errors.daily_quota"
            />
            <Input
              v-model.number="form.priority"
              type="number"
              label="Tầng ưu tiên (Priority)"
              required
              min="1"
              placeholder="1 (thấp), 3 (cao)"
              :error="errors.priority"
            />
            <Input
              v-model.number="form.multiplier"
              type="number"
              step="0.1"
              label="Hệ số điểm (Multiplier)"
              required
              min="1"
              placeholder="1.0"
              :error="errors.multiplier"
            />
            <Input
              v-model.number="form.decay_rate"
              type="number"
              step="0.001"
              label="Tốc độ tụt hạng (Decay Rate)"
              required
              min="0"
              max="1"
              placeholder="0.005"
              :error="errors.decay_rate"
            />
            <Input
              v-model="form.badge"
              label="Nhãn (Badge)"
              placeholder="HOT, VIP..."
            />
          </div>
        </div>

        <!-- Section 3: Bảng giá tự động -->
        <div class="p-6 border-b border-gray-100">
          <div class="mb-4">
            <h3 class="text-lg font-bold text-gray-900">3. Cấu hình bảng giá tự động</h3>
            <p class="text-sm text-gray-500">Nhập giá gốc 1 ngày. Nếu giá bằng 0, hệ thống sẽ coi đây là gói miễn phí.</p>
          </div>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8 items-start">
            <div>
              <Input
                v-model.number="form.price"
                type="number"
                label="Giá 1 ngày (VNĐ)"
                required
                min="0"
                placeholder="50000"
                :error="errors.price"
              />
            </div>
            
            <div class="bg-white border rounded-xl overflow-hidden shadow-sm">
              <div class="px-4 py-3 bg-gray-50 border-b text-sm font-semibold text-gray-700">
                Xem trước bảng giá hệ thống
              </div>
              <div class="px-4 py-3 border-b bg-white">
                <div class="flex gap-2">
                  <Input
                    v-model.number="newDuration"
                    type="number"
                    min="1"
                    max="3650"
                    placeholder="Số ngày"
                    :error="errors.active_durations"
                  />
                  <Button type="button" variant="outline" @click="addDuration">
                    Thêm
                  </Button>
                </div>
              </div>
              <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-white">
                  <tr>
                    <th class="px-4 py-3 text-left font-medium text-gray-500">Thời hạn sử dụng</th>
                    <th class="px-4 py-3 text-right font-medium text-gray-500">Thành tiền (VNĐ)</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                  <tr v-for="days in visibleDurations" :key="days" class="hover:bg-gray-50 transition-colors">
                    <td class="px-4 py-3 font-medium text-gray-800">
                      <div class="flex items-center gap-3">
                        <input 
                          type="checkbox" 
                          :id="'duration-' + days"
                          v-model="form.active_durations" 
                          :value="days"
                          class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 cursor-pointer"
                        />
                        <label :for="'duration-' + days" class="cursor-pointer">{{ days }} ngày</label>
                      </div>
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
        </div>

        <!-- Actions -->
        <div class="px-6 py-4 bg-gray-50 flex items-center justify-end gap-3 rounded-b-lg">
          <Button variant="outline" type="button" @click="$router.push({ name: 'Packages' })">
            Hủy bỏ
          </Button>
          <Button type="submit" variant="primary" :loading="loading">
            Lưu gói tin
          </Button>
        </div>
      </form>
    </Card>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue'
import { useRouter } from 'vue-router'
import { usePackageApi } from '@/composables/usePackageApi'

import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardBody from '@/components/ui/CardBody.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'

const router = useRouter()
const { createPackage, fetchDurationOptions, createDurationOption, loading, error: apiError } = usePackageApi()

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
  price: null,
  active_durations: []
})

const errors = reactive({})

const sortedDurations = computed(() => {
  return [...new Set(form.active_durations.map(days => Number(days)))]
    .filter(days => Number.isInteger(days) && days > 0)
    .sort((a, b) => a - b)
})

const visibleDurations = computed(() => {
  return [...new Set([
    ...durationOptions.value.map(option => Number(option.days)),
    ...form.active_durations.map(days => Number(days)),
  ])]
    .filter(days => Number.isInteger(days) && days > 0)
    .sort((a, b) => a - b)
})

onMounted(async () => {
  try {
    const res = await fetchDurationOptions()
    durationOptions.value = res?.data || []
  } catch (err) {
    durationOptions.value = []
  }
})

const addDuration = async () => {
  const days = Number(newDuration.value)
  errors.active_durations = ''

  if (!Number.isInteger(days) || days < 1 || days > 3650) {
    errors.active_durations = 'Số ngày phải từ 1 đến 3650'
    return
  }

  if (!durationOptions.value.some(option => Number(option.days) === days)) {
    const res = await createDurationOption({ days })
    if (res?.data) {
      durationOptions.value.push(res.data)
    }
  }

  if (!form.active_durations.includes(days)) {
    form.active_durations.push(days)
  }

  newDuration.value = null
}

const validate = () => {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.name) {
    errors.name = 'Vui lòng nhập tên gói'
    isValid = false
  }

  if (!form.slug) {
    errors.slug = 'Vui lòng nhập định danh gói'
    isValid = false
  }

  if (form.priority == null || form.priority < 1) {
    errors.priority = 'Độ ưu tiên phải lớn hơn hoặc bằng 1'
    isValid = false
  }

  if (form.multiplier == null || form.multiplier < 1) {
    errors.multiplier = 'Multiplier phải lớn hơn hoặc bằng 1'
    isValid = false
  }

  if (form.daily_quota == null || form.daily_quota < 0) {
    errors.daily_quota = 'Quota không hợp lệ'
    isValid = false
  }

  if (form.decay_rate == null || form.decay_rate < 0 || form.decay_rate > 1) {
    errors.decay_rate = 'Decay rate phải từ 0 đến 1'
    isValid = false
  }

  if (form.price == null || form.price < 0) {
    errors.price = 'Vui lòng nhập giá'
    isValid = false
  }

  if (sortedDurations.value.length === 0) {
    errors.active_durations = 'Vui lòng thêm ít nhất một thời hạn'
    isValid = false
  }

  return isValid
}

const handleSubmit = async () => {
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
      active_durations: sortedDurations.value
    })
    
    success.value = true
    
    // Đợi 1 giây để user nhìn thấy thông báo thành công rồi redirect
    setTimeout(() => {
      router.push({ name: 'Packages' })
    }, 1000)

    // Reset form after success or navigate
    form.name = ''
    form.slug = ''
    form.badge = ''

  } catch (err) {
    // Error is handled by composable and shown via apiError
  }
}
</script>
