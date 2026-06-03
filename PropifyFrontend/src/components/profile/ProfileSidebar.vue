<template>
  <aside class="w-[260px] shrink-0 max-md:w-full">
    <div class="text-center p-6 bg-white rounded-xl shadow-sm mb-4">
      <div
        class="w-[72px] h-[72px] rounded-full overflow-hidden bg-gradient-to-br from-sky-100 to-sky-200 text-sky-500 flex items-center justify-center mx-auto mb-3"
      >
        <img
          v-if="user?.avatar_url"
          :src="user.avatar_url"
          alt="Avatar"
          class="w-full h-full object-cover"
        />
        <svg v-else xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
      </div>
      <h3 class="text-base font-semibold text-slate-800 mb-0.5">{{ user?.full_name || 'Người dùng' }}</h3>
      <p class="text-xs text-slate-400">{{ user?.email }}</p>
    </div>

    <nav class="bg-white rounded-xl shadow-sm overflow-hidden max-md:flex max-md:flex-wrap">
      <button
        :class="['flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-left transition-all max-md:flex-1 max-md:min-w-[120px] max-md:justify-center',
          activeTab === 'profile'
            ? 'bg-gradient-to-r from-sky-100 to-sky-50 text-sky-500 font-semibold border-l-[3px] border-sky-500 max-md:border-l-0 max-md:border-b-[3px]'
            : 'text-slate-600 hover:bg-slate-50 hover:text-sky-500']"
        @click="$emit('select-tab', 'profile')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/>
        </svg>
        Thông tin tài khoản
      </button>

      <button class="flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-sky-500 transition-all text-left" @click="$emit('toggle-section', 'listings')">
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
          @click="$emit('open-listings')"
        >
          Danh sách tin đăng
        </button>
        <button
          class="w-full text-left pl-12 pr-5 py-2.5 text-[0.82rem] transition-all"
          :class="activeTab === 'verifications' ? 'bg-sky-100 text-sky-600 font-semibold' : 'text-slate-500 hover:bg-slate-200 hover:text-sky-500'"
          @click="$emit('open-verifications')"
        >
          Danh sách xác thực BĐS
        </button>
      </div>

      <button class="flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-sky-500 transition-all text-left" @click="$emit('toggle-section', 'appointments')">
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
          @click="$emit('open-appointments')"
        >
          Đặt lịch xem nhà
        </button>
      </div>

      <button
        :class="['flex items-center gap-2.5 w-full px-5 py-3.5 text-sm transition-all text-left',
          activeTab === 'favorites'
            ? 'bg-gradient-to-r from-sky-100 to-sky-50 text-sky-500 font-semibold border-l-[3px] border-sky-500'
            : 'text-slate-600 hover:bg-slate-50 hover:text-sky-500']"
        @click="$emit('open-favorites')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
        </svg>
        Tin đăng yêu thích
      </button>

      <button
        :class="['flex items-center gap-2.5 w-full px-5 py-3.5 text-sm transition-all text-left',
          activeTab === 'recently-viewed'
            ? 'bg-gradient-to-r from-sky-100 to-sky-50 text-sky-500 font-semibold border-l-[3px] border-sky-500'
            : 'text-slate-600 hover:bg-slate-50 hover:text-sky-500']"
        @click="$emit('open-recently-viewed')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/>
        </svg>
        Tin đã xem
      </button>

      <button
        :class="['flex items-center gap-2.5 w-full px-5 py-3.5 text-sm transition-all text-left',
          activeTab === 'notifications'
            ? 'bg-gradient-to-r from-sky-100 to-sky-50 text-sky-500 font-semibold border-l-[3px] border-sky-500'
            : 'text-slate-600 hover:bg-slate-50 hover:text-sky-500']"
        @click="$emit('open-notifications')"
      >
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <path d="M15 17h5l-1.4-1.4A2 2 0 0 1 18 14.2V11a6 6 0 1 0-12 0v3.2a2 2 0 0 1-.6 1.4L4 17h5"/>
          <path d="M9 17a3 3 0 0 0 6 0"/>
        </svg>
        Thông báo
      </button>

      <button class="flex items-center gap-2.5 w-full px-5 py-3.5 text-sm text-slate-600 hover:bg-slate-50 hover:text-sky-500 transition-all text-left">
        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
        </svg>
        Lịch sử giao dịch
      </button>
    </nav>
  </aside>
</template>

<script setup>
defineProps({
  user: {
    type: Object,
    default: null,
  },
  activeTab: {
    type: String,
    required: true,
  },
  expandedSections: {
    type: Object,
    required: true,
  },
});

defineEmits([
  'select-tab',
  'toggle-section',
  'open-listings',
  'open-verifications',
  'open-appointments',
  'open-favorites',
  'open-recently-viewed',
  'open-notifications',
]);
</script>
