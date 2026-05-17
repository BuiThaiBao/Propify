import { computed, ref } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import listingService from '@/services/listingService';
import { hydrateListingAddresses } from '@/utils/addressFormatter';
import { listingKeys } from '@/composables/queryKeys';

async function fetchHomeListings(demandType) {
  const res = await listingService.getPublicListings({
    demand_type: demandType,
    per_page: 6,
  });
  const listings = res.data.data || [];
  await hydrateListingAddresses(listings);
  return listings;
}

export function useHomeListings() {
  const enabled = ref(false);

  const saleQuery = useQuery({
    queryKey: listingKeys.publicList({ demand_type: 'SALE', per_page: 6, page: 1 }),
    queryFn: () => fetchHomeListings('SALE'),
    enabled,
    staleTime: 2 * 60 * 1000,
  });

  const rentQuery = useQuery({
    queryKey: listingKeys.publicList({ demand_type: 'RENT', per_page: 6, page: 1 }),
    queryFn: () => fetchHomeListings('RENT'),
    enabled,
    staleTime: 2 * 60 * 1000,
  });

  function init() {
    enabled.value = true;
  }

  return {
    saleListings: computed(() => saleQuery.data.value || []),
    rentListings: computed(() => rentQuery.data.value || []),
    saleLoading: computed(() => saleQuery.isLoading.value || saleQuery.isFetching.value),
    rentLoading: computed(() => rentQuery.isLoading.value || rentQuery.isFetching.value),
    init,
  };
}
