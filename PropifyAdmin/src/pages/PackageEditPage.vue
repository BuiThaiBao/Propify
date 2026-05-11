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
              <Input
                v-model="form.name"
                label="Tên gói"
                required
                placeholder="Ví dụ: Gói Cơ bản"
                :error="errors.name"
              />
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

    <!-- ═══════════════ Pricing Management ═══════════════ -->
    <Card v-if="packageLoaded" class="mt-6">
      <CardHeader>Bảng giá theo thời hạn</CardHeader>
      <CardBody>
        <p class="text-sm text-gray-500 mb-4">Thiết lập giá cho từng thời hạn sử dụng gói. Người dùng sẽ chọn thời hạn khi nâng cấp tin đăng.</p>

        <!-- Existing Pricings Table -->
        <div v-if="pricings.length" class="overflow-x-auto mb-6">
          <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
              <tr>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Thời hạn</th>
                <th class="px-4 py-3 text-left font-semibold text-gray-600">Nhãn hiển thị</th>
                <th class="px-4 py-3 text-right font-semibold text-gray-600">Giá (VNĐ)</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Trạng thái</th>
                <th class="px-4 py-3 text-center font-semibold text-gray-600">Thao tác</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="p in pricings" :key="p.id" class="hover:bg-gray-50">
                <td class="px-4 py-3 font-medium">{{ p.duration_days }} ngày</td>
                <td class="px-4 py-3">{{ p.label }}</td>
                <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ Number(p.price).toLocaleString('vi-VN') }}đ</td>
                <td class="px-4 py-3 text-center">
                  <span :class="p.is_active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500'" class="px-2 py-1 rounded-full text-xs font-medium">
                    {{ p.is_active ? 'Hoạt động' : 'Tắt' }}
                  </span>
                </td>
                <td class="px-4 py-3 text-center">
                  <button @click="togglePricingActive(p)" class="text-xs px-2 py-1 rounded hover:bg-gray-100 mr-1" :title="p.is_active ? 'Tắt' : 'Bật'">
                    {{ p.is_active ? '🔇 Tắt' : '🔔 Bật' }}
                  </button>
                  <button @click="removePricing(p)" class="text-xs px-2 py-1 rounded hover:bg-red-50 text-red-600">🗑 Xóa</button>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div v-else class="text-center py-6 text-gray-400 text-sm">
          Chưa có bảng giá nào. Thêm bên dưới.
        </div>

        <!-- Add Pricing Form -->
        <div class="border-t border-gray-200 pt-4">
          <h4 class="text-sm font-semibold text-gray-700 mb-3">Thêm mức giá mới</h4>
          <div class="grid grid-cols-1 sm:grid-cols-4 gap-3 items-end">
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Số ngày</label>
              <select v-model.number="newPricing.duration_days" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2">
                <option :value="0" disabled>Chọn...</option>
                <option v-for="d in availableDurations" :key="d" :value="d">{{ d }} ngày</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Nhãn</label>
              <input v-model="newPricing.label" type="text" placeholder="VD: 1 tuần" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2" />
            </div>
            <div>
              <label class="block text-xs font-medium text-gray-600 mb-1">Giá (VNĐ)</label>
              <input v-model.number="newPricing.price" type="number" min="0" placeholder="50000" class="w-full rounded-lg border-gray-300 shadow-sm text-sm focus:ring-blue-500 focus:border-blue-500 px-3 py-2" />
            </div>
            <Button variant="primary" size="sm" @click="addPricing" :loading="pricingLoading">
              + Thêm
            </Button>
          </div>
          <p v-if="pricingError" class="mt-2 text-xs text-red-600">{{ pricingError }}</p>
        </div>
      </CardBody>
    </Card>
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

const { fetchPackage, updatePackage, fetchPricings, createPricing, updatePricing: updatePricingApi, deletePricing: deletePricingApi, loading, error: apiError } = usePackageApi()

const success = ref(false)
const fetchError = ref('')
const packageLoaded = ref(false)

// ── Pricing state ──────────────────────────────────────────────────────
const pricings = ref([])
const pricingLoading = ref(false)
const pricingError = ref('')
const ALL_DURATIONS = [3, 7, 10, 15, 30]

const newPricing = reactive({
  duration_days: 0,
  label: '',
  price: 0,
})

import { computed } from 'vue'
const availableDurations = computed(() => {
  const used = pricings.value.map(p => p.duration_days)
  return ALL_DURATIONS.filter(d => !used.includes(d))
})

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

  // Load pricings
  await loadPricings()
})

async function loadPricings() {
  try {
    const res = await fetchPricings(packageId)
    pricings.value = res?.data || []
  } catch (e) {
    console.error('Failed to load pricings', e)
  }
}

async function addPricing() {
  pricingError.value = ''
  if (!newPricing.duration_days) { pricingError.value = 'Chọn số ngày'; return }
  if (!newPricing.label) { pricingError.value = 'Nhập nhãn hiển thị'; return }
  if (newPricing.price <= 0) { pricingError.value = 'Giá phải lớn hơn 0'; return }

  pricingLoading.value = true
  try {
    await createPricing(packageId, {
      duration_days: newPricing.duration_days,
      label: newPricing.label,
      price: newPricing.price,
    })
    newPricing.duration_days = 0
    newPricing.label = ''
    newPricing.price = 0
    await loadPricings()
  } catch (e) {
    pricingError.value = e.response?.data?.message || 'Lỗi khi thêm'
  } finally {
    pricingLoading.value = false
  }
}

async function togglePricingActive(p) {
  try {
    await updatePricingApi(packageId, p.id, { is_active: !p.is_active })
    await loadPricings()
  } catch (e) {
    pricingError.value = e.response?.data?.message || 'Lỗi'
  }
}

async function removePricing(p) {
  if (!confirm(`Xóa pricing ${p.label}?`)) return
  try {
    await deletePricingApi(packageId, p.id)
    await loadPricings()
  } catch (e) {
    pricingError.value = e.response?.data?.message || 'Lỗi'
  }
}

const validate = () => {
  let isValid = true
  Object.keys(errors).forEach(key => errors[key] = '')

  if (!form.name) {
    errors.name = 'Vui lòng nhập tên gói'
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
    await updatePackage(packageId, {
      name: form.name,
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
