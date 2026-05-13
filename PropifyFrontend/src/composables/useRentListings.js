import { computed, ref } from 'vue';
import listingService from '@/services/listingService';
import { hydrateListingAddresses } from '@/utils/addressFormatter';

const rentListings = ref([]);
const rentLoading = ref(true);
const rentTotal = ref(0);
const searchKeyword = ref('');
const cache = new Map();
let initialized = false;

function cacheKey(keyword) {
  return (keyword || '').trim();
}

function applyData(response) {
  rentListings.value = response?.data?.data || [];
  rentTotal.value = Number(response?.data?.meta?.total || rentListings.value.length || 0);
  rentLoading.value = false;
}

async function fetchRentListings() {
  const keyword = searchKeyword.value;
  const key = cacheKey(keyword);

  if (cache.has(key)) {
    applyData(cache.get(key));
    return;
  }

  rentLoading.value = true;
  try {
    const response = await listingService.getPublicListings({
      demand_type: 'RENT',
      keyword: keyword?.trim() || undefined,
      per_page: 20,
    });
    await hydrateListingAddresses(response?.data?.data || []);
    cache.set(key, response);
    applyData(response);
  } catch (error) {
    console.error('Failed to fetch rent listings', error);
    rentListings.value = [];
    rentTotal.value = 0;
  } finally {
    rentLoading.value = false;
  }
}

export function useRentListings() {
  const rentSuggestions = computed(() => {
    const query = normalizeText(searchKeyword.value);
    if (!query) return [];

    const candidates = rentListings.value.flatMap((item) => [
      item.title,
      item.property?.full_address,
      item.property?.address_detail,
      item.property?.project_name,
    ]).filter(Boolean);

    return [...new Set(candidates)]
      .filter((text) => normalizeText(text).includes(query))
      .slice(0, 8);
  });

  async function init() {
    if (initialized && rentListings.value.length > 0) return;
    initialized = true;
    await fetchRentListings();
  }

  async function onSearch(value) {
    searchKeyword.value = value || '';
    await fetchRentListings();
  }

  return {
    rentListings,
    rentLoading,
    rentTotal,
    searchKeyword,
    rentSuggestions,
    init,
    onSearch,
  };
}

function normalizeText(value) {
  return String(value || '')
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .toLowerCase();
}
