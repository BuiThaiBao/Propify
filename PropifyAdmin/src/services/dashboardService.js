import api from './api'

export default {
  getStats() {
    return api.get('/v1/admin/dashboard/stats')
  },
}
