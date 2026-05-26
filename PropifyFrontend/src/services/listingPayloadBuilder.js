const LISTING_PAYLOAD_FIELDS = {
  demand_type: "demandType",
  title: "title",
  description: "description",
  property_type: "propertyType",
  province_code: "provinceCode",
  district_code: "districtCode",
  ward_code: "wardCode",
  street_code: "streetCode",
  project_name: "projectName",
  address_detail: "addressDetail",
  area: "area",
  price: "price",
  is_negotiable: "isNegotiable",
  bedrooms: "bedrooms",
  bathrooms: "bathrooms",
  floors: "floors",
  floor_number: "floorNumber",
  balconies: "balconies",
  facade_width: "facadeWidth",
  depth: "depth",
  road_width: "roadWidth",
  direction_code: "directionCode",
  balcony_direction_code: "balconyDirectionCode",
  furniture_status: "furnitureStatus",
  contact_name: "contactName",
  contact_phone: "contactPhone",
  contact_email: "contactEmail",
  poster_type: "posterType",
  lat: "lat",
  lng: "lng",
  package_id: "packageId",
  request_verification: "requestVerification",
  rent_min_term: "rentMinTerm",
  rent_payment_interval: "rentPaymentInterval",
  rent_deposit: "rentDeposit",
  images: "images",
  video: "video",
  attribute_ids: "attributeIds",
  amenities: "amenities",
  legal_paper_types: "legalPaperTypes",
  public_info_agreed: "publicInfoAgreed",
  identity_card_front: "identityCardFront",
  identity_card_back: "identityCardBack",
  legal_documents: "legalDocuments",
  save_as_draft: "saveAsDraft",
};

const DEFAULT_VALUES = {
  images: [],
  video: null,
  attribute_ids: [],
  amenities: [],
  legal_paper_types: [],
  public_info_agreed: false,
  identity_card_front: null,
  identity_card_back: null,
  legal_documents: [],
  save_as_draft: false,
};

function isBlank(value) {
  return value === null || value === undefined || value === "";
}

export function buildListingPayload(payload) {
  const data = {};

  Object.entries(LISTING_PAYLOAD_FIELDS).forEach(([apiField, formField]) => {
    data[apiField] = payload?.[formField] ?? DEFAULT_VALUES[apiField];
  });

  Object.keys(data).forEach((key) => {
    if (isBlank(data[key])) {
      delete data[key];
    }
  });

  return data;
}
