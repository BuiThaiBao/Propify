<template>
  <RentLayout>
    <TopSearchBar
      v-model="searchKeyword"
      :suggestions="rentSuggestions"
      @search="onSearch"
      @select-suggestion="onSearch"
    />

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">

        <!-- Left Column: Main Content -->
        <div class="lg:col-span-8">
          <!-- Header section -->
          <div class="flex justify-between items-end mb-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 flex items-center gap-2 mb-1">
                <KeyRound class="w-6 h-6 text-blue-500" />
                Cho thuê bất động sản
              </h1>
              <p class="text-sm text-gray-500">
                Hiện có <span class="font-bold text-blue-600">{{ rentTotal }}</span> bất động sản
              </p>
            </div>

            <!-- Sort Dropdown -->
            <button class="flex items-center gap-2 bg-white border border-gray-200 px-4 py-2 rounded-xl text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors shadow-sm">
              Mới nhất
              <ChevronDown class="w-4 h-4 text-gray-400" />
            </button>
          </div>

          <!-- Listings -->
          <div class="flex flex-col gap-6">
            <div v-if="rentLoading" class="flex justify-center py-8">
              <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
            </div>

            <div v-else-if="rentListings.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-400">
              Chưa có tin cho thuê nào.
            </div>

            <RentCard
              v-for="item in rentListings"
              :key="item.id"
              :to="'/listings/' + item.id"
              :verified="isVerified(item)"
              :title="item.title"
              :type="propertyTypeLabel(item.property?.type)"
              :price="formatPrice(item.property?.price)"
              :unit="'/tháng'"
              :area="item.property?.area || 0"
              :beds="item.property?.bedrooms || 0"
              :baths="item.property?.bathrooms || 0"
              :location="item.property?.full_address || item.property?.address_detail || ''"
              :author="getAuthor(item)"
              :image="getThumb(item)"
              :package="item.package"
              :rating="null"
              :timeAgo="timeAgo(item.submitted_at)"
              :views="item.views ?? 0"
            />
          </div>
        </div>

        <!-- Right Column: Sidebar Filters -->
        <div class="lg:col-span-4">
          <MapWidget />

          <SidebarWidget title="Người đăng" :icon="User">
            <TabFilterGroup
              v-model="posterType"
              :tabs="[
                { label: 'Tất cả', value: 'all' },
                { label: 'Chủ nhà', value: 'owner' },
                { label: 'Môi giới', value: 'broker' }
              ]"
            />
          </SidebarWidget>

          <SidebarWidget title="Khoảng giá" :icon="DollarSign">
            <RadioFilterGroup
              v-model="priceRange"
              name="rent-price"
              :usePillSkin="true"
              :options="[
                { label: 'Tất cả', value: 'all' },
                { label: 'Dưới 5 triệu', value: 'under_5m' },
                { label: '5 - 10 triệu', value: '5_10m' },
                { label: '10 - 20 triệu', value: '10_20m' },
                { label: '20 - 50 triệu', value: '20_50m' }
              ]"
              :showExpand="true"
            />
          </SidebarWidget>

          <SidebarWidget title="Diện tích" :icon="Ruler">
            <RadioFilterGroup
              v-model="areaRange"
              name="rent-area"
              :usePillSkin="true"
              :options="[
                { label: 'Tất cả', value: 'all' },
                { label: 'Dưới 30 m²', value: 'under_30' },
                { label: '30 - 50 m²', value: '30_50' },
                { label: '50 - 80 m²', value: '50_80' },
                { label: '80 - 100 m²', value: '80_100' }
              ]"
              :showExpand="true"
            />
          </SidebarWidget>
        </div>

      </div>
    </div>
  </RentLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { KeyRound, ChevronDown, User, DollarSign, Ruler } from 'lucide-vue-next';
import RentLayout from '@/layouts/RentLayout.vue';
import TopSearchBar from '@/components/shared/TopSearchBar.vue';
import RentCard from '@/components/shared/RentCard.vue';
import SidebarWidget from '@/components/shared/SidebarWidget.vue';
import MapWidget from '@/components/shared/MapWidget.vue';
import TabFilterGroup from '@/components/shared/TabFilterGroup.vue';
import RadioFilterGroup from '@/components/shared/RadioFilterGroup.vue';
import { useRentListings } from '@/composables/useRentListings';

const posterType = ref('all');
const priceRange = ref('all');
const areaRange = ref('all');
const { rentListings, rentLoading, rentTotal, searchKeyword, rentSuggestions, init, onSearch } = useRentListings();

onMounted(() => {
  init();
});

function getThumb(item) {
  if (item.images && item.images.length > 0) {
    const thumb = item.images.find((image) => image.is_thumbnail);
    return thumb ? thumb.url : item.images[0].url;
  }
  return 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&auto=format&fit=crop&q=60';
}

function getAuthor(item) {
  return {
    name: item.property?.contact_name || item.owner?.full_name || 'Chủ nhà',
    role: item.property?.poster_type === 'OWNER' ? 'Chủ nhà' : 'Môi giới',
  };
}

function formatPrice(value) {
  const num = Number(value || 0);
  if (!num || num <= 0) return 'Thỏa thuận';
  if (num >= 1000000000) return `${(num / 1000000000).toLocaleString('vi-VN')} tỷ`;
  if (num >= 1000000) return `${(num / 1000000).toLocaleString('vi-VN')} triệu`;
  return `${num.toLocaleString('vi-VN')} đ`;
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
  const date = new Date(dateStr);
  const diffMs = now - date;
  const diffMins = Math.floor(diffMs / 60000);
  if (diffMins < 60) return `${diffMins} phút trước`;
  const diffHours = Math.floor(diffMins / 60);
  if (diffHours < 24) return `${diffHours} giờ trước`;
  const diffDays = Math.floor(diffHours / 24);
  if (diffDays < 30) return `${diffDays} ngày trước`;
  return date.toLocaleDateString('vi-VN');
}

function isVerified(item) {
  const value = item?.is_verified;
  return value === true || Number(value) === 1;
}

</script>
