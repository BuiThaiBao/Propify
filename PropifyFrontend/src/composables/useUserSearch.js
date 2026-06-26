import { computed } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import chatService from '@/services/chatService';

/**
 * Composable: tìm kiếm người dùng theo số điện thoại (để chat/nhóm).
 * @param {import('vue').Ref<string>} phoneRef
 * @param {{ enabled?: import('vue').Ref<boolean> }} options
 */
export function useUserSearch(phoneRef, options = {}) {
  const enabledRef = options.enabled ?? computed(() => {
    const clean = (phoneRef.value || '').trim();
    return clean.length >= 2;
  });

  const query = useQuery({
    queryKey: computed(() => ['users', 'search', (phoneRef.value || '').trim()]),
    queryFn: async () => {
      const clean = (phoneRef.value || '').trim();
      if (!clean) return [];
      const res = await chatService.searchUserByPhone(clean);
      return res.data?.data ?? [];
    },
    enabled: enabledRef,
    staleTime: 30 * 1000,
  });

  return {
    results: computed(() => query.data.value || []),
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    error: query.error,
    refetch: query.refetch,
  };
}
