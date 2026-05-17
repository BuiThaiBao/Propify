import { computed, ref } from 'vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import packageService from '@/services/packageService';
import { packageKeys } from '@/composables/queryKeys';

async function fetchPackagesQuery() {
  const res = await packageService.getPackages();
  return res?.data?.data || [];
}

export function usePackages() {
  const queryClient = useQueryClient();
  const enabled = ref(false);
  const query = useQuery({
    queryKey: packageKeys.list(),
    queryFn: fetchPackagesQuery,
    enabled,
    staleTime: 5 * 60 * 1000,
  });

  async function fetchPackages({ force = false } = {}) {
    enabled.value = true;

    if (force) {
      return query.refetch();
    }

    return queryClient.ensureQueryData({
      queryKey: packageKeys.list(),
      queryFn: fetchPackagesQuery,
      staleTime: 5 * 60 * 1000,
    });
  }

  return {
    packages: computed(() => query.data.value || []),
    loading: computed(() => query.isLoading.value || query.isFetching.value),
    error: computed(() => query.error.value ? 'Không thể tải bảng giá. Vui lòng thử lại.' : ''),
    fetchPackages,
  };
}
