import axios from "axios";
import router from "@/router";

/** Named constants to avoid magic strings */
const API_REFRESH_URL = "/v1/auth/refresh";
const TOKEN_KEY = "access_token";

/**
 * Axios instance pre-configured with base URL and JSON headers.
 * All API calls should use this instance.
 */
const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

/**
 * Request interceptor — automatically attach JWT token to every request.
 */
api.interceptors.request.use((config) => {
  const token = localStorage.getItem(TOKEN_KEY);
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
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

    // Prevent infinite loop: skip refresh if the failing request IS the refresh call
    if (
      originalRequest.url === API_REFRESH_URL ||
      originalRequest.url.includes("/refresh")
    ) {
      localStorage.removeItem(TOKEN_KEY);
      router.push({ name: "Home" });
      return Promise.reject(error);
    }

    // If 401 and haven't retried yet → attempt token refresh
    if (error.response?.status === 401 && !originalRequest._retry) {
      originalRequest._retry = true;

      try {
        const res = await api.post(API_REFRESH_URL);
        const newToken = res.data.data.access_token;

        localStorage.setItem(TOKEN_KEY, newToken);

        // Retry original request with new token
        originalRequest.headers.Authorization = `Bearer ${newToken}`;
        return api(originalRequest);
      } catch (refreshError) {
        // Refresh also failed → clear everything, redirect to login
        localStorage.removeItem(TOKEN_KEY);
        router.push({ name: "Home" });
        return Promise.reject(refreshError);
      }
    }

    return Promise.reject(error);
  },
);

export default api;
