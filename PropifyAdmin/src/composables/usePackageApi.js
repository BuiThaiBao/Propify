import { ref } from 'vue'
import api from '@/services/api'

export function usePackageApi() {
  const loading = ref(false)
  const error = ref(null)

  const runRequest = async (request, fallbackMessage) => {
    loading.value = true
    error.value = null

    try {
      const response = await request()
      return response.data
    } catch (err) {
      error.value = err.response?.data?.message || err.message || fallbackMessage
      throw err
    } finally {
      loading.value = false
    }
  }

  const createPackage = async (packageData) => {
    return runRequest(() => api.post('/v1/packages', packageData), 'Có lỗi xảy ra khi tạo gói tin')
  }

  const fetchPackages = async (params = {}) => {
    return runRequest(
      () => api.get('/v1/packages', { params }),
      'Có lỗi xảy ra khi lấy danh sách gói tin',
    )
  }

  const fetchPackage = async (id) => {
    return runRequest(
      () => api.get(`/v1/packages/${id}`),
      'Có lỗi xảy ra khi lấy thông tin gói tin',
    )
  }

  const updatePackage = async (id, packageData) => {
    return runRequest(
      () => api.put(`/v1/packages/${id}`, packageData),
      'Có lỗi xảy ra khi cập nhật gói tin',
    )
  }

  const deletePackage = async (id) => {
    return runRequest(() => api.delete(`/v1/packages/${id}`), 'Có lỗi xảy ra khi xóa gói tin')
  }

  const fetchDurationOptions = async () => {
    return runRequest(
      () => api.get('/v1/packages/duration-options'),
      'Lỗi khi lấy danh sách thời hạn',
    )
  }

  const createDurationOption = async (durationData) => {
    return runRequest(
      () => api.post('/v1/packages/duration-options', durationData),
      'Lỗi khi tạo thời hạn',
    )
  }

  const fetchPricings = async (packageId) => {
    return runRequest(
      () => api.get(`/v1/packages/${packageId}/pricings`),
      'Lỗi khi lấy danh sách pricing',
    )
  }

  const createPricing = async (packageId, pricingData) => {
    return runRequest(
      () => api.post(`/v1/packages/${packageId}/pricings`, pricingData),
      'Lỗi khi tạo pricing',
    )
  }

  const updatePricing = async (packageId, pricingId, pricingData) => {
    return runRequest(
      () => api.put(`/v1/packages/${packageId}/pricings/${pricingId}`, pricingData),
      'Lỗi khi cập nhật pricing',
    )
  }

  const deletePricing = async (packageId, pricingId) => {
    return runRequest(
      () => api.delete(`/v1/packages/${packageId}/pricings/${pricingId}`),
      'Lỗi khi xóa pricing',
    )
  }

  return {
    fetchPackages,
    fetchPackage,
    createPackage,
    updatePackage,
    deletePackage,
    fetchDurationOptions,
    createDurationOption,
    fetchPricings,
    createPricing,
    updatePricing,
    deletePricing,
    loading,
    error,
  }
}
