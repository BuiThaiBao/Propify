import api from "./api";

/**
 * Authentication service for PropifyAdmin.
 * Only supports email/password login — no registration, no Google OAuth.
 */
const authService = {
  /**
   * Login with email and password.
   *
   * @param {string} email
   * @param {string} password
   * @returns {Promise<import('axios').AxiosResponse>} Response containing { user, access_token, token_type, expires_in }
   */
  login(email, password) {
    return api.post("/v1/auth/login", { email, password });
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
   * Refresh the access token using the server's refresh mechanism.
   *
   * @returns {Promise<import('axios').AxiosResponse>}
   */
  refreshToken() {
    return api.post("/v1/auth/refresh");
  },
};

export default authService;
