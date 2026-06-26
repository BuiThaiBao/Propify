import { computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import recentlyViewedService from '@/services/recentlyViewedService';
import { recentlyViewedKeys } from '@/composables/queryKeys';

/**
 * Composable: lấy lịch sử tin đăng đã xem gần đây.
 * @param {import('vue').Ref<boolean>} isAuthenticatedRef
 */
export function useRecentlyViewed(isAuthenticatedRef) {
  const query = useQuery({
    queryKey: computed(() => recentlyViewedKeys.list(isAuthenticatedRef.value)),
    queryFn: () => recentlyViewedService.getRecentlyViewed(isAuthenticatedRef.value),
    staleTime: 30 * 1000,
  });

  return {
    recentlyViewed: computed(() => query.data.value || []),
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    error: query.error,
    refetch: query.refetch,
  };
}
