import api from "./api";

/**
 * User profile service — wraps all user-related API calls.
 */
const userService = {
  /**
   * Lấy thông tin profile user đang đăng nhập.
   */
  getProfile() {
    return api.get("/v1/user/profile");
  },

  /**
   * Cập nhật thông tin cá nhân (chỉ full_name).
   * @param {{ fullName: string }} data
   */
  updateProfile(data) {
    return api.put("/v1/user/profile", {
      full_name: data.fullName,
      phone: data.phone || undefined,
    });
  },

  /**
   * Đổi mật khẩu.
   * @param {{ currentPassword: string, newPassword: string, newPasswordConfirmation: string }} data
   */
  changePassword(data) {
    return api.put("/v1/user/change-password", {
      current_password: data.currentPassword,
      new_password: data.newPassword,
      new_password_confirmation: data.newPasswordConfirmation,
    });
  },
};

export default userService;
