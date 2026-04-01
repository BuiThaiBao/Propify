import { defineStore } from "pinia";
import { ref, computed } from "vue";
import authService from "@/services/authService";

export const useAuthStore = defineStore("auth", () => {
  // ============ STATE ============
  const user = ref(null); // Thông tin user (object hoặc null)
  const token = ref(null); // JWT token (string hoặc null)
  const loading = ref(false); // Đang xử lý request

  // ============ GETTERS ============
  const isAuthenticated = computed(() => !!token.value);

  // ============ ACTIONS ============

  /**
   * Khởi tạo auth khi app mount
   * Đọc token từ localStorage → nếu có thì gọi /me để lấy user
   */
  async function initAuth() {
    const savedToken = localStorage.getItem("access_token");
    if (savedToken) {
      token.value = savedToken;
      try {
        await fetchUser();
      } catch {
        // Token hết hạn hoặc invalid → clear
        clearAuth();
      }
    }
  }

  /**
   * Đăng nhập
   */
  async function login(email, password) {
    loading.value = true;
    try {
      const res = await authService.login(email, password);
      const data = res.data.data; // { user, access_token, token_type, expires_in }

      // Lưu token
      token.value = data.access_token;
      localStorage.setItem("access_token", data.access_token);

      // Lưu user
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
   * Đăng ký
   */
  async function register(formData) {
    loading.value = true;
    try {
      const res = await authService.register(formData);
      const data = res.data.data;

      token.value = data.access_token;
      localStorage.setItem("access_token", data.access_token);
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
   * Lấy thông tin user từ /me
   */
  async function fetchUser() {
    const res = await authService.getMe();
    user.value = res.data.data;
  }

  /**
   * Đăng xuất
   */
  async function logout() {
    try {
      await authService.logout();
    } catch {
      // Kệ lỗi, vẫn clear local
    }
    clearAuth();
  }

  /**
   * Xóa toàn bộ auth state
   */
  function clearAuth() {
    user.value = null;
    token.value = null;
    localStorage.removeItem("access_token");
  }

  /**
   * Xử lý token từ Google OAuth callback
   * Frontend nhận token từ URL: /login-success?token=xxx
   */
  async function setTokenFromGoogle(googleToken) {
    token.value = googleToken;
    localStorage.setItem("access_token", googleToken);
    await fetchUser();
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
