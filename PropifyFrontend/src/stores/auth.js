import { defineStore } from "pinia";
import { ref, computed } from "vue";
import authService from "@/services/authService";

/** Named constant for localStorage key */
const TOKEN_KEY = "access_token";

export const useAuthStore = defineStore("auth", () => {
  // ============ STATE ============
  /** @type {import('vue').Ref<Object|null>} Thông tin user */
  const user = ref(null);
  /** @type {import('vue').Ref<string|null>} JWT token */
  const token = ref(null);
  /** @type {import('vue').Ref<boolean>} Đang xử lý request */
  const loading = ref(false);

  // ============ GETTERS ============
  const isAuthenticated = computed(() => !!token.value);

  // ============ ACTIONS ============

  /**
   * Khởi tạo auth khi app mount.
   * Đọc token từ localStorage → nếu có thì gọi /me để lấy user.
   */
  async function initAuth() {
    const savedToken = localStorage.getItem(TOKEN_KEY);
    if (!savedToken) return;

    token.value = savedToken;
    try {
      await fetchUser();
    } catch {
      // Token hết hạn hoặc invalid → clear
      clearAuth();
    }
  }

  /**
   * Đăng nhập với email và password.
   *
   * @param {string} email
   * @param {string} password
   * @returns {Promise<{success: boolean, message?: string}>}
   */
  async function login(email, password) {
    loading.value = true;
    try {
      const res = await authService.login(email, password);
      const data = res.data.data;

      token.value = data.access_token;
      localStorage.setItem(TOKEN_KEY, data.access_token);
      user.value = data.user;

      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || "Đăng nhập thất bại";
      return { success: false, message };
    } finally {
      loading.value = false;
    }
  }

  /**
   * Đăng ký tài khoản mới.
   *
   * @param {Object} formData
   * @returns {Promise<{success: boolean, message?: string, errors?: Object}>}
   */
  async function register(formData) {
    loading.value = true;
    try {
      const res = await authService.register(formData);
      const data = res.data.data;

      token.value = data.access_token;
      localStorage.setItem(TOKEN_KEY, data.access_token);
      user.value = data.user;

      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || "Đăng ký thất bại";
      const errors = error.response?.data?.errors || null;
      return { success: false, message, errors };
    } finally {
      loading.value = false;
    }
  }

  /**
   * Lấy thông tin user từ /me endpoint.
   */
  async function fetchUser() {
    const res = await authService.getMe();
    user.value = res.data.data;
  }

  /**
   * Đăng xuất — gọi API rồi clear local state.
   */
  async function logout() {
    try {
      await authService.logout();
    } catch {
      // Kệ lỗi, vẫn clear local state
    }
    clearAuth();
  }

  /**
   * Xóa toàn bộ auth state (token + user).
   */
  function clearAuth() {
    user.value = null;
    token.value = null;
    localStorage.removeItem(TOKEN_KEY);
  }

  /**
   * Xử lý token từ Google OAuth callback.
   * Frontend nhận token từ URL: /login-success?token=xxx
   *
   * @param {string} googleToken - JWT token from Google OAuth callback
   */
  async function setTokenFromGoogle(googleToken) {
    token.value = googleToken;
    localStorage.setItem(TOKEN_KEY, googleToken);

    try {
      await fetchUser();
    } catch {
      clearAuth();
      throw new Error("Failed to fetch user after Google login");
    }
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    initAuth,
    login,
    register,
    fetchUser,
    logout,
    setTokenFromGoogle,
  };
});
