<template>
  <SaleLayout>
    <TopSearchBar
      v-model="searchKeyword"
      :suggestions="saleSuggestions"
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
                <Building2 class="w-6 h-6 text-blue-500" />
                Mua bán bất động sản
              </h1>
              <p class="text-sm text-gray-500">
                Hiện có <span class="font-bold text-blue-600">{{ saleTotal }}</span> bất động sản
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
            <div v-if="saleLoading" class="absolute inset-0 z-10 flex items-start justify-center pt-16 bg-white/60 backdrop-blur-[1px] rounded-xl">
              <div class="h-8 w-8 animate-spin rounded-full border-4 border-blue-500 border-t-transparent"></div>
            </div>

            <div v-if="!saleLoading && saleListings.length === 0" class="rounded-xl border border-dashed border-slate-200 bg-white p-6 text-center text-sm text-slate-400">
              Chưa có tin mua bán nào.
            </div>

            <SaleCard 
              v-for="item in saleListings" 
              :key="item.id"
              :to="'/listings/' + item.id"
              :verified="isVerified(item)"
              :title="item.title"
              :type="propertyTypeLabel(item.property?.type)"
              :price="formatPrice(item.property?.price)"
              :unit="''"
              :area="item.property?.area || 0"
              :beds="item.property?.bedrooms || 0"
              :baths="item.property?.bathrooms || 0"
              :location="item.property?.address_detail || ''"
              :author="getAuthor(item)"
              :image="getThumb(item)"
              :badge="getPackageBadge(item.package)"
              :badge-color="getPackageColor(item.package)"
              :rating="null"
              :timeAgo="timeAgo(item.submitted_at)"
              :views="item.views_count || 0"
            />
          </div>

          <!-- Pagination — always visible when there are multiple pages -->
          <nav v-if="lastPage > 1" class="mt-8 flex items-center justify-center gap-1" aria-label="Phân trang">
            <!-- Prev -->
            <button
              :disabled="currentPage <= 1 || saleLoading"
              class="pagination-btn"
              @click="goToPage(currentPage - 1)"
            >
              <ChevronLeft class="w-4 h-4" />
            </button>

            <!-- Page numbers -->
            <template v-for="page in visiblePages" :key="page">
              <span v-if="page === '...'" class="px-2 text-gray-400 text-sm select-none">…</span>
              <button
                v-else
                :disabled="saleLoading"
                :class="['pagination-btn', page === currentPage ? 'pagination-btn--active' : '']"
                @click="goToPage(page)"
              >
                {{ page }}
              </button>
            </template>

            <!-- Next -->
            <button
              :disabled="currentPage >= lastPage || saleLoading"
              class="pagination-btn"
              @click="goToPage(currentPage + 1)"
            >
              <ChevronRight class="w-4 h-4" />
            </button>
          </nav>
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
              name="price"
              :usePillSkin="true"
              :options="[
                { label: 'Tất cả', value: 'all' },
                { label: 'Dưới 5 tỷ', value: 'under_5' },
                { label: '5 - 10 tỷ', value: '5_10' },
                { label: '10 - 20 tỷ', value: '10_20' },
                { label: '20 - 50 tỷ', value: '20_50' }
              ]"
              :showExpand="true"
            />
          </SidebarWidget>

          <SidebarWidget title="Diện tích" :icon="Ruler">
            <RadioFilterGroup 
              v-model="areaRange" 
              name="area"
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
  </SaleLayout>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { Building2, ChevronDown, ChevronLeft, ChevronRight, User, DollarSign, Ruler } from 'lucide-vue-next';
import SaleLayout from '@/layouts/SaleLayout.vue';
import TopSearchBar from '@/components/shared/TopSearchBar.vue';
import SaleCard from '@/components/shared/SaleCard.vue';
import SidebarWidget from '@/components/shared/SidebarWidget.vue';
import MapWidget from '@/components/shared/MapWidget.vue';
import TabFilterGroup from '@/components/shared/TabFilterGroup.vue';
import RadioFilterGroup from '@/components/shared/RadioFilterGroup.vue';
import { useSaleListings } from '@/composables/useSaleListings';

const {
  saleListings, saleLoading, saleTotal,
  currentPage, lastPage, searchKeyword,
  saleSuggestions, visiblePages,
  init, onSearch, goToPage,
} = useSaleListings();

const posterType = ref('all');
const priceRange = ref('all');
const areaRange = ref('all');

onMounted(() => {
  init();
});

function getThumb(item) {
  if (item.images && item.images.length > 0) {
    const thumb = item.images.find((image) => image.is_thumbnail);
    return thumb ? thumb.url : item.images[0].url;
  }
  return 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=60';
}

function getAuthor(item) {
  return {
    name: item.property?.contact_name || item.owner?.full_name || 'Chủ nhà',
    role: item.property?.poster_type === 'OWNER' ? 'Chủ nhà' : 'Môi giới',
  };
}

function getPackageBadge(pkg) {
  if (!pkg?.slug) return null;
  const map = { gold: 'VIP', silver: 'HOT' };
  return map[pkg.slug] || null;
}

function getPackageColor(pkg) {
  if (!pkg?.slug) return null;
  const map = { gold: '#FFD700', silver: '#C0C0C0' };
  return map[pkg.slug] || null;
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

<style scoped>
.pagination-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 36px;
  height: 36px;
  padding: 0 8px;
  border-radius: 10px;
  font-size: 14px;
  font-weight: 500;
  color: #475569;
  background: #fff;
  border: 1px solid #e2e8f0;
  cursor: pointer;
  transition: all 0.2s ease;
}

.pagination-btn:hover:not(:disabled):not(.pagination-btn--active) {
  background: #f1f5f9;
  border-color: #cbd5e1;
}

.pagination-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.pagination-btn--active {
  background: #3b82f6;
  color: #fff;
  border-color: #3b82f6;
  box-shadow: 0 2px 8px rgba(59, 130, 246, 0.3);
}
</style>
