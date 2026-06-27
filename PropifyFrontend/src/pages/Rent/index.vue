<template>
  <RentLayout>
    <TopSearchBar
      v-model="searchKeyword"
      v-model:search-field="searchField"
      :search-field-options="searchFieldOptions"
      :suggestions="rentSuggestions"
      :pinned="true"
      @search="onSearch"
      @select-suggestion="onSearch"
    />

    <div class="max-w-7xl mx-auto px-4 md:px-8 py-8">
      <div class="grid grid-cols-1 lg:grid-cols-12 gap-8">
        <!-- Left Column: Main Content -->
        <div class="lg:col-span-9">
          <!-- Breadcrumb -->
          <Breadcrumb
            :crumbs="[{ label: 'Trang chủ', to: '/' }, { label: 'Cho thuê' }]"
          />

          <!-- Header section -->
          <div class="flex justify-between items-end mb-6">
            <div>
              <h1 class="text-2xl font-bold text-gray-900 mb-1">
                Cho thuê nhà đất giá rẻ tại Việt Nam {{ currentDateStr }}
              </h1>
              <p class="text-sm text-gray-500">
                Hiện có
                <span class="font-bold text-blue-600">{{
                  rentTotal ? Number(rentTotal).toLocaleString("vi-VN") : 0
                }}</span>
                bất động sản.
              </p>
            </div>

            <!-- Sort Dropdown -->
            <SortDropdown v-model="sortBy" />
          </div>

          <!-- Listings -->
          <div class="relative flex flex-col gap-6">
            <!-- Initial loading (first page) -->
            <div
              v-if="isInitialLoading"
              class="flex justify-center py-16"
            >
              <div
                class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"
              ></div>
            </div>

            <template v-else-if="rentListings.length === 0 && !loadingError">
              <div
                class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-400"
              >
                Chưa có tin cho thuê nào.
              </div>
            </template>

            <template v-else>
              <ListingRowCard
                v-for="item in rentListings"
                :key="item.id"
                :listing="item"
                :to="'/listings/' + item.id"
                :unit="'/tháng'"
                :is-favorite="isFavorite(item)"
                @toggle-favorite="toggleFavorite(item)"
              />
            </template>
          </div>

          <!-- Infinite scroll sentinel + status -->
          <div class="mt-6 flex flex-col items-center gap-3 pb-4">
            <!-- Loading more spinner -->
            <div
              v-if="isLoadingMore"
              class="flex items-center gap-2 text-sm text-slate-500"
            >
              <div
                class="h-5 w-5 animate-spin rounded-full border-2 border-blue-500 border-t-transparent"
              ></div>
              Đang tải thêm...
            </div>

            <!-- Error + retry -->
            <div
              v-if="loadingError"
              class="flex flex-col items-center gap-2"
            >
              <p class="text-sm text-red-500">{{ loadingError }}</p>
              <button
                class="rounded-lg border border-red-200 bg-red-50 px-4 py-1.5 text-sm font-medium text-red-600 cursor-pointer hover:bg-red-100 transition"
                @click="loadMore"
              >
                Thử lại
              </button>
            </div>

            <!-- End of list -->
            <!-- removed "Đã tải hết danh sách" -->

            <!-- Sentinel element for IntersectionObserver -->
            <div
              ref="sentinelRef"
              class="h-4"
            ></div>
          </div>
        </div>

        <!-- Right Column: Sidebar Filters (sticky, scrollable) -->
        <div class="lg:col-span-3 lg:sticky lg:top-24 lg:max-h-[calc(100vh-8rem)] lg:overflow-y-auto scrollbar-thin flex flex-col gap-4">
          <!-- Tìm kiếm theo bản đồ -->
          <div
            @click="isMapOpen = true"
            class="bg-sky-50 border border-sky-100 rounded p-5 flex flex-col items-center justify-center cursor-pointer hover:bg-sky-100 transition shadow-sm text-center"
          >
            <div class="bg-sky-200/50 p-2.5 rounded-full mb-2">
              <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6 text-sky-500"
                viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
              >
                <polygon points="3 6 9 3 15 6 21 3 21 18 15 21 9 18 3 21" />
                <line x1="9" y1="3" x2="9" y2="18" />
                <line x1="15" y1="6" x2="15" y2="21" />
              </svg>
            </div>
            <p class="text-sm font-bold text-sky-600">Tìm kiếm theo bản đồ</p>
            <p class="text-[11px] text-slate-400 mt-0.5">
              Xem vị trí trên bản đồ
            </p>
          </div>

          <!-- Loại hình -->
          <div class="bg-white border border-slate-200 rounded p-5 shadow-sm">
            <h3
              class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-3"
            >
              <Home class="w-4 h-4 text-sky-500" />
              Loại hình
            </h3>
            <div class="relative">
              <select
                v-model="propertyType"
                class="w-full h-10 px-3 py-2 text-sm text-slate-700 bg-white border border-slate-200 rounded-lg outline-none transition focus:border-sky-400 focus:ring-1 focus:ring-sky-400/20"
              >
                <option value="">Tất cả loại hình</option>
                <option value="APARTMENT">Căn hộ chung cư</option>
                <option value="MINI_APARTMENT">Chung cư mini</option>
                <option value="HOUSE">Nhà ở</option>
                <option value="LAND">Đất</option>
                <option value="ROOM">Phòng</option>
                <option value="PRIVATE_HOUSE">Nhà riêng</option>
                <option value="STREET_HOUSE">Nhà mặt phố</option>
                <option value="VILLA_TOWNHOUSE">Biệt thự liền kề</option>
                <option value="SHOPHOUSE">Shophouse</option>
                <option value="KIOSK">Ki-ốt</option>
                <option value="RENT_ROOM">Phòng trọ</option>
                <option value="BOARDING_HOUSE">Nhà trọ</option>
                <option value="OFFICE">Văn phòng</option>
                <option value="RESORT">Khu nghỉ dưỡng</option>
                <option value="RESTAURANT_HOTEL">Nhà hàng - Khách sạn</option>
              </select>
            </div>
          </div>

          <!-- Người đăng -->
          <div class="bg-white border border-slate-200 rounded p-5 shadow-sm">
            <h3
              class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-3"
            >
              <User class="w-4 h-4 text-sky-500" />
              Người đăng
            </h3>
            <div class="flex gap-2">
              <button
                type="button"
                @click="posterType = ''"
                class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-full transition-all duration-200 shadow-sm border border-transparent"
                :class="
                  posterType === ''
                    ? 'bg-[#0DA2E7] text-white'
                    : 'bg-[#F1F3F5] text-slate-600 hover:bg-slate-200'
                "
              >
                Tất cả
              </button>
              <button
                type="button"
                @click="posterType = 'OWNER'"
                class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-full transition-all duration-200 shadow-sm border border-transparent"
                :class="
                  posterType === 'OWNER'
                    ? 'bg-[#0DA2E7] text-white'
                    : 'bg-[#F1F3F5] text-slate-600 hover:bg-slate-200'
                "
              >
                Chủ nhà
              </button>
              <button
                type="button"
                @click="posterType = 'BROKER'"
                class="flex-1 py-1.5 px-3 text-xs font-semibold rounded-full transition-all duration-200 shadow-sm border border-transparent"
                :class="
                  posterType === 'BROKER'
                    ? 'bg-[#0DA2E7] text-white'
                    : 'bg-[#F1F3F5] text-slate-600 hover:bg-slate-200'
                "
              >
                Môi giới
              </button>
            </div>
          </div>

          <!-- Khoảng giá -->
          <div class="bg-white border border-slate-200 rounded p-5 shadow-sm">
            <h3
              class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-3"
            >
              <DollarSign class="w-4 h-4 text-sky-500" />
              Khoảng giá
            </h3>
            <div class="flex flex-col gap-1.5">
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedPricePreset === 'all'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedPricePreset"
                  value="all"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{ 'font-semibold': selectedPricePreset === 'all' }"
                  >Tất cả</span
                >
              </label>
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedPricePreset === 'under_5m'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedPricePreset"
                  value="under_5m"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{
                    'font-semibold': selectedPricePreset === 'under_5m',
                  }"
                  >Dưới 5 triệu</span
                >
              </label>
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedPricePreset === '5_10m'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedPricePreset"
                  value="5_10m"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{ 'font-semibold': selectedPricePreset === '5_10m' }"
                  >5 - 10 triệu</span
                >
              </label>
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedPricePreset === '10_20m'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedPricePreset"
                  value="10_20m"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{ 'font-semibold': selectedPricePreset === '10_20m' }"
                  >10 - 20 triệu</span
                >
              </label>
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedPricePreset === '20_50m'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedPricePreset"
                  value="20_50m"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{ 'font-semibold': selectedPricePreset === '20_50m' }"
                  >20 - 50 triệu</span
                >
              </label>

              <!-- Custom range -->
              <div
                class="flex items-center gap-2 py-1.5 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedPricePreset === 'custom'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedPricePreset"
                  value="custom"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors shrink-0"
                  :class="{ 'font-semibold': selectedPricePreset === 'custom' }"
                  >Khác</span
                >
                <div class="flex items-center gap-1">
                  <input
                    v-model="minPriceInput"
                    type="number"
                    min="0"
                    @input="
                      minPriceInput = minPriceInput < 0 ? 0 : minPriceInput
                    "
                    placeholder="Từ"
                    :disabled="selectedPricePreset !== 'custom'"
                    class="w-14 h-7 text-center border border-slate-200 rounded-md focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 outline-none text-xs disabled:bg-slate-50 disabled:cursor-not-allowed"
                  />
                  <span class="text-slate-400 text-xs">-</span>
                  <input
                    v-model="maxPriceInput"
                    type="number"
                    min="0"
                    @input="
                      maxPriceInput = maxPriceInput < 0 ? 0 : maxPriceInput
                    "
                    placeholder="Đến"
                    :disabled="selectedPricePreset !== 'custom'"
                    class="w-14 h-7 text-center border border-slate-200 rounded-md focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 outline-none text-xs disabled:bg-slate-50 disabled:cursor-not-allowed"
                  />
                  <span
                    class="text-xs font-medium shrink-0"
                    :class="
                      selectedPricePreset === 'custom'
                        ? 'text-[#0DA2E7]'
                        : 'text-slate-500'
                    "
                    >triệu</span
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Diện tích -->
          <div class="bg-white border border-slate-200 rounded p-5 shadow-sm">
            <h3
              class="text-sm font-bold text-slate-800 flex items-center gap-2 mb-3"
            >
              <Ruler class="w-4 h-4 text-sky-500" />
              Diện tích
            </h3>
            <div class="flex flex-col gap-1.5">
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedAreaPreset === 'all'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedAreaPreset"
                  value="all"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{ 'font-semibold': selectedAreaPreset === 'all' }"
                  >Tất cả</span
                >
              </label>
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedAreaPreset === 'under_30'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedAreaPreset"
                  value="under_30"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{
                    'font-semibold': selectedAreaPreset === 'under_30',
                  }"
                  >Dưới 30 m²</span
                >
              </label>
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedAreaPreset === '30_50'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedAreaPreset"
                  value="30_50"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{ 'font-semibold': selectedAreaPreset === '30_50' }"
                  >30 - 50 m²</span
                >
              </label>
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedAreaPreset === '50_80'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedAreaPreset"
                  value="50_80"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{ 'font-semibold': selectedAreaPreset === '50_80' }"
                  >50 - 80 m²</span
                >
              </label>
              <label
                class="flex items-center gap-3 cursor-pointer group py-2 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedAreaPreset === '80_100'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedAreaPreset"
                  value="80_100"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors"
                  :class="{ 'font-semibold': selectedAreaPreset === '80_100' }"
                  >80 - 100 m²</span
                >
              </label>

              <!-- Custom range -->
              <div
                class="flex items-center gap-2 py-1.5 px-3.5 rounded-full transition-all duration-200"
                :class="
                  selectedAreaPreset === 'custom'
                    ? 'bg-sky-50/60 text-[#0DA2E7]'
                    : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'
                "
              >
                <input
                  type="radio"
                  v-model="selectedAreaPreset"
                  value="custom"
                  class="appearance-none w-4 h-4 rounded-full border border-slate-300 bg-white checked:bg-[#0DA2E7] checked:border-white checked:border-[4px] checked:ring-1 checked:ring-[#0DA2E7] cursor-pointer transition-all focus:outline-none shrink-0"
                />
                <span
                  class="text-sm transition-colors shrink-0"
                  :class="{ 'font-semibold': selectedAreaPreset === 'custom' }"
                  >Khác</span
                >
                <div class="flex items-center gap-1">
                  <input
                    v-model="minAreaInput"
                    type="number"
                    min="0"
                    @input="minAreaInput = minAreaInput < 0 ? 0 : minAreaInput"
                    placeholder="Từ"
                    :disabled="selectedAreaPreset !== 'custom'"
                    class="w-14 h-7 text-center border border-slate-200 rounded-md focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 outline-none text-xs disabled:bg-slate-50 disabled:cursor-not-allowed"
                  />
                  <span class="text-slate-400 text-xs">-</span>
                  <input
                    v-model="maxAreaInput"
                    type="number"
                    min="0"
                    @input="maxAreaInput = maxAreaInput < 0 ? 0 : maxAreaInput"
                    placeholder="Đến"
                    :disabled="selectedAreaPreset !== 'custom'"
                    class="w-14 h-7 text-center border border-slate-200 rounded-md focus:border-sky-500 focus:ring-1 focus:ring-sky-500/20 outline-none text-xs disabled:bg-slate-50 disabled:cursor-not-allowed"
                  />
                  <span
                    class="text-xs font-medium shrink-0"
                    :class="
                      selectedAreaPreset === 'custom'
                        ? 'text-[#0DA2E7]'
                        : 'text-slate-500'
                    "
                    >m²</span
                  >
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </RentLayout>
  <MapSearchModal
    :open="isMapOpen"
    :filters="mapFilters"
    @close="isMapOpen = false"
  />
</template>

<script setup>
import { onMounted, onUnmounted, ref, watch, computed } from "vue";
import {
  User,
  DollarSign,
  Ruler,
  Home,
} from "lucide-vue-next";
import RentLayout from "@/layouts/RentLayout.vue";
import TopSearchBar from "@/components/shared/TopSearchBar.vue";
import ListingRowCard from "@/components/shared/ListingRowCard.vue";
import { usePublicListings } from "@/composables/usePublicListings";
import { useFavoriteListings } from "@/composables/useFavoriteListings";
import MapSearchModal from "@/components/shared/MapSearchModal.vue";
import Breadcrumb from "@/components/shared/Breadcrumb.vue";
import SortDropdown from "@/components/shared/SortDropdown.vue";

const currentDateStr = computed(() => {
  const now = new Date();
  return `(${now.getMonth() + 1}/${now.getFullYear()})`;
});

const {
  listings: rentListings,
  total: rentTotal,
  searchKeyword,
  searchField,
  suggestions: rentSuggestions,
  posterType,
  minPrice,
  maxPrice,
  minArea,
  maxArea,
  sortBy,
  propertyType,
  hasMore,
  loadingError,
  isLoadingMore,
  isInitialLoading,
  init,
  loadMore,
  onSearch,
} = usePublicListings({ demandType: "RENT" });
const { isFavorite, toggleFavorite, loadFavorites } = useFavoriteListings();
const isMapOpen = ref(false);
const sentinelRef = ref(null);
let observer = null;

function setupObserver() {
  observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting) {
        loadMore();
      }
    },
    { rootMargin: "200px" },
  );
  if (sentinelRef.value) observer.observe(sentinelRef.value);
}

onMounted(() => {
  setupObserver();
  init();
  loadFavorites();
});

onUnmounted(() => {
  if (observer) observer.disconnect();
});

const mapFilters = computed(() => ({
  demand_type: "RENT",
  keyword: searchKeyword.value,
  poster_type: posterType.value,
  min_price: minPrice.value,
  max_price: maxPrice.value,
  min_area: minArea.value,
  max_area: maxArea.value,
  property_type: propertyType.value,
}));
const searchFieldOptions = [
  { value: "all", label: "Tất cả" },
  { value: "address", label: "Địa chỉ" },
  { value: "project_name", label: "Dự án" },
];

// Custom UI Filter presets
const selectedPricePreset = ref("all");
const minPriceInput = ref("");
const maxPriceInput = ref("");

const selectedAreaPreset = ref("all");
const minAreaInput = ref("");
const maxAreaInput = ref("");

// Watch price preset
watch(selectedPricePreset, (newPreset) => {
  if (newPreset !== "custom") {
    minPriceInput.value = "";
    maxPriceInput.value = "";
    if (newPreset === "all") {
      minPrice.value = null;
      maxPrice.value = null;
    } else if (newPreset === "under_5m") {
      minPrice.value = null;
      maxPrice.value = 5000000;
    } else if (newPreset === "5_10m") {
      minPrice.value = 5000000;
      maxPrice.value = 10000000;
    } else if (newPreset === "10_20m") {
      minPrice.value = 10000000;
      maxPrice.value = 20000000;
    } else if (newPreset === "20_50m") {
      minPrice.value = 20000000;
      maxPrice.value = 50000000;
    }
  } else {
    updateCustomPrice();
  }
});

// Watch area preset
watch(selectedAreaPreset, (newPreset) => {
  if (newPreset !== "custom") {
    minAreaInput.value = "";
    maxAreaInput.value = "";
    if (newPreset === "all") {
      minArea.value = null;
      maxArea.value = null;
    } else if (newPreset === "under_30") {
      minArea.value = null;
      maxArea.value = 30;
    } else if (newPreset === "30_50") {
      minArea.value = 30;
      maxArea.value = 50;
    } else if (newPreset === "50_80") {
      minArea.value = 50;
      maxArea.value = 80;
    } else if (newPreset === "80_100") {
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
    if (selectedPricePreset.value === "custom") {
      const minVal =
        minPriceInput.value !== ""
          ? Number(minPriceInput.value) * 1000000
          : null;
      const maxVal =
        maxPriceInput.value !== ""
          ? Number(maxPriceInput.value) * 1000000
          : null;
      minPrice.value = minVal;
      maxPrice.value = maxVal;
    }
  }, 500);
}

let areaDebounce = null;
function updateCustomArea() {
  if (areaDebounce) clearTimeout(areaDebounce);
  areaDebounce = setTimeout(() => {
    if (selectedAreaPreset.value === "custom") {
      const minVal =
        minAreaInput.value !== "" ? Number(minAreaInput.value) : null;
      const maxVal =
        maxAreaInput.value !== "" ? Number(maxAreaInput.value) : null;
      minArea.value = minVal;
      maxArea.value = maxVal;
    }
  }, 500);
}

watch([minPriceInput, maxPriceInput], () => {
  if (selectedPricePreset.value === "custom") {
    updateCustomPrice();
  }
});

watch([minAreaInput, maxAreaInput], () => {
  if (selectedAreaPreset.value === "custom") {
    updateCustomArea();
  }
});
</script>

<style scoped>
.scrollbar-thin::-webkit-scrollbar { width: 6px; }
.scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(0,0,0,0.08); border-radius: 99px; }
.scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
</style>

