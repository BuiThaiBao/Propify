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
   * Refresh the current JWT token.
   *
   * @returns {Promise<import('axios').AxiosResponse>}
   */
  refreshToken() {
    return api.post("/v1/auth/refresh");
  },
};

export default authService;
