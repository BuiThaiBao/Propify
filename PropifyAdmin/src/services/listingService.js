import api from './api'

export const listingService = {
  /**
   * Lấy danh sách tin đăng cho Admin
   * @param {Object} params - Các tham số query (status, demand_type, keyword, per_page, page)
   */
  getAllListings(params = {}) {
    return api.get('/v1/admin/listings', { params })
  }
}
