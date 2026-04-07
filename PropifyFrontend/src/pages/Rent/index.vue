<template>
  <RentLayout>
    <TopSearchBar />

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
                Hiện có <span class="font-bold text-blue-600">4</span> bất động sản
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
              v-for="item in rentListings"
              :key="item.id"
              :title="item.title"
              :type="item.type"
              :price="item.price"
              :unit="item.unit"
              :area="item.area"
              :beds="item.beds"
              :baths="item.baths"
              :location="item.location"
              :author="item.author"
              :image="item.image"
              :timeAgo="item.timeAgo"
              :views="item.views"
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
import { ref } from 'vue';
import { KeyRound, ChevronDown, User, DollarSign, Ruler } from 'lucide-vue-next';
import RentLayout from '@/layouts/RentLayout.vue';
import TopSearchBar from '@/components/shared/TopSearchBar.vue';
import ListingCard from '@/components/shared/ListingCard.vue';
import SidebarWidget from '@/components/shared/SidebarWidget.vue';
import MapWidget from '@/components/shared/MapWidget.vue';
import TabFilterGroup from '@/components/shared/TabFilterGroup.vue';
import RadioFilterGroup from '@/components/shared/RadioFilterGroup.vue';

const posterType = ref('all');
const priceRange = ref('all');
const areaRange = ref('all');

const rentListings = ref([
  {
    id: 1,
    title: 'Căn hộ studio full nội thất trung tâm quận 1',
    type: 'Studio',
    price: '12 triệu',
    unit: '/tháng',
    area: 38,
    beds: 1,
    baths: 1,
    location: 'Quận 1, TP. Hồ Chí Minh',
    author: { name: 'Trần Minh Tuấn', role: 'Chủ nhà' },
    image: 'https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800&auto=format&fit=crop&q=60',
    timeAgo: '2 giờ trước',
    views: 312,
  },
  {
    id: 2,
    title: 'Căn hộ 2 phòng ngủ view sông Sài Gòn thoáng mát',
    type: 'Căn hộ',
    price: '18 triệu',
    unit: '/tháng',
    area: 72,
    beds: 2,
    baths: 2,
    location: 'Thủ Thiêm, TP. Thủ Đức',
    author: { name: 'Lê Thị Hoa', role: 'Môi giới' },
    image: 'https://images.unsplash.com/photo-1560448204-e02f11c3d0e2?w=800&auto=format&fit=crop&q=60',
    timeAgo: '4 giờ trước',
    views: 178,
  },
  {
    id: 3,
    title: 'Nhà nguyên căn 3 tầng hẻm xe hơi quận Bình Thạnh',
    type: 'Nhà phố',
    price: '25 triệu',
    unit: '/tháng',
    area: 120,
    beds: 4,
    baths: 3,
    location: 'Bình Thạnh, TP. Hồ Chí Minh',
    author: { name: 'Nguyễn Văn Hùng', role: 'Chủ nhà' },
    image: 'https://images.unsplash.com/photo-1568605114967-8130f3a36994?w=800&auto=format&fit=crop&q=60',
    timeAgo: '1 ngày trước',
    views: 504,
  },
  {
    id: 4,
    title: 'Officetel cho thuê tiện nghi đường Võ Văn Kiệt',
    type: 'Officetel',
    price: '9 triệu',
    unit: '/tháng',
    area: 30,
    beds: 1,
    baths: 1,
    location: 'Quận 5, TP. Hồ Chí Minh',
    author: { name: 'Phạm Thu Hằng', role: 'Môi giới' },
    image: 'https://images.unsplash.com/photo-1630699144867-37acec97df5a?w=800&auto=format&fit=crop&q=60',
    timeAgo: '6 giờ trước',
    views: 97,
  },
]);
</script>
