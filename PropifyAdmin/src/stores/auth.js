import { defineStore } from "pinia";
import { ref, computed } from "vue";
import authService from "@/services/authService";
import { getAccessToken } from "@/utils/authCookies";

const USER_CACHE_KEY = "admin_user";

function decodeJwtPayload(jwt) {
  try {
    const payload = jwt.split(".")[1];
    if (!payload) return null;
    const base64 = payload.replace(/-/g, "+").replace(/_/g, "/").padEnd(Math.ceil(payload.length / 4) * 4, "=");
    const json = decodeURIComponent(
      atob(base64)
        .split("")
        .map((char) => `%${char.charCodeAt(0).toString(16).padStart(2, "0")}`)
        .join(""),
    );
    return JSON.parse(json);
  } catch {
    return null;
  }
}

function roleFromToken(jwt) {
  return decodeJwtPayload(jwt)?.role || null;
}

export const useAuthStore = defineStore("auth", () => {
  const user = ref(null);
  const token = ref(null);
  const loading = ref(false);

  const isAuthenticated = computed(() => !!token.value);
  const isAdmin = computed(() => user.value?.role === "ADMIN");

  async function initAuth() {
    const savedToken = getAccessToken();
    if (!savedToken) return;

    if (roleFromToken(savedToken) !== "ADMIN") {
      clearAuth();
      return;
    }

    token.value = savedToken;
    sessionStorage.removeItem(USER_CACHE_KEY);

    try {
      await fetchUser();
      if (!isAdmin.value) {
        clearAuth();
      }
    } catch {
      clearAuth();
    }
  }

  async function login(email, password) {
    loading.value = true;
    try {
      await authService.login(email, password);
      token.value = getAccessToken();

      if (!token.value || roleFromToken(token.value) !== "ADMIN") {
        clearAuth();
        return {
          success: false,
          message: "Tài khoản không có quyền truy cập trang quản trị.",
        };
      }

      await fetchUser();

      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || "Đăng nhập thất bại";
      return { success: false, message };
    } finally {
      loading.value = false;
    }
  }

  async function fetchUser() {
    const res = await authService.getMe();
    user.value = res.data.data;
    sessionStorage.removeItem(USER_CACHE_KEY);
  }

  async function logout() {
    try {
      await authService.logout();
    } catch {
      // Always clear local auth state even when the server request fails.
    }
    clearAuth();
  }

  function clearAuth() {
    user.value = null;
    token.value = null;
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
