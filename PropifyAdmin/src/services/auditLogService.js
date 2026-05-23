import api from '@/services/api'

export default {
  getAuditLogs(params = {}) {
    return api.get('/v1/admin/audit-logs', { params })
  },
}
