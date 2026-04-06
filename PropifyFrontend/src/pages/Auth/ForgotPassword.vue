<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm px-7 py-8 relative">

        <!-- Close button -->
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
              <rect width="32" height="32" rx="12" fill="url(#logo_forgot)"/>
              <path d="M21.3337 14.6667C21.3337 17.9953 17.641 21.462 16.401 22.5327C16.2855 22.6195 16.1449 22.6665 16.0003 22.6665C15.8558 22.6665 15.7152 22.6195 15.5997 22.5327C14.3597 21.462 10.667 17.9953 10.667 14.6667C10.667 13.2522 11.2289 11.8956 12.2291 10.8954C13.2293 9.89525 14.5858 9.33334 16.0003 9.33334C17.4148 9.33334 18.7714 9.89525 19.7716 10.8954C20.7718 11.8956 21.3337 13.2522 21.3337 14.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 16.6667C17.1046 16.6667 18 15.7712 18 14.6667C18 13.5621 17.1046 12.6667 16 12.6667C14.8954 12.6667 14 13.5621 14 14.6667C14 15.7712 14.8954 16.6667 16 16.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              <defs>
                <linearGradient id="logo_forgot" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#0DA2E7"/>
                  <stop offset="1" stop-color="#0DA2E7"/>
                </linearGradient>
              </defs>
            </svg>
            <span class="text-xl font-bold text-gray-900">Propify</span>
          </div>
        </div>

        <!-- Step indicator -->
        <div class="flex items-center justify-center gap-2 mb-6">
          <div v-for="i in 3" :key="i"
            class="h-1.5 rounded-full transition-all duration-300"
            :class="[
              step >= i ? 'bg-blue-500 w-8' : 'bg-gray-200 w-4'
            ]"
          ></div>
        </div>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 1: Nhập email                             -->
        <!-- ═══════════════════════════════════════════════ -->
        <template v-if="step === 1">
          <div class="flex justify-center mb-4">
            <div class="w-14 h-14 rounded-full bg-blue-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="3"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
            </div>
          </div>

          <h1 class="text-2xl font-bold text-gray-900 text-center mb-1">Quên mật khẩu?</h1>
          <p class="text-sm text-gray-500 text-center mb-6">Nhập email để nhận mã OTP đặt lại mật khẩu</p>

          <input
            v-model="email"
            type="email"
            placeholder="Email của bạn"
            @keyup.enter="handleSendOtp"
            class="w-full border border-gray-200 rounded-xl px-4 py-3 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100 transition text-sm mb-2"
            :class="{ 'border-red-400': emailError }"
          />
          <p v-if="emailError" class="text-red-500 text-xs mb-3 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ emailError }}
          </p>

          <button
            @click="handleSendOtp"
            :disabled="authStore.loading"
            class="w-full hero-gradient text-white font-semibold rounded-xl py-3.5 text-sm mb-4 disabled:opacity-60 transition-all hover:opacity-90 active:scale-[0.98] shadow-md shadow-blue-200"
          >
            {{ authStore.loading ? 'Đang gửi...' : 'Gửi mã OTP' }}
          </button>
        </template>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 2: Nhập OTP                               -->
        <!-- ═══════════════════════════════════════════════ -->
        <template v-else-if="step === 2">
          <div class="flex justify-center mb-4">
            <div class="w-14 h-14 rounded-full bg-purple-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
              </svg>
            </div>
          </div>

          <h1 class="text-2xl font-bold text-gray-900 text-center mb-1">Nhập mã OTP</h1>
          <p class="text-sm text-gray-500 text-center mb-1">Mã đã được gửi tới</p>
          <p class="text-sm font-semibold text-blue-600 text-center mb-5 truncate">{{ email }}</p>

          <!-- 6 ô OTP -->
          <div class="flex gap-2 justify-center mb-2">
            <input
              v-for="(_, i) in 6"
              :key="i"
              :ref="el => otpInputs[i] = el"
              type="text"
              inputmode="numeric"
              maxlength="1"
              :value="otpDigits[i]"
              @input="handleOtpInput(i, $event)"
              @keydown="handleOtpKeydown(i, $event)"
              @paste="handleOtpPaste($event)"
              class="w-11 h-12 text-center text-xl font-bold border-2 rounded-xl outline-none transition-all"
              :class="[
                otpDigits[i] ? 'border-purple-400 bg-purple-50 text-purple-700' : 'border-gray-200 text-gray-900',
                otpError && 'border-red-400 bg-red-50'
              ]"
            />
          </div>

          <p v-if="otpError" class="text-red-500 text-xs text-center mb-2 flex items-center justify-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ otpError }}
          </p>

          <!-- Countdown -->
          <p class="text-xs text-center text-gray-400 mb-5">
            <span v-if="countdown > 0">Mã hết hạn sau <span class="font-semibold text-gray-600">{{ formattedCountdown }}</span></span>
            <span v-else class="text-red-500">Mã OTP đã hết hạn</span>
          </p>

          <button
            @click="handleVerifyOtp"
            :disabled="authStore.loading || otpValue.length < 6"
            class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold rounded-xl py-3.5 text-sm mb-3 disabled:opacity-50 transition-all hover:opacity-90 active:scale-[0.98] shadow-md shadow-purple-200"
          >
            {{ authStore.loading ? 'Đang xác thực...' : 'Xác nhận OTP' }}
          </button>

          <p class="text-center text-xs text-gray-500">
            Không nhận được mã?
            <button
              @click="handleResendOtp"
              :disabled="countdown > 0 || authStore.loading"
              class="text-blue-500 hover:underline font-medium ml-1"
              :class="{ 'opacity-40 cursor-not-allowed': countdown > 0 }"
            >
              Gửi lại
            </button>
          </p>
        </template>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 3: Đặt mật khẩu mới                       -->
        <!-- ═══════════════════════════════════════════════ -->
        <template v-else-if="step === 3">
          <div class="flex justify-center mb-4">
            <div class="w-14 h-14 rounded-full bg-green-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
              </svg>
            </div>
          </div>

          <h1 class="text-2xl font-bold text-gray-900 text-center mb-1">Mật khẩu mới</h1>
          <p class="text-sm text-gray-500 text-center mb-6">Tạo mật khẩu mạnh để bảo vệ tài khoản</p>

          <!-- New password -->
          <div class="relative mb-2">
            <input
              v-model="newPassword"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Mật khẩu mới"
              class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 outline-none focus:border-green-400 focus:ring-2 focus:ring-green-100 transition text-sm"
              :class="{ 'border-red-400': passwordError }"
            />
            <button @click="showPassword = !showPassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600">
              <svg v-if="!showPassword" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>
              </svg>
              <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" y1="2" x2="22" y2="22"/>
              </svg>
            </button>
          </div>
          <p v-if="passwordError" class="text-red-500 text-xs mb-2 flex items-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ passwordError }}
          </p>

          <!-- Confirm password -->
          <div class="relative mb-5">
            <input
              v-model="confirmPassword"
              :type="showPassword ? 'text' : 'password'"
              placeholder="Nhập lại mật khẩu"
              @keyup.enter="handleResetPassword"
              class="w-full border border-gray-200 rounded-xl px-4 py-3 pr-12 outline-none focus:border-green-400 focus:ring-2 focus:ring-green-100 transition text-sm"
            />
          </div>

          <button
            @click="handleResetPassword"
            :disabled="authStore.loading"
            class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold rounded-xl py-3.5 text-sm mb-4 disabled:opacity-60 transition-all hover:opacity-90 active:scale-[0.98] shadow-md shadow-green-200"
          >
            {{ authStore.loading ? 'Đang lưu...' : 'Đặt mật khẩu mới' }}
          </button>
        </template>

        <!-- ═══════════════════════════════════════════════ -->
        <!-- STEP 4: Thành công                             -->
        <!-- ═══════════════════════════════════════════════ -->
        <template v-else-if="step === 4">
          <div class="text-center py-4">
            <div class="w-16 h-16 rounded-full bg-green-100 flex items-center justify-center mx-auto mb-4">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="20 6 9 17 4 12"/>
              </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 mb-2">Thành công!</h1>
            <p class="text-sm text-gray-500 mb-6">Mật khẩu đã được đặt lại. Bạn có thể đăng nhập ngay.</p>
            <button
              @click="$emit('switchToLogin')"
              class="w-full hero-gradient text-white font-semibold rounded-xl py-3.5 text-sm shadow-md shadow-blue-200 hover:opacity-90 transition-all"
            >
              Đăng nhập ngay
            </button>
          </div>
        </template>

        <!-- Back link (step 1 và 2) -->
        <p v-if="step < 4" class="text-center text-xs text-gray-500 mt-3">
          <button @click="$emit('switchToLogin')" class="text-blue-500 hover:underline">← Quay lại đăng nhập</button>
        </p>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useAuthStore } from '@/stores/auth';

const emit = defineEmits(['close', 'switchToLogin']);
const authStore = useAuthStore();

// ── State ─────────────────────────────────────────────────────
const step = ref(1);
const email = ref('');
const emailError = ref('');

const otpDigits = ref(['', '', '', '', '', '']);
const otpInputs = ref([]);
const otpError = ref('');
const verifiedOtp = ref('');   // lưu OTP đã verify để gửi khi reset

const newPassword = ref('');
const confirmPassword = ref('');
const passwordError = ref('');
const showPassword = ref(false);
const isSending = ref(false);   // chặn double-click spam

// ── OTP helpers ───────────────────────────────────────────────
const otpValue = computed(() => otpDigits.value.join(''));

// Countdown 3 phút
const countdown = ref(180);
let timer = null;

const formattedCountdown = computed(() => {
  const m = Math.floor(countdown.value / 60).toString().padStart(2, '0');
  const s = (countdown.value % 60).toString().padStart(2, '0');
  return `${m}:${s}`;
});

function startCountdown() {
  clearInterval(timer);
  countdown.value = 180;
  timer = setInterval(() => {
    if (countdown.value > 0) countdown.value--;
    else clearInterval(timer);
  }, 1000);
}

onUnmounted(() => clearInterval(timer));

// ── STEP 1: Gửi OTP ──────────────────────────────────────────
async function handleSendOtp() {
  emailError.value = '';
  if (isSending.value) return;  // chặn double-click

  // Validate email format client-side trước
  if (!email.value || !/\S+@\S+\.\S+/.test(email.value)) {
    emailError.value = 'Vui lòng nhập email hợp lệ';
    return;
  }

  isSending.value = true;
  
  const result = await authStore.forgotPassword(email.value);
  
  isSending.value = false;

  if (result.success) {
    step.value = 2;
    startCountdown();
    // Focus ô OTP đầu tiên
    setTimeout(() => otpInputs.value[0]?.focus(), 150);
  } else {
    emailError.value = result.errors?.email?.[0] || result.message || 'Email này chưa được đăng ký';
  }
}

// ── STEP 2: Nhập OTP ─────────────────────────────────────────
function handleOtpInput(index, event) {
  const char = event.target.value.replace(/\D/g, '').slice(-1);
  otpDigits.value[index] = char;
  event.target.value = char;
  otpError.value = '';
  if (char && index < 5) otpInputs.value[index + 1]?.focus();
  if (otpValue.value.length === 6) handleVerifyOtp();
}

function handleOtpKeydown(index, event) {
  if (event.key === 'Backspace') {
    if (otpDigits.value[index]) {
      otpDigits.value[index] = '';
    } else if (index > 0) {
      otpInputs.value[index - 1]?.focus();
      otpDigits.value[index - 1] = '';
    }
  }
  if (event.key === 'ArrowLeft' && index > 0)  otpInputs.value[index - 1]?.focus();
  if (event.key === 'ArrowRight' && index < 5) otpInputs.value[index + 1]?.focus();
}

function handleOtpPaste(event) {
  event.preventDefault();
  const text = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
  if (!text) return;
  for (let i = 0; i < 6; i++) otpDigits.value[i] = text[i] || '';
  otpInputs.value[Math.min(text.length, 5)]?.focus();
  if (text.length >= 6) handleVerifyOtp();
}

// OTP ở bước forgot-password → gọi backend /check-reset-otp để verify thật sự
async function handleVerifyOtp() {
  if (authStore.loading || otpValue.value.length < 6) return;

  otpError.value = '';
  const result = await authStore.checkResetOtp(email.value, otpValue.value);

  if (result.success) {
    verifiedOtp.value = otpValue.value;
    step.value = 3;
  } else {
    otpError.value = result.message;
    // Xóa các ô OTP và focus lại ô đầu
    otpDigits.value = ['', '', '', '', '', ''];
    setTimeout(() => otpInputs.value[0]?.focus(), 50);
  }
}

async function handleResendOtp() {
  otpDigits.value = ['', '', '', '', '', ''];
  otpError.value = '';
  await authStore.forgotPassword(email.value);
  startCountdown();
  setTimeout(() => otpInputs.value[0]?.focus(), 150);
}

// ── STEP 3: Đặt mật khẩu ─────────────────────────────────────
async function handleResetPassword() {
  passwordError.value = '';

  if (newPassword.value.length < 8) {
    passwordError.value = 'Mật khẩu phải có ít nhất 8 ký tự';
    return;
  }
  if (newPassword.value !== confirmPassword.value) {
    passwordError.value = 'Mật khẩu xác nhận không khớp';
    return;
  }

  const result = await authStore.resetPassword(
    email.value,
    verifiedOtp.value,
    newPassword.value,
    confirmPassword.value
  );

  if (result.success) {
    step.value = 4;
  } else {
    // OTP hết hạn → quay về bước 2
    otpError.value = result.message;
    otpDigits.value = ['', '', '', '', '', ''];
    verifiedOtp.value = '';
    step.value = 2;
    startCountdown();
  }
}
</script>
