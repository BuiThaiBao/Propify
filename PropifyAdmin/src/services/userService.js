import api from './api'

export const userService = {
  /**
   * Lấy danh sách tài khoản cho Admin
   * @param {Object} params - Các tham số query (search, role, page, per_page)
   */
  getUsers(params = {}) {
    return api.get('/v1/admin/users', { params })
  },

  /**
   * Thay đổi trạng thái tài khoản (Khóa/Mở khóa)
   * @param {Number} id - ID của user
   * @param {Object} data - { status: 'active' | 'locked' }
   */
  changeStatus(id, data) {
    return api.patch(`/v1/admin/users/${id}/status`, data)
  },

  /**
   * Lấy chi tiết tài khoản
   * @param {Number} id - ID của user
   */
  getUser(id) {
    return api.get(`/v1/admin/users/${id}`)
  },

  /**
   * Lấy danh sách tin đăng của tài khoản
   * @param {Number} id - ID của user
   * @param {Object} params - Tham số phân trang/lọc
   */
  getUserListings(id, params = {}) {
    return api.get(`/v1/admin/users/${id}/listings`, { params })
  },

  /**
   * Lấy danh sách lịch sử tác vụ của tài khoản
   * @param {Number} id - ID của user
   * @param {Object} params - Tham số phân trang/lọc
   */
  getUserAuditLogs(id, params = {}) {
    return api.get('/v1/admin/audit-logs', {
      params: {
        ...params,
        auditable_type: 'App\\Models\\User',
        auditable_id: id,
      }
    })
  },
}
