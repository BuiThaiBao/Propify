<template>
  <section class="mx-auto w-full max-w-[1240px] px-4 lg:px-6 py-10 space-y-12">

    <!-- Tin mua bán bất động sản -->
    <div>
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
          <span class="text-blue-500">🏠</span> Tin mua bán bất động sản
        </h2>
        <router-link to="/listings?type=SALE" class="text-sm font-medium text-blue-500 hover:underline">Xem tất cả →</router-link>
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
          :location="item.property?.address_detail || ''"
          :price="formatPrice(item.property?.price)"
          :unit="''"
          :area="item.property?.area || 0"
          :beds="item.property?.bedrooms || 0"
          :baths="item.property?.bathrooms || 0"
          :rating="null"
          :timeAgo="timeAgo(item.submitted_at)"
          :views="0"
        />
      </div>
    </div>

    <!-- Tin cho thuê bất động sản -->
    <div>
      <div class="flex items-center justify-between mb-5">
        <h2 class="text-xl font-bold text-gray-900 flex items-center gap-2">
          <span class="text-emerald-500">🏢</span> Tin cho thuê bất động sản
        </h2>
        <router-link to="/listings?type=RENT" class="text-sm font-medium text-blue-500 hover:underline">Xem tất cả →</router-link>
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
          :location="item.property?.address_detail || ''"
          :price="formatPrice(item.property?.price)"
          :unit="'/tháng'"
          :area="item.property?.area || 0"
          :beds="item.property?.bedrooms || 0"
          :baths="item.property?.bathrooms || 0"
          :rating="null"
          :timeAgo="timeAgo(item.submitted_at)"
          :views="0"
        />
      </div>
    </div>

  </section>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import ListingCardHome from '@/components/shared/ListingCardHome.vue';
import listingService from '@/services/listingService';

const saleListings = ref([]);
const rentListings = ref([]);
const saleLoading = ref(true);
const rentLoading = ref(true);

onMounted(async () => {
  fetchSale();
  fetchRent();
});

async function fetchSale() {
  try {
    const res = await listingService.getPublicListings({ demand_type: 'SALE', per_page: 6 });
    saleListings.value = res.data.data || [];
  } catch (e) {
    console.error('Failed to fetch sale listings', e);
  } finally {
    saleLoading.value = false;
  }
}

async function fetchRent() {
  try {
    const res = await listingService.getPublicListings({ demand_type: 'RENT', per_page: 6 });
    rentListings.value = res.data.data || [];
  } catch (e) {
    console.error('Failed to fetch rent listings', e);
  } finally {
    rentLoading.value = false;
  }
}

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
