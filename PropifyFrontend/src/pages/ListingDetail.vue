<template>
  <main :class="previewMode ? 'min-h-0 bg-[#f4f8fc] pb-6 pt-0' : 'min-h-screen bg-[#f4f8fc] pb-14 pt-24'">
    <div v-if="loading" class="flex min-h-[50vh] items-center justify-center">
      <div class="h-10 w-10 animate-spin rounded-full border-4 border-sky-500 border-t-transparent"></div>
    </div>
    <div v-else-if="error" class="mx-auto mt-10 max-w-[1240px] px-4 text-center text-red-500">
      <p class="text-xl font-bold">{{ error }}</p>
      <button class="mt-4 rounded-xl bg-sky-500 px-6 py-2 text-white" @click="router.push('/')">Về trang chủ</button>
    </div>
    <div v-else-if="listing" :class="previewMode ? 'mx-auto w-full max-w-[1240px] px-0' : 'mx-auto w-full max-w-[1240px] px-4 lg:px-6'">
      
      <!-- Breadcrumb & Header -->
      <div class="mb-4">
        <p class="text-xs text-slate-500 hover:text-sky-600">
          <router-link to="/">Trang chủ</router-link>
          <span class="mx-1">></span>
          <span class="text-slate-400">{{ listing.title }}</span>
        </p>
        <div class="mt-2 flex items-start justify-between gap-4">
          <div>
            <h1 class="text-2xl font-extrabold tracking-tight text-slate-900 md:text-3xl">{{ listing.title }}</h1>
            <p class="mt-1 text-sm text-slate-500">
              <svg xmlns="http://www.w3.org/2000/svg" class="mr-1 inline h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
              Ngày đăng: {{ formatDate(listing.submitted_at) }}
              <span v-if="listing.views != null" class="ml-3 inline-flex items-center gap-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="inline h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                {{ listing.views }} lượt xem
              </span>
            </p>
          </div>
          <div class="flex shrink-0 items-center gap-2">
            <button class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-rose-50 hover:text-rose-500 hover:border-rose-200">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z" /></svg>
            </button>
            <button class="flex h-10 w-10 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-500 transition hover:bg-sky-50 hover:text-sky-500 hover:border-sky-200">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z" /></svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Gallery -->
      <div v-if="listing.images" class="mb-6 overflow-hidden rounded-2xl bg-white shadow-sm">
        <div class="relative h-[300px] w-full bg-slate-900 md:h-[450px]">
          <img v-if="activeImage" :src="activeImage" class="h-full w-full object-cover opacity-95" alt="Listing image" />
          <div class="absolute left-4 top-1/2 -translate-y-1/2">
            <button class="flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-slate-800 shadow backdrop-blur transition hover:bg-white" @click="prevImage">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 pr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
            </button>
          </div>
          <div class="absolute right-4 top-1/2 -translate-y-1/2">
            <button class="flex h-10 w-10 items-center justify-center rounded-full bg-white/80 text-slate-800 shadow backdrop-blur transition hover:bg-white" @click="nextImage">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 pl-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
            </button>
          </div>
          <div class="absolute bottom-4 left-4 rounded-full bg-slate-900/60 px-3 py-1 text-xs font-semibold text-white backdrop-blur">
            Hình ảnh {{ activeImageIndex + 1 }}/{{ displayImages.length }}
          </div>
        </div>
        <div class="flex gap-2 p-2 overflow-x-auto bg-slate-50">
          <button 
            v-for="(img, idx) in displayImages" 
            :key="idx"
            class="relative h-16 w-24 shrink-0 overflow-hidden rounded-lg transition"
            :class="activeImageIndex === idx ? 'ring-2 ring-sky-500 ring-offset-1' : 'opacity-70 hover:opacity-100'"
            @click="activeImageIndex = idx"
          >
            <img :src="img.url" class="h-full w-full object-cover" />
          </button>
        </div>
      </div>

      <!-- Two Column Layout -->
      <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-[1fr_360px]">
        
        <!-- Left Main Content -->
        <div class="space-y-6">
          
          <!-- Description -->
          <section class="rounded-2xl border border-slate-200 bg-white p-5 lg:p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
              <h2 class="text-[17px] font-bold text-slate-800">Mô tả tin đăng</h2>
            </div>
            <div class="whitespace-pre-wrap text-[15px] leading-relaxed text-slate-600">
              {{ listing.description }}
            </div>
          </section>

          <!-- Characteristics -->
          <section class="rounded-2xl border border-slate-200 bg-white p-5 lg:p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>
              <h2 class="text-[17px] font-bold text-slate-800">Thông tin chi tiết</h2>
            </div>
            <div class="grid grid-cols-1 gap-y-4 gap-x-6 sm:grid-cols-2 text-[14px]">
              
              <div v-if="listing.property?.type" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Loại BĐS</span>
                <span class="font-medium text-slate-800">{{ propertyTypeLabel(listing.property.type) }}</span>
              </div>
              <div v-if="listing.property?.area" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Diện tích</span>
                <span class="font-medium text-slate-800">{{ listing.property.area }} m²</span>
              </div>
              <div v-if="listing.property?.bedrooms" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Phòng ngủ</span>
                <span class="font-medium text-slate-800">{{ listing.property.bedrooms }} PN</span>
              </div>
              <div v-if="listing.property?.bathrooms" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Phòng tắm</span>
                <span class="font-medium text-slate-800">{{ listing.property.bathrooms }} WC</span>
              </div>
              <div v-if="listing.property?.direction_code" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Hướng nhà</span>
                <span class="font-medium text-slate-800">{{ directionLabel(listing.property.direction_code) }}</span>
              </div>
              <div v-if="listing.property?.balcony_direction_code" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Hướng ban công</span>
                <span class="font-medium text-slate-800">{{ directionLabel(listing.property.balcony_direction_code) }}</span>
              </div>
              <div v-if="listing.property?.facade_width" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Mặt tiền</span>
                <span class="font-medium text-slate-800">{{ listing.property.facade_width }} m</span>
              </div>
              <div v-if="listing.property?.road_width" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Đường vào</span>
                <span class="font-medium text-slate-800">{{ listing.property.road_width }} m</span>
              </div>
              <div v-if="listing.property?.furniture_status" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Nội thất</span>
                <span class="font-medium text-slate-800">{{ furnitureLabel(listing.property.furniture_status) }}</span>
              </div>
              <div v-if="listing.property?.legal_paper_types?.length" class="flex justify-between border-b border-slate-50 pb-2">
                <span class="text-slate-500">Pháp lý</span>
                <span class="font-medium text-slate-800 text-right">{{ listing.property.legal_paper_types.map(v => legalPaperLabel(v)).join(', ') }}</span>
              </div>

            </div>
          </section>

          <!-- Amenities -->
          <section v-if="listing.property?.amenities?.length" class="rounded-2xl border border-slate-200 bg-white p-5 lg:p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" /></svg>
              <h2 class="text-[17px] font-bold text-slate-800">Tiện ích</h2>
            </div>
            <div class="flex flex-wrap gap-2">
              <span v-for="amenity in listing.property.amenities" :key="amenity" class="rounded-full bg-sky-50 px-3 py-1.5 text-sm font-medium text-sky-600 border border-sky-100">
                ✓ {{ amenity }}
              </span>
            </div>
          </section>

          <!-- Map -->
          <section class="rounded-2xl border border-slate-200 bg-white p-5 lg:p-6 shadow-sm">
            <div class="mb-4 flex items-center gap-2 border-b border-slate-100 pb-3">
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
              <h2 class="text-[17px] font-bold text-slate-800">Vị trí bản đồ</h2>
            </div>
            <p class="mb-3 text-sm text-slate-600">{{ fullAddress }}</p>
            <div class="relative h-[360px] w-full overflow-hidden rounded-xl border border-slate-200 bg-slate-100">
              <button
                v-if="hasLatLng"
                type="button"
                class="absolute left-3 top-3 z-10 rounded-lg border border-white/70 bg-white/95 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm backdrop-blur transition hover:bg-white hover:text-sky-700"
                @click.stop="toggleMapMode"
              >
                {{ mapMode === 'satellite' ? 'Bản đồ' : 'Vệ tinh' }}
              </button>
              <button
                v-if="hasLatLng"
                type="button"
                class="absolute left-3 top-[52px] z-10 rounded-lg border border-white/70 px-3 py-2 text-xs font-semibold shadow-sm backdrop-blur transition hover:bg-white"
                :class="isMap3dEnabled ? 'bg-sky-500/95 text-white hover:text-white' : 'bg-white/95 text-slate-700 hover:text-sky-700'"
                @click.stop="toggleMap3d"
              >
                {{ isMap3dEnabled ? '2D' : '3D' }}
              </button>
              <div v-show="hasLatLng" ref="mapElement" class="h-full w-full"></div>
              <div v-if="!hasLatLng" class="flex h-full w-full items-center justify-center text-slate-400">
                Bản đồ không được hỗ trợ cho vị trí này
              </div>
            </div>
          </section>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6 lg:sticky lg:top-24">
          <div class="rounded-2xl border border-slate-200 bg-white p-5 lg:p-6 shadow-sm">
            <!-- Price and Specs -->
            <div class="border-b border-slate-100 pb-4">
              <h3 class="text-[28px] font-extrabold text-sky-500">
                {{ formatPrice(listing.property?.price) }} <span v-if="listing.demand_type === 'RENT'" class="text-base text-slate-500 font-normal">/tháng</span>
              </h3>
              <div class="mt-2 flex items-center justify-start gap-4 text-sm font-medium text-slate-600">
                <span class="flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v6a1 1 0 01-1 1H4a1 1 0 01-1-1v-6z" clip-rule="evenodd" /></svg> {{ listing.property?.bedrooms || 0 }} PN</span>
                <span class="flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" viewBox="0 0 20 20" fill="currentColor"><path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4zM3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z" /></svg> {{ listing.property?.bathrooms || 0 }} WC</span>
                <span class="flex items-center gap-1.5"><svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4" /></svg> {{ listing.property?.area || 0 }} m²</span>
              </div>
            </div>

            <!-- Owner Profile -->
            <div class="py-4">
              <div class="flex items-center gap-3">
                <div class="flex h-12 w-12 items-center justify-center overflow-hidden rounded-full bg-sky-100 font-bold text-sky-600">
                  <img v-if="listing.owner?.avatar_url" :src="listing.owner.avatar_url" class="h-full w-full object-cover" />
                  <span v-else>{{ (listing.owner?.full_name || listing.property?.contact_name || 'U').charAt(0).toUpperCase() }}</span>
                </div>
                <div>
                  <p class="font-semibold text-slate-800">{{ listing.property?.contact_name || listing.owner?.full_name }}</p>
                  <p class="text-[13px] text-slate-500">{{ listing.property?.poster_type === 'OWNER' ? 'Chủ nhà' : 'Môi giới' }}</p>
                </div>
              </div>
            </div>

            <!-- Actions -->
            <div class="space-y-3 pt-2">
              <a v-if="listing.property?.contact_phone" :href="`tel:${listing.property.contact_phone}`" class="flex w-full items-center justify-center gap-2 rounded-xl bg-sky-500 px-4 py-3 font-semibold text-white transition hover:bg-sky-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 006.105 6.105l.774-1.548a1 1 0 011.059-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" /></svg>
                GỌI {{ formatPhone(listing.property?.contact_phone) }}
              </a>
              <button class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 font-semibold text-slate-700 transition hover:border-sky-500 hover:text-sky-600" @click="showAppointmentPopup = true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                Đặt lịch xem nhà
              </button>
              <button class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-slate-200 bg-white px-4 py-2.5 font-semibold text-slate-700 transition hover:border-sky-500 hover:text-sky-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                Nhắn tin
              </button>
            </div>
          </div>
        </div>

      </div>
    </div>

    <!-- Appointment Booking Popup -->
    <AppointmentBookingPopup
      :visible="showAppointmentPopup"
      :listing-id="listing?.id"
      @close="showAppointmentPopup = false"
      @success="showAppointmentPopup = false"
    />
  </main>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, nextTick, watch } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import listingService from '@/services/listingService';
import AppointmentBookingPopup from '@/components/AppointmentBookingPopup.vue';
import { buildPropertyAddress, hydratePropertyAddress } from '@/utils/addressFormatter';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import realEstateLightStyle from '@/assets/maps/real-estate-light.json';

const route = useRoute();
const router = useRouter();

const props = defineProps({
  previewMode: {
    type: Boolean,
    default: false,
  },
  previewListing: {
    type: Object,
    default: null,
  },
});

const loading = ref(!props.previewMode);
const error = ref('');
const listing = ref(props.previewListing || {});

const activeImageIndex = ref(0);
const mapElement = ref(null);
const showAppointmentPopup = ref(false);
const mapMode = ref('standard');
const isMap3dEnabled = ref(false);
let map = null;

const SATELLITE_LAYER_ID = 'satellite-base';
const SATELLITE_SOURCE_ID = 'satellite';
const STANDARD_MAP_PITCH = 38;
const TOP_DOWN_MAP_PITCH = 0;
const SATELLITE_HIDDEN_BASE_LAYERS = [
  'landcover-park',
  'landuse-public',
  'water',
  'waterway',
  'aeroway',
  'building-footprints',
  'building-3d',
  'road-minor-casing',
  'road-minor',
  'road-major-casing',
  'road-major',
  'rail-transit',
  'place-label-city',
  'road-label',
];
let lastStandardCamera = null;

const displayImages = computed(() => {
  if (!listing.value?.images?.length) return [];
  // Sort by sort_order
  return [...listing.value.images].sort((a, b) => a.sort_order - b.sort_order);
});

const activeImage = computed(() => {
  if (!displayImages.value.length) return null;
  return displayImages.value[activeImageIndex.value]?.url;
});

const hasLatLng = computed(() => {
  const lat = listing.value?.property?.lat;
  const lng = listing.value?.property?.lng;
  return lat != null && lng != null && lat !== 0 && lng !== 0;
});

const fullAddress = computed(() => {
  if (!listing.value?.property) return '';
  const p = listing.value.property;
  return p.full_address || buildPropertyAddress(p);
});

function prevImage() {
  if (activeImageIndex.value > 0) {
    activeImageIndex.value--;
  } else {
    activeImageIndex.value = displayImages.value.length - 1;
  }
}

function nextImage() {
  if (activeImageIndex.value < displayImages.value.length - 1) {
    activeImageIndex.value++;
  } else {
    activeImageIndex.value = 0;
  }
}

function formatDate(isoString) {
  if (!isoString) return '';
  const d = new Date(isoString);
  return d.toLocaleDateString('vi-VN');
}

function formatPrice(value) {
  if (!value || value <= 0) return 'Thỏa thuận';
  if (value >= 1000000000) return (value / 1000000000).toLocaleString('vi-VN') + ' tỷ';
  if (value >= 1000000) return (value / 1000000).toLocaleString('vi-VN') + ' triệu';
  return value.toLocaleString('vi-VN') + ' đ';
}

function formatPhone(phone) {
  if (!phone) return '';
  return phone.replace(/(\d{3})(\d{3})(\d{4})/, '$1 $2 $3');
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
  return map[type] || type;
}

function directionLabel(code) {
  const map = {
    N: 'Bắc', NE: 'Đông Bắc', E: 'Đông', SE: 'Đông Nam',
    S: 'Nam', SW: 'Tây Nam', W: 'Tây', NW: 'Tây Bắc'
  };
  return map[code] || 'Không xác định';
}

function furnitureLabel(status) {
  const map = { NONE: 'Cơ bản', BASIC: 'Đầy đủ', FULL: 'Cao cấp' };
  return map[status] || status;
}

function legalPaperLabel(value) {
  const map = {
    LAND_USE_CERTIFICATE: 'Giấy CN QSDĐ - Sổ đỏ - Sổ hồng',
    SALE_CONTRACT: 'Hợp đồng mua bán',
    CAPITAL_CONTRIBUTION_CONTRACT: 'Hợp đồng góp vốn',
    ALLOTTED_OR_SUBDIVIDED_LAND: 'Đất giao - Đất phân',
    BORROWED_LAND: 'Đất mượn',
    LEASED_LAND: 'Đất thuê',
    ORIGIN_PROOF_DOCUMENT: 'Giấy tờ chứng minh nguồn gốc',
    NO_LAND_CERTIFICATE: 'Chưa làm giấy CN QSDĐ',
    PROCESSING_LAND_CERTIFICATE: 'Đang làm giấy CN QSDĐ',
    APPOINTMENT_FOR_CERTIFICATE: 'Đã có giấy hẹn lấy sổ',
    BUSINESS_TRANSFER: 'Sang nhượng doanh nghiệp',
    SHARE_TRANSFER: 'Mua bán cổ phần',
    INVESTMENT_COOPERATION: 'Hợp tác đầu tư',
    HANDWRITTEN: 'Viết tay',
  };
  return map[value] || value;
}

async function loadListing() {
  try {
    loading.value = true;
    error.value = '';
    const id = route.params.id;
    const response = await listingService.getById(id);
    const data = response.data.data;
    await hydratePropertyAddress(data?.property);
    listing.value = data;
  } catch (err) {
    console.error(err);
    error.value = 'Không tìm thấy tin đăng hoặc đã bị xóa.';
  } finally {
    loading.value = false;
    // Init map after loading is set to false so v-show renders the div
    if (hasLatLng.value) {
      await nextTick();
      // Give the DOM extra time to paint the map container
      setTimeout(() => initMap(), 200);
    }

    // View tracking: track after listing load and visibility check.
    if (listing.value?.id && listing.value?.status === 'ACTIVE') {
      scheduleViewTracking(listing.value.id);
    }
  }
}

watch(
  () => props.previewListing,
  async (value) => {
    if (!props.previewMode) return;
    listing.value = value || {};
    loading.value = false;
    error.value = '';
    activeImageIndex.value = 0;

    if (map) {
      map.remove();
      map = null;
      marker = null;
    }

    if (hasLatLng.value) {
      await nextTick();
      setTimeout(() => initMap(), 200);
    }
  },
  { deep: true, immediate: true },
);


/**
 * Schedule view tracking after the listing has loaded.
 * Protection:
 *   - Only track when the tab is visible.
 *   - Only track once per page load.
 *   - Silent fail so it does not affect UX.
 */
function scheduleViewTracking(listingId) {
  if (document.visibilityState !== 'visible') {
    const onVisible = () => {
      if (document.visibilityState === 'visible') {
        document.removeEventListener('visibilitychange', onVisible);
        trackViewNow(listingId);
      }
    };
    document.addEventListener('visibilitychange', onVisible);
    return;
  }

  trackViewNow(listingId);
}

function trackViewNow(listingId) {
  if (document.visibilityState !== 'visible') return;

  listingService.trackView(listingId).catch(() => {
    // View tracking should never affect the listing detail UX.
  });
}

function replaceMapTilerKey(value, key) {
  if (typeof value === 'string') {
    return value.replaceAll('{MAPTILER_KEY}', key);
  }

  if (Array.isArray(value)) {
    return value.map((item) => replaceMapTilerKey(item, key));
  }

  if (value && typeof value === 'object') {
    return Object.fromEntries(
      Object.entries(value).map(([entryKey, entryValue]) => [
        entryKey,
        replaceMapTilerKey(entryValue, key),
      ])
    );
  }

  return value;
}

function createPropertyFeature(lat, lng) {
  return {
    type: 'Feature',
    id: 'selected-property',
    properties: {
      label: 'BĐS đang xem',
      title: listing.value?.title || 'BĐS đang xem',
    },
    geometry: {
      type: 'Point',
      coordinates: [lng, lat],
    },
  };
}

function createRadiusFeature(lat, lng, radiusMeters = 800) {
  const steps = 96;
  const coordinates = [];
  const earthRadius = 6378137;
  const latRad = (lat * Math.PI) / 180;

  for (let i = 0; i <= steps; i += 1) {
    const angle = (i / steps) * Math.PI * 2;
    const dx = radiusMeters * Math.cos(angle);
    const dy = radiusMeters * Math.sin(angle);
    const pointLat = lat + (dy / earthRadius) * (180 / Math.PI);
    const pointLng = lng + (dx / (earthRadius * Math.cos(latRad))) * (180 / Math.PI);
    coordinates.push([pointLng, pointLat]);
  }

  return {
    type: 'Feature',
    properties: {
      radius_meters: radiusMeters,
      label: 'Khoảng 10 phút đi bộ',
    },
    geometry: {
      type: 'Polygon',
      coordinates: [coordinates],
    },
  };
}

function buildRealEstateMapStyle(lat, lng) {
  const mapTilerKey = import.meta.env.VITE_MAPTILER_KEY;
  const style = replaceMapTilerKey(realEstateLightStyle, mapTilerKey);
  const propertyFeature = createPropertyFeature(lat, lng);
  const radiusFeature = createRadiusFeature(lat, lng);

  style.center = [lng, lat];
  style.zoom = 15;
  style.pitch = 0;
  style.sources.property.data = {
    type: 'FeatureCollection',
    features: [propertyFeature],
  };
  style.sources['nearby-radius'].data = {
    type: 'FeatureCollection',
    features: [radiusFeature],
  };
  style.sources[SATELLITE_SOURCE_ID] = {
    type: 'raster',
    tiles: [
      `https://api.maptiler.com/maps/hybrid/256/{z}/{x}/{y}@2x.jpg?key=${mapTilerKey}`,
    ],
    tileSize: 256,
    maxzoom: 20,
  };
  style.layers.splice(1, 0, {
    id: SATELLITE_LAYER_ID,
    type: 'raster',
    source: SATELLITE_SOURCE_ID,
    layout: {
      visibility: 'none',
    },
    paint: {
      'raster-opacity': 0.92,
      'raster-saturation': -0.18,
      'raster-contrast': 0.08,
      'raster-brightness-min': 0.08,
      'raster-brightness-max': 0.96,
      'raster-resampling': 'linear',
      'raster-fade-duration': 0,
    },
  });

  return style;
}

function setMapMode(mode) {
  mapMode.value = mode;

  if (!map?.getLayer(SATELLITE_LAYER_ID)) return;

  const isSatellite = mode === 'satellite';
  const pitch = isSatellite || !isMap3dEnabled.value ? TOP_DOWN_MAP_PITCH : STANDARD_MAP_PITCH;

  map.setLayoutProperty(
    SATELLITE_LAYER_ID,
    'visibility',
    isSatellite ? 'visible' : 'none'
  );

  SATELLITE_HIDDEN_BASE_LAYERS.forEach((layerId) => {
    if (!map.getLayer(layerId)) return;
    map.setLayoutProperty(layerId, 'visibility', isSatellite ? 'none' : 'visible');
  });

  map.easeTo({
    pitch,
    duration: 450,
    essential: true,
  });
}

function toggleMapMode() {
  setMapMode(mapMode.value === 'satellite' ? 'standard' : 'satellite');
}

function toggleMap3d() {
  if (!map) return;

  isMap3dEnabled.value = !isMap3dEnabled.value;

  if (isMap3dEnabled.value && mapMode.value === 'satellite') {
    setMapMode('standard');
    return;
  }

  const camera = isMap3dEnabled.value
    ? {
        center: map.getCenter(),
        zoom: Math.max(map.getZoom(), 15.5),
        bearing: map.getBearing(),
        pitch: STANDARD_MAP_PITCH,
      }
    : {
        center: map.getCenter(),
        zoom: map.getZoom(),
        bearing: 0,
        pitch: TOP_DOWN_MAP_PITCH,
      };

  if (isMap3dEnabled.value) {
    lastStandardCamera = camera;
  }

  map.easeTo({
    ...camera,
    duration: 500,
    essential: true,
  });
}

function getCurrentCamera() {
  if (!map) return null;

  return {
    center: map.getCenter(),
    zoom: map.getZoom(),
    bearing: map.getBearing(),
    pitch: map.getPitch(),
  };
}

function toggleStandardMapPitch() {
  if (!map || mapMode.value === 'satellite') return false;

  const currentPitch = map.getPitch();
  const isTopDown = currentPitch <= 1;

  if (isTopDown) {
    isMap3dEnabled.value = true;

    const camera = lastStandardCamera || {
      center: map.getCenter(),
      zoom: map.getZoom(),
      bearing: map.getBearing(),
      pitch: STANDARD_MAP_PITCH,
    };

    map.easeTo({
      center: camera.center,
      zoom: camera.zoom,
      bearing: camera.bearing,
      pitch: camera.pitch > 1 ? camera.pitch : STANDARD_MAP_PITCH,
      duration: 450,
      essential: true,
    });
  } else {
    isMap3dEnabled.value = false;
    lastStandardCamera = getCurrentCamera();

    map.easeTo({
      pitch: TOP_DOWN_MAP_PITCH,
      bearing: 0,
      duration: 450,
      essential: true,
    });
  }

  return true;
}

function bindNavigationPitchToggle() {
  if (!mapElement.value) return;

  const compassButton = mapElement.value.querySelector('.maplibregl-ctrl-compass');
  if (!compassButton) return;

  compassButton.addEventListener(
    'click',
    (event) => {
      if (!toggleStandardMapPitch()) return;
      event.preventDefault();
      event.stopImmediatePropagation();
    },
    true,
  );
}

function initMap() {
  if (!mapElement.value || map) return;
  const lat = Number(listing.value.property.lat);
  const lng = Number(listing.value.property.lng);

  if (!Number.isFinite(lat) || !Number.isFinite(lng)) return;

  map = new maplibregl.Map({
    container: mapElement.value,
    style: buildRealEstateMapStyle(lat, lng),
    center: [lng, lat],
    zoom: 15,
    minZoom: 5,
    maxZoom: 20,
    pitch: 0,
    maxPitch: 55,
    antialias: true,
    cooperativeGestures: false,
  });

  map.addControl(
    new maplibregl.NavigationControl({
      visualizePitch: true,
    }),
    'top-right'
  );

  map.scrollZoom.enable();
  map.doubleClickZoom.enable();
  map.touchZoomRotate.enable();

  map.on('load', () => {
    setMapMode(mapMode.value);

    map.setFeatureState(
      {
        source: 'property',
        id: 'selected-property',
      },
      {
        selected: true,
      }
    );

    setTimeout(() => {
      if (!map) return;
      map.easeTo({
        center: [lng, lat],
        zoom: 15.5,
        pitch: isMap3dEnabled.value ? STANDARD_MAP_PITCH : TOP_DOWN_MAP_PITCH,
        duration: 900,
        essential: true,
      });
    }, 250);

    bindNavigationPitchToggle();
  });
}


onMounted(() => {
  loadListing();
});

onUnmounted(() => {
  if (map) {
    map.remove();
    map = null;
  }
});
</script>

<style scoped>
/* Custom styled scrollbars for the image strip */
.overflow-x-auto::-webkit-scrollbar {
  height: 6px;
}
.overflow-x-auto::-webkit-scrollbar-track {
  background: transparent;
}
.overflow-x-auto::-webkit-scrollbar-thumb {
  background-color: #cbd5e1;
  border-radius: 10px;
}
</style>
