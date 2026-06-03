<template>
  <div class="w-full max-w-[1600px] mx-auto mt-24 mb-8 px-4 lg:px-8">
    <Breadcrumb :crumbs="[
      { label: 'Trang chủ', to: '/' },
      { label: 'Trang cá nhân' }
    ]" />

    <div class="flex gap-8 min-h-[calc(100vh-200px)] max-md:flex-col mt-4">
<!-- Sidebar (chỉ giữ nguyên icon tóm tắt) -->
    <aside class="w-[260px] shrink-0 max-md:w-full">
      <div class="text-center p-6 bg-white rounded-xl shadow-sm mb-4">
        <div
          class="w-[72px] h-[72px] rounded-full overflow-hidden bg-gradient-to-br from-sky-100 to-sky-200 text-sky-500 flex items-center justify-center mx-auto mb-3"
        >
          <img
            v-if="authStore.user?.avatar_url"
            :src="authStore.user.avatar_url"
            alt="Avatar"
            class="w-full h-full object-cover"
          />
          <svg v-else xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
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
          <button
            class="w-full text-left pl-12 pr-5 py-2.5 text-[0.82rem] transition-all"
            :class="activeTab === 'listings' ? 'bg-sky-100 text-sky-600 font-semibold' : 'text-slate-500 hover:bg-slate-200 hover:text-sky-500'"
            @click="openListingsTab"
          >
            Danh sách tin đăng
          </button>
          <button
            class="w-full text-left pl-12 pr-5 py-2.5 text-[0.82rem] transition-all"
            :class="activeTab === 'verifications' ? 'bg-sky-100 text-sky-600 font-semibold' : 'text-slate-500 hover:bg-slate-200 hover:text-sky-500'"
            @click="openVerificationsTab"
          >
            Danh sách xác thực BĐS
          </button>
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
          <button
            class="w-full text-left pl-12 pr-5 py-2.5 text-[0.82rem] transition-all"
            :class="activeTab === 'appointments' ? 'bg-sky-100 text-sky-600 font-semibold' : 'text-slate-500 hover:bg-slate-200 hover:text-sky-500'"
            @click="openAppointmentsTab"
          >
            Đặt lịch xem nhà
          </button>
        </div>

        <!-- Tin đăng yêu thích -->
        <button
          :class="['flex items-center gap-2.5 w-full px-5 py-3.5 text-sm transition-all text-left',
            activeTab === 'favorites'
              ? 'bg-gradient-to-r from-sky-100 to-sky-50 text-sky-500 font-semibold border-l-[3px] border-sky-500'
              : 'text-slate-600 hover:bg-slate-50 hover:text-sky-500']"
          @click="openFavoritesTab"
        >
          <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
          </svg>
          Tin đăng yêu thích
        </button>

        <!-- Tin đã xem -->
        <button
          :class="['flex items-center gap-2.5 w-full px-5 py-3.5 text-sm transition-all text-left',
            activeTab === 'recently-viewed'
              ? 'bg-gradient-to-r from-sky-100 to-sky-50 text-sky-500 font-semibold border-l-[3px] border-sky-500'
              : 'text-slate-600 hover:bg-slate-50 hover:text-sky-500']"
          @click="openRecentlyViewedTab"
        >
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
          <div class="flex items-center gap-5">
            <!-- Avatar preview -->
            <div class="relative group w-20 h-20 shrink-0">
              <div
                class="w-20 h-20 rounded-full overflow-hidden bg-gradient-to-br from-sky-100 to-sky-200 text-sky-500 flex items-center justify-center cursor-pointer ring-2 ring-sky-200 ring-offset-2 transition-all group-hover:ring-sky-400"
                @click="triggerAvatarInput"
              >
                <img
                  v-if="avatarPreview || authStore.user?.avatar_url"
                  :src="avatarPreview || authStore.user.avatar_url"
                  alt="Avatar"
                  class="w-full h-full object-cover"
                />
                <svg v-else xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
                </svg>
                <!-- Overlay camera icon on hover -->
                <div class="absolute inset-0 rounded-full bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/>
                  </svg>
                </div>
              </div>
              <!-- Input ẩn -->
              <input
                ref="avatarInputRef"
                type="file"
                accept=".jpg,.jpeg,.png,.webp,.gif,.avif,.svg,.heic,.heif"
                class="hidden"
                @change="handleAvatarSelected"
              />
            </div>

            <!-- Info + actions -->
            <div class="flex-1 min-w-0">
              <p class="text-[0.95rem] font-semibold text-slate-800">{{ authStore.user?.full_name }}</p>

              <!-- Trạng thái khi đã chọn ảnh nhưng chưa upload -->
              <p v-if="avatarPreview && !avatarUploading" class="text-xs text-amber-600 mb-2"></p>
              <p v-else class="text-xs text-slate-400 mb-3">JPG, PNG, WebP · Tối đa 5 MB</p>

              <!-- Progress bar -->
              <div v-if="avatarUploading" class="mb-3">
                <div class="flex justify-between text-xs text-slate-500 mb-1">
                  <span>Đang tải lên...</span>
                  <span>{{ avatarUploadProgress }}%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-1.5">
                  <div
                    class="bg-sky-500 h-1.5 rounded-full transition-all duration-300"
                    :style="{ width: avatarUploadProgress + '%' }"
                  />
                </div>
              </div>

              <!-- Message -->
              <p v-if="avatarMessage" :class="['text-xs mb-2', avatarSuccess ? 'text-green-600' : 'text-red-500']">{{ avatarMessage }}</p>

              <div class="flex gap-2">
                <!-- Nút chọn ảnh -->
                <button
                  type="button"
                  :disabled="avatarUploading"
                  class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 text-slate-600 hover:bg-slate-200 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="triggerAvatarInput"
                >
                  {{ avatarPreview ? 'Chọn ảnh khác' : 'Chọn ảnh' }}
                </button>

                <!-- Nút xác nhận upload — chỉ hiện khi đã chọn ảnh và chưa upload -->
                <button
                  v-if="avatarPreview"
                  type="button"
                  :disabled="avatarUploading"
                  class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-sky-500 text-white hover:bg-sky-600 transition-all disabled:opacity-50 disabled:cursor-not-allowed"
                  @click="uploadAvatar"
                >
                  {{ avatarUploading ? 'Đang xử lý...' : 'Cập nhật ảnh' }}
                </button>

                <!-- Nút hủy -->
                <button
                  v-if="avatarPreview && !avatarUploading"
                  type="button"
                  class="px-4 py-1.5 rounded-lg text-xs font-semibold bg-white border border-slate-200 text-slate-600 hover:bg-slate-50 transition-all"
                  @click="cancelAvatarSelection"
                >
                  Hủy
                </button>
              </div>
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
              <label for="email" class="text-[0.85rem] font-semibold text-slate-700 after:content-['*'] after:text-red-500 after:ml-1">Email</label>
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                </svg>
                <input required="" id="email" :value="authStore.user?.email" type="email" disabled
                  class="w-full pl-9 pr-3.5 py-2.5 border-[1.5px] border-slate-200 rounded-lg text-sm bg-slate-100 text-slate-500 cursor-not-allowed outline-none" />
              </div>
            </div>

            <div class="flex flex-col gap-1.5">
              <label for="phone" class="text-[0.85rem] font-semibold text-slate-700">Số điện thoại</label>
              <div class="relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                  <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
                <!-- Nếu đã có SĐT → luôn disable, icon khóa -->
                <input
                  id="phone"
                  v-model="profileForm.phone"
                  type="tel"
                  placeholder="Nhập số điện thoại"
                  :disabled="phoneAlreadySet || !isEditing"
                  ref="phoneInputRef"
                  :class="['w-full pl-9 pr-3.5 py-2.5 border-[1.5px] rounded-lg text-sm outline-none transition-all',
                    phoneAlreadySet ? 'bg-slate-100 border-slate-200 text-slate-500 cursor-not-allowed'
                      : !isEditing ? 'bg-slate-100 border-slate-200 text-slate-500 cursor-not-allowed'
                      : requirePhone ? 'bg-white border-amber-400 ring-[3px] ring-amber-400/15'
                      : 'bg-white border-sky-500']"
                />
                <!-- Icon khóa khi đã có SĐT -->
                
              </div>
              <span v-if="phoneAlreadySet" class="text-xs text-slate-400">Số điện thoại đã xác nhận, không thể thay đổi.</span>
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
                <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-br from-sky-500 to-sky-600 text-white hover:from-sky-600 hover:to-sky-700 hover:shadow-lg hover:shadow-sky-500/30 transition-all disabled:opacity-60 disabled:cursor-not-allowed" :disabled="profileLoading || isProfileFormUnchanged">
                  {{ profileLoading ? 'Đang lưu...' : 'Lưu thay đổi' }}
                </button>
              </template>
            </div>
          </form>
        </div>
      </section>

      <!-- ===== MY LISTINGS TAB ===== -->
      <section v-if="activeTab === 'listings'" class="bg-white rounded-xl shadow-sm p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6">Danh sách tin đăng</h2>

        <div class="grid grid-cols-1 gap-3 mb-4 lg:grid-cols-[1fr_auto]">
          <input
            v-model.trim="listingFilters.keyword"
            type="text"
            class="h-10 rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-400"
            placeholder="Nhập giá trị tìm kiếm..."
          />

          <select
            v-model="listingFilters.demandType"
            class="h-10 rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-400"
            @change="loadMyListings(1)"
          >
            <option value="">Loại tin: Tất cả</option>
            <option value="SALE">Mua bán</option>
            <option value="RENT">Cho thuê</option>
          </select>
        </div>

        <div class="flex flex-nowrap overflow-x-auto gap-2 pb-2 mb-4 w-full no-scrollbar">
          <button
            v-for="tab in statusTabs"
            :key="tab.value"
            class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-full border text-[0.85rem] font-medium transition-colors"
            :class="listingFilters.status === tab.value 
              ? 'border-sky-300 bg-sky-50 text-sky-600' 
              : 'border-slate-100 bg-white text-slate-600 hover:bg-slate-50'"
            @click="listingFilters.status = tab.value; loadMyListings(1)"
          >
            <span v-if="tab.colorClass" class="w-1.5 h-1.5 rounded-full" :class="tab.colorClass"></span>
            {{ tab.label }}
            <span class="text-[0.75rem] font-bold" :class="listingFilters.status === tab.value ? 'text-sky-500' : 'text-slate-500'">{{ tab.count || 0 }}</span>
          </button>
        </div>

        <div
          v-if="listingActionMessage"
          :class="['mb-4 rounded-lg px-4 py-3 text-sm border', listingActionSuccess ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700']"
        >
          {{ listingActionMessage }}
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs text-slate-500">
              <tr>
                <th class="px-3 py-4 whitespace-nowrap col-sticky-id">ID</th>
                <th class="px-3 py-4 whitespace-nowrap w-[72px] min-w-[72px]">Ảnh</th>
                <th class="px-3 py-4 whitespace-nowrap">Mã tin đăng</th>
                <th class="px-3 py-4 min-w-[200px]">Tin đăng</th>
                <th class="px-3 py-4 whitespace-nowrap">Ngày tạo</th>
                <th class="px-3 py-4 whitespace-nowrap">Ngày đăng</th>
                <th class="px-3 py-4 min-w-[180px]">Địa chỉ</th>
                <th class="px-3 py-4 whitespace-nowrap">Giá</th>
                <th class="px-3 py-4 whitespace-nowrap">Gói tin</th>
                <th class="px-3 py-4 text-center whitespace-nowrap">Hiển thị</th>
                <th class="px-3 py-4 whitespace-nowrap col-sticky-status">Trạng thái</th>
                <th class="pl-3 pr-5 py-4 w-12 text-center col-sticky-actions"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="listingsLoading">
                <td class="px-3 py-6 text-center text-slate-400" colspan="12">Đang tải dữ liệu...</td>
              </tr>
              <tr v-else-if="myListings.length === 0">
                <td class="px-3 py-6 text-center text-slate-400" colspan="12">Bạn chưa có tin đăng nào.</td>
              </tr>
              <tr v-for="item in myListings" :key="item.id" class="border-t border-slate-100 cursor-pointer hover:bg-sky-50/50 transition group" @click="openListingEdit(item)">
                <td class="px-3 py-4 font-medium text-sky-600 group-hover:underline whitespace-nowrap col-sticky-id">{{ item.id }}</td>
                <td class="px-3 py-4 whitespace-nowrap w-[72px] min-w-[72px]">
                  <img v-if="item.thumbnail" :src="item.thumbnail" alt="thumb" class="h-12 w-12 min-w-12 rounded-md object-cover border border-slate-200" />
                  <div v-else class="h-12 w-12 min-w-12 rounded-md bg-slate-100 border border-slate-200"></div>
                </td>
                <td class="px-3 py-4 text-slate-500 whitespace-nowrap">{{ item.code }}</td>
                <td class="px-3 py-4 font-semibold text-slate-700 group-hover:text-sky-600">{{ item.title }}</td>
                <td class="px-3 py-4 text-slate-500 whitespace-nowrap">{{ item.createdAt }}</td>
                <td class="px-3 py-4 text-slate-500 whitespace-nowrap">{{ item.publishedAt }}</td>
                <td class="px-3 py-4 text-slate-500">{{ item.address }}</td>
                <td class="px-3 py-4 font-semibold text-slate-700 whitespace-nowrap">{{ item.price }}</td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <span :class="['rounded-full px-2 py-1 text-xs font-semibold', packageBadgeClass(item.package)]">
                    {{ packageLabel(item.package) }}
                  </span>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span class="font-medium text-sm">{{ item.views ?? 0 }}</span>
                  </div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap col-sticky-verification">
                  <span :class="['rounded-full px-2 py-1 text-xs font-semibold', verificationBadgeClass(item.isVerified)]">
                    {{ item.isVerified ? 'Đã xác thực' : 'Chưa xác thực' }}
                  </span>
                </td>
                <td class="px-3 py-4 whitespace-nowrap col-sticky-status">
                  <span :class="['rounded-full px-2 py-1 text-xs font-medium', statusBadgeClass(item.status)]">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="pl-3 pr-5 py-4 relative col-sticky-actions">
                  <button @click.stop="toggleDropdown(item.id, $event)" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-colors flex items-center justify-center mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                  </button>
                  <Teleport to="body">
                    <div v-if="openDropdownId === item.id" :style="{ top: dropdownStyle.top, left: dropdownStyle.left }" class="absolute w-[200px] bg-white border border-slate-100 shadow-xl rounded-xl py-2 z-[9999] text-left">
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('edit', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        Sửa tin đăng
                      </button>
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('upgrade', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Nâng cấp gói tin
                      </button>
                      <button v-if="!item.isVerified" class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('verify', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                        Xác thực BĐS
                      </button>
                      <button
                        :disabled="item.status !== 'DRAFT'"
                        :class="[
                          'w-full text-left px-4 py-2.5 text-[0.85rem] flex items-center gap-3 transition-colors',
                          item.status === 'DRAFT'
                            ? 'text-slate-700 hover:bg-slate-50'
                            : 'cursor-not-allowed text-slate-300 bg-slate-50'
                        ]"
                        @click.stop="handleDropdownAction('publish', item)"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Đăng tin
                      </button>
                      <button
                        :disabled="item.status !== 'ACTIVE'"
                        :class="[
                          'w-full text-left px-4 py-2.5 text-[0.85rem] flex items-center gap-3 transition-colors',
                          item.status === 'ACTIVE'
                            ? 'text-slate-700 hover:bg-slate-50'
                            : 'cursor-not-allowed text-slate-300 bg-slate-50'
                        ]"
                        @click.stop="handleDropdownAction('unpublish', item)"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Gỡ tin đăng
                      </button>
                      <button v-if="item.status !== 'LOCKED'" class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('lock', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Khóa tin đăng
                      </button>
                      <button v-if="item.status === 'LOCKED'" class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('unlock', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                        Mở khóa tin đăng
                      </button>
                    </div>
                  </Teleport>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
          <p>Tổng cộng {{ verificationPagination.total }} tin</p>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-50"
              :disabled="verificationPagination.currentPage <= 1 || verificationLoading"
              @click="loadVerificationListings(verificationPagination.currentPage - 1)"
            >
              Trước
            </button>
            <span>Trang {{ verificationPagination.currentPage }}/{{ verificationPagination.lastPage }}</span>
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-50"
              :disabled="verificationPagination.currentPage >= verificationPagination.lastPage || verificationLoading"
              @click="loadVerificationListings(verificationPagination.currentPage + 1)"
            >
              Sau
            </button>
          </div>
        </div>
      </section>

      <!-- ===== PROPERTY VERIFICATIONS TAB ===== -->
      <section v-if="activeTab === 'verifications'" class="bg-white rounded-xl shadow-sm p-8">
        <h2 class="text-xl font-bold text-slate-800 mb-6">Danh sách xác thực BĐS</h2>

        <div class="grid grid-cols-1 gap-3 mb-4">
          <input
            v-model.trim="verificationFilters.keyword"
            type="text"
            class="h-10 rounded-lg border border-slate-200 px-3 text-sm outline-none transition focus:border-sky-400"
            placeholder="Nhập giá trị tìm kiếm..."
          />
        </div>

        <div class="flex flex-nowrap overflow-x-auto gap-2 pb-2 mb-4 w-full no-scrollbar">
          <button
            v-for="tab in verificationStatusTabs"
            :key="tab.value"
            class="flex-shrink-0 flex items-center gap-2 px-4 py-2 rounded-full border text-[0.85rem] font-medium transition-colors"
            :class="verificationFilters.status === tab.value 
              ? 'border-sky-300 bg-sky-50 text-sky-600' 
              : 'border-slate-100 bg-white text-slate-600 hover:bg-slate-50'"
            @click="verificationFilters.status = tab.value; loadVerificationListings(1)"
          >
            <span v-if="tab.colorClass" class="w-1.5 h-1.5 rounded-full" :class="tab.colorClass"></span>
            {{ tab.label }}
            <span class="text-[0.75rem] font-bold" :class="verificationFilters.status === tab.value ? 'text-sky-500' : 'text-slate-500'">{{ tab.count || 0 }}</span>
          </button>
        </div>

        <div
          v-if="listingActionMessage"
          :class="['mb-4 rounded-lg px-4 py-3 text-sm border', listingActionSuccess ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700']"
        >
          {{ listingActionMessage }}
        </div>

        <div class="overflow-x-auto rounded-lg border border-slate-200">
          <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-xs text-slate-500">
              <tr>
                <th class="px-3 py-4 whitespace-nowrap col-sticky-id">ID</th>
                <th class="px-3 py-4 whitespace-nowrap w-[72px] min-w-[72px]">Ảnh</th>
                <th class="px-3 py-4 whitespace-nowrap">Mã tin đăng</th>
                <th class="px-3 py-4 min-w-[200px]">Tin đăng</th>
                <th class="px-3 py-4 whitespace-nowrap">Ngày tạo</th>
                <th class="px-3 py-4 whitespace-nowrap">Ngày đăng</th>
                <th class="px-3 py-4 min-w-[180px]">Địa chỉ</th>
                <th class="px-3 py-4 whitespace-nowrap">Giá</th>
                <th class="px-3 py-4 whitespace-nowrap">Gói tin</th>
                <th class="px-3 py-4 text-center whitespace-nowrap">Hiển thị</th>
                <th class="px-3 py-4 whitespace-nowrap col-sticky-verification">Xác thực</th>
                <th class="px-3 py-4 whitespace-nowrap col-sticky-status">Trạng thái</th>
                <th class="pl-3 pr-5 py-4 w-12 text-center col-sticky-actions"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="verificationLoading">
                <td class="px-3 py-6 text-center text-slate-400" colspan="13">Đang tải dữ liệu...</td>
              </tr>
              <tr v-else-if="verificationListings.length === 0">
                <td class="px-3 py-6 text-center text-slate-400" colspan="13">Bạn chưa có tin đăng nào.</td>
              </tr>
              <tr v-for="item in verificationListings" :key="item.id" class="border-t border-slate-100 cursor-pointer hover:bg-sky-50/50 transition group" @click="openListingEdit(item)">
                <td class="px-3 py-4 font-medium text-sky-600 group-hover:underline whitespace-nowrap col-sticky-id">{{ item.id }}</td>
                <td class="px-3 py-4 whitespace-nowrap w-[72px] min-w-[72px]">
                  <img v-if="item.thumbnail" :src="item.thumbnail" alt="thumb" class="h-12 w-12 min-w-12 rounded-md object-cover border border-slate-200" />
                  <div v-else class="h-12 w-12 min-w-12 rounded-md bg-slate-100 border border-slate-200"></div>
                </td>
                <td class="px-3 py-4 text-slate-500 whitespace-nowrap">{{ item.code }}</td>
                <td class="px-3 py-4 font-semibold text-slate-700 group-hover:text-sky-600">{{ item.title }}</td>
                <td class="px-3 py-4 text-slate-500 whitespace-nowrap">{{ item.createdAt }}</td>
                <td class="px-3 py-4 text-slate-500 whitespace-nowrap">{{ item.publishedAt }}</td>
                <td class="px-3 py-4 text-slate-500">{{ item.address }}</td>
                <td class="px-3 py-4 font-semibold text-slate-700 whitespace-nowrap">{{ item.price }}</td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <span :class="['rounded-full px-2 py-1 text-xs font-semibold', packageBadgeClass(item.package)]">
                    {{ packageLabel(item.package) }}
                  </span>
                </td>
                <td class="px-3 py-4 whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span class="font-medium text-sm">{{ item.views ?? 0 }}</span>
                  </div>
                </td>
                <td class="px-3 py-4 whitespace-nowrap col-sticky-verification">
                  <span :class="['rounded-full px-2 py-1 text-xs font-semibold', verificationBadgeClass(item.isVerified)]">
                    {{ item.isVerified ? 'Đã xác thực' : 'Chưa xác thực' }}
                  </span>
                </td>
                <td class="px-3 py-4 whitespace-nowrap col-sticky-status">
                  <span :class="['rounded-full px-2 py-1 text-xs font-medium', statusBadgeClass(item.status)]">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="pl-3 pr-5 py-4 relative col-sticky-actions">
                  <button @click.stop="toggleDropdown(item.id, $event)" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-colors flex items-center justify-center mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                  </button>
                  <Teleport to="body">
                    <div v-if="openDropdownId === item.id" :style="{ top: dropdownStyle.top, left: dropdownStyle.left }" class="absolute w-[200px] bg-white border border-slate-100 shadow-xl rounded-xl py-2 z-[9999] text-left">
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('edit', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        Sửa tin đăng
                      </button>
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('upgrade', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Nâng cấp gói tin
                      </button>
                      <button v-if="!item.isVerified" class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('verify', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 13c0 5-3.5 7.5-7.66 8.95a1 1 0 0 1-.68 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.24-2.72a1.17 1.17 0 0 1 1.52 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z"/><path d="m9 12 2 2 4-4"/></svg>
                        Xác thực BĐS
                      </button>
                      <button
                        :disabled="item.status !== 'DRAFT'"
                        :class="[
                          'w-full text-left px-4 py-2.5 text-[0.85rem] flex items-center gap-3 transition-colors',
                          item.status === 'DRAFT'
                            ? 'text-slate-700 hover:bg-slate-50'
                            : 'cursor-not-allowed text-slate-300 bg-slate-50'
                        ]"
                        @click.stop="handleDropdownAction('publish', item)"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Đăng tin
                      </button>
                      <button
                        :disabled="item.status !== 'ACTIVE'"
                        :class="[
                          'w-full text-left px-4 py-2.5 text-[0.85rem] flex items-center gap-3 transition-colors',
                          item.status === 'ACTIVE'
                            ? 'text-slate-700 hover:bg-slate-50'
                            : 'cursor-not-allowed text-slate-300 bg-slate-50'
                        ]"
                        @click.stop="handleDropdownAction('unpublish', item)"
                      >
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Gỡ tin đăng
                      </button>
                      <button v-if="item.status !== 'LOCKED'" class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('lock', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Khóa tin đăng
                      </button>
                      <button v-if="item.status === 'LOCKED'" class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('unlock', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 9.9-1"/></svg>
                        Mở khóa tin đăng
                      </button>
                    </div>
                  </Teleport>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
          <p>Tổng cộng {{ verificationPagination.total }} tin</p>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-50"
              :disabled="verificationPagination.currentPage <= 1 || verificationLoading"
              @click="loadVerificationListings(verificationPagination.currentPage - 1)"
            >
              Trước
            </button>
            <span>Trang {{ verificationPagination.currentPage }}/{{ verificationPagination.lastPage }}</span>
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-50"
              :disabled="verificationPagination.currentPage >= verificationPagination.lastPage || verificationLoading"
              @click="loadVerificationListings(verificationPagination.currentPage + 1)"
            >
              Sau
            </button>
          </div>
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
            <label for="currentPassword" class="text-[0.85rem] font-semibold text-slate-700 after:content-['*'] after:text-red-500 after:ml-1">Mật khẩu hiện tại</label>
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
            <label for="newPassword" class="text-[0.85rem] font-semibold text-slate-700 after:content-['*'] after:text-red-500 after:ml-1">Mật khẩu mới</label>
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
            <label for="confirmPassword" class="text-[0.85rem] font-semibold text-slate-700 after:content-['*'] after:text-red-500 after:ml-1">Xác nhận mật khẩu mới</label>
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
            <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-br from-sky-500 to-sky-600 text-white hover:from-sky-600 hover:to-sky-700 hover:shadow-lg hover:shadow-sky-500/30 transition-all disabled:opacity-60 disabled:cursor-not-allowed" :disabled="passwordLoading || !isPasswordFormValid">
              {{ passwordLoading ? 'Đang xử lý...' : 'Cập nhật mật khẩu' }}
            </button>
          </div>
        </form>
      </section>

      <!-- ===== APPOINTMENTS TAB ===== -->
      <section v-if="activeTab === 'appointments'" class="bg-white rounded-xl shadow-sm p-8">
        <AppointmentManagement />
      </section>

      <!-- ===== FAVORITES TAB ===== -->
      <section v-if="activeTab === 'favorites'" class="bg-white rounded-xl shadow-sm p-6">
        <!-- Breadcrumb & Title -->
        <div class="mb-5 flex flex-col gap-1">
          <p class="text-xs text-slate-400">
            <span class="hover:text-sky-500 cursor-pointer" @click="router.push('/')">Trang chủ</span>
            <span class="mx-1.5">&gt;</span>
            <span class="text-slate-600 font-medium">Tin đăng yêu thích</span>
          </p>
          <h1 class="text-[22px] font-bold text-slate-800">Tin đăng yêu thích</h1>
        </div>

        <!-- Filters & Search Row -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <!-- Type Filter Tabs -->
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded-full border text-[0.85rem] font-medium transition-colors flex items-center gap-1.5"
              :class="favoriteDemandType === ''
                ? 'border-sky-300 bg-sky-50 text-sky-600'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
              @click="favoriteDemandType = ''; favoriteCurrentPage = 1"
            >
              Tất cả {{ favoriteCounts.all }}
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-full border text-[0.85rem] font-medium transition-colors flex items-center gap-1.5"
              :class="favoriteDemandType === 'RENT'
                ? 'border-emerald-300 bg-emerald-50 text-emerald-600'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
              @click="favoriteDemandType = 'RENT'; favoriteCurrentPage = 1"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              Cho thuê {{ favoriteCounts.rent }}
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-full border text-[0.85rem] font-medium transition-colors flex items-center gap-1.5"
              :class="favoriteDemandType === 'SALE'
                ? 'border-blue-300 bg-blue-50 text-blue-600'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
              @click="favoriteDemandType = 'SALE'; favoriteCurrentPage = 1"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
              Bán {{ favoriteCounts.sale }}
            </button>
          </div>

          <!-- Search Bar -->
          <div class="relative w-full md:w-[320px]">
            <input
              v-model="favoriteSearchQuery"
              type="text"
              class="h-10 w-full rounded-lg border border-slate-200 pl-9 pr-3 text-sm outline-none transition focus:border-sky-400 focus:ring-1 focus:ring-sky-400/20"
              placeholder="Nhập giá trị tìm kiếm..."
              @input="favoriteCurrentPage = 1"
            />
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
          </div>
        </div>

        <div v-if="favoritesLoading" class="py-8 text-center text-sm text-slate-400">
          Đang tải dữ liệu...
        </div>

        <div v-else-if="filteredFavoriteListings.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-400">
          Không tìm thấy tin đăng yêu thích nào.
        </div>

        <div v-else class="space-y-4">
          <template v-for="item in paginatedFavoriteListings" :key="item.id">
            <!-- Rent Card -->
            <RentCard
              v-if="item.demandType === 'RENT'"
              :to="'/listings/' + item.id"
              :verified="isVerified(item)"
              :title="item.title"
              :type="propertyTypeLabel(item.property?.type)"
              :price="formatPrice(item.property?.price)"
              :unit="'/tháng'"
              :area="item.property?.area || 0"
              :beds="item.property?.bedrooms || 0"
              :baths="item.property?.bathrooms || 0"
              :location="item.address"
              :author="getAuthor(item)"
              :image="getThumb(item)"
              :images="item.images"
              :package="item.package"
              :listing-id="item.id"
              :is-favorite="true"
              :rating="null"
              :timeAgo="timeAgo(item.publishedAt || item.submittedAt)"
              :views="item.views ?? 0"
              @toggle-favorite="removeFavorite(item)"
            />
            <!-- Sale Card -->
            <SaleCard 
              v-else
              :to="'/listings/' + item.id"
              :verified="isVerified(item)"
              :title="item.title"
              :type="propertyTypeLabel(item.property?.type)"
              :price="formatPrice(item.property?.price)"
              :unit="''"
              :area="item.property?.area || 0"
              :beds="item.property?.bedrooms || 0"
              :baths="item.property?.bathrooms || 0"
              :location="item.address"
              :author="getAuthor(item)"
              :image="getThumb(item)"
              :images="item.images"
              :package="item.package"
              :listing-id="item.id"
              :is-favorite="true"
              :rating="null"
              :timeAgo="timeAgo(item.publishedAt || item.submittedAt)"
              :views="item.views ?? 0"
              @toggle-favorite="removeFavorite(item)"
            />
          </template>

          <!-- Frontend Pagination -->
          <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
            <p>Tất cả {{ favoritePagination.total }} dòng</p>
            
            <div class="flex items-center gap-3">
              <!-- Back Button -->
              <button
                type="button"
                class="rounded-lg border border-slate-200 w-8 h-8 flex items-center justify-center hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                :disabled="favoriteCurrentPage <= 1"
                @click="favoriteCurrentPage--"
              >
                ‹
              </button>
              
              <!-- Current page number input display -->
              <input
                v-model.number="favoriteCurrentPage"
                type="text"
                class="w-10 h-8 text-center rounded-lg border border-slate-200 text-sm outline-none transition focus:border-sky-400"
              />
              
              <!-- Next Button -->
              <button
                type="button"
                class="rounded-lg border border-slate-200 w-8 h-8 flex items-center justify-center hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                :disabled="favoriteCurrentPage >= favoritePagination.lastPage"
                @click="favoriteCurrentPage++"
              >
                ›
              </button>

              <!-- Page Size Select -->
              <select
                v-model="favoritePageSize"
                class="h-8 rounded-lg border border-slate-200 px-2 text-xs outline-none focus:border-sky-400"
                @change="favoriteCurrentPage = 1"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </div>
          </div>
        </div>
      </section>

      <!-- ===== RECENTLY VIEWED TAB ===== -->
      <section v-if="activeTab === 'recently-viewed'" class="bg-white rounded-xl shadow-sm p-6">
        <!-- Breadcrumb & Title -->
        <div class="mb-5 flex flex-col gap-1">
          <p class="text-xs text-slate-400">
            <span class="hover:text-sky-500 cursor-pointer" @click="router.push('/')">Trang chủ</span>
            <span class="mx-1.5">&gt;</span>
            <span class="text-slate-600 font-medium">Tin đăng đã xem</span>
          </p>
          <h1 class="text-[22px] font-bold text-slate-800">Tin đăng đã xem</h1>
        </div>

        <!-- Filters & Search Row -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
          <!-- Type Filter Tabs -->
          <div class="flex flex-wrap gap-2">
            <button
              type="button"
              class="px-4 py-2 rounded-full border text-[0.85rem] font-medium transition-colors flex items-center gap-1.5"
              :class="viewedDemandType === ''
                ? 'border-sky-300 bg-sky-50 text-sky-600'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
              @click="viewedDemandType = ''; viewedCurrentPage = 1"
            >
              Tất cả {{ viewedCounts.all }}
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-full border text-[0.85rem] font-medium transition-colors flex items-center gap-1.5"
              :class="viewedDemandType === 'RENT'
                ? 'border-emerald-300 bg-emerald-50 text-emerald-600'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
              @click="viewedDemandType = 'RENT'; viewedCurrentPage = 1"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
              Cho thuê {{ viewedCounts.rent }}
            </button>
            <button
              type="button"
              class="px-4 py-2 rounded-full border text-[0.85rem] font-medium transition-colors flex items-center gap-1.5"
              :class="viewedDemandType === 'SALE'
                ? 'border-blue-300 bg-blue-50 text-blue-600'
                : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
              @click="viewedDemandType = 'SALE'; viewedCurrentPage = 1"
            >
              <span class="w-1.5 h-1.5 rounded-full bg-blue-500"></span>
              Bán {{ viewedCounts.sale }}
            </button>
          </div>

          <!-- Search Bar -->
          <div class="relative w-full md:w-[320px]">
            <input
              v-model="viewedSearchQuery"
              type="text"
              class="h-10 w-full rounded-lg border border-slate-200 pl-9 pr-3 text-sm outline-none transition focus:border-sky-400 focus:ring-1 focus:ring-sky-400/20"
              placeholder="Nhập giá trị tìm kiếm..."
              @input="viewedCurrentPage = 1"
            />
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/>
            </svg>
          </div>
        </div>

        <div v-if="viewedLoading" class="py-8 text-center text-sm text-slate-400">
          Đang tải dữ liệu...
        </div>

        <div v-else-if="filteredViewedListings.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-8 text-center text-sm text-slate-400">
          Không tìm thấy tin đăng đã xem nào.
        </div>

        <div v-else class="space-y-4">
          <template v-for="item in paginatedViewedListings" :key="item.id">
            <!-- Rent Card -->
            <RentCard
              v-if="item.demandType === 'RENT'"
              :to="'/listings/' + item.id"
              :verified="isVerified(item)"
              :title="item.title"
              :type="propertyTypeLabel(item.property?.type)"
              :price="formatPrice(item.property?.price)"
              :unit="'/tháng'"
              :area="item.property?.area || 0"
              :beds="item.property?.bedrooms || 0"
              :baths="item.property?.bathrooms || 0"
              :location="item.address"
              :author="getAuthor(item)"
              :image="getThumb(item)"
              :images="item.images"
              :package="item.package"
              :listing-id="item.id"
              :is-favorite="isFavorite(item)"
              :rating="null"
              :timeAgo="timeAgo(item.publishedAt || item.submittedAt)"
              :views="item.views ?? 0"
              @toggle-favorite="toggleFavoriteFromViewed(item)"
            />
            <!-- Sale Card -->
            <SaleCard 
              v-else
              :to="'/listings/' + item.id"
              :verified="isVerified(item)"
              :title="item.title"
              :type="propertyTypeLabel(item.property?.type)"
              :price="formatPrice(item.property?.price)"
              :unit="''"
              :area="item.property?.area || 0"
              :beds="item.property?.bedrooms || 0"
              :baths="item.property?.bathrooms || 0"
              :location="item.address"
              :author="getAuthor(item)"
              :image="getThumb(item)"
              :images="item.images"
              :package="item.package"
              :listing-id="item.id"
              :is-favorite="isFavorite(item)"
              :rating="null"
              :timeAgo="timeAgo(item.publishedAt || item.submittedAt)"
              :views="item.views ?? 0"
              @toggle-favorite="toggleFavoriteFromViewed(item)"
            />
          </template>

          <!-- Frontend Pagination -->
          <div class="mt-6 pt-4 border-t border-slate-100 flex flex-wrap items-center justify-between gap-3 text-sm text-slate-500">
            <p>Tất cả {{ viewedPagination.total }} dòng</p>
            
            <div class="flex items-center gap-3">
              <button
                type="button"
                class="rounded-lg border border-slate-200 w-8 h-8 flex items-center justify-center hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                :disabled="viewedCurrentPage <= 1"
                @click="viewedCurrentPage--"
              >
                ‹
              </button>
              
              <input
                v-model.number="viewedCurrentPage"
                type="text"
                class="w-10 h-8 text-center rounded-lg border border-slate-200 text-sm outline-none transition focus:border-sky-400"
              />
              
              <button
                type="button"
                class="rounded-lg border border-slate-200 w-8 h-8 flex items-center justify-center hover:bg-slate-50 disabled:opacity-50 disabled:cursor-not-allowed transition"
                :disabled="viewedCurrentPage >= viewedPagination.lastPage"
                @click="viewedCurrentPage++"
              >
                ›
              </button>

              <select
                v-model="viewedPageSize"
                class="h-8 rounded-lg border border-slate-200 px-2 text-xs outline-none focus:border-sky-400"
                @change="viewedCurrentPage = 1"
              >
                <option :value="10">10</option>
                <option :value="25">25</option>
                <option :value="50">50</option>
              </select>
            </div>
          </div>
        </div>
      </section>
            <ProfileNotificationsPanel v-if="activeTab === 'notifications'" />
    </main>
    </div>

    <ConfirmActionModal
      :open="lockListingModalOpen"
      title="Xác nhận khóa tin"
      :message="lockListingModalMessage"
      confirm-text="Xác nhận khóa"
      cancel-text="Hủy"
      :loading="lockingListing"
      loading-text="Đang khóa..."
      @confirm="handleConfirmLockListing"
      @cancel="closeLockListingModal"
    />

    <ConfirmActionModal
      :open="unlistListingModalOpen"
      title="Xác nhận gỡ tin"
      :message="unlistListingModalMessage"
      confirm-text="Xác nhận gỡ"
      cancel-text="Hủy"
      :loading="unlistingListing"
      loading-text="Đang gỡ..."
      @confirm="handleConfirmUnlistListing"
      @cancel="closeUnlistListingModal"
    />

    <!-- Package Upgrade Modal -->
    <PackageUpgradeModal
      :visible="upgradeModalVisible"
      :listing-id="upgradeListingId"
      :current-package-id="upgradeCurrentPackageId"
      @close="upgradeModalVisible = false"
      @upgraded="onUpgradeSuccess"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useAuthStore } from '@/stores/auth';
import { useRoute, useRouter } from 'vue-router';
import Breadcrumb from '@/components/shared/Breadcrumb.vue';
import userService from '@/services/userService';
import cloudinaryService from '@/services/cloudinaryService';
import listingService from '@/services/listingService';
import favoriteService from '@/services/favoriteService';
import recentlyViewedService from '@/services/recentlyViewedService';
import PackageUpgradeModal from '@/components/shared/PackageUpgradeModal.vue';
import ConfirmActionModal from '@/components/shared/ConfirmActionModal.vue';
import AppointmentManagement from '@/components/appointments/AppointmentManagement.vue';
import ProfileNotificationsPanel from '@/components/profile/ProfileNotificationsPanel.vue';
import SaleCard from '@/components/shared/SaleCard.vue';
import RentCard from '@/components/shared/RentCard.vue';
import { buildPropertyAddress, hydrateListingAddresses } from '@/utils/addressFormatter';
import { Heart } from 'lucide-vue-next';

const authStore = useAuthStore();
const route = useRoute();
const router = useRouter();

// ── Avatar upload ──
const avatarInputRef = ref(null);
const avatarPreview = ref(null);
const avatarFile = ref(null);
const avatarUploading = ref(false);
const avatarUploadProgress = ref(0);
const avatarMessage = ref('');
const avatarSuccess = ref(false);

function triggerAvatarInput() {
  avatarInputRef.value?.click();
}

function handleAvatarSelected(event) {
  const file = event.target.files?.[0];
  if (!file) return;

  const allowedTypes = [
    'image/jpeg', 'image/png', 'image/webp', 'image/gif', 
    'image/avif', 'image/svg+xml', 'image/heic', 'image/heif'
  ];

  if (!allowedTypes.includes(file.type)) {
    avatarMessage.value = 'Định dạng ảnh không được hỗ trợ. Vui lòng chọn ảnh JPG, PNG, WebP, GIF hoặc AVIF.';
    avatarSuccess.value = false;
    if (avatarInputRef.value) avatarInputRef.value.value = '';
    return;
  }

  // Validate size (5MB)
  if (file.size > 5 * 1024 * 1024) {
    avatarMessage.value = 'Ảnh không được vượt quá 5 MB.';
    avatarSuccess.value = false;
    return;
  }

  avatarFile.value = file;
  avatarMessage.value = '';
  avatarSuccess.value = false;

  // Chỉ preview tức thì — KHÔNG upload, đợi user nhấn "Cập nhật ảnh"
  const reader = new FileReader();
  reader.onload = (e) => { avatarPreview.value = e.target.result; };
  reader.readAsDataURL(file);
}

function cancelAvatarSelection() {
  avatarPreview.value = null;
  avatarFile.value = null;
  avatarMessage.value = '';
  if (avatarInputRef.value) avatarInputRef.value.value = '';
}

async function uploadAvatar() {
  if (!avatarFile.value) return;

  avatarUploading.value = true;
  avatarUploadProgress.value = 0;
  avatarMessage.value = '';

  try {
    const result = await cloudinaryService.uploadImage(avatarFile.value, 'avatar', (percent) => {
      avatarUploadProgress.value = percent;
    });

    const newAvatarUrl = result.secure_url;

    const res = await userService.updateProfile({
      fullName: authStore.user?.full_name || profileForm.fullName,
      avatarUrl: newAvatarUrl,
    });

    const updatedUser = res.data.data;
    authStore.user = { ...authStore.user, ...updatedUser };
    sessionStorage.setItem('auth_user', JSON.stringify(authStore.user));

    avatarPreview.value = null;
    avatarFile.value = null;
    avatarSuccess.value = true;
    avatarMessage.value = 'Cập nhật ảnh đại diện thành công!';
  } catch (error) {
    avatarSuccess.value = false;
    avatarMessage.value = error.response?.data?.message || error.message || 'Upload ảnh thất bại. Vui lòng thử lại.';
    avatarPreview.value = null;
    avatarFile.value = null;
  } finally {
    avatarUploading.value = false;
    if (avatarInputRef.value) avatarInputRef.value.value = '';
  }
}

// ── Tabs ──
const validTabs = ['profile', 'listings', 'verifications', 'appointments', 'favorites', 'recently-viewed', 'notifications', 'password'];
const initialTab = validTabs.includes(route.query.tab) ? route.query.tab : 'profile';
const activeTab = ref(initialTab);

watch(activeTab, (newTab) => {
  const query = { ...route.query };
  if (newTab === 'profile') {
    delete query.tab;
  } else {
    query.tab = newTab;
  }
  router.replace({ query }).catch(() => {});
});

// ── Dropdown ──
watch(
  () => route.query.tab,
  (tab) => {
    const nextTab = validTabs.includes(tab) ? tab : 'profile';
    if (nextTab === activeTab.value) return;

    if (nextTab === 'listings') {
      openListingsTab();
    } else if (nextTab === 'verifications') {
      openVerificationsTab();
    } else if (nextTab === 'appointments') {
      openAppointmentsTab();
    } else if (nextTab === 'favorites') {
      openFavoritesTab();
    } else if (nextTab === 'recently-viewed') {
      openRecentlyViewedTab();
    } else if (nextTab === 'notifications') {
      openNotificationsTab();
    } else {
      activeTab.value = nextTab;
    }
  },
);

const openDropdownId = ref(null);
const dropdownStyle = reactive({ top: '0px', left: '0px' });

function toggleDropdown(id, event) {
  if (openDropdownId.value === id) {
    openDropdownId.value = null;
  } else {
    openDropdownId.value = id;
    const rect = event.currentTarget.getBoundingClientRect();
    dropdownStyle.top = `${rect.bottom + window.scrollY + 4}px`;
    dropdownStyle.left = `${rect.right + window.scrollX - 200}px`;
  }
}
function closeDropdown() {
  openDropdownId.value = null;
}

function openListingEdit(item) {
  if (!item?.id) return;
  router.push('/listings/' + item.id + '/edit');
}

function handleDropdownAction(action, item) {
  closeDropdown();
  if (action === 'edit') {
    openListingEdit(item);
  } else if (action === 'verify') {
    router.push({ path: '/listings/' + item.id + '/edit', query: { mode: 'verification' } });
  } else if (action === 'upgrade') {
    if (item.status === 'ACTIVE') openUpgradeModal(item);
    else alert('Chỉ có thể nâng cấp tin đang đăng.');
  } else if (action === 'publish') {
    if (item.status === 'DRAFT') {
      alert('Tính năng đang phát triển');
    } else {
      alert('Chỉ có thể đăng tin nháp.');
    }
  } else if (action === 'unpublish') {
    openUnlistListingModal(item);
  } else {
    alert('Tính năng đang phát triển');
  }
}

const listingsLoaded = ref(false);
const listingsLoading = ref(false);
const myListings = ref([]);
const listingFilters = reactive({
  keyword: '',
  status: '',
  demandType: '',
});
const listingPagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
});
const listingStatusOptions = ref([]);

const statusCounts = reactive({
  ALL: 0,
  PENDING: 0,
  ACTIVE: 0,
  DRAFT: 0,
  REJECTED: 0,
  LOCKED: 0,
  UNLISTED: 0,
});

const statusTabs = computed(() => [
  { label: 'Tất cả', value: '', colorClass: '', count: statusCounts.ALL },
  { label: 'Chờ duyệt', value: 'PENDING', colorClass: 'bg-orange-500', count: statusCounts.PENDING },
  { label: 'Tin đang đăng', value: 'ACTIVE', colorClass: 'bg-emerald-500', count: statusCounts.ACTIVE },
  { label: 'Tin nháp', value: 'DRAFT', colorClass: 'bg-slate-400', count: statusCounts.DRAFT },
  { label: 'Từ chối', value: 'REJECTED', colorClass: 'bg-rose-500', count: statusCounts.REJECTED },
  { label: 'Tin bị khóa', value: 'LOCKED', colorClass: 'bg-red-600', count: statusCounts.LOCKED },
  { label: 'Đã gỡ', value: 'UNLISTED', colorClass: 'bg-slate-500', count: statusCounts.UNLISTED },
]);

const verificationListings = ref([]);
const verificationLoading = ref(false);
const verificationLoaded = ref(false);
const verificationFilters = reactive({
  keyword: '',
  status: '',
});
const verificationPagination = reactive({
  currentPage: 1,
  lastPage: 1,
  total: 0,
});
const verificationStatusCounts = reactive({
  ALL: 0,
  PENDING: 0,
  ACTIVE: 0,
  DRAFT: 0,
  REJECTED: 0,
  LOCKED: 0,
  UNLISTED: 0,
});
const verificationStatusTabs = computed(() => [
  { label: 'Tất cả', value: '', colorClass: '', count: verificationStatusCounts.ALL },
  { label: 'Chờ duyệt', value: 'PENDING', colorClass: 'bg-orange-500', count: verificationStatusCounts.PENDING },
  { label: 'Tin đang đăng', value: 'ACTIVE', colorClass: 'bg-emerald-500', count: verificationStatusCounts.ACTIVE },
  { label: 'Tin nháp', value: 'DRAFT', colorClass: 'bg-slate-400', count: verificationStatusCounts.DRAFT },
  { label: 'Từ chối', value: 'REJECTED', colorClass: 'bg-rose-500', count: verificationStatusCounts.REJECTED },
  { label: 'Tin bị khóa', value: 'LOCKED', colorClass: 'bg-red-600', count: verificationStatusCounts.LOCKED },
  { label: 'Đã gỡ', value: 'UNLISTED', colorClass: 'bg-slate-500', count: verificationStatusCounts.UNLISTED },
]);
let verificationSearchTimeout = null;
watch(() => verificationFilters.keyword, () => {
  if (verificationSearchTimeout) clearTimeout(verificationSearchTimeout);
  verificationSearchTimeout = setTimeout(() => {
    loadVerificationListings(1);
  }, 300);
});

let searchTimeout = null;
watch(() => listingFilters.keyword, () => {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => {
    loadMyListings(1);
  }, 300);
});

const lockListingModalOpen = ref(false);
const lockListingTarget = ref(null);
const lockingListing = ref(false);
const unlistListingModalOpen = ref(false);
const unlistListingTarget = ref(null);
const unlistingListing = ref(false);
const listingActionMessage = ref('');
const listingActionSuccess = ref(false);
const favoritesLoaded = ref(false);
const favoritesLoading = ref(false);
const favoriteListings = ref([]);

// ── Phone required from đăng tin ──
const requirePhone = ref(false);
const phoneInputRef = ref(null);

// ── Phone đã có → không cho sửa ──
const phoneAlreadySet = computed(() => !!authStore.user?.phone);

// ── Sidebar expand/collapse ──
const expandedSections = reactive({
  listings: route.query.tab === 'listings' || route.query.tab === 'verifications',
  appointments: route.query.tab === 'appointments',
});

function toggleSection(key) {
  expandedSections[key] = !expandedSections[key];
}

function statusLabel(status) {
  return listingStatusOptions.value.find((option) => option.value === status)?.label || status;
}

function statusTabColorClass(status) {
  const map = {
    DRAFT: 'bg-slate-400',
    PENDING: 'bg-orange-500',
    ACTIVE: 'bg-emerald-500',
    EXPIRED: 'bg-slate-500',
    REJECTED: 'bg-rose-500',
    LOCKED: 'bg-red-600',
    UNLISTED: 'bg-slate-500',
  };
  return map[status] || 'bg-slate-400';
}

function statusBadgeClass(status) {
  const map = {
    DRAFT: 'bg-slate-100 text-slate-600',
    PENDING: 'bg-amber-100 text-amber-700',
    ACTIVE: 'bg-emerald-100 text-emerald-700',
    EXPIRED: 'bg-slate-200 text-slate-600',
    REJECTED: 'bg-rose-100 text-rose-700',
    LOCKED: 'bg-red-100 text-red-700',
    UNLISTED: 'bg-slate-100 text-slate-600',
  };
  return map[status] || 'bg-slate-100 text-slate-600';
}

async function fetchListingOptions() {
  try {
    const response = await listingService.getPostingOptions();
    listingStatusOptions.value = response?.data?.data?.listing_statuses || [];
  } catch (error) {
    console.error('Failed to load listing options:', error);
  }
}

function toListingCode(id) {
  return `LH-${String(id).padStart(8, '0')}`;
}

function formatCurrency(value) {
  const number = Number(value || 0);
  if (!Number.isFinite(number) || number <= 0) return 'Thỏa thuận';
  return number.toLocaleString('vi-VN');
}

function formatListingDate(value) {
  if (!value) return '--';
  const date = new Date(value);
  if (Number.isNaN(date.getTime())) return '--';
  return date.toLocaleDateString('vi-VN');
}

function buildAddress(property) {
  return property?.full_address || buildPropertyAddress(property);
}

function packageLabel(pkg) {
  return pkg?.badge || pkg?.name || 'Thường';
}

function packageBadgeClass(pkg) {
  const priority = Number(pkg?.priority || 0);
  if (priority === 4) return 'bg-sky-100 text-sky-700';
  if (priority === 3) return 'bg-rose-100 text-rose-700';
  if (priority === 2) return 'bg-amber-100 text-amber-700';
  if (priority === 1) return 'bg-indigo-100 text-indigo-700';
  return 'bg-slate-100 text-slate-600';
}

function verificationBadgeClass(isVerified) {
  return isVerified ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600';
}

function normalizeListings(items) {
  return items.map((item) => {
    const thumbnail = item?.images?.find((img) => img?.is_thumbnail)?.url || item?.images?.[0]?.url || '';
    return {
      id: item.id,
      code: toListingCode(item.id),
      title: item.title || '(Không tiêu đề)',
      thumbnail,
      createdAt: formatListingDate(item.created_at),
      publishedAt: item.published_at ? formatListingDate(item.published_at) : '--',
      address: buildAddress(item.property),
      price: formatCurrency(item?.property?.price),
      status: item.status,
      package: item.package || null,
      isVerified: Boolean(item.is_verified),
      views: item.views ?? 0,
    };
  });
}

function normalizeFavoriteListings(items) {
  return items.map((item) => {
    const thumbnail = item?.images?.find((img) => img?.is_thumbnail)?.url || item?.images?.[0]?.url || '';
    return {
      id: item.id,
      title: item.title || '(Không tiêu đề)',
      thumbnail,
      images: item.images || [],
      address: buildAddress(item.property),
      price: formatCurrency(item?.property?.price),
      property: item.property || {},
      owner: item.owner || {},
      demandType: item.demand_type,
      publishedAt: item.published_at || item.submitted_at || item.created_at,
      submittedAt: item.submitted_at || item.created_at,
      views: item.views ?? 0,
      isVerified: Boolean(item.is_verified),
      package: item.package || null,
      score: item.score ?? 8.0,
    };
  });
}

// ── Client-side search, filter and pagination for Favorites ──
const favoriteSearchQuery = ref('');
const favoriteDemandType = ref('');
const favoritePageSize = ref(25);
const favoriteCurrentPage = ref(1);

const favoriteCounts = computed(() => {
  const all = favoriteListings.value.length;
  const rent = favoriteListings.value.filter(item => item.demandType === 'RENT').length;
  const sale = favoriteListings.value.filter(item => item.demandType === 'SALE').length;
  return { all, rent, sale };
});

const filteredFavoriteListings = computed(() => {
  return favoriteListings.value.filter((item) => {
    if (favoriteDemandType.value && item.demandType !== favoriteDemandType.value) {
      return false;
    }
    if (favoriteSearchQuery.value.trim()) {
      const q = favoriteSearchQuery.value.toLowerCase().trim();
      const matchTitle = item.title.toLowerCase().includes(q);
      const matchAddress = item.address.toLowerCase().includes(q);
      const matchPrice = item.price.toLowerCase().includes(q);
      return matchTitle || matchAddress || matchPrice;
    }
    return true;
  });
});

const favoritePagination = computed(() => {
  const total = filteredFavoriteListings.value.length;
  const lastPage = Math.ceil(total / favoritePageSize.value) || 1;
  return {
    total,
    lastPage,
  };
});

const paginatedFavoriteListings = computed(() => {
  const start = (favoriteCurrentPage.value - 1) * favoritePageSize.value;
  const end = start + favoritePageSize.value;
  return filteredFavoriteListings.value.slice(start, end);
});

function propertyTypeLabel(type) {
  const map = {
    APARTMENT: 'Căn hộ chung cư',
    HOUSE: 'Nhà ở',
    LAND: 'Đất',
    ROOM: 'Phòng',
    PRIVATE_HOUSE: 'Nhà riêng',
    STREET_HOUSE: 'Nhà mặt phố',
    VILLA_TOWNHOUSE: 'Biệt thự liền kề',
    SHOPHOUSE: 'Shophouse',
    RENT_ROOM: 'Phòng trọ',
    OFFICE: 'Văn phòng',
  };
  return map[type] || type || 'BĐS';
}

function packageBadgeColor(pkg) {
  const priority = Number(pkg?.priority || 0);
  if (priority === 4) return 'bg-[#3b82f6]'; // Blue for Diamond
  if (priority === 3) return 'bg-[#dc2626]'; // Red for Premium/Ruby
  if (priority === 2) return 'bg-[#d97706]'; // Orange for Gold
  if (priority === 1) return 'bg-indigo-500';
  return 'bg-slate-500';
}

function timeAgo(dateStr) {
  if (!dateStr) return '';
  const now = new Date();
  const d = new Date(dateStr);
  const diffMs = now - d;
  const diffMins = Math.floor(diffMs / 60000);
  if (diffMins < 60) return `${diffMins} phút trước`;
  const diffHours = Math.floor(diffMins / 60);
  if (diffHours < 24) return `${diffHours} giờ trước`;
  const diffDays = Math.floor(diffHours / 24);
  if (diffDays < 30) return `${diffDays} ngày trước`;
  return d.toLocaleDateString('vi-VN');
}

function isVerified(item) {
  const value = item?.is_verified ?? item?.isVerified;
  return value === true || Number(value) === 1;
}

function isFavorite(item) {
  return favoriteListings.value.some((fav) => Number(fav.id) === Number(item.id));
}

function getThumb(item) {
  if (item.images && item.images.length > 0) {
    const thumb = item.images.find((image) => image.is_thumbnail || image.isThumbnail);
    return thumb ? thumb.url : item.images[0].url;
  }
  return item.thumbnail || 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=60';
}

function getAuthor(item) {
  return {
    name: item.property?.contact_name || item.owner?.full_name || 'Chủ nhà',
    role: item.property?.poster_type === 'OWNER' ? 'Chủ nhà' : 'Môi giới',
    phone: item.property?.contact_phone || item.owner?.phone,
  };
}

function formatPrice(value) {
  const num = Number(value || 0);
  if (!num || num <= 0) return 'Thỏa thuận';
  if (num >= 1000000000) return `${(num / 1000000000).toLocaleString('vi-VN')} tỷ`;
  if (num >= 1000000) return `${(num / 1000000).toLocaleString('vi-VN')} triệu`;
  return `${num.toLocaleString('vi-VN')} đ`;
}

// ==================== Package Upgrade ====================
const upgradeModalVisible = ref(false);
const upgradeListingId = ref(null);
const upgradeCurrentPackageId = ref(null);

function openUpgradeModal(item) {
  upgradeListingId.value = item.id;
  upgradeCurrentPackageId.value = item.package?.id || null;
  upgradeModalVisible.value = true;
}

function onUpgradeSuccess() {
  if (activeTab.value === 'verifications') {
    loadVerificationListings(verificationPagination.currentPage);
  } else {
    loadMyListings(listingPagination.currentPage);
  }
}

async function loadMyListings(page = 1) {
  listingsLoading.value = true;
  try {
    const response = await listingService.getMyListings({
      page,
      per_page: 10,
      keyword: listingFilters.keyword || undefined,
      status: listingFilters.status || undefined,
      demand_type: listingFilters.demandType || undefined,
    });

    const data = response?.data?.data || [];
    const meta = response?.data?.meta || {};
    await hydrateListingAddresses(data);

    myListings.value = normalizeListings(data);
    listingPagination.currentPage = Number(meta.current_page || 1);
    listingPagination.lastPage = Number(meta.last_page || 1);
    listingPagination.total = Number(meta.total || 0);

    if (meta.counts) {
      statusCounts.ALL = meta.counts.ALL || 0;
      statusCounts.PENDING = meta.counts.PENDING || 0;
      statusCounts.ACTIVE = meta.counts.ACTIVE || 0;
      statusCounts.DRAFT = meta.counts.DRAFT || 0;
      statusCounts.REJECTED = meta.counts.REJECTED || 0;
      statusCounts.LOCKED = meta.counts.LOCKED || 0;
      statusCounts.UNLISTED = meta.counts.UNLISTED || 0;
    }

    listingsLoaded.value = true;
  } catch {
    myListings.value = [];
    listingPagination.currentPage = 1;
    listingPagination.lastPage = 1;
    listingPagination.total = 0;
  } finally {
    listingsLoading.value = false;
  }
}

async function loadVerificationListings(page = 1) {
  verificationLoading.value = true;
  try {
    const response = await listingService.getMyListings({
      page,
      per_page: 10,
      keyword: verificationFilters.keyword || undefined,
      status: verificationFilters.status || undefined,
      demand_type: 'SALE',
    });

    const data = response?.data?.data || [];
    const meta = response?.data?.meta || {};
    await hydrateListingAddresses(data);

    verificationListings.value = normalizeListings(data);
    verificationPagination.currentPage = Number(meta.current_page || 1);
    verificationPagination.lastPage = Number(meta.last_page || 1);
    verificationPagination.total = Number(meta.total || 0);

    if (meta.counts) {
      verificationStatusCounts.ALL = meta.counts.ALL || 0;
      verificationStatusCounts.PENDING = meta.counts.PENDING || 0;
      verificationStatusCounts.ACTIVE = meta.counts.ACTIVE || 0;
      verificationStatusCounts.DRAFT = meta.counts.DRAFT || 0;
      verificationStatusCounts.REJECTED = meta.counts.REJECTED || 0;
      verificationStatusCounts.LOCKED = meta.counts.LOCKED || 0;
      verificationStatusCounts.UNLISTED = meta.counts.UNLISTED || 0;
    }

    verificationLoaded.value = true;
  } catch {
    verificationListings.value = [];
    verificationPagination.currentPage = 1;
    verificationPagination.lastPage = 1;
    verificationPagination.total = 0;
  } finally {
    verificationLoading.value = false;
  }
}

function openVerificationsTab() {
  activeTab.value = 'verifications';
  expandedSections.listings = true;
  if (!verificationLoaded.value) {
    loadVerificationListings(1);
  }
}

const lockListingModalMessage = computed(() => {
  if (!lockListingTarget.value) {
    return 'Bạn có chắc chắn muốn khóa tin này không?';
  }

  return `Bạn có chắc chắn muốn khóa tin "${lockListingTarget.value.title}" không?`;
});

function openLockListingModal(item) {
  lockListingTarget.value = item;
  lockListingModalOpen.value = true;
}

function closeLockListingModal() {
  if (lockingListing.value) {
    return;
  }

  lockListingModalOpen.value = false;
  lockListingTarget.value = null;
}

async function handleConfirmLockListing() {
  if (!lockListingTarget.value) {
    return;
  }

  lockingListing.value = true;
  listingActionMessage.value = '';

  try {
    await listingService.lock(lockListingTarget.value.id);
    listingActionSuccess.value = true;
    listingActionMessage.value = 'Khóa tin đăng thành công.';
    lockListingModalOpen.value = false;
    lockListingTarget.value = null;
    if (activeTab.value === 'verifications') {
      await loadVerificationListings(verificationPagination.currentPage);
    } else {
      await loadMyListings(listingPagination.currentPage);
    }
  } catch (error) {
    listingActionSuccess.value = false;
    listingActionMessage.value = error?.response?.data?.message || 'Không thể khóa tin đăng. Vui lòng thử lại.';
  } finally {
    lockingListing.value = false;
  }
}

const unlistListingModalMessage = computed(() => {
  if (!unlistListingTarget.value) {
    return 'Bạn có chắc chắn muốn gỡ tin này không?';
  }

  return `Bạn có chắc chắn muốn gỡ tin "${unlistListingTarget.value.title}" không? Tin sẽ không còn hiển thị công khai.`;
});

function openUnlistListingModal(item) {
  unlistListingTarget.value = item;
  unlistListingModalOpen.value = true;
}

function closeUnlistListingModal() {
  if (unlistingListing.value) {
    return;
  }

  unlistListingModalOpen.value = false;
  unlistListingTarget.value = null;
}

async function handleConfirmUnlistListing() {
  if (!unlistListingTarget.value) {
    return;
  }

  unlistingListing.value = true;
  listingActionMessage.value = '';

  try {
    await listingService.unlist(unlistListingTarget.value.id);
    listingActionSuccess.value = true;
    listingActionMessage.value = 'Gỡ tin đăng thành công.';
    unlistListingModalOpen.value = false;
    unlistListingTarget.value = null;
    if (activeTab.value === 'verifications') {
      await loadVerificationListings(verificationPagination.currentPage);
    } else {
      await loadMyListings(listingPagination.currentPage);
    }
  } catch (error) {
    listingActionSuccess.value = false;
    listingActionMessage.value = error?.response?.data?.message || 'Không thể gỡ tin đăng. Vui lòng thử lại.';
  } finally {
    unlistingListing.value = false;
  }
}

function openListingsTab() {
  activeTab.value = 'listings';
  expandedSections.listings = true;
  if (!listingsLoaded.value) {
    loadMyListings(1);
  }
}

function openAppointmentsTab() {
  activeTab.value = 'appointments';
  expandedSections.appointments = true;
}

async function openFavoritesTab() {
  activeTab.value = 'favorites';
  if (!favoritesLoaded.value) {
    await loadFavorites();
  }
}

async function loadFavorites() {
  favoritesLoading.value = true;
  try {
    const response = await favoriteService.getFavorites();
    const data = response?.data?.data || [];
    await hydrateListingAddresses(data);
    favoriteListings.value = normalizeFavoriteListings(data);
    favoritesLoaded.value = true;
  } catch {
    favoriteListings.value = [];
  } finally {
    favoritesLoading.value = false;
  }
}

async function removeFavorite(item) {
  const previous = [...favoriteListings.value];
  favoriteListings.value = favoriteListings.value.filter((favorite) => favorite.id !== item.id);

  try {
    await favoriteService.toggle(item.id);
  } catch {
    favoriteListings.value = previous;
  }
}

// ── Recently Viewed ──
const viewedListings = ref([]);
const viewedLoading = ref(false);
const viewedLoaded = ref(false);
const viewedSearchQuery = ref('');
const viewedDemandType = ref('');
const viewedCurrentPage = ref(1);
const viewedPageSize = ref(10);

const viewedCounts = computed(() => {
  const all = viewedListings.value.length;
  const rent = viewedListings.value.filter(item => item.demandType === 'RENT').length;
  const sale = viewedListings.value.filter(item => item.demandType === 'SALE').length;
  return { all, rent, sale };
});

const filteredViewedListings = computed(() => {
  return viewedListings.value.filter((item) => {
    const matchType = !viewedDemandType.value || item.demandType === viewedDemandType.value;
    const matchQuery = !viewedSearchQuery.value || 
      item.title.toLowerCase().includes(viewedSearchQuery.value.toLowerCase()) ||
      (item.address && item.address.toLowerCase().includes(viewedSearchQuery.value.toLowerCase()));
    return matchType && matchQuery;
  });
});

const viewedPagination = computed(() => {
  const total = filteredViewedListings.value.length;
  const lastPage = Math.ceil(total / viewedPageSize.value) || 1;
  return { total, lastPage };
});

const paginatedViewedListings = computed(() => {
  const start = (viewedCurrentPage.value - 1) * viewedPageSize.value;
  const end = start + viewedPageSize.value;
  return filteredViewedListings.value.slice(start, end);
});

async function loadRecentlyViewed() {
  viewedLoading.value = true;
  try {
    const data = await recentlyViewedService.getRecentlyViewed(authStore.isAuthenticated);
    await hydrateListingAddresses(data);
    viewedListings.value = normalizeFavoriteListings(data);
    viewedLoaded.value = true;
  } catch {
    viewedListings.value = [];
  } finally {
    viewedLoading.value = false;
  }
}

function openRecentlyViewedTab() {
  activeTab.value = 'recently-viewed';
  if (!viewedLoaded.value) {
    loadRecentlyViewed();
  }
}

function openNotificationsTab() {
  activeTab.value = 'notifications';
}

async function toggleFavoriteFromViewed(item) {
  try {
    await favoriteService.toggle(item.id);
    await loadFavorites();
  } catch (err) {
    console.error(err);
  }
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

const isProfileFormUnchanged = computed(() => {
  const currentFullName = authStore.user?.full_name || '';
  const currentPhone = authStore.user?.phone || '';
  return profileForm.fullName.trim() === currentFullName && profileForm.phone.trim() === currentPhone;
});

onMounted(async () => {
  document.addEventListener('click', closeDropdown);
  await fetchListingOptions();

  // Luôn fetch fresh user để đảm bảo avatar_url mới nhất từ DB
  // (sessionStorage cache có thể không có avatar_url nếu đăng nhập trước khi tích hợp Cloudinary)
  try {
    await authStore.fetchUser();
  } catch {
    // Giữ nguyên data cũ nếu fetch thất bại
  }

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

  // Mở tab ban đầu
  const tab = route.query.tab;
  if (tab === 'listings') {
    openListingsTab();
  } else if (tab === 'verifications') {
    openVerificationsTab();
  } else if (tab === 'appointments') {
    openAppointmentsTab();
  } else if (tab === 'favorites') {
    openFavoritesTab();
  } else if (tab === 'recently-viewed') {
    openRecentlyViewedTab();
  } else if (tab === 'notifications') {
    openNotificationsTab();
  } else if (tab === 'password') {
    activeTab.value = 'password';
  }

  if (route.query.payment === 'success') {
    listingActionSuccess.value = true;
    listingActionMessage.value = 'Nâng cấp gói tin thành công.';
    router.replace({ query: { tab: 'listings' } }).catch(() => {});
  } else if (route.query.payment === 'failed') {
    listingActionSuccess.value = false;
    listingActionMessage.value = 'Thanh toán chưa thành công. Gói tin chưa được nâng cấp.';
    router.replace({ query: { tab: 'listings' } }).catch(() => {});
  }
});

onUnmounted(() => {
  document.removeEventListener('click', closeDropdown);
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
  if (isProfileFormUnchanged.value) {
    isEditing.value = false;
    return;
  }

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

const isPasswordFormValid = computed(() => {
  const current = passwordForm.currentPassword.trim();
  const pwd = passwordForm.newPassword.trim();
  const confirm = passwordForm.newPasswordConfirmation.trim();
  
  if (!current || !pwd || !confirm) return false;
  if (pwd.length < 8) return false;
  if (!/[a-z]/.test(pwd) || !/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd)) return false;
  if (current === pwd) return false;
  if (pwd !== confirm) return false;
  
  return true;
});

watch(
  passwordForm,
  (newVal) => {
    const current = newVal.currentPassword.trim();
    const pwd = newVal.newPassword.trim();
    const confirm = newVal.newPasswordConfirmation.trim();

    passwordSuccess.value = false;

    if (!pwd && !confirm) {
      passwordMessage.value = '';
      return;
    }

    if (pwd) {
      if (pwd.length < 8) {
        passwordMessage.value = 'Mật khẩu mới phải có ít nhất 8 ký tự.';
        return;
      }
      if (!/[a-z]/.test(pwd) || !/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd)) {
        passwordMessage.value = 'Mật khẩu phải chứa chữ hoa, chữ thường và chữ số.';
        return;
      }
      if (current && current === pwd) {
        passwordMessage.value = 'Mật khẩu mới không trùng với mật khẩu cũ.';
        return;
      }
    }

    if (confirm && pwd !== confirm) {
      passwordMessage.value = 'Xác nhận mật khẩu mới không khớp.';
      return;
    }

    passwordMessage.value = '';
  },
  { deep: true }
);

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
  // Trim passwords
  passwordForm.currentPassword = passwordForm.currentPassword.trim();
  passwordForm.newPassword = passwordForm.newPassword.trim();
  passwordForm.newPasswordConfirmation = passwordForm.newPasswordConfirmation.trim();

  if (!isPasswordFormValid.value) return;

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

<style scoped>
.col-sticky-id,
.col-sticky-verification,
.col-sticky-status,
.col-sticky-actions {
  position: sticky !important;
  z-index: 10;
}

.col-sticky-verification,
.col-sticky-status,
.col-sticky-actions {
  box-shadow: inset 1px 0 0 0 #e2e8f0;
}

.col-sticky-id {
  left: 0;
  box-shadow: inset -1px 0 0 0 #e2e8f0, 3px 0 5px -2px rgba(0, 0, 0, 0.08);
}

thead tr th.col-sticky-id,
thead tr th.col-sticky-verification,
thead tr th.col-sticky-status,
thead tr th.col-sticky-actions {
  background-color: #f8fafc !important;
  z-index: 20;
}

tbody tr td.col-sticky-id,
tbody tr td.col-sticky-verification,
tbody tr td.col-sticky-status,
tbody tr td.col-sticky-actions {
  background-color: #ffffff;
  transition: background-color 0.15s ease;
}

tbody tr:hover td.col-sticky-id,
tbody tr:hover td.col-sticky-verification,
tbody tr:hover td.col-sticky-status,
tbody tr:hover td.col-sticky-actions {
  background-color: #f0f9ff !important;
}

.col-sticky-id {
  width: 55px;
  min-width: 55px;
  max-width: 55px;
}

.col-sticky-actions {
  right: 0px;
  width: 48px;
  min-width: 48px;
  max-width: 48px;
}

.col-sticky-status {
  right: 48px;
  width: 120px;
  min-width: 120px;
  max-width: 120px;
}

.col-sticky-verification {
  right: 168px;
  width: 120px;
  min-width: 120px;
  max-width: 120px;
  box-shadow: inset 1px 0 0 0 #e2e8f0, -3px 0 5px -2px rgba(0, 0, 0, 0.08);
}
thead tr th.col-sticky-verification {
  box-shadow: inset 1px 0 0 0 #e2e8f0, -3px 0 5px -2px rgba(0, 0, 0, 0.08);
}
</style>


