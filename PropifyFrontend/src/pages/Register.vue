<template>
  <Teleport to="body">
    <div
      class="fixed inset-0 bg-black/60 backdrop-blur-sm flex items-center justify-center z-[100] p-4 overflow-y-auto"
    >
      <div
        class="bg-white rounded-2xl shadow-2xl w-full max-w-sm px-7 py-8 relative my-8"
      >
      <!-- Close -->
      <button
        @click="$emit('close')"
        class="absolute top-4 right-4 text-gray-400 hover:text-gray-600 transition"
      >
        ✕
      </button>

      <!-- Logo -->
      <div class="flex flex-col items-center mb-6">
        <h2 class="text-xl font-bold text-gray-800">
          Meey <span class="text-yellow-500">ID</span>
        </h2>
      </div>

      <!-- Heading -->
      <h1 class="text-2xl font-bold text-gray-900 mb-1">Tạo tài khoản</h1>
      <p class="text-sm text-gray-500 mb-5">Điền thông tin để đăng ký</p>

      <!-- Full name -->
      <input
        v-model="form.fullName"
        type="text"
        placeholder="Họ và tên"
        class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
      />
      <p v-if="fieldErrors?.fullName" class="text-red-500 text-xs mb-2">
        {{ fieldErrors.fullName[0] }}
      </p>

      <!-- Phone -->
      <input
        v-model="form.phone"
        type="tel"
        placeholder="Số điện thoại"
        class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
      />
      <p v-if="fieldErrors?.phone" class="text-red-500 text-xs mb-2">
        {{ fieldErrors.phone[0] }}
      </p>

      <!-- Email -->
      <input
        v-model="form.email"
        type="email"
        placeholder="Email"
        class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
      />
      <p v-if="fieldErrors?.email" class="text-red-500 text-xs mb-2">
        {{ fieldErrors.email[0] }}
      </p>

      <!-- Password -->
      <input
        v-model="form.password"
        type="password"
        placeholder="Mật khẩu"
        class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
      />
      <p v-if="fieldErrors?.password" class="text-red-500 text-xs mb-2">
        {{ fieldErrors.password[0] }}
      </p>

      <!-- Confirm Password -->
      <input
        v-model="form.passwordConfirmation"
        type="password"
        placeholder="Nhập lại mật khẩu"
        class="w-full border border-gray-200 rounded-xl px-4 py-3 mb-2 outline-none focus:border-blue-400 focus:ring-2 focus:ring-blue-100"
        @keyup.enter="handleRegister"
      />
      <p
        v-if="fieldErrors?.passwordConfirmation"
        class="text-red-500 text-xs mb-2"
      >
        {{ fieldErrors.passwordConfirmation[0] }}
      </p>

      <!-- General error -->
      <p v-if="errorMessage" class="text-red-500 text-sm mb-3">
        {{ errorMessage }}
      </p>

      <!-- Register button -->
      <button
        @click="handleRegister"
        class="w-full bg-blue-500 hover:bg-blue-600 text-white font-semibold rounded-xl py-3.5 text-sm transition mb-4"
      >
        Đăng kí
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
        class="w-full border border-gray-200 hover:bg-gray-50 text-gray-700 font-medium rounded-xl py-3.5 text-sm transition mb-5"
      >
        Đăng kí với Google
      </button>

      <!-- Switch to login -->
      <p class="text-center text-sm text-gray-500">
        Đã có tài khoản?
        <span
          class="text-blue-500 hover:underline cursor-pointer"
          @click="$emit('switchToLogin')"
        >
          Đăng nhập
        </span>
      </p>
    </div>
  </div>
  </Teleport>
</template>

<script setup>
import { ref } from "vue";
import { useRouter, useRoute } from "vue-router";
import { useAuthStore } from "@/stores/auth";

const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();

const emit = defineEmits(["close", "success", "switchToLogin"]);

const form = ref({
  fullName: "",
  phone: "",
  email: "",
  password: "",
  passwordConfirmation: "",
});
const errorMessage = ref("");
const fieldErrors = ref(null);

async function handleRegister() {
  errorMessage.value = "";
  fieldErrors.value = null;

  const result = await authStore.register(form.value);

  if (result.success) {
    emit("success");
  } else {
    errorMessage.value = result.message;
    fieldErrors.value = result.errors;
  }
}

function handleGoogleLogin() {
  window.location.href = import.meta.env.VITE_GOOGLE_AUTH_URL;
}
</script>
