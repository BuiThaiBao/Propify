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
          <div class="relative flex flex-col gap-6">
            <!-- Loading overlay — shows on top of existing listings -->
            <div v-if="rentLoading && rentListings.length > 0" class="absolute inset-0 z-10 flex items-start justify-center pt-16 bg-white/60 backdrop-blur-[1px] rounded-xl">
              <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
            </div>

            <div v-if="rentLoading && rentListings.length === 0" class="flex justify-center py-8">
              <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
            </div>

            <div v-else-if="!rentLoading && rentListings.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-400">
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
              :listing-id="item.id"
              :is-favorite="isFavorite(item)"
              :rating="null"
              :timeAgo="timeAgo(item.submitted_at)"
              :views="item.views ?? 0"
              @toggle-favorite="toggleFavorite(item)"
            />
          </div>

          <!-- Pagination — always visible when there are multiple pages -->
          <nav v-if="lastPage > 1" class="mt-8 flex items-center justify-center gap-1" aria-label="Phân trang">
            <!-- Prev -->
            <button
              :disabled="currentPage <= 1 || rentLoading"
              class="inline-flex h-9 min-w-9 cursor-pointer items-center justify-center rounded-[10px] border border-slate-200 bg-white px-2 text-sm font-medium text-slate-600 transition-all hover:not-disabled:border-slate-300 hover:not-disabled:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
              @click="goToPage(currentPage - 1)"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <!-- Page numbers -->
            <template v-for="page in visiblePages" :key="page">
              <span v-if="page === '...'" class="px-2 text-gray-400 text-sm select-none">…</span>
              <button
                v-else
                :disabled="rentLoading"
                class="inline-flex h-9 min-w-9 cursor-pointer items-center justify-center rounded-[10px] border border-slate-200 bg-white px-2 text-sm font-medium text-slate-600 transition-all hover:not-disabled:border-slate-300 hover:not-disabled:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
                :class="page === currentPage ? '!border-blue-500 !bg-blue-500 !text-white shadow-lg shadow-blue-500/30' : ''"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>
            </template>

            <!-- Next -->
            <button
              :disabled="currentPage >= lastPage || rentLoading"
              class="inline-flex h-9 min-w-9 cursor-pointer items-center justify-center rounded-[10px] border border-slate-200 bg-white px-2 text-sm font-medium text-slate-600 transition-all hover:not-disabled:border-slate-300 hover:not-disabled:bg-slate-100 disabled:cursor-not-allowed disabled:opacity-40"
              @click="goToPage(currentPage + 1)"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </nav>
        </div>

        <!-- Right Column: Sidebar Filters -->
        <div class="lg:col-span-4 flex flex-col gap-4">
          <!-- Tìm kiếm theo bản đồ -->
          <div @click="isMapOpen = true" class="bg-sky-50 border border-sky-100 rounded-2xl p-5 flex flex-col items-center justify-center cursor-pointer hover:bg-sky-100 transition shadow-sm text-center">
            <div class="bg-sky-200/50 p-2.5 rounded-full mb-2">
              <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-sky-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21"/><line x1="9" y1="3" x2="9" y2="18"/><line x1="15" y1="6" x2="15" y2="21"/>
              </svg>
            </div>
            <p class="text-sm font-bold text-sky-600">Tìm kiếm theo bản đồ</p>
            <p class="text-[11px] text-slate-400 mt-0.5">Xem vị trí trên bản đồ</p>
          </div>

          <!-- Người đăng -->
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-3">
              <User class="w-4 h-4 text-sky-500" />
              Người đăng
            </h3>
            <div class="flex gap-2">
              <button
                type="button"
                @click="posterType = ''"
                class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-full transition-all duration-200 shadow-sm border border-transparent"
                :class="posterType === '' ? 'bg-[#0DA2E7] text-white' : 'bg-[#F1F3F5] text-slate-600 hover:bg-slate-200'"
              >
                Tất cả
              </button>
              <button
                type="button"
                @click="posterType = 'OWNER'"
                class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-full transition-all duration-200 shadow-sm border border-transparent"
                :class="posterType === 'OWNER' ? 'bg-[#0DA2E7] text-white' : 'bg-[#F1F3F5] text-slate-600 hover:bg-slate-200'"
              >
                Chủ nhà
              </button>
              <button
                type="button"
                @click="posterType = 'BROKER'"
                class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-full transition-all duration-200 shadow-sm border border-transparent"
                :class="posterType === 'BROKER' ? 'bg-[#0DA2E7] text-white' : 'bg-[#F1F3F5] text-slate-600 hover:bg-slate-200'"
              >
                Môi giới
              </button>
            </div>
          </div>

          <!-- Khoảng giá -->
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-3">
              <DollarSign class="w-4 h-4 text-sky-500" />
              Khoảng giá
            </h3>
            <div class="flex flex-col gap-1.5">
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedPricePreset === 'all' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedPricePreset" value="all" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedPricePreset === 'all'}">Tất cả</span>
              </label>
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedPricePreset === 'under_5m' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedPricePreset" value="under_5m" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedPricePreset === 'under_5m'}">Dưới 5 triệu</span>
              </label>
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedPricePreset === '5_10m' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedPricePreset" value="5_10m" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedPricePreset === '5_10m'}">5 - 10 triệu</span>
              </label>
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedPricePreset === '10_20m' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedPricePreset" value="10_20m" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedPricePreset === '10_20m'}">10 - 20 triệu</span>
              </label>
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedPricePreset === '20_50m' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedPricePreset" value="20_50m" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedPricePreset === '20_50m'}">20 - 50 triệu</span>
              </label>
              
              <!-- Custom range -->
              <div 
                class="flex items-center justify-between py-1.5 px-3.5 rounded-full transition-all duration-200"
                :class="selectedPricePreset === 'custom' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <div class="flex items-center gap-3">
                  <span class="text-sm transition-colors" :class="{'font-semibold': selectedPricePreset === 'custom'}">Khác</span>
                  <input type="radio" v-model="selectedPricePreset" value="custom" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                </div>
                <div class="flex items-center gap-1.5">
                  <input
                    v-model="minPriceInput"
                    type="number"
                    placeholder="Từ"
                    :disabled="selectedPricePreset !== 'custom'"
                    class="w-14 h-7 text-center border border-slate-200 rounded-md focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 outline-none text-xs disabled:bg-slate-50 disabled:cursor-not-allowed"
                  />
                  <span class="text-slate-400 text-xs">-</span>
                  <input
                    v-model="maxPriceInput"
                    type="number"
                    placeholder="Đến"
                    :disabled="selectedPricePreset !== 'custom'"
                    class="w-14 h-7 text-center border border-slate-200 rounded-md focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 outline-none text-xs disabled:bg-slate-50 disabled:cursor-not-allowed"
                  />
                  <span class="text-xs font-medium" :class="selectedPricePreset === 'custom' ? 'text-[#0DA2E7]' : 'text-slate-500'">triệu</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Diện tích -->
          <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-3">
              <Ruler class="w-4 h-4 text-sky-500" />
              Diện tích
            </h3>
            <div class="flex flex-col gap-1.5">
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedAreaPreset === 'all' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedAreaPreset" value="all" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedAreaPreset === 'all'}">Tất cả</span>
              </label>
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedAreaPreset === 'under_30' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedAreaPreset" value="under_30" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedAreaPreset === 'under_30'}">Dưới 30 m²</span>
              </label>
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedAreaPreset === '30_50' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedAreaPreset" value="30_50" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedAreaPreset === '30_50'}">30 - 50 m²</span>
              </label>
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedAreaPreset === '50_80' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedAreaPreset" value="50_80" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedAreaPreset === '50_80'}">50 - 80 m²</span>
              </label>
              <label 
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200" 
                :class="selectedAreaPreset === '80_100' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <input type="radio" v-model="selectedAreaPreset" value="80_100" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                <span class="text-sm transition-colors" :class="{'font-semibold': selectedAreaPreset === '80_100'}">80 - 100 m²</span>
              </label>

              <!-- Custom range -->
              <div 
                class="flex items-center justify-between py-1.5 px-3.5 rounded-full transition-all duration-200"
                :class="selectedAreaPreset === 'custom' ? 'bg-sky-50/60 text-[#0DA2E7]' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
              >
                <div class="flex items-center gap-3">
                  <span class="text-sm transition-colors" :class="{'font-semibold': selectedAreaPreset === 'custom'}">Khác</span>
                  <input type="radio" v-model="selectedAreaPreset" value="custom" class="accent-[#0DA2E7] w-4 h-4 cursor-pointer" />
                </div>
                <div class="flex items-center gap-1.5">
                  <input
                    v-model="minAreaInput"
                    type="number"
                    placeholder="Từ"
                    :disabled="selectedAreaPreset !== 'custom'"
                    class="w-14 h-7 text-center border border-slate-200 rounded-md focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 outline-none text-xs disabled:bg-slate-50 disabled:cursor-not-allowed"
                  />
                  <span class="text-slate-400 text-xs">-</span>
                  <input
                    v-model="maxAreaInput"
                    type="number"
                    placeholder="Đến"
                    :disabled="selectedAreaPreset !== 'custom'"
                    class="w-14 h-7 text-center border border-slate-200 rounded-md focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 outline-none text-xs disabled:bg-slate-50 disabled:cursor-not-allowed"
                  />
                  <span class="text-xs font-medium" :class="selectedAreaPreset === 'custom' ? 'text-[#0DA2E7]' : 'text-slate-500'">m²</span>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </RentLayout>
  <MapSearchModal :open="isMapOpen" :filters="mapFilters" @close="isMapOpen = false" />
</template>

<script setup>
import { onMounted, ref, watch } from 'vue';
import { KeyRound, ChevronDown, ChevronLeft, ChevronRight, User, DollarSign, Ruler } from 'lucide-vue-next';
import RentLayout from '@/layouts/RentLayout.vue';
import TopSearchBar from '@/components/shared/TopSearchBar.vue';
import RentCard from '@/components/shared/RentCard.vue';
import SidebarWidget from '@/components/shared/SidebarWidget.vue';
import MapWidget from '@/components/shared/MapWidget.vue';
import TabFilterGroup from '@/components/shared/TabFilterGroup.vue';
import RadioFilterGroup from '@/components/shared/RadioFilterGroup.vue';
import { useRentListings } from '@/composables/useRentListings';
import { useFavoriteListings } from '@/composables/useFavoriteListings';
import { computed } from 'vue';
import MapSearchModal from '@/components/shared/MapSearchModal.vue';

const {
  rentListings, rentLoading, rentTotal,
  currentPage, lastPage, searchKeyword,
  rentSuggestions, visiblePages,
  posterType, minPrice, maxPrice, minArea, maxArea,
  init, onSearch, goToPage,
} = useRentListings();
const { isFavorite, toggleFavorite, loadFavorites } = useFavoriteListings();
const isMapOpen = ref(false);
const mapFilters = computed(() => ({ demand_type: 'RENT', keyword: searchKeyword.value, poster_type: posterType.value, min_price: minPrice.value, max_price: maxPrice.value, min_area: minArea.value, max_area: maxArea.value }));

// Custom UI Filter presets
const selectedPricePreset = ref('all');
const minPriceInput = ref('');
const maxPriceInput = ref('');

const selectedAreaPreset = ref('all');
const minAreaInput = ref('');
const maxAreaInput = ref('');

// Watch price preset
watch(selectedPricePreset, (newPreset) => {
  if (newPreset !== 'custom') {
    minPriceInput.value = '';
    maxPriceInput.value = '';
    if (newPreset === 'all') {
      minPrice.value = null;
      maxPrice.value = null;
    } else if (newPreset === 'under_5m') {
      minPrice.value = null;
      maxPrice.value = 5000000;
    } else if (newPreset === '5_10m') {
      minPrice.value = 5000000;
      maxPrice.value = 10000000;
    } else if (newPreset === '10_20m') {
      minPrice.value = 10000000;
      maxPrice.value = 20000000;
    } else if (newPreset === '20_50m') {
      minPrice.value = 20000000;
      maxPrice.value = 50000000;
    }
  } else {
    updateCustomPrice();
  }
});

// Watch area preset
watch(selectedAreaPreset, (newPreset) => {
  if (newPreset !== 'custom') {
    minAreaInput.value = '';
    maxAreaInput.value = '';
    if (newPreset === 'all') {
      minArea.value = null;
      maxArea.value = null;
    } else if (newPreset === 'under_30') {
      minArea.value = null;
      maxArea.value = 30;
    } else if (newPreset === '30_50') {
      minArea.value = 30;
      maxArea.value = 50;
    } else if (newPreset === '50_80') {
      minArea.value = 50;
      maxArea.value = 80;
    } else if (newPreset === '80_100') {
      minArea.value = 80;
      maxArea.value = 100;
    }
  } else {
    updateCustomArea();
  }
});

let priceDebounce = null;
function updateCustomPrice() {
  if (priceDebounce) clearTimeout(priceDebounce);
  priceDebounce = setTimeout(() => {
    if (selectedPricePreset.value === 'custom') {
      const minVal = minPriceInput.value !== '' ? Number(minPriceInput.value) * 1000000 : null;
      const maxVal = maxPriceInput.value !== '' ? Number(maxPriceInput.value) * 1000000 : null;
      minPrice.value = minVal;
      maxPrice.value = maxVal;
    }
  }, 500);
}

let areaDebounce = null;
function updateCustomArea() {
  if (areaDebounce) clearTimeout(areaDebounce);
  areaDebounce = setTimeout(() => {
    if (selectedAreaPreset.value === 'custom') {
      const minVal = minAreaInput.value !== '' ? Number(minAreaInput.value) : null;
      const maxVal = maxAreaInput.value !== '' ? Number(maxAreaInput.value) : null;
      minArea.value = minVal;
      maxArea.value = maxVal;
    }
  }, 500);
}

watch([minPriceInput, maxPriceInput], () => {
  if (selectedPricePreset.value === 'custom') {
    updateCustomPrice();
  }
});

watch([minAreaInput, maxAreaInput], () => {
  if (selectedAreaPreset.value === 'custom') {
    updateCustomArea();
  }
});

onMounted(() => {
  init();
  loadFavorites();
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
