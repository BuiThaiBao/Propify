<template>
  <div class="max-w-[1100px] mx-auto mt-20 mb-8 px-4 flex gap-8 min-h-[calc(100vh-200px)] max-md:flex-col">
    <!-- Sidebar -->
    <aside class="w-[260px] shrink-0 max-md:w-full">
      <div class="text-center p-6 bg-white rounded-xl shadow-sm mb-4">
        <div class="w-[72px] h-[72px] rounded-full bg-gradient-to-br from-sky-100 to-sky-200 text-sky-500 flex items-center justify-center mx-auto mb-3">
          <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
        </div>
        <h3 class="text-base font-semibold text-slate-800 mb-0.5">{{ authStore.user?.full_name || 'Người dùng' }}</h3>
        <p class="text-xs text-slate-400">{{ authStore.user?.email }}</p>
      </div>

      <nav class="bg-white rounded-xl shadow-sm overflow-hidden max-md:flex max-md:flex-wrap">
        <!-- Thông tin tài khoản -->
        <button
          :class="['flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-left transition-all max-md:flex-1 max-md:min-w-[120px] max-md:justify-center',
            activeTab === 'profile'
              ? 'bg-gradient-to-r from-sky-100 to-sky-50 text-sky-500 font-semibold border-l-[3px] border-sky-500 max-md:border-l-0 max-md:border-b-[3px]'
              : 'text-slate-600 hover:bg-slate-50 hover:text-sky-500']"
          @click="activeTab = 'profile'"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
          </svg>
          Thông tin tài khoản
        </button>

        <!-- Quản lý tin đăng -->
        <button class="flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-sky-500 transition-all text-left" @click="toggleSection('listings')">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="7" height="7" x="3" y="3" rx="1"/><rect width="7" height="7" x="14" y="3" rx="1"/><rect width="7" height="7" x="14" y="14" rx="1"/><rect width="7" height="7" x="3" y="14" rx="1"/>
          </svg>
          <span class="flex-1">Quản lý tin đăng</span>
          <svg :class="['transition-transform text-slate-400', expandedSections.listings && 'rotate-180']" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        <div v-show="expandedSections.listings" class="bg-slate-50 border-t border-slate-100">
          <button class="w-full text-left pl-12 pr-5 py-2.5 text-[0.82rem] text-slate-500 hover:bg-slate-200 hover:text-sky-500 transition-all">Danh sách tin đăng</button>
          <button class="w-full text-left pl-12 pr-5 py-2.5 text-[0.82rem] text-slate-500 hover:bg-slate-200 hover:text-sky-500 transition-all">Danh sách xác thực BĐS</button>
        </div>

        <!-- Quản lý đặt lịch -->
        <button class="flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-sky-500 transition-all text-left" @click="toggleSection('appointments')">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/><line x1="16" x2="16" y1="2" y2="6"/><line x1="8" x2="8" y1="2" y2="6"/><line x1="3" x2="21" y1="10" y2="10"/>
          </svg>
          <span class="flex-1">Quản lý đặt lịch</span>
          <svg :class="['transition-transform text-slate-400', expandedSections.appointments && 'rotate-180']" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="m6 9 6 6 6-6"/>
          </svg>
        </button>
        <div v-show="expandedSections.appointments" class="bg-slate-50 border-t border-slate-100">
          <button class="w-full text-left pl-12 pr-5 py-2.5 text-[0.82rem] text-slate-500 hover:bg-slate-200 hover:text-sky-500 transition-all">Lịch hẹn của tôi</button>
        </div>

        <!-- Tin đăng yêu thích -->
        <button class="flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-sky-500 transition-all text-left">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
          </svg>
          Tin đăng yêu thích
        </button>

        <!-- Tin đã xem -->
        <button class="flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-sky-500 transition-all text-left">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
          </svg>
          Tin đã xem
        </button>

        <!-- Lịch sử giao dịch -->
        <button class="flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-sky-500 transition-all text-left">
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
          Lịch sử giao dịch
        </button>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 min-w-0">
      <!-- ===== PROFILE TAB ===== -->
      <section v-if="activeTab === 'profile'" class="bg-white rounded-xl shadow-sm p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6">Thông tin tài khoản</h2>

        <!-- Warning: phone required -->
        <div v-if="requirePhone" class="bg-amber-50 text-amber-800 border border-amber-200 rounded-lg px-4 py-3 text-sm mb-5">
          ⚠️ Bạn cần cập nhật số điện thoại trước khi đăng tin.
        </div>

        <!-- Avatar Card -->
        <div class="border border-slate-200 rounded-lg p-6 mb-5">
          <h3 class="text-[0.95rem] font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100">Ảnh đại diện</h3>
          <div class="flex items-center gap-4">
            <div class="w-14 h-14 rounded-full bg-gradient-to-br from-sky-100 to-sky-200 text-sky-500 flex items-center justify-center">
              <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
              </svg>
            </div>
            <div>
              <p class="text-[0.95rem] font-semibold text-slate-800">{{ authStore.user?.full_name }}</p>
              <p class="text-xs text-sky-500 cursor-pointer hover:underline">Thay đổi ảnh đại diện</p>
            </div>
          </div>
        </div>

        <!-- Personal Info Card -->
        <div class="border border-slate-200 rounded-lg p-6">
          <h3 class="text-[0.95rem] font-bold text-slate-800 mb-4 pb-3 border-b border-slate-100">Thông tin cá nhân</h3>

          <div v-if="profileMessage" :class="['rounded-lg px-4 py-3 text-sm mb-5',
            profileSuccess ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200']">
            {{ profileMessage }}
          </div>

          <form @submit.prevent="handleUpdateProfile" class="flex flex-col gap-5">
            <div class="flex flex-col gap-1.5">
              <label for="fullName" class="text-[0.85rem] font-semibold text-slate-700">Họ và tên</label>
              <input
                id="fullName"
                v-model="profileForm.fullName"
                type="text"
                placeholder="Nhập họ và tên"
                :disabled="!isEditing"
                :class="['w-full px-3.5 py-2.5 border-[1.5px] rounded-lg text-sm text-slate-800 outline-none transition-all',
                  isEditing ? 'bg-white border-sky-500' : 'bg-slate-100 border-slate-200 text-slate-500 cursor-not-allowed']"
              />
            </div>

            <div class="flex flex-col gap-1.5">
              <label for="email" class="text-[0.85rem] font-semibold text-slate-700">Email</label>
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
                <input id="email" :value="authStore.user?.email" type="email" disabled
                  class="w-full pl-9 pr-3.5 py-2.5 border-[1.5px] border-slate-200 rounded-lg text-sm bg-slate-100 text-slate-500 cursor-not-allowed outline-none" />
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label for="phone" class="text-[0.85rem] font-semibold text-slate-700">Số điện thoại</label>
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <input
                  id="phone"
                  v-model="profileForm.phone"
                  type="tel"
                  placeholder="Nhập số điện thoại"
                  :disabled="!isEditing"
                  ref="phoneInputRef"
                  :class="['w-full pl-9 pr-3.5 py-2.5 border-[1.5px] rounded-lg text-sm outline-none transition-all',
                    !isEditing ? 'bg-slate-100 border-slate-200 text-slate-500 cursor-not-allowed'
                      : requirePhone ? 'bg-white border-amber-400 ring-[3px] ring-amber-400/15'
                      : 'bg-white border-sky-500']"
                />
              </div>
            </div>

            <div class="flex gap-3 pt-2">
              <template v-if="!isEditing">
                <button type="button" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-br from-sky-500 to-sky-600 text-white hover:from-sky-600 hover:to-sky-700 hover:shadow-lg hover:shadow-sky-500/30 transition-all" @click="startEditing">
                  Chỉnh sửa
                </button>
                <button type="button" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-sky-500 border-[1.5px] border-sky-500 hover:bg-sky-50 transition-all" @click="activeTab = 'password'">
                  Đổi mật khẩu
                </button>
              </template>
              <template v-else>
                <button type="button" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-slate-500 border-[1.5px] border-slate-200 hover:bg-slate-50 transition-all" @click="cancelEditing">
                  Hủy
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-br from-sky-500 to-sky-600 text-white hover:from-sky-600 hover:to-sky-700 hover:shadow-lg hover:shadow-sky-500/30 transition-all disabled:opacity-60 disabled:cursor-not-allowed" :disabled="profileLoading">
                  {{ profileLoading ? 'Đang lưu...' : 'Lưu thay đổi' }}
                </button>
              </template>
            </div>
          </form>
        </div>
      </section>

      <!-- ===== PASSWORD TAB ===== -->
      <section v-if="activeTab === 'password'" class="bg-white rounded-xl shadow-sm p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>
          </svg>
          Đổi mật khẩu
        </h2>

        <div v-if="passwordMessage" :class="['rounded-lg px-4 py-3 text-sm mb-5',
          passwordSuccess ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200']">
          {{ passwordMessage }}
        </div>

        <form @submit.prevent="handleChangePassword" class="flex flex-col gap-5">
          <!-- Current password -->
          <div class="flex flex-col gap-1.5">
            <label for="currentPassword" class="text-[0.85rem] font-semibold text-slate-700">Mật khẩu hiện tại</label>
            <div class="relative">
              <input
                id="currentPassword"
                v-model="passwordForm.currentPassword"
                :type="showCurrentPassword ? 'text' : 'password'"
                placeholder="Nhập mật khẩu hiện tại"
                class="w-full px-3.5 py-2.5 pr-11 border-[1.5px] border-slate-200 rounded-lg text-sm text-slate-800 bg-slate-50 outline-none transition-all focus:border-sky-500 focus:ring-[3px] focus:ring-sky-500/10 focus:bg-white"
              />
              <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 transition-colors" @click="showCurrentPassword = !showCurrentPassword" tabindex="-1">
                <svg v-if="!showCurrentPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>

          <!-- New password -->
          <div class="flex flex-col gap-1.5">
            <label for="newPassword" class="text-[0.85rem] font-semibold text-slate-700">Mật khẩu mới</label>
            <div class="relative">
              <input
                id="newPassword"
                v-model="passwordForm.newPassword"
                :type="showNewPassword ? 'text' : 'password'"
                placeholder="Nhập mật khẩu mới"
                class="w-full px-3.5 py-2.5 pr-11 border-[1.5px] border-slate-200 rounded-lg text-sm text-slate-800 bg-slate-50 outline-none transition-all focus:border-sky-500 focus:ring-[3px] focus:ring-sky-500/10 focus:bg-white"
              />
              <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 transition-colors" @click="showNewPassword = !showNewPassword" tabindex="-1">
                <svg v-if="!showNewPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
            <span class="text-xs text-slate-400">Tối thiểu 8 ký tự, gồm chữ hoa, chữ thường và số</span>
          </div>

          <!-- Confirm password -->
          <div class="flex flex-col gap-1.5">
            <label for="confirmPassword" class="text-[0.85rem] font-semibold text-slate-700">Xác nhận mật khẩu mới</label>
            <div class="relative">
              <input
                id="confirmPassword"
                v-model="passwordForm.newPasswordConfirmation"
                :type="showConfirmPassword ? 'text' : 'password'"
                placeholder="Nhập lại mật khẩu mới"
                class="w-full px-3.5 py-2.5 pr-11 border-[1.5px] border-slate-200 rounded-lg text-sm text-slate-800 bg-slate-50 outline-none transition-all focus:border-sky-500 focus:ring-[3px] focus:ring-sky-500/10 focus:bg-white"
              />
              <button type="button" class="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-slate-400 hover:text-slate-600 transition-colors" @click="showConfirmPassword = !showConfirmPassword" tabindex="-1">
                <svg v-if="!showConfirmPassword" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>

          <div class="flex gap-3 pt-2">
            <button type="button" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-white text-slate-500 border-[1.5px] border-slate-200 hover:bg-slate-50 transition-all" @click="resetPasswordForm(); activeTab = 'profile'">
              Hủy
            </button>
            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-br from-sky-500 to-sky-600 text-white hover:from-sky-600 hover:to-sky-700 hover:shadow-lg hover:shadow-sky-500/30 transition-all disabled:opacity-60 disabled:cursor-not-allowed" :disabled="passwordLoading">
              {{ passwordLoading ? 'Đang xử lý...' : 'Cập nhật mật khẩu' }}
            </button>
          </div>
        </form>
      </section>
    </main>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, nextTick } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRoute, useRouter } from 'vue-router';
import userService from '@/services/userService';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

// ── Tabs ──
const activeTab = ref('profile');

// ── Phone required from đăng tin ──
const requirePhone = ref(false);
const phoneInputRef = ref(null);

// ── Sidebar expand/collapse ──
const expandedSections = reactive({
  listings: false,
  appointments: false,
});

function toggleSection(key) {
  expandedSections[key] = !expandedSections[key];
}

// ── Profile form ──
const isEditing = ref(false);
const profileLoading = ref(false);
const profileMessage = ref('');
const profileSuccess = ref(false);
const profileForm = reactive({
  fullName: '',
  phone: '',
});

onMounted(() => {
  profileForm.fullName = authStore.user?.full_name || '';
  profileForm.phone = authStore.user?.phone || '';

  // Nếu đến từ nút "Đăng tin" mà chưa có SĐT → tự bật edit + focus phone
  if (route.query.require === 'phone' && !authStore.user?.phone) {
    requirePhone.value = true;
    isEditing.value = true;
    nextTick(() => {
      phoneInputRef.value?.focus();
    });
  }
});

function startEditing() {
  isEditing.value = true;
  profileMessage.value = '';
}

function cancelEditing() {
  isEditing.value = false;
  profileForm.fullName = authStore.user?.full_name || '';
  profileForm.phone = authStore.user?.phone || '';
  profileMessage.value = '';
  requirePhone.value = false;
}

async function handleUpdateProfile() {
  // Nếu yêu cầu phone mà chưa nhập
  if (requirePhone.value && !profileForm.phone.trim()) {
    profileSuccess.value = false;
    profileMessage.value = 'Vui lòng nhập số điện thoại để tiếp tục đăng tin.';
    return;
  }

  // Validate phone format: đúng 10 chữ số
  const phone = profileForm.phone.trim();
  if (phone && !/^[0-9]{10}$/.test(phone)) {
    profileSuccess.value = false;
    profileMessage.value = 'Số điện thoại phải đúng 10 chữ số.';
    return;
  }

  profileLoading.value = true;
  profileMessage.value = '';

  try {
    const payload = {
      fullName: profileForm.fullName,
      phone: profileForm.phone.trim() || undefined,
    };
    const res = await userService.updateProfile(payload);
    const updatedUser = res.data.data;

    authStore.user = { ...authStore.user, ...updatedUser };
    sessionStorage.setItem('auth_user', JSON.stringify(authStore.user));

    profileSuccess.value = true;
    profileMessage.value = 'Cập nhật thông tin thành công!';
    isEditing.value = false;

    // Nếu đang require phone → sau khi cập nhật xong, chuyển sang đăng tin
    if (requirePhone.value) {
      requirePhone.value = false;
      router.replace('/profile');
      setTimeout(() => router.push('/post-listing'), 500);
    }
  } catch (error) {
    profileSuccess.value = false;
    profileMessage.value = error.response?.data?.message || 'Cập nhật thất bại. Vui lòng thử lại.';
  } finally {
    profileLoading.value = false;
  }
}

// ── Password form ──
const passwordLoading = ref(false);
const passwordMessage = ref('');
const passwordSuccess = ref(false);
const showCurrentPassword = ref(false);
const showNewPassword = ref(false);
const showConfirmPassword = ref(false);
const passwordForm = reactive({
  currentPassword: '',
  newPassword: '',
  newPasswordConfirmation: '',
});

function resetPasswordForm() {
  passwordForm.currentPassword = '';
  passwordForm.newPassword = '';
  passwordForm.newPasswordConfirmation = '';
  passwordMessage.value = '';
  showCurrentPassword.value = false;
  showNewPassword.value = false;
  showConfirmPassword.value = false;
}

async function handleChangePassword() {
  // Trim passwords (BR.ACC.03)
  passwordForm.currentPassword = passwordForm.currentPassword.trim();
  passwordForm.newPassword = passwordForm.newPassword.trim();
  passwordForm.newPasswordConfirmation = passwordForm.newPasswordConfirmation.trim();

  // Validate format (BR.ACC.03)
  const pwd = passwordForm.newPassword;
  if (pwd.length < 8) {
    passwordSuccess.value = false;
    passwordMessage.value = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
    return;
  }
  if (!/[a-z]/.test(pwd) || !/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd)) {
    passwordSuccess.value = false;
    passwordMessage.value = 'Mật khẩu phải chứa chữ hoa, chữ thường và chữ số.';
    return;
  }
  if (pwd !== passwordForm.newPasswordConfirmation) {
    passwordSuccess.value = false;
    passwordMessage.value = 'Xác nhận mật khẩu mới không khớp.';
    return;
  }

  passwordLoading.value = true;
  passwordMessage.value = '';

  try {
    await userService.changePassword(passwordForm);
    passwordSuccess.value = true;
    passwordMessage.value = 'Đổi mật khẩu thành công!';
    resetPasswordForm();
  } catch (error) {
    passwordSuccess.value = false;
    const data = error.response?.data;
    passwordMessage.value = data?.message || 'Đổi mật khẩu thất bại. Vui lòng thử lại.';
  } finally {
    passwordLoading.value = false;
  }
}
</script>
