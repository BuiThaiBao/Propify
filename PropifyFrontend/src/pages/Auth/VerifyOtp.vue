<template>
  <Teleport to="body">
    <div class="fixed inset-0 bg-black/50 backdrop-blur-sm flex items-center justify-center z-[100] p-4">
      <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm px-7 py-8 relative">

        <!-- Logo -->
        <div class="flex flex-col items-center mb-6">
          <div class="flex items-center gap-2">
            <svg width="36" height="36" viewBox="0 0 32 32" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect width="32" height="32" rx="12" fill="url(#logo_verify)"/>
              <path d="M21.3337 14.6667C21.3337 17.9953 17.641 21.462 16.401 22.5327C16.2855 22.6195 16.1449 22.6665 16.0003 22.6665C15.8558 22.6665 15.7152 22.6195 15.5997 22.5327C14.3597 21.462 10.667 17.9953 10.667 14.6667C10.667 13.2522 11.2289 11.8956 12.2291 10.8954C13.2293 9.89525 14.5858 9.33334 16.0003 9.33334C17.4148 9.33334 18.7714 9.89525 19.7716 10.8954C20.7718 11.8956 21.3337 13.2522 21.3337 14.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M16 16.6667C17.1046 16.6667 18 15.7712 18 14.6667C18 13.5621 17.1046 12.6667 16 12.6667C14.8954 12.6667 14 13.5621 14 14.6667C14 15.7712 14.8954 16.6667 16 16.6667Z" stroke="white" stroke-width="1.33333" stroke-linecap="round" stroke-linejoin="round"/>
              <defs>
                <linearGradient id="logo_verify" x1="0" y1="0" x2="32" y2="32" gradientUnits="userSpaceOnUse">
                  <stop stop-color="#0DA2E7"/>
                  <stop offset="1" stop-color="#0DA2E7"/>
                </linearGradient>
              </defs>
            </svg>
            <span class="text-xl font-bold text-gray-900">Propify</span>
          </div>
          <span class="text-[11px] text-gray-400 mt-1 tracking-wide uppercase">Nền tảng bất động sản</span>
        </div>

        <!-- Success State -->
        <div v-if="isSuccess" class="py-4 flex flex-col items-center animate-in fade-in zoom-in duration-300">
          <div class="w-20 h-20 rounded-full bg-green-50 flex items-center justify-center mb-5 text-green-500">
            <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
              <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
          </div>
          <h2 class="text-2xl font-bold text-gray-900 mb-2">Đăng ký thành công!</h2>
          <p class="text-gray-500 text-center mb-8 px-2 text-sm leading-relaxed">
            Tuyệt vời! Tài khoản của bạn đã được xác thực và kích hoạt. Chào mừng bạn đến với Propify.
          </p>
          <button 
            @click="$emit('success')" 
            class="w-full hero-gradient text-white font-semibold rounded-xl py-3.5 text-sm transition-all hover:opacity-90 active:scale-[0.98] shadow-md shadow-blue-200"
          >
            Khám phá ngay
          </button>
        </div>

        <!-- Default OTP State -->
        <div v-else class="animate-in fade-in duration-300">
          <!-- Icon check mail -->
          <div class="flex justify-center mb-4">
            <div class="w-16 h-16 rounded-full bg-blue-50 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="4" width="20" height="16" rx="3"/>
                <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
              </svg>
            </div>
          </div>

          <!-- Heading -->
          <h1 class="text-2xl font-bold text-gray-900 text-center mb-1">Xác thực email</h1>
          <p class="text-sm text-gray-500 text-center mb-1">
            Mã OTP đã được gửi tới
          </p>
          <p class="text-sm font-semibold text-blue-600 text-center mb-5 truncate">{{ email }}</p>

          <!-- OTP inputs (6 ô riêng lẻ) -->
          <div class="flex gap-2 justify-center mb-2">
            <input
              v-for="(_, i) in 6"
              :key="i"
              :ref="el => otpInputs[i] = el"
              type="text"
              inputmode="numeric"
              maxlength="1"
              :value="otpDigits[i]"
              @input="handleInput(i, $event)"
              @keydown="handleKeydown(i, $event)"
              @paste="handlePaste($event)"
              class="w-11 h-12 text-center text-xl font-bold border-2 rounded-xl outline-none transition-all focus:border-blue-500 focus:bg-blue-50"
              :class="[
                otpDigits[i] ? 'border-blue-400 bg-blue-50 text-blue-700' : 'border-gray-200 text-gray-900',
                errorMessage && 'border-red-400 bg-red-50'
              ]"
            />
          </div>

          <!-- Error -->
          <p v-if="errorMessage" class="text-red-500 text-xs text-center mb-3 flex items-center justify-center gap-1">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            {{ errorMessage }}
          </p>

          <!-- Countdown -->
          <p class="text-xs text-center text-gray-400 mb-5 mt-3">
            <span v-if="countdown > 0">
              Mã hết hạn sau <span class="font-semibold text-gray-600">{{ formattedCountdown }}</span>
            </span>
            <span v-else class="text-red-500 font-medium">Mã OTP đã hết hạn</span>
          </p>

          <!-- Verify button -->
          <button
            @click="handleVerify"
            :disabled="authStore.loading || otpValue.length < 6"
            class="w-full hero-gradient text-white font-semibold rounded-xl py-3.5 text-sm mb-4 disabled:opacity-50 disabled:cursor-not-allowed transition-all hover:opacity-90 active:scale-[0.98] shadow-md shadow-blue-200"
          >
            {{ authStore.loading ? 'Đang xác thực...' : 'Xác nhận' }}
          </button>

          <!-- Resend -->
          <p class="text-center text-xs text-gray-500 mt-2">
            Không nhận được mã?
            <button
              @click="$emit('resend')"
              class="text-blue-500 hover:underline font-medium ml-1"
              :disabled="countdown > 0"
              :class="{ 'opacity-40 cursor-not-allowed': countdown > 0 }"
            >
              Gửi lại
            </button>
          </p>
        </div>

      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';

const props = defineProps({
  email: { type: String, required: true },
});

const emit = defineEmits(['success', 'resend']);

const authStore = useAuthStore();

// ── OTP state ─────────────────────────────────────────────────
const otpDigits = ref(['', '', '', '', '', '']);
const otpInputs = ref([]);
const errorMessage = ref('');
const isSuccess = ref(false);

const otpValue = computed(() => otpDigits.value.join(''));

// ── Countdown 3 phút ──────────────────────────────────────────
const countdown = ref(180); // 3 phút = 180 giây
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
    if (countdown.value > 0) {
      countdown.value--;
    } else {
      clearInterval(timer);
    }
  }, 1000);
}

onMounted(() => {
  startCountdown();
  // Focus ô đầu tiên
  setTimeout(() => otpInputs.value[0]?.focus(), 100);
});

onUnmounted(() => clearInterval(timer));

// Reset đếm ngược khi email thay đổi (resend)
watch(() => props.email, () => startCountdown());

// ── Input handlers ────────────────────────────────────────────
function handleInput(index, event) {
  const raw = event.target.value.replace(/\D/g, '');  // chỉ cho số
  const char = raw.slice(-1);

  otpDigits.value[index] = char;
  event.target.value = char;
  errorMessage.value = '';

  if (char && index < 5) {
    otpInputs.value[index + 1]?.focus();
  }

  // Auto-verify khi điền đủ 6 số
  if (otpValue.value.length === 6) {
    handleVerify();
  }
}

function handleKeydown(index, event) {
  if (event.key === 'Backspace') {
    if (otpDigits.value[index]) {
      otpDigits.value[index] = '';
    } else if (index > 0) {
      otpInputs.value[index - 1]?.focus();
      otpDigits.value[index - 1] = '';
    }
  }
  if (event.key === 'ArrowLeft' && index > 0) otpInputs.value[index - 1]?.focus();
  if (event.key === 'ArrowRight' && index < 5) otpInputs.value[index + 1]?.focus();
}

function handlePaste(event) {
  event.preventDefault();
  const text = (event.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
  if (!text) return;

  for (let i = 0; i < 6; i++) {
    otpDigits.value[i] = text[i] || '';
  }
  const nextEmpty = Math.min(text.length, 5);
  otpInputs.value[nextEmpty]?.focus();

  if (text.length >= 6) handleVerify();
}

// ── Verify ────────────────────────────────────────────────────
async function handleVerify() {
  if (authStore.loading || otpValue.value.length < 6) return;

  errorMessage.value = '';
  const result = await authStore.verifyOtp(props.email, otpValue.value);

  if (result.success) {
    isSuccess.value = true;
  } else {
    errorMessage.value = result.message;
    // Shake animation — clear inputs
    otpDigits.value = ['', '', '', '', '', ''];
    setTimeout(() => otpInputs.value[0]?.focus(), 50);
  }
}
</script>
