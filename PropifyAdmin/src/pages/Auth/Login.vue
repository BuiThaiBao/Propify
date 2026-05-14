<script setup>
import { ref, reactive } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const form = reactive({ email: '', password: '' })
const error = ref('')
const showPassword = ref(false)

async function handleLogin() {
  error.value = ''
  const result = await authStore.login(form.email, form.password)
  if (result.success) {
    router.push({ name: 'Dashboard' })
  } else {
    error.value = result.message
  }
}
</script>

<template>
  <div class="login-page">
    <!-- Background decoration -->
    <div class="bg-decoration">
      <div class="blob blob-1"></div>
      <div class="blob blob-2"></div>
      <div class="blob blob-3"></div>
    </div>

    <!-- Login Card -->
    <div class="login-card">
      <!-- Logo / Brand -->
      <div class="brand">
        <div class="brand-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path d="M3 9l9-7 9 7v11a2 2 0 01-2 2H5a2 2 0 01-2-2z"/>
            <polyline points="9 22 9 12 15 12 15 22"/>
          </svg>
        </div>
        <div class="brand-text">
          <h1>Propify</h1>
          <span>Admin Dashboard</span>
        </div>
      </div>

      <!-- Heading -->
      <div class="login-header">
        <h2>Chào mừng trở lại</h2>
        <p>Đăng nhập vào trang quản trị hệ thống</p>
      </div>

      <!-- Form -->
      <form @submit.prevent="handleLogin" class="login-form">
        <!-- Email -->
        <div class="field">
          <label for="email">Email</label>
          <div class="input-wrapper">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
              <polyline points="22,6 12,13 2,6"/>
            </svg>
            <input
              id="email"
              v-model="form.email"
              type="email"
              placeholder="admin@propify.vn"
              required
              autocomplete="email"
            />
          </div>
        </div>

        <!-- Password -->
        <div class="field">
          <label for="password">Mật khẩu</label>
          <div class="input-wrapper">
            <svg class="input-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
              <path d="M7 11V7a5 5 0 0110 0v4"/>
            </svg>
            <input
              id="password"
              v-model="form.password"
              :type="showPassword ? 'text' : 'password'"
              placeholder="••••••••"
              required
              autocomplete="current-password"
            />
            <button
              type="button"
              class="toggle-password"
              @click="showPassword = !showPassword"
              :aria-label="showPassword ? 'Ẩn mật khẩu' : 'Hiện mật khẩu'"
            >
              <!-- Eye open -->
              <svg v-if="!showPassword" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                <circle cx="12" cy="12" r="3"/>
              </svg>
              <!-- Eye closed -->
              <svg v-else viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                <line x1="1" y1="1" x2="23" y2="23"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- Error message -->
        <transition name="slide-down">
          <div v-if="error" class="error-box" role="alert">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
              <circle cx="12" cy="12" r="10"/>
              <line x1="12" y1="8" x2="12" y2="12"/>
              <line x1="12" y1="16" x2="12.01" y2="16"/>
            </svg>
            {{ error }}
          </div>
        </transition>

        <!-- Submit -->
        <button
          id="login-submit"
          type="submit"
          class="login-btn"
          :class="{ loading: authStore.loading }"
          :disabled="authStore.loading"
        >
          <span v-if="!authStore.loading">Đăng nhập</span>
          <span v-else class="spinner"></span>
        </button>
      </form>

      <!-- Footer note -->
      <p class="login-footer">
        Chỉ tài khoản có quyền <strong>Admin</strong> mới có thể đăng nhập
      </p>
    </div>
  </div>
</template>

<style scoped>
.login-page {
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #0f1117;
  position: relative;
  overflow: hidden;
  font-family: 'Inter', sans-serif;
}

/* Background blobs */
.bg-decoration { position: absolute; inset: 0; pointer-events: none; }

.blob {
  position: absolute;
  border-radius: 50%;
  filter: blur(80px);
  opacity: 0.25;
  animation: float 8s ease-in-out infinite;
}
.blob-1 {
  width: 500px; height: 500px;
  background: radial-gradient(circle, #6366f1, #4f46e5);
  top: -150px; left: -150px;
  animation-delay: 0s;
}
.blob-2 {
  width: 400px; height: 400px;
  background: radial-gradient(circle, #8b5cf6, #7c3aed);
  bottom: -100px; right: -100px;
  animation-delay: 3s;
}
.blob-3 {
  width: 300px; height: 300px;
  background: radial-gradient(circle, #06b6d4, #0891b2);
  top: 50%; left: 60%;
  animation-delay: 6s;
}

@keyframes float {
  0%, 100% { transform: translate(0, 0) scale(1); }
  33% { transform: translate(20px, -20px) scale(1.05); }
  66% { transform: translate(-15px, 15px) scale(0.95); }
}

/* Card */
.login-card {
  position: relative;
  z-index: 1;
  width: 100%;
  max-width: 420px;
  background: rgba(255, 255, 255, 0.05);
  border: 1px solid rgba(255, 255, 255, 0.1);
  border-radius: 20px;
  padding: 40px;
  backdrop-filter: blur(20px);
  box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4);
  animation: card-in 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both;
}

@keyframes card-in {
  from { opacity: 0; transform: translateY(30px) scale(0.97); }
  to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Brand */
.brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 28px;
}

.brand-icon {
  width: 44px; height: 44px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 15px rgba(99, 102, 241, 0.4);
}

.brand-icon svg { width: 22px; height: 22px; color: white; }

.brand-text h1 {
  font-size: 1.25rem;
  font-weight: 700;
  color: #fff;
  line-height: 1;
  margin: 0;
}

.brand-text span {
  font-size: 0.7rem;
  color: rgba(255,255,255,0.5);
  letter-spacing: 0.05em;
  text-transform: uppercase;
}

/* Header */
.login-header { margin-bottom: 28px; }

.login-header h2 {
  font-size: 1.6rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 6px;
}

.login-header p {
  font-size: 0.875rem;
  color: rgba(255,255,255,0.5);
  margin: 0;
}

/* Form */
.login-form { display: flex; flex-direction: column; gap: 18px; }

.field { display: flex; flex-direction: column; gap: 6px; }

.field label {
  font-size: 0.8rem;
  font-weight: 500;
  color: rgba(255,255,255,0.7);
  letter-spacing: 0.02em;
}

.input-wrapper {
  position: relative;
  display: flex;
  align-items: center;
}

.input-icon {
  position: absolute;
  left: 14px;
  width: 16px; height: 16px;
  color: rgba(255,255,255,0.35);
  pointer-events: none;
  flex-shrink: 0;
}

.input-wrapper input {
  width: 100%;
  padding: 13px 16px 13px 42px;
  background: rgba(255,255,255,0.07);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 10px;
  color: #fff;
  font-size: 0.9rem;
  font-family: inherit;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.2s;
  outline: none;
}

.input-wrapper input::placeholder { color: rgba(255,255,255,0.25); }

.input-wrapper input:focus {
  border-color: #6366f1;
  background: rgba(99,102,241,0.08);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.2);
}

.toggle-password {
  position: absolute;
  right: 12px;
  background: none;
  border: none;
  padding: 4px;
  cursor: pointer;
  color: rgba(255,255,255,0.4);
  display: flex;
  align-items: center;
  transition: color 0.2s;
}

.toggle-password:hover { color: rgba(255,255,255,0.8); }
.toggle-password svg { width: 16px; height: 16px; }

/* Error */
.error-box {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 12px 14px;
  background: rgba(239, 68, 68, 0.12);
  border: 1px solid rgba(239, 68, 68, 0.3);
  border-radius: 10px;
  color: #fca5a5;
  font-size: 0.85rem;
}

.error-box svg { width: 16px; height: 16px; flex-shrink: 0; }

.slide-down-enter-active { transition: all 0.25s ease; }
.slide-down-leave-active { transition: all 0.2s ease; }
.slide-down-enter-from { opacity: 0; transform: translateY(-8px); }
.slide-down-leave-to   { opacity: 0; transform: translateY(-8px); }

/* Login button */
.login-btn {
  width: 100%;
  padding: 14px;
  margin-top: 4px;
  background: linear-gradient(135deg, #6366f1, #8b5cf6);
  border: none;
  border-radius: 10px;
  color: #fff;
  font-size: 0.95rem;
  font-weight: 600;
  font-family: inherit;
  cursor: pointer;
  transition: opacity 0.2s, transform 0.2s, box-shadow 0.2s;
  box-shadow: 0 4px 20px rgba(99,102,241,0.4);
  display: flex;
  align-items: center;
  justify-content: center;
  min-height: 50px;
}

.login-btn:hover:not(:disabled) {
  opacity: 0.9;
  transform: translateY(-1px);
  box-shadow: 0 8px 25px rgba(99,102,241,0.5);
}

.login-btn:active:not(:disabled) {
  transform: translateY(0);
}

.login-btn:disabled { opacity: 0.7; cursor: not-allowed; }

/* Spinner */
.spinner {
  width: 20px; height: 20px;
  border: 2px solid rgba(255,255,255,0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}

@keyframes spin { to { transform: rotate(360deg); } }

/* Footer */
.login-footer {
  margin-top: 24px;
  text-align: center;
  font-size: 0.78rem;
  color: rgba(255,255,255,0.35);
}

.login-footer strong {
  color: rgba(99,102,241,0.8);
}
</style>
