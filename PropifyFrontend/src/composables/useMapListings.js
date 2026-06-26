import { computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import listingService from '@/services/listingService';
import { listingKeys } from '@/composables/queryKeys';

/**
 * Composable: lấy danh sách tin đăng trên bản đồ.
 * @param {import('vue').Ref<Object>} filtersRef
 * @param {{ enabled?: import('vue').Ref<boolean> }} options
 */
export function useMapListings(filtersRef, options = {}) {
  const enabledRef = options.enabled ?? computed(() => true);

  const query = useQuery({
    queryKey: computed(() => listingKeys.mapList(filtersRef.value)),
    queryFn: async () => {
      const res = await listingService.getMapListings(filtersRef.value);
      const rawItems = res?.data?.data?.data ?? res?.data?.data ?? [];
      return rawItems.filter((x) => x.latitude !== null && x.longitude !== null);
    },
    enabled: enabledRef,
    staleTime: 60 * 1000,
  });

  return {
    items: computed(() => query.data.value || []),
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    error: query.error,
    refetch: query.refetch,
  };
}
