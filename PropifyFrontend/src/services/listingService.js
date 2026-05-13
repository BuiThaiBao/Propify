import api from "./api";

function appendIfPresent(formData, key, value) {
  if (value === null || value === undefined || value === "") return;
  formData.append(key, value);
}

function appendBoolean(formData, key, value) {
  formData.append(key, value ? "1" : "0");
}

const listingService = {
  getMyListings(params = {}) {
    return api.get("/v1/listings/my", { params });
  },

  getPublicListings(params = {}) {
    return api.get("/v1/listings", { params });
  },

  getById(id) {
    return api.get(`/v1/listings/${id}`);
  },

  update(id, payload) {
    const data = {
      demand_type: payload.demandType,
      title: payload.title,
      description: payload.description,
      property_type: payload.propertyType,
      province_code: payload.provinceCode,
      district_code: payload.districtCode,
      ward_code: payload.wardCode,
      street_code: payload.streetCode,
      project_name: payload.projectName,
      address_detail: payload.addressDetail,
      area: payload.area,
      price: payload.price,
      is_negotiable: payload.isNegotiable,
      bedrooms: payload.bedrooms,
      bathrooms: payload.bathrooms,
      floors: payload.floors,
      floor_number: payload.floorNumber,
      balconies: payload.balconies,
      facade_width: payload.facadeWidth,
      depth: payload.depth,
      road_width: payload.roadWidth,
      direction_code: payload.directionCode,
      balcony_direction_code: payload.balconyDirectionCode,
      furniture_status: payload.furnitureStatus,
      contact_name: payload.contactName,
      contact_phone: payload.contactPhone,
      contact_email: payload.contactEmail,
      poster_type: payload.posterType,
      lat: payload.lat,
      lng: payload.lng,
      package_id: payload.packageId,
      request_verification: payload.requestVerification,
      rent_min_term: payload.rentMinTerm,
      rent_payment_interval: payload.rentPaymentInterval,
      rent_deposit: payload.rentDeposit,
      images: payload.images || [],
      video: payload.video || null,
      attribute_ids: payload.attributeIds || [],
      amenities: payload.amenities || [],
      legal_paper_types: payload.legalPaperTypes || [],
      public_info_agreed: payload.publicInfoAgreed || false,
      identity_card_front: payload.identityCardFront || null,
      identity_card_back: payload.identityCardBack || null,
      legal_documents: payload.legalDocuments || [],
    };

    Object.keys(data).forEach(key => {
      if (data[key] === null || data[key] === undefined || data[key] === "") {
        delete data[key];
      }
    });

    return api.put(`/v1/listings/${id}`, data);
  },

  lock(id) {
    return api.post(`/v1/listings/${id}/lock`);
  },

  create(payload) {
    const data = {
      demand_type: payload.demandType,
      title: payload.title,
      description: payload.description,
      property_type: payload.propertyType,
      province_code: payload.provinceCode,
      district_code: payload.districtCode,
      ward_code: payload.wardCode,
      street_code: payload.streetCode,
      project_name: payload.projectName,
      address_detail: payload.addressDetail,
      
      area: payload.area,
      price: payload.price,
      is_negotiable: payload.isNegotiable,
      
      bedrooms: payload.bedrooms,
      rent_min_term: payload.rentMinTerm,
      rent_payment_interval: payload.rentPaymentInterval,
      rent_deposit: payload.rentDeposit,
      bathrooms: payload.bathrooms,
      floors: payload.floors,
      floor_number: payload.floorNumber,
      balconies: payload.balconies,
      facade_width: payload.facadeWidth,
      depth: payload.depth,
      road_width: payload.roadWidth,
      direction_code: payload.directionCode,
      balcony_direction_code: payload.balconyDirectionCode,
      furniture_status: payload.furnitureStatus,
      
      contact_name: payload.contactName,
      contact_phone: payload.contactPhone,
      contact_email: payload.contactEmail,
      poster_type: payload.posterType,
      
      lat: payload.lat,
      lng: payload.lng,
      package_id: payload.packageId,
      
      request_verification: payload.requestVerification,
      
      images: payload.images || [],
      video: payload.video || null,
      attribute_ids: payload.attributeIds || [],
      amenities: payload.amenities || [],
      legal_paper_types: payload.legalPaperTypes || [],
      public_info_agreed: payload.publicInfoAgreed || false,
      
      identity_card_front: payload.identityCardFront || null,
      identity_card_back: payload.identityCardBack || null,
      legal_documents: payload.legalDocuments || [],
    };

    // Remove undefined/null/empty strings for cleaner payload
    Object.keys(data).forEach(key => {
      if (data[key] === null || data[key] === undefined || data[key] === "") {
        delete data[key];
      }
    });

    return api.post("/v1/listings", data);
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
