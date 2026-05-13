<template>
  <section class="mx-auto w-full max-w-[1240px] px-4 lg:px-6 py-10 space-y-12">

    <!-- Tin mua bán bất động sản -->
    <div>
      <p class="text-[0.75rem] font-bold text-[#0DA2E7] uppercase tracking-wider mb-1">Mua bán</p>
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-[1.75rem] font-bold text-slate-800 leading-tight">
          Tin mua bán bất động sản
        </h2>
        <router-link to="/listings?type=SALE" class="px-5 py-2.5 rounded-full border border-slate-200 text-[0.85rem] font-medium text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-2">
          Xem tất cả
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
          </svg>
        </router-link>
      </div>

      <div v-if="saleLoading" class="flex justify-center py-10">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
      </div>
      <div v-else-if="saleListings.length === 0" class="text-center text-gray-400 py-8">
        Chưa có tin mua bán nào.
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <ListingCardHome
          v-for="item in saleListings"
          :key="item.id"
          :to="'/listings/' + item.id"
          :image="getThumb(item)"
          :verified="item.is_verified"
          :type="propertyTypeLabel(item.property?.type)"
          :title="item.title"
          :location="item.property?.full_address || item.property?.address_detail || ''"
          :price="formatPrice(item.property?.price)"
          :unit="''"
          :area="item.property?.area || 0"
          :beds="item.property?.bedrooms || 0"
          :baths="item.property?.bathrooms || 0"
          :rating="null"
          :timeAgo="timeAgo(item.submitted_at)"
          :package="item.package"
        />
      </div>
    </div>

    <!-- Tin cho thuê bất động sản -->
    <div>
      <p class="text-[0.75rem] font-bold text-emerald-500 uppercase tracking-wider mb-1 mt-8">Cho thuê</p>
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-[1.75rem] font-bold text-slate-800 leading-tight">
          Tin cho thuê bất động sản
        </h2>
        <router-link to="/listings?type=RENT" class="px-5 py-2.5 rounded-full border border-slate-200 text-[0.85rem] font-medium text-slate-700 hover:bg-slate-50 transition-colors flex items-center gap-2">
          Xem tất cả
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M5 12h14"/><path d="m12 5 7 7-7 7"/>
          </svg>
        </router-link>
      </div>

      <div v-if="rentLoading" class="flex justify-center py-10">
        <div class="h-8 w-8 animate-spin rounded-full border-4 border-emerald-500 border-t-transparent"></div>
      </div>
      <div v-else-if="rentListings.length === 0" class="text-center text-gray-400 py-8">
        Chưa có tin cho thuê nào.
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <ListingCardHome
          v-for="item in rentListings"
          :key="item.id"
          :to="'/listings/' + item.id"
          :image="getThumb(item)"
          :verified="item.is_verified"
          :type="propertyTypeLabel(item.property?.type)"
          :title="item.title"
          :location="item.property?.full_address || item.property?.address_detail || ''"
          :price="formatPrice(item.property?.price)"
          :unit="'/tháng'"
          :area="item.property?.area || 0"
          :beds="item.property?.bedrooms || 0"
          :baths="item.property?.bathrooms || 0"
          :rating="null"
          :timeAgo="timeAgo(item.submitted_at)"
          :package="item.package"
        />
      </div>
    </div>

    <!-- Tin xem gần đây -->
    <div class="pt-8">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#0DA2E7" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>
          </svg>
          Tin xem gần đây
        </h2>
      </div>

      <div v-if="saleListings.length === 0" class="text-center text-gray-400 py-8">
        Bạn chưa xem tin nào gần đây.
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <ListingCardHome
          v-for="item in saleListings.slice(0,3)"
          :key="'viewed-' + item.id"
          :to="'/listings/' + item.id"
          :image="getThumb(item)"
          :verified="item.is_verified"
          :type="propertyTypeLabel(item.property?.type)"
          :title="item.title"
          :location="item.property?.full_address || item.property?.address_detail || ''"
          :price="formatPrice(item.property?.price)"
          :unit="''"
          :area="item.property?.area || 0"
          :beds="item.property?.bedrooms || 0"
          :baths="item.property?.bathrooms || 0"
          :rating="null"
          :timeAgo="timeAgo(item.submitted_at)"
          :package="item.package"
        />
      </div>
    </div>

    <!-- Tin đăng yêu thích -->
    <div class="pt-8">
      <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
          <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#EF4444" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="fill-[#EF4444]">
            <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/>
          </svg>
          Tin đăng yêu thích
        </h2>
      </div>

      <div v-if="rentListings.length === 0" class="text-center text-gray-400 py-8">
        Bạn chưa lưu tin yêu thích nào.
      </div>
      <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <ListingCardHome
          v-for="item in rentListings.slice(0,3)"
          :key="'fav-' + item.id"
          :to="'/listings/' + item.id"
          :image="getThumb(item)"
          :verified="item.is_verified"
          :type="propertyTypeLabel(item.property?.type)"
          :title="item.title"
          :location="item.property?.full_address || item.property?.address_detail || ''"
          :price="formatPrice(item.property?.price)"
          :unit="'/tháng'"
          :area="item.property?.area || 0"
          :beds="item.property?.bedrooms || 0"
          :baths="item.property?.bathrooms || 0"
          :rating="null"
          :timeAgo="timeAgo(item.submitted_at)"
          :package="item.package"
        />
      </div>
    </div>

  </section>
</template>

<script setup>
import { onMounted } from 'vue';
import ListingCardHome from '@/components/shared/ListingCardHome.vue';
import { useHomeListings } from '@/composables/useHomeListings';

const { saleListings, rentListings, saleLoading, rentLoading, init } = useHomeListings();

onMounted(() => {
  init();
});

function getThumb(item) {
  if (item.images && item.images.length > 0) {
    const thumb = item.images.find(i => i.is_thumbnail);
    return thumb ? thumb.url : item.images[0].url;
  }
  return 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=60';
}

function formatPrice(value) {
  if (!value || value <= 0) return 'Thỏa thuận';
  if (value >= 1000000000) return (value / 1000000000).toLocaleString('vi-VN') + ' tỷ';
  if (value >= 1000000) return (value / 1000000).toLocaleString('vi-VN') + ' triệu';
  return value.toLocaleString('vi-VN') + ' đ';
}

function propertyTypeLabel(type) {
  const map = {
    APARTMENT: 'Căn hộ chung cư',
    PRIVATE_HOUSE: 'Nhà riêng',
    STREET_HOUSE: 'Nhà mặt phố',
    VILLA_TOWNHOUSE: 'Biệt thự liền kề',
    SHOPHOUSE: 'Shophouse',
    RENT_ROOM: 'Phòng trọ',
    OFFICE: 'Văn phòng',
  };
  return map[type] || type || 'BĐS';
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
</script>
