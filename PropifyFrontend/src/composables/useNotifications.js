import { computed } from 'vue';
import { keepPreviousData, useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';
import notificationService from '@/services/notificationService';
import { notificationKeys } from '@/composables/queryKeys';

/**
 * Composable: lấy danh sách thông báo (phân trang).
 * @param {import('vue').Ref<number>} pageRef - trang hiện tại
 * @param {{ perPage?: number, enabled?: import('vue').Ref<boolean> }} options
 */
export function useProfileNotifications(pageRef, options = {}) {
  const { perPage = 10, enabled } = options;

  const queryParams = computed(() => ({
    page: pageRef.value,
    per_page: perPage,
  }));

  const query = useQuery({
    queryKey: computed(() => notificationKeys.list(queryParams.value)),
    queryFn: async () => {
      const response = await notificationService.getNotifications(queryParams.value);
      return {
        items: response?.data?.data || [],
        meta: {
          unreadCount: Number(response?.data?.meta?.unread_count || 0),
          currentPage: Number(response?.data?.meta?.current_page || 1),
          lastPage: Number(response?.data?.meta?.last_page || 1),
          total: Number(response?.data?.meta?.total || 0),
        },
      };
    },
    enabled,
    staleTime: 30 * 1000,
    placeholderData: keepPreviousData,
  });

  return {
    notifications: computed(() => query.data.value?.items || []),
    meta: computed(() => query.data.value?.meta || {
      unreadCount: 0,
      currentPage: 1,
      lastPage: 1,
      total: 0,
    }),
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    error: query.error,
    refetch: query.refetch,
  };
}

/**
 * Mutation: đánh dấu một thông báo đã đọc.
 */
export function useMarkNotificationRead() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (notificationId) => notificationService.markAsRead(notificationId),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: notificationKeys.all });
    },
  });
}

/**
 * Mutation: đánh dấu tất cả thông báo đã đọc.
 */
export function useMarkAllNotificationsRead() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: () => notificationService.markAllAsRead(),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: notificationKeys.all });
    },
  });
}
