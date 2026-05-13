const PROVINCES_API = 'https://provinces.open-api.vn/api/v2';

let provincesPromise = null;
const provinceNameCache = new Map();
const wardNameCache = new Map();
const provinceWardsPromiseCache = new Map();

function normalizeCode(value) {
  if (value === null || value === undefined || value === '') return '';
  return String(value).trim();
}

function uniqueParts(parts) {
  const seen = new Set();
  return parts
    .map((part) => String(part || '').trim())
    .filter(Boolean)
    .filter((part) => {
      const key = part.toLocaleLowerCase('vi-VN');
      if (seen.has(key)) return false;
      seen.add(key);
      return true;
    });
}

export function buildPropertyAddress(property) {
  if (!property) return '';
  return uniqueParts([
    property.address_detail,
    property.project_name,
    property.street_code,
    property.ward_name,
    property.province_name,
  ]).join(', ');
}

async function ensureProvinces() {
  if (!provincesPromise) {
    provincesPromise = fetch(`${PROVINCES_API}/p/`)
      .then((response) => {
        if (!response.ok) throw new Error('Không thể tải danh sách tỉnh/thành phố');
        return response.json();
      })
      .then((items) => {
        items.forEach((item) => {
          provinceNameCache.set(normalizeCode(item.code), item.name);
        });
        return items;
      })
      .catch((error) => {
        provincesPromise = null;
        throw error;
      });
  }
  return provincesPromise;
}

async function resolveProvinceName(provinceCode) {
  const code = normalizeCode(provinceCode);
  if (!code) return '';
  if (provinceNameCache.has(code)) return provinceNameCache.get(code);
  await ensureProvinces();
  return provinceNameCache.get(code) || '';
}

async function ensureWards(provinceCode) {
  const code = normalizeCode(provinceCode);
  if (!code) return [];
  if (!provinceWardsPromiseCache.has(code)) {
    const promise = fetch(`${PROVINCES_API}/w/?province=${encodeURIComponent(code)}`)
      .then((response) => {
        if (!response.ok) throw new Error('Không thể tải danh sách phường/xã');
        return response.json();
      })
      .then((items) => {
        items.forEach((item) => {
          wardNameCache.set(`${code}:${normalizeCode(item.code)}`, item.name);
        });
        return items;
      })
      .catch((error) => {
        provinceWardsPromiseCache.delete(code);
        throw error;
      });
    provinceWardsPromiseCache.set(code, promise);
  }
  return provinceWardsPromiseCache.get(code);
}

async function resolveWardName(provinceCode, wardCode) {
  const province = normalizeCode(provinceCode);
  const ward = normalizeCode(wardCode);
  if (!province || !ward) return '';
  const key = `${province}:${ward}`;
  if (wardNameCache.has(key)) return wardNameCache.get(key);
  await ensureWards(province);
  return wardNameCache.get(key) || '';
}

export async function hydratePropertyAddress(property) {
  if (!property) return property;

  try {
    const [provinceName, wardName] = await Promise.all([
      resolveProvinceName(property.province_code),
      resolveWardName(property.province_code, property.ward_code),
    ]);

    property.province_name = provinceName;
    property.ward_name = wardName;
    property.full_address = buildPropertyAddress(property);
  } catch {
    property.full_address = buildPropertyAddress(property);
  }

  return property;
}

export async function hydrateListingAddresses(listings) {
  if (!Array.isArray(listings) || listings.length === 0) return listings || [];
  await Promise.all(listings.map((item) => hydratePropertyAddress(item?.property)));
  return listings;
}
