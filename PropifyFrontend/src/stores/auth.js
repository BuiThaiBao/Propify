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
   * Đọc token từ localStorage → restore user từ sessionStorage (cache),
   * chỉ gọi /me nếu cache bị miss.
   */
  async function initAuth() {
    const savedToken = localStorage.getItem(TOKEN_KEY);
    if (!savedToken) return;

    token.value = savedToken;

    // Thử restore user từ sessionStorage cache trước (tránh gọi /me mỗi lần)
    const cachedUser = sessionStorage.getItem('auth_user');
    if (cachedUser) {
      try {
        const parsedUser = JSON.parse(cachedUser);
        if (parsedUser.role === 'ADMIN') {
          clearAuth();
          return;
        }
        user.value = parsedUser;
        return; // Cache hit — không cần gọi API
      } catch {
        sessionStorage.removeItem('auth_user');
      }
    }

    // Cache miss → gọi /me để lấy thông tin user
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

      // Fetch full user data (avatar_url, phone, ...) thay vì dùng data rút gọn từ login response
      await fetchUser();

      return { success: true };
    } catch (error) {
      const code = error.response?.data?.code;
      if (code === 1012 || error.message === "ADMIN_NOT_ALLOWED") {
        clearAuth();
        return { success: false, message: "Tài khoản quản trị không được phép đăng nhập tại đây." };
      }
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
      await authService.register(formData);
      // Backend trả 202 — chưa có token, cần verify OTP trước
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
   * Sinh lại OTP cho user đang đăng ký (Tránh lỗi 422 rules unique email của register module)
   */
  async function resendRegisterOtp(email) {
    loading.value = true;
    try {
      await authService.resendRegisterOtp(email);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || "Không thể gửi lại OTP";
      return { success: false, message };
    } finally {
      loading.value = false;
    }
  }

  /**
   * Xác thực OTP sau đăng ký.
   *
   * @param {string} email
   * @param {string} otp
   * @returns {Promise<{success: boolean, message?: string}>}
   */
  async function verifyOtp(email, otp) {
    loading.value = true;
    try {
      const res = await authService.verifyOtp(email, otp);
      const data = res.data.data;

      token.value = data.access_token;
      localStorage.setItem(TOKEN_KEY, data.access_token);

      // Fetch full user data
      await fetchUser();

      return { success: true };
    } catch (error) {
      const code = error.response?.data?.code;
      if (code === 1012 || error.message === "ADMIN_NOT_ALLOWED") {
        clearAuth();
        return { success: false, message: "Tài khoản quản trị không được phép đăng nhập tại đây." };
      }
      const message = error.response?.data?.message || "Mã OTP không hợp lệ";
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
    const userData = res.data.data;

    // Chặn ADMIN đăng nhập ở client site
    if (userData.role === 'ADMIN') {
      throw new Error("ADMIN_NOT_ALLOWED");
    }

    user.value = userData;
    // Lưu vào sessionStorage để tránh gọi /me lần sau khi chuyển route
    sessionStorage.setItem('auth_user', JSON.stringify(user.value));
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
    sessionStorage.removeItem('auth_user');
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

  /**
   * Gửi OTP quên mật khẩu tới email.
   * @param {string} email
   */
  async function forgotPassword(email) {
    loading.value = true;
    try {
      await authService.forgotPassword(email);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || "Có lỗi xảy ra";
      const errors  = error.response?.data?.errors  || null;
      return { success: false, message, errors };
    } finally {
      loading.value = false;
    }
  }

  /** Bước 2: verify OTP mà không xóa — để dùng lại ở bước 3 */
  async function checkResetOtp(email, otp) {
    loading.value = true;
    try {
      await authService.checkResetOtp(email, otp);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || "Mã OTP không hợp lệ";
      return { success: false, message };
    } finally {
      loading.value = false;
    }
  }

  /**
   * Đặt lại mật khẩu bằng OTP.
   * @param {string} email
   * @param {string} otp
   * @param {string} password
   * @param {string} passwordConfirmation
   */
  async function resetPassword(email, otp, password, passwordConfirmation) {
    loading.value = true;
    try {
      await authService.resetPassword(email, otp, password, passwordConfirmation);
      return { success: true };
    } catch (error) {
      const message = error.response?.data?.message || "Đặt lại mật khẩu thất bại";
      return { success: false, message };
    } finally {
      loading.value = false;
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
    resendRegisterOtp,
    verifyOtp,
    forgotPassword,
    checkResetOtp,
    resetPassword,
    fetchUser,
    logout,
    setTokenFromGoogle,
  };
});
