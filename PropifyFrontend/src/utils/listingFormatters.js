const PROPERTY_TYPE_LABELS = {
  APARTMENT: "Căn hộ chung cư",
  MINI_APARTMENT: "Chung cư mini",
  HOUSE: "Nhà ở",
  LAND: "Đất",
  ROOM: "Phòng",
  PRIVATE_HOUSE: "Nhà riêng",
  STREET_HOUSE: "Nhà mặt phố",
  VILLA_TOWNHOUSE: "Biệt thự liền kề",
  SHOPHOUSE: "Shophouse",
  KIOSK: "Ki-ốt",
  RENT_ROOM: "Phòng trọ",
  BOARDING_HOUSE: "Nhà trọ",
  OFFICE: "Văn phòng",
  RESORT: "Khu nghỉ dưỡng",
  RESTAURANT_HOTEL: "Nhà hàng - Khách sạn",
};

const DEFAULT_THUMB =
  "https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=60";

export function formatPrice(value) {
  const num = Number(value || 0);
  if (!num || num <= 0) return "Thỏa thuận";
  if (num >= 1000000000)
    return `${(num / 1000000000).toLocaleString("vi-VN")} tỷ`;
  if (num >= 1000000) return `${(num / 1000000).toLocaleString("vi-VN")} triệu`;
  return `${num.toLocaleString("vi-VN")} đ`;
}

export function propertyTypeLabel(type) {
  return PROPERTY_TYPE_LABELS[type] || type || "BĐS";
}

export function timeAgo(dateStr) {
  if (!dateStr) return "";

  const now = new Date();
  const date = new Date(dateStr);
  if (Number.isNaN(date.getTime())) return "";

  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  if (diffMins < 60) return `${Math.max(diffMins, 0)} phút trước`;

  const diffHours = Math.floor(diffMins / 60);
  if (diffHours < 24) return `${diffHours} giờ trước`;

  const diffDays = Math.floor(diffHours / 24);
  if (diffDays < 30) return `${diffDays} ngày trước`;

  return date.toLocaleDateString("vi-VN");
}

export function isVerified(item) {
  const value = item?.is_verified ?? item?.isVerified;
  return (
    value === true ||
    Number(value) === 1 ||
    String(value).toUpperCase() === "VERIFIED"
  );
}

export function getThumb(item) {
  if (Array.isArray(item?.images) && item.images.length > 0) {
    const thumb = item.images.find(
      (image) => image?.is_thumbnail || image?.isThumbnail,
    );
    return (
      thumb?.url || item.images[0]?.url || item?.thumbnail || DEFAULT_THUMB
    );
  }

  return item?.thumbnail || DEFAULT_THUMB;
}

export function getAuthor(item) {
  return {
    name: item?.property?.contact_name || item?.owner?.full_name || "Chủ nhà",
    role: item?.property?.poster_type === "OWNER" ? "Chủ nhà" : "Môi giới",
    phone: item?.property?.contact_phone || item?.owner?.phone || "",
  };
}
