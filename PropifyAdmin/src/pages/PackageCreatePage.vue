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

    <form @submit.prevent="handleSubmit" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Cấu hình cơ bản -->
        <Card>
          <CardHeader>Cấu hình cơ bản</CardHeader>
          <CardBody class="space-y-4">
            <Select
              v-model="form.type"
              label="Loại gói"
              required
              :options="[
                { label: 'Gold (Vàng)', value: 'gold' },
                { label: 'Silver (Bạc)', value: 'silver' },
                { label: 'Diamond (Kim Cương)', value: 'diamond' }
              ]"
              placeholder="Chọn loại gói"
              :error="errors.type"
            />

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
          Lưu gói tin
        </Button>
      </div>
    </form>
  </div>
</template>

<script setup>
import { reactive, ref } from 'vue'
import { useRouter } from 'vue-router'
import { usePackageApi } from '@/composables/usePackageApi'

import Card from '@/components/ui/Card.vue'
import CardHeader from '@/components/ui/CardHeader.vue'
import CardBody from '@/components/ui/CardBody.vue'
import Input from '@/components/ui/Input.vue'
import Select from '@/components/ui/Select.vue'
import Button from '@/components/ui/Button.vue'

const router = useRouter()
const { createPackage, loading, error: apiError } = usePackageApi()

const success = ref(false)

const form = reactive({
  type: '',
  price: 0,
  priority: 1,
  multiplier: 1.0,
  daily_quota: 100,
  decay_rate: 0.05,
  badge: '',
  color: ''
})

const errors = reactive({})

const validate = () => {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.type) {
    errors.type = 'Vui lòng chọn loại gói'
    isValid = false
  }

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
    await createPackage({
      type: form.type,
      price: Number(form.price),
      priority: Number(form.priority),
      multiplier: Number(form.multiplier),
      daily_quota: Number(form.daily_quota),
      decay_rate: Number(form.decay_rate),
      badge: form.badge || null,
      color: form.color || null
    })
    
    success.value = true
    
    // Đợi 1 giây để user nhìn thấy thông báo thành công rồi redirect
    setTimeout(() => {
      router.push({ name: 'Packages' })
    }, 1000)

  } catch (err) {
    // Error is handled by composable and shown via apiError
  }
}
</script>
