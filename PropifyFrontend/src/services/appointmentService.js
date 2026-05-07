import api from "./api";

const appointmentService = {
  /**
   * Lấy danh sách khung giờ hẹn có sẵn cho listing (API công khai, không cần auth).
   * @param {number} listingId
   */
  getAppointmentSlots(listingId) {
    return api.post(`/v1/appointment-slots`, {
      listing_id: listingId,
    });
  },

  /**
   * Đặt lịch hẹn xem nhà (cần đăng nhập).
   * @param {Object} payload - { slot_id, date, full_name, phone, email?, note? }
   */
  createBooking(payload) {
    return api.post(`/v1/appointment-bookings`, payload);
  },

  /**
   * Lấy danh sách lịch hẹn của tôi (viewer - khách thuê).
   */
  getMyBookings() {
    return api.get(`/v1/appointment-bookings`);
  },

  /**
   * Lấy danh sách lịch hẹn nhận được (poster - chủ nhà).
   */
  getReceivedBookings() {
    return api.get(`/v1/appointment-bookings/received`);
  },

  /**
   * Xác nhận / Từ chối lịch hẹn (chủ nhà).
   * @param {number} bookingId
   * @param {string} status - 'APPROVED' hoặc 'CANCELLED'
   * @param {string|null} note
   */
  updateBookingStatus(bookingId, status, note = null) {
    return api.post(`/v1/appointment-bookings/update-status`, {
      booking_id: bookingId,
      status,
      note,
    });
  },

  /**
   * Hủy lịch hẹn (cả khách và chủ nhà).
   * @param {number} bookingId
   * @param {string} reason
   */
  cancelBooking(bookingId, reason) {
    return api.post(`/v1/appointment-bookings/cancel`, {
      booking_id: bookingId,
      reason,
    });
  },
};

export default appointmentService;

