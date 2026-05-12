import api from './api'

export const listingService = {
  /**
   * Lấy danh sách tin đăng cho Admin
   * @param {Object} params - Các tham số query (status, demand_type, keyword, per_page, page)
   */
  getAllListings(params = {}) {
    return api.get('/v1/admin/listings', { params })
  },
  
  /**
   * Thay đổi trạng thái tin đăng (Dành cho admin)
   * @param {Number} id - ID của listing
   * @param {Object} data - { status: 'ACTIVE' | 'REJECTED' | 'LOCKED', rejection_reason?: string }
   */
  changeStatusForAdmin(id, data) {
    return api.patch(`/v1/admin/listings/${id}/status`, data)
  }
}
