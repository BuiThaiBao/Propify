export const EMPTY_LABEL = 'Không có'

export function formatAdminListingPrice(value) {
  const amount = Number(value)
  if (!Number.isFinite(amount) || amount <= 0) return '--'
  return new Intl.NumberFormat('vi-VN').format(amount)
}

export function formatAdminDateTime(value) {
  if (!value) return '--'
  const date = new Date(value)
  if (Number.isNaN(date.getTime())) return '--'

  const time = new Intl.DateTimeFormat('vi-VN', {
    hour: '2-digit',
    minute: '2-digit',
    hour12: false,
  }).format(date)
  const day = new Intl.DateTimeFormat('vi-VN', {
    day: '2-digit',
    month: '2-digit',
    year: 'numeric',
  }).format(date)

  return `${time} ${day}`
}

export function formatAdminPropertyType(type) {
  const labels = {
    APARTMENT: 'Chung cư',
    MINI_APARTMENT: 'Chung cư mini',
    HOUSE: 'Nhà riêng',
    PRIVATE_HOUSE: 'Nhà riêng',
    STREET_HOUSE: 'Nhà mặt phố',
    VILLA: 'Biệt thự',
    VILLA_TOWNHOUSE: 'Biệt thự liền kề',
    SHOPHOUSE: 'Shophouse',
    KIOSK: 'Ki-ốt',
    LAND: 'Đất nền',
    TOWNHOUSE: 'Liền kề',
    OFFICE: 'Văn phòng',
    ROOM: 'Phòng',
    RENT_ROOM: 'Phòng trọ',
    BOARDING_HOUSE: 'Nhà trọ',
    HOTEL: 'Khách sạn',
    RESORT: 'Resort',
    RESTAURANT_HOTEL: 'Nhà hàng - Khách sạn',
  }

  if (!type) return '--'
  return (
    labels[type] ||
    type
      .toLowerCase()
      .replace(/_/g, ' ')
      .replace(/\b\w/g, (char) => char.toUpperCase())
  )
}

export function formatAdminDemandType(type) {
  const labels = {
    RENT: 'Cho thuê',
    SALE: 'Mua bán',
  }

  return labels[type] || type || '--'
}

export function mapAdminListingStatusKey(status) {
  const statuses = {
    ACTIVE: 'approved',
    PENDING: 'pending',
    REJECTED: 'rejected',
    LOCKED: 'locked',
    EXPIRED: 'locked',
  }

  return statuses[status] || 'pending'
}

export function displayAdminValue(value, emptyLabel = EMPTY_LABEL) {
  return value === null || value === undefined || value === '' ? emptyLabel : value
}

export function formatAdminMetric(value, unit, emptyLabel = EMPTY_LABEL) {
  return value === null || value === undefined || value === '' ? emptyLabel : `${value} ${unit}`
}

export function formatAdminPosterType(value) {
  return (
    {
      OWNER: 'Chủ sở hữu',
      BROKER: 'Môi giới',
    }[value] ||
    value ||
    EMPTY_LABEL
  )
}
