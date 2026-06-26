import { computed } from 'vue';
import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';
import appointmentService from '@/services/appointmentService';
import { appointmentKeys } from '@/composables/queryKeys';

/**
 * Composable: lấy danh sách khung giờ hẹn cho một listing.
 * @param {import('vue').Ref<number|string>} listingIdRef
 * @param {{ enabled?: import('vue').Ref<boolean> }} options
 */
export function useAppointmentSlots(listingIdRef, options = {}) {
  const enabledRef = options.enabled ?? computed(() => !!listingIdRef.value);

  const query = useQuery({
    queryKey: computed(() => appointmentKeys.slots(listingIdRef.value)),
    queryFn: async () => {
      const res = await appointmentService.getAppointmentSlots(listingIdRef.value);
      return res.data.data || [];
    },
    enabled: enabledRef,
    staleTime: 60 * 1000,
  });

  return {
    slots: computed(() => query.data.value || []),
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    error: query.error,
    refetch: query.refetch,
  };
}

/**
 * Composable: lấy danh sách lịch hẹn (của tôi hoặc nhận được).
 * @param {import('vue').Ref<'my'|'received'>} viewRef
 */
export function useBookings(viewRef) {
  const queryClient = useQueryClient();

  const query = useQuery({
    queryKey: computed(() => appointmentKeys.bookings(viewRef.value)),
    queryFn: async () => {
      const res = viewRef.value === 'my'
        ? await appointmentService.getMyBookings()
        : await appointmentService.getReceivedBookings();
      return res.data.data || [];
    },
    staleTime: 30 * 1000,
  });

  function invalidateBookings() {
    return queryClient.invalidateQueries({ queryKey: appointmentKeys.all });
  }

  return {
    bookings: computed(() => query.data.value || []),
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    error: query.error,
    refetch: query.refetch,
    invalidateBookings,
  };
}

/**
 * Mutation: tạo mới một booking.
 */
export function useCreateBooking() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload) => appointmentService.createBooking(payload),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: appointmentKeys.all });
    },
  });
}

/**
 * Mutation: cập nhật trạng thái booking (approve / reject).
 */
export function useUpdateBookingStatus() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ bookingId, status, note }) =>
      appointmentService.updateBookingStatus(bookingId, status, note),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: appointmentKeys.all });
    },
  });
}

/**
 * Mutation: hủy booking.
 */
export function useCancelBooking() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: ({ bookingId, reason }) =>
      appointmentService.cancelBooking(bookingId, reason),
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: appointmentKeys.all });
    },
  });
}
