<template>
  <Teleport to="body">
    <!-- Forgot Password modal -->
    <ForgotPassword
      v-if="showForgotPassword"
      @close="showForgotPassword = false"
      @switchToLogin="showForgotPassword = false"
    />

    <div
      v-else
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[100] p-4"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm px-7 py-8 relative">

        <!-- Close -->
        <button
          @click="$emit('close')"
          class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition-colors w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
          </svg>
        </button>

        <!-- Logo -->
        <div class="flex flex-col items-center mb-6">
          <div class="flex items-center gap-2">
            <div class="w-9 h-9 rounded-xl hero-gradient flex items-center justify-center shadow-md">
              <span class="w-4 h-4 bg-white rounded-sm block"></span>
            </div>
            <span class="text-xl font-bold text-gray-900">Rent<span class="text-gradient">House</span></span>
          </div>
          <span class="text-[11px] text-gray-400 mt-1 tracking-wide uppercase">Nền tảng bất động sản</span>
        </div>

        <!-- Heading -->
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Xin chào,</h1>
        <p class="text-sm text-gray-500 mb-5">Đăng nhập hoặc tạo tài khoản</p>

        <!-- Email -->
        <div class="relative mb-3">
          <input
            v-model="email"
            type="email"
            placeholder="Email của bạn"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-sm"
            @keyup.enter="handleLogin"
          />
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-400">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="3"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
          </span>
        </div>

        <!-- Password -->
        <div class="relative mb-3">
          <input
            v-model="password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Mật khẩu"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-sm"
            @keyup.enter="handleLogin"
          />
          <button
            @click="showPassword = !showPassword"
            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition-colors"
          >
            <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
            </svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/>
            </svg>
          </button>
        </div>

        <!-- Error -->
        <p v-if="errorMessage" class="text-red-500 text-xs mb-3 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/>
          </svg>
          {{ errorMessage }}
        </p>

        <!-- Quên mật khẩu -->
        <div class="flex justify-end mb-3">
          <button
            @click="showForgotPassword = true"
            class="text-xs text-blue-500 hover:underline"
          >
            Quên mật khẩu?
          </button>
        </div>

        <!-- Login button -->
        <button
          @click="handleLogin"
          :disabled="authStore.loading"
          class="w-full hero-gradient text-white font-semibold rounded-xl py-3.5 text-sm mb-4 disabled:opacity-60 disabled:cursor-not-allowed transition-all hover:opacity-90 active:scale-[0.98] shadow-md shadow-blue-200"
        >
          {{ authStore.loading ? 'Đang đăng nhập...' : 'Tiếp tục' }}
        </button>

        <!-- Divider -->
        <div class="flex items-center gap-3 mb-4">
          <div class="flex-1 h-px bg-gray-200"></div>
          <span class="text-xs text-gray-400">Hoặc</span>
          <div class="flex-1 h-px bg-gray-200"></div>
        </div>

        <!-- Google -->
        <button
          @click="handleGoogleLogin"
          class="w-full border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium rounded-xl py-3 text-sm mb-5 transition-colors flex items-center justify-center gap-2.5"
        >
          <!-- Google SVG icon -->
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Đăng nhập với Google
        </button>

        <!-- Switch to Register -->
        <p class="text-center text-xs text-gray-500 mb-4">
          Chưa có tài khoản?
          <button
            class="text-blue-500 hover:underline font-medium ml-1"
            @click="$emit('switchToRegister')"
          >
            Đăng ký ngay
          </button>
        </p>

        <!-- Terms -->
        <p class="text-center text-[11px] text-gray-400 leading-relaxed">
          Bằng việc tiếp tục, bạn đã đồng ý với chúng tôi về<br/>
          <button class="text-blue-500 hover:underline">Điều khoản sử dụng</button>
          &amp;
          <button class="text-blue-500 hover:underline">Chính sách bảo vệ dữ liệu cá nhân</button>
        </p>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import ForgotPassword from "./ForgotPassword.vue";

const authStore = useAuthStore();
const emit = defineEmits(["close", "success", "switchToRegister"]);

const email = ref("");
const password = ref("");
const showPassword = ref(false);
const errorMessage = ref("");
const showForgotPassword = ref(false);

async function handleLogin() {
  if (authStore.loading) return;

  errorMessage.value = "";
  const result = await authStore.login(email.value, password.value);

  if (result.success) {
    emit("success");
  } else {
    errorMessage.value = result.message;
  }
}

function handleGoogleLogin() {
  window.location.href = import.meta.env.VITE_GOOGLE_AUTH_URL;
}
</script>
