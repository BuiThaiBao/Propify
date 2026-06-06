import { ref } from 'vue'
import api from '@/services/api'

export function useTransactionApi() {
  const loading = ref(false)
  const error = ref(null)

  const runRequest = async (request, fallbackMessage) => {
    loading.value = true
    error.value = null

    try {
      return await request()
    } catch (err) {
      error.value = err.response?.data?.message || err.message || fallbackMessage
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchTransactions = async (params = {}) => {
    const response = await runRequest(
      () => api.get('/v1/admin/transactions', { params }),
      'Có lỗi xảy ra khi lấy danh sách giao dịch',
    )

    return response.data
  }

  const fetchTransaction = async (id) => {
    const response = await runRequest(
      () => api.get(`/v1/admin/transactions/${id}`),
      'Có lỗi xảy ra khi lấy chi tiết giao dịch',
    )

    return response.data.data
  }

  const storeNote = async (id, note) => {
    const response = await runRequest(
      () => api.post(`/v1/admin/transactions/${id}/notes`, { note }),
      'Có lỗi xảy ra khi thêm ghi chú',
    )

    return response.data.data
  }

  const exportCsv = async (params = {}) => {
    const response = await runRequest(
      () =>
        api.get('/v1/admin/transactions/export', {
          params,
          responseType: 'blob',
        }),
      'Có lỗi xảy ra khi tải báo cáo',
    )

    const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
    const url = window.URL.createObjectURL(blob)
    const link = document.createElement('a')
    link.href = url

    const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '')
    const timeStr = new Date().toTimeString().slice(0, 8).replace(/:/g, '')
    link.setAttribute('download', `bao_cao_giao_dich_${dateStr}_${timeStr}.csv`)

    document.body.appendChild(link)
    link.click()
    document.body.removeChild(link)
    window.URL.revokeObjectURL(url)

    return true
  }

  return {
    fetchTransactions,
    fetchTransaction,
    storeNote,
    exportCsv,
    loading,
    error,
  }
}
