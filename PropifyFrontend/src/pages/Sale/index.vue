<template>
  <SaleLayout>
    <TopSearchBar />
    
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
                Hiện có <span class="font-bold text-blue-600">3</span> bất động sản
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
            <ListingCard 
              v-for="item in 3" 
              :key="item"
              :title="item === 2 ? 'Penthouse ban công panorama' : 'Căn hộ view thành phố tầng cao'"
              :type="item === 2 ? 'Penthouse' : 'Căn hộ'"
              :price="item === 2 ? '65 tỷ' : '18 tỷ'"
              :unit="''"
              :area="item === 2 ? 180 : 95"
              :beds="item === 2 ? 3 : 2"
              :baths="item === 2 ? 2 : 2"
              :location="item === 2 ? 'Quận 1, TP. Hồ Chí Minh' : 'Bình Thạnh, TP. Hồ Chí Minh'"
              :author="{ name: item === 2 ? 'Lê Hoàng Nam' : 'Nguyễn Thị Lan', role: 'Môi giới' }"
              :image="item === 2 ? 'https://images.unsplash.com/photo-1512917774080-9991f1c4c750?w=800&auto=format&fit=crop&q=60' : 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=60'"
              :timeAgo="item === 2 ? '3 giờ trước' : '5 giờ trước'"
              :views="item === 2 ? 143 : 215"
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
import { ref } from 'vue';
import { Building2, ChevronDown, User, DollarSign, Ruler } from 'lucide-vue-next';
import SaleLayout from '@/layouts/SaleLayout.vue';
import TopSearchBar from '@/components/shared/TopSearchBar.vue';
import ListingCard from '@/components/shared/ListingCard.vue';
import SidebarWidget from '@/components/shared/SidebarWidget.vue';
import MapWidget from '@/components/shared/MapWidget.vue';
import TabFilterGroup from '@/components/shared/TabFilterGroup.vue';
import RadioFilterGroup from '@/components/shared/RadioFilterGroup.vue';

const posterType = ref('all');
const priceRange = ref('all');
const areaRange = ref('all');
</script>
