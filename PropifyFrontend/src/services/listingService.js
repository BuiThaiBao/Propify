import api from "./api";

function appendIfPresent(formData, key, value) {
  if (value === null || value === undefined || value === "") return;
  formData.append(key, value);
}

function appendBoolean(formData, key, value) {
  formData.append(key, value ? "1" : "0");
}

const listingService = {
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
      
      identity_card_front: payload.identityCardFront || null,
      identity_card_back: payload.identityCardBack || null,
      legal_documents: payload.legalDocuments || [],
      
      appointment_at: payload.appointmentAt,
      appointment_contact_name: payload.appointmentContactName,
      appointment_contact_phone: payload.appointmentContactPhone,
      appointment_contact_email: payload.appointmentContactEmail,
      appointment_note: payload.appointmentNote,
    };

    // Remove undefined/null/empty strings for cleaner payload
    Object.keys(data).forEach(key => {
      if (data[key] === null || data[key] === undefined || data[key] === "") {
        delete data[key];
      }
    });

    return api.post("/v1/listings", data);
  },
};

export default listingService;
