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

const CLOUDINARY_REGEX = /res\.cloudinary\.com\/[^/]+\/image\/upload\/(?:v\d+\/)?(.+)/;

/**
 * Thêm cloudinary transform vào URL ảnh.
 * @param {string} url
 * @param {object} opts - { width?, quality?, format? }
 * @returns {string}
 */
export function optimizeImage(url, opts = {}) {
  if (!url || !CLOUDINARY_REGEX.test(url)) return url;

  const { width = 0, quality = "auto", format = "auto" } = opts;
  const parts = [];

  parts.push(format === "auto" ? "f_auto" : `f_${format}`);
  parts.push(quality === "auto" ? "q_auto" : `q_${quality}`);
  if (width > 0) parts.push(`w_${width}`);

  return url.replace(
    /(res\.cloudinary\.com\/[^/]+\/image\/upload)\//,
    `$1/${parts.join(",")}/`,
  );
}

/**
 * Lấy URL ảnh thumbnail cho listing card (tối ưu).
 * Fallback: nếu không có ảnh, lấy frame đầu tiên từ video.
 */
export function getThumb(item) {
  // Ưu tiên ảnh có sẵn
  if (Array.isArray(item?.images) && item.images.length > 0) {
    const thumb = item.images.find(
      (image) => image?.is_thumbnail || image?.isThumbnail,
    );
    const raw = thumb?.url || item.images[0]?.url || item?.thumbnail || DEFAULT_THUMB;
    return optimizeImage(raw, { width: 300, quality: "auto" });
  }

  // Fallback sang video thumbnail
  if (Array.isArray(item?.videos) && item.videos.length > 0) {
    return getVideoThumb(item.videos[0]?.url, { width: 300 });
  }

  return optimizeImage(item?.thumbnail || DEFAULT_THUMB, { width: 300 });
}

/**
 * Sinh thumbnail từ Cloudinary video (lấy frame đầu).
 */
export function getVideoThumb(videoUrl, opts = {}) {
  if (!videoUrl) return DEFAULT_THUMB;
  const thumb = videoUrl
    .replace(/\/upload\//, "/upload/so_1/")
    .replace(/\.\w+$/, ".jpg");
  return optimizeImage(thumb, opts);
}

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

export function getAuthor(item) {
  return {
    name: item?.property?.contact_name || item?.owner?.full_name || "Chủ nhà",
    role: item?.property?.poster_type === "OWNER" ? "Chủ nhà" : "Môi giới",
    phone: item?.property?.contact_phone || item?.owner?.phone || "",
  };
}
