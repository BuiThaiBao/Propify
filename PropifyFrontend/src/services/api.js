import axios from "axios";
import router from "@/router";
import { getAccessToken } from "@/utils/authCookies";

/** Named constants to avoid magic strings */
const API_REFRESH_URL = "/v1/auth/refresh";

/**
 * Axios instance pre-configured with base URL and JSON headers.
 * All API calls should use this instance.
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  withCredentials: true,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

/** Public endpoints that should NOT send the JWT token */
const PUBLIC_ENDPOINTS = [
  "/v1/auth/login",
  "/v1/auth/register",
  "/v1/auth/resend-register-otp",
  "/v1/auth/verify-otp",
  "/v1/auth/forgot-password",
  "/v1/auth/check-reset-otp",
  "/v1/auth/reset-password",
];

/**
 * Request interceptor — automatically attach JWT token to every request,
 * except for public endpoints (login, register) to avoid sending stale tokens.
 */
api.interceptors.request.use((config) => {
  const isPublic = PUBLIC_ENDPOINTS.some((url) => config.url?.includes(url));
  if (!isPublic) {
    const token = getAccessToken();
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

/**
 * Response interceptor — handle 401 errors with automatic token refresh.
 * If refresh fails, clear auth state and redirect to login.
 */
api.interceptors.response.use(
  (response) => response,

  async (error) => {
    const originalRequest = error.config;

    // Skip refresh for: the refresh endpoint itself, and public endpoints (login/register)
    // Public endpoints returning 401 should surface their own error message, not a refresh error
    const isPublicEndpoint = PUBLIC_ENDPOINTS.some((url) =>
      originalRequest.url?.includes(url),
    );
    const isRefreshEndpoint =
      originalRequest.url === API_REFRESH_URL ||
      originalRequest.url.includes("/refresh");

    if (isRefreshEndpoint) {
      router.push({ name: "Home" });
      return Promise.reject(error);
    }

    if (isPublicEndpoint) {
      return Promise.reject(error);
    }

    // If 401 and haven't retried yet → attempt token refresh
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        await api.post(API_REFRESH_URL);
        const newToken = getAccessToken();

        // Retry original request with new token
        if (newToken) {
          originalRequest.headers.Authorization = `Bearer ${newToken}`;
        }
        return api(originalRequest);
      } catch (refreshError) {
        // Refresh also failed → clear everything, redirect to login
        router.push({ name: "Home" });
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  },
);

export default api;
