<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-sm px-7 py-8 relative"
      >
      <!-- Close -->
      <button
        @click="$emit('close')"
        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600"
      >
        ✕
      </button>

      <!-- Title -->
      <h1 class="text-2xl font-bold text-gray-900 mb-1">Xin chào</h1>
      <p class="text-sm text-gray-500 mb-5">Đăng nhập tài khoản</p>

      <!-- Email -->
      <input
        v-model="email"
        type="email"
        placeholder="Email"
        class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-3 outline-none focus:border-blue-400"
      />

      <!-- Password -->
      <input
        v-model="password"
        type="password"
        placeholder="Mật khẩu"
        class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-3 outline-none focus:border-blue-400"
        @keyup.enter="handleLogin"
      />

      <!-- Error -->
      <p v-if="errorMessage" class="text-red-500 text-sm mb-3">
        {{ errorMessage }}
      </p>

      <!-- Login button -->
      <button
        @click="handleLogin"
        :disabled="authStore.loading"
        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-xl py-3.5 text-sm mb-4 disabled:opacity-50 disabled:cursor-not-allowed transition"
      >
        {{ authStore.loading ? 'Đang đăng nhập...' : 'Đăng nhập' }}
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
        class="w-full border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium rounded-xl py-3.5 text-sm mb-5 transition-colors"
      >
        Đăng nhập với Google
      </button>

      <!-- Switch to Register -->
      <p class="text-center text-sm text-gray-500">
        Chưa có tài khoản?
        <span
          class="text-blue-500 hover:underline cursor-pointer"
          @click="$emit('switchToRegister')"
        >
          Đăng ký
        </span>
      </p>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { ref } from "vue";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();

const emit = defineEmits(["close", "success", "switchToRegister"]);

const email = ref("");
const password = ref("");
const errorMessage = ref("");

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

/** Redirect to backend Google OAuth endpoint */
function handleGoogleLogin() {
  window.location.href = import.meta.env.VITE_GOOGLE_AUTH_URL;
}
</script>
