import api from "./api";

/**
 * Authentication service — wraps all auth-related API calls.
 * Each method returns the raw Axios response (caller extracts .data).
 */
const authService = {
  /**
   * Login with email and password.
   *
   * @param {string} email - User's email address
   * @param {string} password - User's password
   * @returns {Promise<import('axios').AxiosResponse>} Response containing { user, access_token, token_type, expires_in }
   */
  login(email, password) {
    return api.post("/v1/auth/login", { email, password });
  },

  /**
   * Register a new user account.
   *
   * @param {Object} data - Registration form data
   * @param {string} data.fullName - User's full name
   * @param {string} data.email - Email address
   * @param {string} data.password - Password (min 8 chars, mixed case, numbers)
   * @param {string} data.passwordConfirmation - Password confirmation
   * @returns {Promise<import('axios').AxiosResponse>}
   */
  register(data) {
    return api.post("/v1/auth/register", {
      full_name: data.fullName,
      email: data.email,
      password: data.password,
      password_confirmation: data.passwordConfirmation,
    });
  },

  /**
   * Resend OTP for registration
   * @param {string} email
   */
  resendRegisterOtp(email) {
    return api.post("/v1/auth/resend-register-otp", { email });
  },

  /**
   * Get the currently authenticated user's profile.
   * Token is attached automatically via interceptor.
   *
   * @returns {Promise<import('axios').AxiosResponse>}
   */
  getMe() {
    return api.get("/v1/auth/me");
  },

  /**
   * Logout — invalidate the current token on the server.
   *
   * @returns {Promise<import('axios').AxiosResponse>}
   */
  logout() {
    return api.post("/v1/auth/logout");
  },

  /**
   * Verify OTP after registration.
   *
   * @param {string} email
   * @param {string} otp - 6 digit OTP
   * @returns {Promise<import('axios').AxiosResponse>}
   */
  verifyOtp(email, otp) {
    return api.post("/v1/auth/verify-otp", { email, otp });
  },

  /**
   * Gửi OTP đặt lại mật khẩu.
   * @param {string} email
   */
  forgotPassword(email) {
    return api.post("/v1/auth/forgot-password", { email });
  },

  /** Bước 2: kiểm tra OTP mà không xóa (để dùng lại ở bước 3) */
  checkResetOtp(email, otp) {
    return api.post("/v1/auth/check-reset-otp", { email, otp });
  },

  /**
   * Đặt lại mật khẩu bằng OTP.
   * @param {string} email
   * @param {string} otp
   * @param {string} password
   * @param {string} passwordConfirmation
   */
  resetPassword(email, otp, password, passwordConfirmation) {
    return api.post("/v1/auth/reset-password", {
      email,
      otp,
      password,
      password_confirmation: passwordConfirmation,
    });
  },

  refreshToken() {
    return api.post("/v1/auth/refresh");
  },
};

export default authService;
