function buildPreviewAddress(form, provinceName, wardName) {
  return [
    form.addressDetail,
    form.projectName,
    form.streetCode,
    wardName,
    provinceName,
  ].map((part) => String(part || "").trim()).filter(Boolean).join(", ");
}

function buildPreviewImages(formImages, imagePreviews) {
  if (imagePreviews.length > 0) {
    return imagePreviews.map((item, index) => ({
      id: `preview-${index}`,
      url: item.url,
      sort_order: index,
      is_thumbnail: index === 0,
    }));
  }

  return (Array.isArray(formImages) ? formImages : [])
    .map((item, index) => ({
      id: `preview-${index}`,
      url: typeof item === "string" ? item : "",
      sort_order: index,
      is_thumbnail: index === 0,
    }))
    .filter((item) => Boolean(item.url));
}

export function buildListingPreview({
  form,
  imagePreviews = [],
  selectedAmenities = [],
  authUser = null,
  provinceName = "",
  wardName = "",
}) {
  return {
    id: "preview",
    title: form.title?.trim() || "Tin đăng chưa có tiêu đề",
    description: form.description?.trim() || "Chưa có mô tả",
    demand_type: form.demandType,
    status: "PREVIEW",
    submitted_at: new Date().toISOString(),
    views: 0,
    is_verified: "UNVERIFIED",
    images: buildPreviewImages(form.images, imagePreviews),
    owner: authUser
      ? {
          full_name: authUser.full_name || authUser.name || "",
          avatar_url: authUser.avatar_url || authUser.avatar || "",
        }
      : null,
    property: {
      type: form.propertyType,
      full_address: buildPreviewAddress(form, provinceName, wardName),
      province_code: form.provinceCode,
      ward_code: form.wardCode,
      street_code: form.streetCode,
      project_name: form.projectName,
      address_detail: form.addressDetail,
      area: Number(form.area || 0),
      price: form.isNegotiable ? 0 : Number(form.price || 0),
      is_negotiable: Boolean(form.isNegotiable),
      bedrooms: Number(form.bedrooms || 0),
      bathrooms: Number(form.bathrooms || 0),
      floors: form.floors,
      floor_number: form.floorNumber,
      balconies: form.balconies,
      facade_width: form.facadeWidth,
      depth: form.depth,
      road_width: form.roadWidth,
      direction_code: form.directionCode,
      balcony_direction_code: form.balconyDirectionCode,
      furniture_status: form.furnitureStatus,
      legal_paper_types: Array.isArray(form.legalPaperTypes) ? [...form.legalPaperTypes] : [],
      amenities: [...selectedAmenities],
      contact_name: form.contactName?.trim() || authUser?.full_name || authUser?.name || "",
      contact_phone: form.contactPhone?.trim() || authUser?.phone || "",
      contact_email: form.contactEmail?.trim() || authUser?.email || "",
      poster_type: form.posterType,
      lat: form.lat ? Number(form.lat) : null,
      lng: form.lng ? Number(form.lng) : null,
    },
  };
}
