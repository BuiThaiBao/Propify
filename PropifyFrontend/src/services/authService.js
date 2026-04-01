import api from "./api";

// Mỗi function gọi 1 API endpoint, trả về response.data
const authService = {
  /**
   * Đăng nhập
   * Backend cần: { email, password }
   * Backend trả: { status, message, data: { user, access_token, token_type, expires_in } }
   */
  login(email, password) {
    return api.post("/v1/auth/login", { email, password });
  },

  /**
   * Đăng ký
   * Backend cần: { full_name, phone, email, password, password_confirmation }
   */
  register(data) {
    return api.post("/v1/auth/register", {
      full_name: data.fullName,
      phone: data.phone,
      email: data.email,
      password: data.password,
      password_confirmation: data.passwordConfirmation,
    });
  },

  /**
   * Lấy thông tin user đang login
   * Cần gửi token → interceptor tự xử lý
   */
  getMe() {
    return api.get("/v1/auth/me");
  },

  /**
   * Đăng xuất — invalidate token phía server
   */
  logout() {
    return api.post("/v1/auth/logout");
  },

  /**
   * Refresh token
   */
  refreshToken() {
    return api.post("/v1/auth/refresh");
  },
};

export default authService;
