import api from "./api";
import { buildListingPayload } from "./listingPayloadBuilder";

const listingService = {
  getMyListings(params = {}) {
    return api.get("/v1/listings/my", { params });
  },

  getPublicListings(params = {}) {
    return api.get("/v1/listings", { params });
  },
  getMapListings(params = {}) {
    return api.get("/v1/listings/map", { params });
  },

  getById(id) {
    return api.get(`/v1/listings/${id}`);
  },

  getMineById(id) {
    return api.get(`/v1/listings/my/${id}`);
  },

  update(id, payload) {
    return api.put(`/v1/listings/${id}`, buildListingPayload(payload));
  },

  updateVerification(id, payload) {
    return api.patch(`/v1/listings/${id}/verification`, {
      identity_card_front: payload.identityCardFront,
      identity_card_back: payload.identityCardBack,
      legal_documents: payload.legalDocuments || [],
      public_info_agreed: payload.publicInfoAgreed || false,
    });
  },

  lock(id) {
    return api.post(`/v1/listings/${id}/lock`);
  },

  unlist(id) {
    return api.post(`/v1/listings/${id}/unlist`);
  },

  report(id, payload) {
    return api.post(`/v1/listings/${id}/reports`, payload);
  },

  create(payload) {
    return api.post("/v1/listings", buildListingPayload(payload));
  },

  /**
   * Nâng cấp gói tin cho listing.
   * @param {number} listingId
   * @param {number} packageId
   * @param {number} durationDays - 3, 7, 10, 15, 30
   */
  upgradeListing(listingId, packageId, durationDays) {
    return api.post(`/v1/listings/${listingId}/upgrade`, {
      package_id: packageId,
      duration_days: durationDays,
    });
  },

  /**
   * Tạo nhiều khung giờ hẹn cùng lúc (bulk create).
   * @param {number} listingId
   * @param {Array} slots - [{ day_of_week, start_time, end_time }, ...]
   */
  createAppointmentSlots(listingId, slots) {
    return api.post(`/v1/appointment-slots/create`, {
      listing_id: listingId,
      slots: slots,
    });
  },

  /**
   * Track a view for a listing.
   * Sử dụng sendBeacon (reliable, non-blocking) với fetch fallback.
   *
   * @param {number} id - Listing ID
   */
  trackView(id) {
    return api.post(`/v1/listings/${id}/view`, {}, {
      headers: {
        "X-Anon-Id": getAnonId(),
      },
    });
  },

  replaceAppointmentSlots(listingId, slots) {
    return api.post('/v1/appointment-slots/replace', {
      listing_id: listingId,
      slots: slots,
    });
  },
};

function getAnonId() {
  const key = "propify_anon_id";
  let anonId = localStorage.getItem(key);

  if (!anonId) {
    anonId = crypto.randomUUID?.() || `${Date.now()}-${Math.random().toString(36).slice(2)}`;
    localStorage.setItem(key, anonId);
  }

  return anonId;
}

export default listingService;
