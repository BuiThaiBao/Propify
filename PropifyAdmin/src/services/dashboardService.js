import api from './api'

export default {
  getStats(params = {}) {
    return api.get('/v1/admin/dashboard/stats', { params })
  },
}
