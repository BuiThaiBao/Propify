<template>
  <Teleport to="body">
    <!-- OTP modal — hiện sau khi đăng ký thành công -->
    <VerifyOtp
      v-if="showOtp"
      :email="form.email"
      @success="$emit('success')"
      @resend="handleResend"
    />

    <!-- Register modal -->
    <div
      v-else
      class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[100] p-4 overflow-y-auto"
    >
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm px-7 py-8 relative my-8">

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
            <svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="32" height="32" rx="12" fill="url(#logo_register)"/>
              <path d="M21.3337 14.6667C21.3337 17.9953 17.641 21.462 16.401 22.5327C16.2855 22.6195 16.1449 22.6665 16.0003 22.6665C15.8558 22.6665 15.7152 22.6195 15.5997 22.5327C14.3597 21.462 10.667 17.9953 10.667 14.6667C10.667 13.2522 11.2289 11.8956 12.2291 10.8954C13.2293 9.89525 14.5858 9.33334 16.0003 9.33334C17.4148 9.33334 18.7714 9.89525 19.7716 10.8954C20.7718 11.8956 21.3337 13.2522 21.3337 14.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 16.6667C17.1046 16.6667 18 15.7712 18 14.6667C18 13.5621 17.1046 12.6667 16 12.6667C14.8954 12.6667 14 13.5621 14 14.6667C14 15.7712 14.8954 16.6667 16 16.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              <defs>
                <linearGradient id="logo_register" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#0DA2E7"/>
                  <stop offset="1" stop-color="#0DA2E7"/>
                </linearGradient>
              </defs>
            </svg>
            <span class="text-xl font-bold text-gray-900">Propify</span>
          </div>
          <span class="text-[11px] text-gray-400 mt-1 tracking-wide uppercase">Nền tảng bất động sản</span>
        </div>

        <!-- Heading -->
        <h1 class="text-2xl font-bold text-gray-900 mb-1">Tạo tài khoản</h1>
        <p class="text-sm text-gray-500 mb-5">Điền thông tin để bắt đầu</p>

        <!-- Full name -->
        <div class="relative mb-2">
          <input
            v-model="form.fullName"
            type="text"
            placeholder="Họ và tên"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-sm"
            :class="{ 'border-red-400': fieldErrors?.full_name }"
          />
        </div>
        <p v-if="fieldErrors?.full_name" class="text-red-500 text-xs mb-2 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ fieldErrors.full_name[0] }}
        </p>

        <!-- Email -->
        <div class="relative mb-2">
          <input
            v-model="form.email"
            type="email"
            placeholder="Email của bạn"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-sm"
            :class="{ 'border-red-400': fieldErrors?.email }"
          />
          <span class="absolute right-3 top-1/2 -translate-y-1/2 text-blue-400">
            <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
              <rect x="2" y="4" width="20" height="16" rx="3"/>
              <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
            </svg>
          </span>
        </div>
        <p v-if="fieldErrors?.email" class="text-red-500 text-xs mb-2 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ fieldErrors.email[0] }}
        </p>

        <!-- Password -->
        <div class="relative mb-2">
          <input
            v-model="form.password"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Mật khẩu"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-sm"
            :class="{ 'border-red-400': fieldErrors?.password }"
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
        <p v-if="fieldErrors?.password" class="text-red-500 text-xs mb-2 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ fieldErrors.password[0] }}
        </p>

        <!-- Confirm Password -->
        <div class="relative mb-2">
          <input
            v-model="form.passwordConfirmation"
            :type="showPassword ? 'text' : 'password'"
            placeholder="Nhập lại mật khẩu"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-sm"
            @keyup.enter="handleRegister"
          />
        </div>

        <!-- General error -->
        <p v-if="errorMessage" class="text-red-500 text-xs mb-3 flex items-center gap-1">
          <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
          {{ errorMessage }}
        </p>

        <!-- Register button -->
        <button
          @click="handleRegister"
          :disabled="authStore.loading"
          class="w-full hero-gradient text-white font-semibold rounded-xl py-3.5 text-sm mb-4 disabled:opacity-60 disabled:cursor-not-allowed transition-all hover:opacity-90 active:scale-[0.98] shadow-md shadow-blue-200"
        >
          {{ authStore.loading ? 'Đang xử lý...' : 'Tạo tài khoản' }}
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
          <svg width="18" height="18" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
          </svg>
          Đăng nhập với Google
        </button>

        <!-- Switch to login -->
        <p class="text-center text-xs text-gray-500 mb-4">
          Đã có tài khoản?
          <button class="text-blue-500 hover:underline font-medium ml-1" @click="$emit('switchToLogin')">
            Đăng nhập
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
import { ref } from 'vue';
import { useAuthStore } from '@/stores/auth';
import VerifyOtp from './VerifyOtp.vue';

const authStore = useAuthStore();
const emit = defineEmits(['close', 'success', 'switchToLogin']);

const showOtp = ref(false);
const showPassword = ref(false);
const form = ref({ fullName: '', email: '', password: '', passwordConfirmation: '' });
const errorMessage = ref('');
const fieldErrors = ref(null);

async function handleRegister() {
  if (authStore.loading) return;

  errorMessage.value = '';
  fieldErrors.value = null;

  // Validate cơ bản phía client trước khi gọi API
  if (!form.value.fullName.trim()) {
    fieldErrors.value = { full_name: ['Vui lòng nhập họ và tên'] };
    return;
  }
  if (!form.value.email || !/\S+@\S+\.\S+/.test(form.value.email)) {
    fieldErrors.value = { email: ['Vui lòng nhập email hợp lệ'] };
    return;
  }
  if (form.value.password.length < 8) {
    fieldErrors.value = { password: ['Mật khẩu phải có ít nhất 8 ký tự'] };
    return;
  }
  if (form.value.password !== form.value.passwordConfirmation) {
    fieldErrors.value = { password: ['Mật khẩu xác nhận không khớp'] };
    return;
  }

  const result = await authStore.register(form.value);

  if (result.success) {
    // Đăng ký OK → chuyển sang nhập OTP
    showOtp.value = true;
  } else {
    errorMessage.value = result.message;
    fieldErrors.value = result.errors;
  }
}

async function handleResend() {
  await authStore.resendRegisterOtp(form.value.email);
}

function handleGoogleLogin() {
  window.location.href = import.meta.env.VITE_GOOGLE_AUTH_URL;
}
</script>
