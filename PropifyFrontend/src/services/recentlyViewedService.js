import api from './api';

const GUEST_STORAGE_KEY = 'guest_view_history';
const MAX_GUEST_ITEMS = 12;

export default {
  /**
   * Thêm tin đăng vào lịch sử khách vãng lai
   */
  addGuestView(listing) {
    if (!listing || !listing.id) return;

    let items = this.getGuestViews();
    // Loại bỏ tin cũ nếu trùng ID
    items = items.filter(item => item.id !== listing.id);

    // Thêm tin mới lên đầu
    items.unshift({
      id: listing.id,
      title: listing.title,
      demand_type: listing.demand_type,
      status: listing.status,
      published_at: listing.published_at || listing.submitted_at || null,
      views: listing.views || 0,
      is_verified: listing.is_verified ?? 'UNVERIFIED',
      package: listing.package || null,
      property: {
        type: listing.property?.type || null,
        price: listing.property?.price || 0,
        area: listing.property?.area || 0,
        bedrooms: listing.property?.bedrooms || 0,
        bathrooms: listing.property?.bathrooms || 0,
        full_address: listing.property?.full_address || listing.property?.address_detail || '',
      },
      images: listing.images || []
    });

    // Giới hạn số lượng phần tử
    if (items.length > MAX_GUEST_ITEMS) {
      items = items.slice(0, MAX_GUEST_ITEMS);
    }

    localStorage.setItem(GUEST_STORAGE_KEY, JSON.stringify(items));
  },

  /**
   * Lấy lịch sử khách vãng lai
   */
  getGuestViews() {
    try {
      const items = JSON.parse(localStorage.getItem(GUEST_STORAGE_KEY) || '[]');
      return Array.isArray(items) ? items.filter(item => item && (item.status === 'ACTIVE' || item.status === 1 || item.status === true)) : [];
    } catch {
      return [];
    }
  },

  /**
   * Lấy danh sách tin đã xem (cho cả người dùng đã đăng nhập hoặc khách vãng lai)
   */
  async getRecentlyViewed(isAuthenticated) {
    if (isAuthenticated) {
      const response = await api.get('/v1/recently-viewed');
      return response.data?.data || [];
    }
    return this.getGuestViews();
  },

  /**
   * Ghi nhận tin đã xem
   */
  trackListingView(listing, isAuthenticated) {
    if (!listing || !listing.id) return;

    if (!isAuthenticated) {
      this.addGuestView(listing);
    }
  }
};
