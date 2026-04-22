<template>
  <div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Sửa Gói Tin</h1>
        <p class="mt-1 text-sm text-gray-500">Chỉnh sửa cấu hình gói tin hệ thống</p>
      </div>
      <Button variant="outline" @click="$router.push({ name: 'Packages' })">
        Quay lại
      </Button>
    </div>

    <!-- Error/Loading for fetching -->
    <div v-if="loading && !packageLoaded" class="py-8 text-center text-gray-500">Đang tải thông tin...</div>
    <div v-if="fetchError" class="py-4 text-red-500">{{ fetchError }}</div>

    <!-- Error Alert -->
    <div v-if="apiError" class="p-4 rounded-md bg-red-50 border border-red-200">
      <div class="flex">
        <div class="flex-shrink-0">
          <svg class="w-5 h-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
          </svg>
        </div>
        <div class="ml-3">
          <h3 class="text-sm font-medium text-red-800">Lỗi cập nhật gói tin</h3>
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
          <p class="text-sm font-medium text-green-800">Cập nhật gói tin thành công!</p>
        </div>
      </div>
    </div>

    <form v-if="packageLoaded" @submit.prevent="handleSubmit" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Cấu hình cơ bản -->
        <Card>
          <CardHeader>Cấu hình cơ bản</CardHeader>
          <CardBody class="space-y-4">
            <div class="mb-4">
              <label class="block mb-1.5 text-sm font-medium text-gray-700">Tên gói (Không thể sửa)</label>
              <input type="text" disabled :value="form.name" class="w-full px-3 py-2 text-sm border border-gray-200 rounded-lg bg-gray-100 text-gray-500" />
            </div>

            <Input
              v-model.number="form.price"
              type="number"
              label="Giá tiền (VNĐ)"
              required
              min="0"
              placeholder="0"
              :error="errors.price"
            />

            <Input
              v-model.number="form.daily_quota"
              type="number"
              label="Số lượt hiển thị/ngày (Daily Quota)"
              required
              min="0"
              placeholder="100"
              :error="errors.daily_quota"
            />

            <div class="flex items-center mt-4">
              <input
                id="is_active"
                v-model="form.is_active"
                type="checkbox"
                class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
              />
              <label for="is_active" class="ml-2 block text-sm text-gray-900">
                Kích hoạt (Cho phép người dùng mua)
              </label>
            </div>
          </CardBody>
        </Card>

        <!-- Ranking & UI -->
        <Card>
          <CardHeader>Ranking & Giao diện</CardHeader>
          <CardBody class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
              <Input
                v-model.number="form.priority"
                type="number"
                label="Độ ưu tiên"
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
            </div>

            <Input
              v-model.number="form.decay_rate"
              type="number"
              step="0.01"
              label="Tốc độ tụt hạng (Decay Rate)"
              required
              min="0"
              max="1"
              placeholder="0.05"
              :error="errors.decay_rate"
            />

            <div class="grid grid-cols-2 gap-4">
              <Input
                v-model="form.badge"
                label="Badge hiển thị"
                placeholder="HOT, VIP..."
              />
              <Input
                v-model="form.color"
                label="Màu sắc (Color code)"
                placeholder="#FFD700"
              />
            </div>
          </CardBody>
        </Card>
      </div>

      <!-- Actions -->
      <div class="flex justify-end pt-4 border-t border-gray-200">
        <Button variant="outline" class="mr-3" @click="$router.push({ name: 'Packages' })">
          Hủy bỏ
        </Button>
        <Button type="submit" variant="primary" :loading="loading">
          Lưu thay đổi
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { usePackageApi } from '@/composables/usePackageApi'

import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardBody from '@/components/ui/CardBody.vue'
import Input from '@/components/ui/Input.vue'
import Button from '@/components/ui/Button.vue'

const router = useRouter()
const route = useRoute()
const packageId = route.params.id

const { fetchPackage, updatePackage, loading, error: apiError } = usePackageApi()

const success = ref(false)
const fetchError = ref('')
const packageLoaded = ref(false)

const form = reactive({
  name: '',
  price: 0,
  priority: 1,
  multiplier: 1.0,
  daily_quota: 100,
  decay_rate: 0.05,
  badge: '',
  color: '',
  is_active: true
})

const errors = reactive({})

onMounted(async () => {
  try {
    const res = await fetchPackage(packageId)
    if (res?.data) {
      const data = res.data
      form.name = data.name
      form.price = Number(data.price)
      form.priority = Number(data.priority)
      form.multiplier = Number(data.multiplier)
      form.daily_quota = Number(data.daily_quota)
      form.decay_rate = Number(data.decay_rate)
      form.badge = data.badge || ''
      form.color = data.color || ''
      form.is_active = Boolean(data.is_active)
      packageLoaded.value = true
    }
  } catch (e) {
    fetchError.value = e.response?.data?.message || 'Không thể lấy thông tin gói tin'
  }
})

const validate = () => {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (form.price === '' || form.price < 0) {
    errors.price = 'Giá tiền không hợp lệ'
    isValid = false
  }

  if (form.priority < 1) {
    errors.priority = 'Độ ưu tiên phải lớn hơn hoặc bằng 1'
    isValid = false
  }

  if (form.multiplier < 1) {
    errors.multiplier = 'Multiplier phải lớn hơn hoặc bằng 1'
    isValid = false
  }

  if (form.daily_quota < 0) {
    errors.daily_quota = 'Quota không hợp lệ'
    isValid = false
  }

  if (form.decay_rate < 0 || form.decay_rate > 1) {
    errors.decay_rate = 'Decay rate phải từ 0 đến 1'
    isValid = false
  }

  return isValid
}

const handleSubmit = async () => {
  if (!validate()) return

  success.value = false

  try {
    await updatePackage(packageId, {
      price: Number(form.price),
      priority: Number(form.priority),
      multiplier: Number(form.multiplier),
      daily_quota: Number(form.daily_quota),
      decay_rate: Number(form.decay_rate),
      badge: form.badge || null,
      color: form.color || null,
      is_active: Boolean(form.is_active)
    })
    
    success.value = true
    setTimeout(() => {
      router.push({ name: 'Packages' })
    }, 1000)
  } catch (err) {
    // Error is handled by composable and shown via apiError
  }
}
</script>
