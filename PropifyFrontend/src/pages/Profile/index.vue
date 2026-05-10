<template>
  <div class="max-w-[1400px] mx-auto mt-20 mb-8 px-4 flex gap-8 min-h-[calc(100vh-200px)] max-md:flex-col">
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
          <button
            class="w-full text-left pl-12 pr-5 py-2.5 text-[0.82rem] transition-all"
            :class="activeTab === 'appointments' ? 'bg-sky-100 text-sky-600 font-semibold' : 'text-slate-500 hover:bg-slate-200 hover:text-sky-500'"
            @click="openAppointmentsTab"
          >
            Đặt lịch xem nhà
          </button>
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
                accept="image/jpeg,image/png,image/webp"
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
                <button type="submit" class="px-6 py-2.5 rounded-lg text-sm font-semibold bg-gradient-to-br from-sky-500 to-sky-600 text-white hover:from-sky-600 hover:to-sky-700 hover:shadow-lg hover:shadow-sky-500/30 transition-all disabled:opacity-60 disabled:cursor-not-allowed" :disabled="profileLoading">
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
                <th class="px-3 py-3 whitespace-nowrap">ID</th>
                <th class="px-3 py-3 whitespace-nowrap">Ảnh</th>
                <th class="px-3 py-3 whitespace-nowrap">Mã tin đăng</th>
                <th class="px-3 py-3 min-w-[200px]">Tin đăng</th>
                <th class="px-3 py-3 min-w-[200px]">Địa chỉ</th>
                <th class="px-3 py-3 whitespace-nowrap">Giá</th>
                <th class="px-3 py-3 text-center whitespace-nowrap">Hiển thị</th>
                <th class="px-3 py-3 whitespace-nowrap">Trạng thái</th>
                <th class="px-3 py-3 w-10 text-center"></th>
              </tr>
            </thead>
            <tbody>
              <tr v-if="listingsLoading">
                <td class="px-3 py-6 text-center text-slate-400" colspan="9">Đang tải dữ liệu...</td>
              </tr>
              <tr v-else-if="myListings.length === 0">
                <td class="px-3 py-6 text-center text-slate-400" colspan="9">Bạn chưa có tin đăng nào.</td>
              </tr>
              <tr v-for="item in myListings" :key="item.id" class="border-t border-slate-100 cursor-pointer hover:bg-sky-50/50 transition group" @click="router.push('/listings/' + item.id)">
                <td class="px-3 py-3 font-medium text-sky-600 group-hover:underline whitespace-nowrap">{{ item.id }}</td>
                <td class="px-3 py-3 whitespace-nowrap">
                  <img v-if="item.thumbnail" :src="item.thumbnail" alt="thumb" class="h-12 w-14 rounded-md object-cover" />
                  <div v-else class="h-12 w-14 rounded-md bg-slate-100"></div>
                </td>
                <td class="px-3 py-3 text-slate-500 whitespace-nowrap">{{ item.code }}</td>
                <td class="px-3 py-3 font-semibold text-slate-700 group-hover:text-sky-600">{{ item.title }}</td>
                <td class="px-3 py-3 text-slate-500">{{ item.address }}</td>
                <td class="px-3 py-3 font-semibold text-slate-700 whitespace-nowrap">{{ item.price }}</td>
                <td class="px-3 py-3 whitespace-nowrap">
                  <div class="flex items-center justify-center gap-1.5 text-slate-500">
                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                    <span class="font-medium text-sm">{{ item.views || 1000 }}</span>
                  </div>
                </td>
                <td class="px-3 py-3 whitespace-nowrap">
                  <span :class="['rounded-full px-2 py-1 text-xs font-medium', statusBadgeClass(item.status)]">
                    {{ statusLabel(item.status) }}
                  </span>
                </td>
                <td class="px-3 py-3 relative">
                  <button @click.stop="toggleDropdown(item.id, $event)" class="text-slate-400 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition-colors flex items-center justify-center mx-auto">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="1"/><circle cx="19" cy="12" r="1"/><circle cx="5" cy="12" r="1"/></svg>
                  </button>
                  
                  <Teleport to="body">
                    <div v-if="openDropdownId === item.id" :style="{ top: dropdownStyle.top, left: dropdownStyle.left }" class="absolute w-[200px] bg-white border border-slate-100 shadow-xl rounded-xl py-2 z-[9999] text-left">
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('edit', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Thông tin chi tiết
                      </button>
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('upgrade', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                        Nâng cấp gói tin
                      </button>
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('publish', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        Đăng tin
                      </button>
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('unpublish', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                        Gỡ tin đăng
                      </button>
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('lock', item)">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#334155" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                        Khóa tin đăng
                      </button>
                      <button class="w-full text-left px-4 py-2.5 text-[0.85rem] text-slate-700 hover:bg-slate-50 flex items-center gap-3 transition-colors" @click.stop="handleDropdownAction('unlock', item)">
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
          <p>Tổng cộng {{ listingPagination.total }} tin</p>
          <div class="flex items-center gap-2">
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-50"
              :disabled="listingPagination.currentPage <= 1 || listingsLoading"
              @click="loadMyListings(listingPagination.currentPage - 1)"
            >
              Trước
            </button>
            <span>Trang {{ listingPagination.currentPage }}/{{ listingPagination.lastPage }}</span>
            <button
              type="button"
              class="rounded-lg border border-slate-200 px-3 py-1.5 disabled:opacity-50"
              :disabled="listingPagination.currentPage >= listingPagination.lastPage || listingsLoading"
              @click="loadMyListings(listingPagination.currentPage + 1)"
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

      <!-- ===== APPOINTMENTS TAB ===== -->
      <section v-if="activeTab === 'appointments'" class="bg-white rounded-xl shadow-sm p-8">
        <AppointmentManagement />
      </section>
    </main>

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
import userService from '@/services/userService';
import cloudinaryService from '@/services/cloudinaryService';
import listingService from '@/services/listingService';
import PackageUpgradeModal from '@/components/shared/PackageUpgradeModal.vue';
import ConfirmActionModal from '@/components/shared/ConfirmActionModal.vue';
import AppointmentManagement from '@/components/AppointmentManagement.vue';

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
const validTabs = ['profile', 'listings', 'appointments', 'password'];
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
function handleDropdownAction(action, item) {
  closeDropdown();
  if (action === 'edit') {
    router.push('/listings/' + item.id + '/edit');
  } else if (action === 'upgrade') {
    if (item.status === 'ACTIVE') openUpgradeModal(item);
    else alert('Chỉ có thể nâng cấp tin đang đăng.');
  } else if (action === 'lock') {
    if (item.status === 'ACTIVE') openLockListingModal(item);
    else alert('Chỉ có thể khóa tin đang đăng.');
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

const statusCounts = reactive({
  ALL: 0,
  PENDING: 0,
  ACTIVE: 0,
  DRAFT: 0,
  REJECTED: 0,
  LOCKED: 0,
});

const statusTabs = computed(() => [
  { label: 'Tất cả', value: '', colorClass: '', count: statusCounts.ALL },
  { label: 'Chờ duyệt', value: 'PENDING', colorClass: 'bg-orange-500', count: statusCounts.PENDING },
  { label: 'Tin đang đăng', value: 'ACTIVE', colorClass: 'bg-emerald-500', count: statusCounts.ACTIVE },
  { label: 'Tin nháp', value: 'DRAFT', colorClass: 'bg-slate-400', count: statusCounts.DRAFT },
  { label: 'Từ chối', value: 'REJECTED', colorClass: 'bg-rose-500', count: statusCounts.REJECTED },
  { label: 'Tin bị khóa', value: 'LOCKED', colorClass: 'bg-red-600', count: statusCounts.LOCKED },
]);

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
const listingActionMessage = ref('');
const listingActionSuccess = ref(false);

// ── Phone required from đăng tin ──
const requirePhone = ref(false);
const phoneInputRef = ref(null);

// ── Phone đã có → không cho sửa ──
const phoneAlreadySet = computed(() => !!authStore.user?.phone);

// ── Sidebar expand/collapse ──
const expandedSections = reactive({
  listings: route.query.tab === 'listings',
  appointments: route.query.tab === 'appointments',
});

function toggleSection(key) {
  expandedSections[key] = !expandedSections[key];
}

function statusLabel(status) {
  const map = {
    DRAFT: 'Tin nháp',
    PENDING: 'Chờ duyệt',
    ACTIVE: 'Đang đăng',
    EXPIRED: 'Hết hạn',
    REJECTED: 'Từ chối',
    LOCKED: 'Tin bị khóa',
  };
  return map[status] || status;
}

function statusBadgeClass(status) {
  const map = {
    DRAFT: 'bg-slate-100 text-slate-600',
    PENDING: 'bg-amber-100 text-amber-700',
    ACTIVE: 'bg-emerald-100 text-emerald-700',
    EXPIRED: 'bg-slate-200 text-slate-600',
    REJECTED: 'bg-rose-100 text-rose-700',
    LOCKED: 'bg-red-100 text-red-700',
  };
  return map[status] || 'bg-slate-100 text-slate-600';
}

function toListingCode(id) {
  return `LH-${String(id).padStart(8, '0')}`;
}

function formatCurrency(value) {
  const number = Number(value || 0);
  if (!Number.isFinite(number) || number <= 0) return 'Thỏa thuận';
  return number.toLocaleString('vi-VN');
}

function buildAddress(property) {
  return property?.address_detail || '';
}

function normalizeListings(items) {
  return items.map((item) => {
    const thumbnail = item?.images?.find((img) => img?.is_thumbnail)?.url || item?.images?.[0]?.url || '';
    return {
      id: item.id,
      code: toListingCode(item.id),
      title: item.title || '(Không tiêu đề)',
      thumbnail,
      address: buildAddress(item.property),
      price: formatCurrency(item?.property?.price),
      status: item.status,
      package: item.package || null,
      views: item.view_count || 0,
    };
  });
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
  loadMyListings(listingPagination.currentPage);
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
    await loadMyListings(listingPagination.currentPage);
  } catch (error) {
    listingActionSuccess.value = false;
    listingActionMessage.value = error?.response?.data?.message || 'Không thể khóa tin đăng. Vui lòng thử lại.';
  } finally {
    lockingListing.value = false;
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

// ── Profile form ──
const isEditing = ref(false);
const profileLoading = ref(false);
const profileMessage = ref('');
const profileSuccess = ref(false);
const profileForm = reactive({
  fullName: '',
  phone: '',
});

onMounted(async () => {
  document.addEventListener('click', closeDropdown);

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
  } else if (tab === 'appointments') {
    openAppointmentsTab();
  } else if (tab === 'password') {
    activeTab.value = 'password';
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
