import { buildPropertyAddress } from '@/utils/addressFormatter';

function hasSameDemandType(current, item) {
  return item?.demand_type === current?.demand_type;
}

function hasSameProvince(current, item) {
  const currentProvince = String(current?.property?.province_code || '');
  const itemProvince = String(item?.property?.province_code || '');
  return currentProvince !== '' && itemProvince === currentProvince;
}

function getListingImage(item) {
  const images = Array.isArray(item?.images) ? item.images : [];
  return images.find((image) => image?.is_thumbnail)?.url || images[0]?.url || '';
}

function shortPropertyAddress(property) {
  return [
    property?.ward_name || property?.ward,
    property?.province_name || property?.province,
  ].filter(Boolean).join(', ');
}

function coordinateDistanceKm(source, target) {
  const lat1 = Number(source?.lat);
  const lng1 = Number(source?.lng);
  const lat2 = Number(target?.lat);
  const lng2 = Number(target?.lng);

  if (![lat1, lng1, lat2, lng2].every(Number.isFinite)) return Number.POSITIVE_INFINITY;

  const toRad = (value) => (value * Math.PI) / 180;
  const earthRadiusKm = 6371;
  const dLat = toRad(lat2 - lat1);
  const dLng = toRad(lng2 - lng1);
  const a = Math.sin(dLat / 2) ** 2
    + Math.cos(toRad(lat1)) * Math.cos(toRad(lat2)) * Math.sin(dLng / 2) ** 2;

  return 2 * earthRadiusKm * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
}

class RelatedListingStrategy {
  select() {
    throw new Error('RelatedListingStrategy.select() must be implemented.');
  }
}

class SameDemandSameProvinceNearestStrategy extends RelatedListingStrategy {
  select(currentListing, listings, options = {}) {
    const limit = options.limit || 4;
    const formatPrice = options.formatPrice || ((value) => value);

    if (!currentListing?.id || !Array.isArray(listings)) return [];

    return listings
      .filter((item) => Number(item.id) !== Number(currentListing.id))
      .filter((item) => hasSameDemandType(currentListing, item))
      .filter((item) => hasSameProvince(currentListing, item))
      .map((item) => ({
        id: item.id,
        image: getListingImage(item),
        title: item.title,
        address: shortPropertyAddress(item.property) || buildPropertyAddress(item.property),
        price: formatPrice(item.property?.price),
        distance: coordinateDistanceKm(currentListing.property, item.property),
        latitude: item.property?.lat,
        longitude: item.property?.lng,
      }))
      .sort((a, b) => a.distance - b.distance)
      .slice(0, limit);
  }
}

const defaultRelatedListingStrategy = new SameDemandSameProvinceNearestStrategy();

export function selectRelatedListings(currentListing, listings, options = {}) {
  const strategy = options.strategy || defaultRelatedListingStrategy;
  return strategy.select(currentListing, listings, options);
}

export {
  RelatedListingStrategy,
  SameDemandSameProvinceNearestStrategy,
};
