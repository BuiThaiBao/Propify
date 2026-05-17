import { computed, ref } from 'vue';
import { keepPreviousData, useQuery } from '@tanstack/vue-query';
import listingService from '@/services/listingService';
import { hydrateListingAddresses } from '@/utils/addressFormatter';
import { listingKeys } from '@/composables/queryKeys';

async function fetchRentListings(keyword) {
  const response = await listingService.getPublicListings({
    demand_type: 'RENT',
    keyword: keyword?.trim() || undefined,
    per_page: 20,
  });
  const listings = response?.data?.data || [];
  await hydrateListingAddresses(listings);
  return {
    data: listings,
    meta: response?.data?.meta || {},
  };
}

export function useRentListings() {
  const enabled = ref(false);
  const searchKeyword = ref('');

  const queryKey = computed(() => listingKeys.publicList({
    demand_type: 'RENT',
    keyword: searchKeyword.value.trim(),
    per_page: 20,
    page: 1,
  }));

  const query = useQuery({
    queryKey,
    queryFn: () => fetchRentListings(searchKeyword.value),
    enabled,
    placeholderData: keepPreviousData,
    staleTime: 60 * 1000,
  });

  const rentListings = computed(() => query.data.value?.data || []);
  const rentTotal = computed(() => Number(query.data.value?.meta?.total || rentListings.value.length || 0));
  const rentLoading = computed(() => query.isLoading.value || query.isFetching.value);

  const rentSuggestions = computed(() => {
    const queryText = normalizeText(searchKeyword.value);
    if (!queryText) return [];

    const candidates = rentListings.value.flatMap((item) => [
      item.title,
      item.property?.full_address,
      item.property?.address_detail,
      item.property?.project_name,
    ]).filter(Boolean);

    return [...new Set(candidates)]
      .filter((text) => normalizeText(text).includes(queryText))
      .slice(0, 8);
  });

  function init() {
    enabled.value = true;
  }

  async function onSearch(value) {
    searchKeyword.value = value || '';
    enabled.value = true;
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
