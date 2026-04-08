import { defineStore } from "pinia";
import { ref, computed } from "vue";
import authService from "@/services/authService";

/** Named constants for storage keys */
const TOKEN_KEY = "admin_access_token";
const USER_CACHE_KEY = "admin_user";

export const useAuthStore = defineStore("auth", () => {
  // ============ STATE ============
  /** @type {import('vue').Ref<Object|null>} Thông tin admin */
  const user = ref(null);
  /** @type {import('vue').Ref<string|null>} JWT token */
  const token = ref(null);
  /** @type {import('vue').Ref<boolean>} Đang xử lý request */
  const loading = ref(false);

  // ============ GETTERS ============
  const isAuthenticated = computed(() => !!token.value);
  const isAdmin = computed(() => user.value?.role === "ADMIN");

  // ============ ACTIONS ============

  /**
   * Khởi tạo auth khi app mount.
   * Đọc token từ localStorage → restore user từ sessionStorage (cache),
   * chỉ gọi /me nếu cache bị miss.
   * Nếu user không phải admin → clear auth.
   */
  async function initAuth() {
    const savedToken = localStorage.getItem(TOKEN_KEY);
    if (!savedToken) return;

    token.value = savedToken;

    // Thử restore user từ sessionStorage cache trước (tránh gọi /me mỗi lần)
    const cachedUser = sessionStorage.getItem(USER_CACHE_KEY);
    if (cachedUser) {
      try {
        const parsed = JSON.parse(cachedUser);
        if (parsed?.role === "ADMIN") {
          user.value = parsed;
          return; // Cache hit — không cần gọi API
        }
        // Role không hợp lệ trong cache → clear và re-fetch
        sessionStorage.removeItem(USER_CACHE_KEY);
      } catch {
        sessionStorage.removeItem(USER_CACHE_KEY);
      }
    }

    // Cache miss → gọi /me để lấy thông tin user
    try {
      await fetchUser();
      // Kiểm tra role sau khi fetch
      if (!isAdmin.value) {
        clearAuth();
      }
    } catch {
      // Token hết hạn hoặc invalid → clear
      clearAuth();
    }
  }

  /**
   * Đăng nhập với email và password.
   * Chỉ cho phép user có role = "admin" đăng nhập thành công.
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

      // Kiểm tra role trong response trước khi lưu token
      if (data.user?.role !== "ADMIN") {
        return {
          success: false,
          message: "Tài khoản không có quyền truy cập trang quản trị.",
        };
      }

      token.value = data.access_token;
      localStorage.setItem(TOKEN_KEY, data.access_token);
      user.value = data.user;
      sessionStorage.setItem(USER_CACHE_KEY, JSON.stringify(data.user));

      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || "Đăng nhập thất bại";
      return { success: false, message };
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
    // Lưu vào sessionStorage để tránh gọi /me lần sau khi chuyển route
    sessionStorage.setItem(USER_CACHE_KEY, JSON.stringify(user.value));
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
    sessionStorage.removeItem(USER_CACHE_KEY);
  }

  return {
    user,
    token,
    loading,
    isAuthenticated,
    isAdmin,
    initAuth,
    login,
    fetchUser,
    logout,
    clearAuth,
  };
});
