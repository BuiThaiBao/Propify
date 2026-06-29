import api from './api'

export function fetchRevenueStats(params = {}) {
  return api.get('/v1/admin/revenue/stats', { params })
}
