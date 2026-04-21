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
    const formData = new FormData();

    appendIfPresent(formData, "demand_type", payload.demandType);
    appendIfPresent(formData, "title", payload.title);
    appendIfPresent(formData, "description", payload.description);
    appendIfPresent(formData, "property_type", payload.propertyType);
    appendIfPresent(formData, "province_code", payload.provinceCode);
    appendIfPresent(formData, "district_code", payload.districtCode);
    appendIfPresent(formData, "ward_code", payload.wardCode);
    appendIfPresent(formData, "street_code", payload.streetCode);
    appendIfPresent(formData, "project_name", payload.projectName);
    appendIfPresent(formData, "address_detail", payload.addressDetail);

    appendIfPresent(formData, "area", payload.area);
    appendIfPresent(formData, "price", payload.price);
    appendBoolean(formData, "is_negotiable", payload.isNegotiable);

    appendIfPresent(formData, "bedrooms", payload.bedrooms);
    appendIfPresent(formData, "bathrooms", payload.bathrooms);
    appendIfPresent(formData, "floors", payload.floors);
    appendIfPresent(formData, "floor_number", payload.floorNumber);
    appendIfPresent(formData, "balconies", payload.balconies);
    appendIfPresent(formData, "facade_width", payload.facadeWidth);
    appendIfPresent(formData, "depth", payload.depth);
    appendIfPresent(formData, "road_width", payload.roadWidth);
    appendIfPresent(formData, "direction_code", payload.directionCode);
    appendIfPresent(formData, "balcony_direction_code", payload.balconyDirectionCode);
    appendIfPresent(formData, "furniture_status", payload.furnitureStatus);

    appendIfPresent(formData, "contact_name", payload.contactName);
    appendIfPresent(formData, "contact_phone", payload.contactPhone);
    appendIfPresent(formData, "contact_email", payload.contactEmail);
    appendIfPresent(formData, "poster_type", payload.posterType);

    appendIfPresent(formData, "lat", payload.lat);
    appendIfPresent(formData, "lng", payload.lng);
    appendIfPresent(formData, "package_id", payload.packageId);

    appendBoolean(formData, "request_verification", payload.requestVerification);

    if (Array.isArray(payload.images)) {
      payload.images.forEach((file) => {
        formData.append("images[]", file);
      });
    }

    if (payload.video) {
      formData.append("video", payload.video);
    }

    if (Array.isArray(payload.attributeIds)) {
      payload.attributeIds.forEach((id) => {
        appendIfPresent(formData, "attribute_ids[]", id);
      });
    }

    if (Array.isArray(payload.amenities)) {
      payload.amenities.forEach((item) => {
        appendIfPresent(formData, "amenities[]", item);
      });
    }

    if (Array.isArray(payload.legalPaperTypes)) {
      payload.legalPaperTypes.forEach((item) => {
        appendIfPresent(formData, "legal_paper_types[]", item);
      });
    }

    appendBoolean(formData, "public_info_agreed", payload.publicInfoAgreed);

    if (payload.identityCardFront) {
      formData.append("identity_card_front", payload.identityCardFront);
    }

    if (payload.identityCardBack) {
      formData.append("identity_card_back", payload.identityCardBack);
    }

    if (Array.isArray(payload.legalDocuments)) {
      payload.legalDocuments.forEach((file) => {
        formData.append("legal_documents[]", file);
      });
    }

    appendIfPresent(formData, "appointment_at", payload.appointmentAt);
    if (Array.isArray(payload.appointmentDays)) {
      payload.appointmentDays.forEach((day) => {
        appendIfPresent(formData, "appointment_days[]", day);
      });
    }
    appendIfPresent(formData, "appointment_time_slot", payload.appointmentTimeSlot);
    appendIfPresent(formData, "appointment_contact_name", payload.appointmentContactName);
    appendIfPresent(formData, "appointment_contact_phone", payload.appointmentContactPhone);
    appendIfPresent(formData, "appointment_contact_email", payload.appointmentContactEmail);
    appendIfPresent(formData, "appointment_note", payload.appointmentNote);

    return api.post("/v1/listings", formData, {
      headers: {
        "Content-Type": "multipart/form-data",
      },
    });
  },
};

export default listingService;
