import { ref } from 'vue'
import api from '@/services/api'

export function useTransactionApi() {
  const loading = ref(false)
  const error = ref(null)

  const fetchTransactions = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/v1/admin/transactions', { params })
      return response.data // Trả về { status, message, data, summary, meta }
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi lấy danh sách giao dịch'
      throw err
    } finally {
      loading.value = false
    }
  }

  const fetchTransaction = async (id) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get(`/v1/admin/transactions/${id}`)
      return response.data.data // Trả về chi tiết transaction
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi lấy chi tiết giao dịch'
      throw err
    } finally {
      loading.value = false
    }
  }

  const storeNote = async (id, note) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.post(`/v1/admin/transactions/${id}/notes`, { note })
      return response.data.data // Trả về note vừa tạo
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi thêm ghi chú'
      throw err
    } finally {
      loading.value = false
    }
  }

  // Tải file CSV từ API và lưu xuống trình duyệt
  const exportCsv = async (params = {}) => {
    loading.value = true
    error.value = null
    try {
      const response = await api.get('/v1/admin/transactions/export', {
        params,
        responseType: 'blob'
      })
      
      const blob = new Blob([response.data], { type: 'text/csv;charset=utf-8;' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      
      // Đặt tên file theo thời gian hiện tại
      const dateStr = new Date().toISOString().slice(0, 10).replace(/-/g, '')
      const timeStr = new Date().toTimeString().slice(0, 8).replace(/:/g, '')
      link.setAttribute('download', `bao_cao_giao_dich_${dateStr}_${timeStr}.csv`)
      
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
      
      return true
    } catch (err) {
      error.value = err.response?.data?.message || err.message || 'Có lỗi xảy ra khi tải báo cáo'
      throw err
    } finally {
      loading.value = false
    }
  }

  return {
    fetchTransactions,
    fetchTransaction,
    storeNote,
    exportCsv,
    loading,
    error
  }
}
