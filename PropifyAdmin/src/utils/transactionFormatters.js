export const TRANSACTION_EMPTY_LABEL = '-'

export function formatTransactionAmount(value) {
  if (value === undefined || value === null || value === '') return '0 đ'
  return new Intl.NumberFormat('vi-VN', {
    style: 'currency',
    currency: 'VND',
  }).format(value)
}

export function formatTransactionDateTime(value, emptyLabel = TRANSACTION_EMPTY_LABEL) {
  if (!value) return emptyLabel

  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return emptyLabel

  return date.toLocaleString('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit',
    timeZone: 'Asia/Ho_Chi_Minh',
  })
}

export function getTransactionPackageBadgeClass(slug) {
  const normalized = String(slug || '').toLowerCase()

  if (normalized === 'diamond') return 'bg-blue-50 text-blue-600 border border-blue-200'
  if (normalized === 'ruby') return 'bg-rose-50 text-rose-600 border border-rose-200/80'
  if (normalized === 'gold') return 'bg-amber-50 text-amber-600 border border-amber-200/80'

  return 'bg-slate-50 text-slate-600 border border-slate-200/60'
}

export function formatCompactCurrency(val) {
  const amount = Number(val || 0)
  return (
    new Intl.NumberFormat('vi-VN', { notation: 'compact', maximumFractionDigits: 1 }).format(
      amount,
    ) + 'đ'
  )
}
