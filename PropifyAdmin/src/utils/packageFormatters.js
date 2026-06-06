export function formatPackagePrice(price) {
  return Number(price || 0).toLocaleString('vi-VN', {
    style: 'currency',
    currency: 'VND',
    maximumFractionDigits: 0,
  })
}

export function normalizeDurationDays(values = []) {
  return [...new Set(values.map((days) => Number(days)))]
    .filter((days) => Number.isInteger(days) && days > 0)
    .sort((a, b) => a - b)
}

export function activeDurationDays(pkg) {
  return normalizeDurationDays(
    (pkg?.pricings || [])
      .filter((pricing) => pricing.is_active)
      .map((pricing) => pricing.duration_days),
  )
}

export function formatDurationLabel(days, label) {
  return label || `${days} ngày`
}

export function summarizePricingDurations(pkg) {
  const durations = activeDurationDays(pkg)
  return durations.length ? durations.map((days) => `${days} ngày`).join(', ') : 'Chưa cấu hình'
}
