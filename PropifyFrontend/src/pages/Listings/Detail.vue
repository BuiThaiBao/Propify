<template>
  <main :class="previewMode ? 'min-h-0 bg-[#f6f9fc] pb-6 pt-0' : 'min-h-screen bg-[#f6f9fc] pb-14 pt-24'">
    <div v-if="loading" class="flex min-h-[50vh] items-center justify-center">
      <div class="h-10 w-10 animate-spin rounded-full border-4 border-sky-500 border-t-transparent"></div>
    </div>

    <div v-else-if="error" class="mx-auto mt-10 max-w-[1120px] px-4 text-center text-red-500">
      <p class="text-xl font-bold">{{ error }}</p>
      <button class="mt-4 rounded-lg bg-sky-500 px-6 py-2 text-white" @click="router.push('/')">Về trang chủ</button>
    </div>

    <div v-else-if="listing" :class="previewMode ? 'mx-auto w-full max-w-[1280px] px-0' : 'mx-auto w-full max-w-[1280px] px-4 lg:px-8'">
      <div class="mb-5 flex items-start justify-between gap-4">
        <div class="min-w-0">
          <p class="flex items-center gap-2 text-xs text-slate-400">
            <button type="button" class="flex items-center gap-1 hover:text-sky-500" @click="router.back()">
              <span class="text-base leading-none">←</span>
              <span>Danh sách</span>
            </button>
            <span>/</span>
            <span class="truncate text-slate-600">{{ listing.title }}</span>
          </p>
          <h1 class="mt-3 text-[24px] font-extrabold leading-tight text-slate-900">{{ listing.title }}</h1>
          <p class="mt-1 flex items-center gap-2 text-xs text-slate-500">
            <span>Ngày đăng: {{ formatDate(listing.submitted_at || listing.created_at) }}</span>
            <span v-if="listing.views != null">• {{ listing.views }} lượt xem</span>
          </p>
        </div>
        <div class="hidden shrink-0 items-center gap-2 sm:flex">
          <button class="detail-icon-button" aria-label="Yêu thích">
            <img :src="favoriteIcon" class="h-4 w-4" alt="" />
          </button>
          <button class="detail-icon-button" aria-label="Chia sẻ">
            <img :src="shareIcon" class="h-4 w-4" alt="" />
          </button>
        </div>
      </div>

      <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,820px)_360px] xl:grid-cols-[minmax(0,860px)_370px]">
        <div class="min-w-0">

          <section v-if="displayImages.length" class="mb-3">
            <div class="relative overflow-hidden rounded-[14px] bg-slate-200">
              <img v-if="activeImage" :src="activeImage" class="aspect-[16/10] w-full object-cover" alt="Listing image" />
              <div class="absolute left-3 top-3 flex gap-2">
                <span v-if="listing.is_verified" class="rounded-full bg-emerald-500 px-3 py-1 text-xs font-semibold text-white">Đã xác thực</span>
                <span class="rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700">{{ propertyTypeLabel(listing.property?.type) }}</span>
              </div>
              <button v-if="displayImages.length > 1" class="absolute left-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-2xl text-white backdrop-blur hover:bg-black/45" @click="prevImage">‹</button>
              <button v-if="displayImages.length > 1" class="absolute right-4 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/35 text-2xl text-white backdrop-blur hover:bg-black/45" @click="nextImage">›</button>
              <div class="absolute bottom-3 left-3 rounded-full bg-slate-900/70 px-3 py-1 text-xs font-semibold text-white">
                Hình ảnh {{ activeImageIndex + 1 }}/{{ displayImages.length }}
              </div>
            </div>
            <div class="mt-2 flex gap-2 overflow-x-auto pb-1">
              <button
                v-for="(img, idx) in displayImages"
                :key="idx"
                class="h-[54px] w-[70px] shrink-0 overflow-hidden rounded-lg border bg-white transition"
                :class="activeImageIndex === idx ? 'border-sky-500 ring-2 ring-sky-100' : 'border-slate-200 opacity-80 hover:opacity-100'"
                @click="activeImageIndex = idx"
              >
                <img :src="img.url" class="h-full w-full object-cover" alt="" />
              </button>
            </div>
          </section>

          <section class="detail-card">
            <h2 class="detail-title">
              <img :src="legalIcon" class="detail-title-icon" alt="" />
              Mô tả tin đăng
            </h2>
            <p class="whitespace-pre-wrap text-sm leading-6 text-slate-600">{{ listing.description || 'Chưa có mô tả.' }}</p>
          </section>

          <section class="detail-card">
            <h2 class="detail-title">
              <img :src="propertyIcon" class="detail-title-icon" alt="" />
              Thông tin chi tiết
            </h2>
            <div class="grid gap-x-10 sm:grid-cols-2">
              <div v-for="item in detailRows" :key="item.label" class="flex items-center justify-between border-b border-slate-100 py-3 text-sm">
                <span class="flex min-w-0 items-center gap-2 text-slate-500">
                  <img :src="item.icon" class="h-4 w-4 shrink-0 object-contain opacity-75" alt="" />
                  <span class="truncate">{{ item.label }}</span>
                </span>
                <span class="ml-4 text-right font-medium text-slate-800">{{ item.value }}</span>
              </div>
            </div>
          </section>

          <section v-if="listing.property?.amenities?.length" class="detail-card">
            <h2 class="detail-title">
              <img :src="amenitiesIcon" class="detail-title-icon" alt="" />
              Tiện ích
            </h2>
            <div class="flex flex-wrap gap-2">
              <span v-for="amenity in listing.property.amenities" :key="amenity" class="rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-medium text-sky-600">
                {{ amenity }}
              </span>
            </div>
          </section>

          <section class="detail-card">
            <h2 class="detail-title">
              <img :src="pointIcon" class="detail-title-icon" alt="" />
              Vị trí bản đồ
            </h2>
            <p class="mb-3 text-xs text-slate-500">{{ fullAddress }}</p>
            <div class="relative h-[360px] w-full overflow-hidden rounded-xl bg-slate-100">
              <button v-if="hasLatLng" type="button" class="absolute left-3 top-3 z-10 rounded-lg border border-white/70 bg-white/95 px-3 py-2 text-xs font-semibold text-slate-700 shadow-sm" @click.stop="toggleMapMode">
                {{ mapMode === 'satellite' ? 'Bản đồ' : 'Vệ tinh' }}
              </button>
              <button v-if="hasLatLng" type="button" class="absolute left-3 top-[52px] z-10 rounded-lg border border-white/70 px-3 py-2 text-xs font-semibold shadow-sm" :class="isMap3dEnabled ? 'bg-sky-500/95 text-white' : 'bg-white/95 text-slate-700'" @click.stop="toggleMap3d">
                {{ isMap3dEnabled ? '2D' : '3D' }}
              </button>
              <div v-show="hasLatLng" ref="mapElement" class="h-full w-full"></div>
              <div v-if="!hasLatLng" class="flex h-full w-full flex-col items-center justify-center text-sm text-slate-400">
                <div class="mb-2 text-4xl">⌖</div>
                Bản đồ sẽ hiển thị tại đây
              </div>
            </div>
          </section>

          <section class="detail-card">
            <h2 class="detail-title">
              <img :src="flagIcon" class="detail-title-icon" alt="" />
              Báo cáo tin đăng
            </h2>
            <div class="grid gap-3 sm:grid-cols-2">
              <label v-for="option in reportOptions" :key="option" class="flex items-center gap-2 text-xs text-slate-500">
                <input type="radio" name="listing-report" class="h-3.5 w-3.5 accent-sky-500" />
                <span>{{ option }}</span>
              </label>
            </div>
            <button class="mt-4 rounded-lg border border-slate-200 bg-white px-4 py-2 text-xs font-semibold text-slate-600 hover:bg-slate-50">Gửi phản ánh</button>
          </section>
        </div>

        <aside class="space-y-5 lg:sticky lg:top-24">
          <section class="rounded-[14px] border border-slate-200 bg-white p-5 shadow-[0_10px_28px_rgba(15,23,42,0.04)]">
            <div class="border-b border-slate-100 pb-4">
              <h2 class="text-[26px] font-extrabold text-sky-500">
                {{ formatPrice(listing.property?.price) }}<span v-if="listing.demand_type === 'RENT'" class="text-sm font-medium text-slate-500">/tháng</span>
              </h2>
              <div class="mt-1.5 flex flex-wrap items-center gap-3 text-xs text-slate-500">
                <span class="flex items-center gap-1.5">
                  <img :src="bedIcon" class="h-3.5 w-3.5 object-contain opacity-70" alt="" />
                  {{ listing.property?.bedrooms || 0 }} PN
                </span>
                <span class="flex items-center gap-1.5">
                  <img :src="bathIcon" class="h-3.5 w-3.5 object-contain opacity-70" alt="" />
                  {{ listing.property?.bathrooms || 0 }} WC
                </span>
                <span class="flex items-center gap-1.5">
                  <img :src="areaIcon" class="h-3.5 w-3.5 object-contain opacity-70" alt="" />
                  {{ listing.property?.area || 0 }} m²
                </span>
              </div>
            </div>

            <div class="py-4 text-xs">
              <p class="mb-3 font-semibold text-slate-800">Thông tin của bất động sản</p>
              <div class="space-y-3">
                <div class="flex justify-between gap-4"><span class="text-slate-400">Loại BĐS</span><span class="text-slate-700">{{ propertyTypeLabel(listing.property?.type) }}</span></div>
                <div class="flex justify-between gap-4"><span class="text-slate-400">Địa chỉ</span><span class="truncate text-sky-500">{{ fullAddress }}</span></div>
              </div>
            </div>

            <div class="mb-4 flex items-center gap-3 rounded-xl bg-slate-50 p-3">
              <div class="flex h-10 w-10 items-center justify-center rounded-full bg-sky-500 font-bold text-white">
                {{ (listing.owner?.full_name || listing.property?.contact_name || 'U').charAt(0).toUpperCase() }}
              </div>
              <div class="min-w-0">
                <p class="truncate text-sm font-semibold text-slate-800">{{ listing.property?.contact_name || listing.owner?.full_name }}</p>
                <p class="text-xs text-slate-500">{{ listing.property?.poster_type === 'OWNER' ? 'Chủ nhà' : 'Môi giới' }}</p>
              </div>
              <button class="ml-auto flex h-8 w-8 items-center justify-center rounded-full bg-sky-100" aria-label="Nhắn tin chủ nhà">
                <img :src="messagesIcon" class="h-4 w-4" alt="" />
              </button>
            </div>

            <div class="space-y-2">
              <a v-if="listing.property?.contact_phone" :href="`tel:${listing.property.contact_phone}`" class="flex w-full items-center justify-center rounded-lg bg-sky-500 px-4 py-2.5 text-sm font-semibold text-white hover:bg-sky-600">
                <img :src="callIcon" class="detail-action-icon mr-2 h-3.5 w-3.5" alt="" />
                Liên hệ chủ nhà
              </a>
              <button v-if="!previewMode" class="flex w-full items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-sky-300 hover:text-sky-600" @click="showAppointmentPopup = true">
                <img :src="calendarIcon" class="mr-2 h-3.5 w-3.5" alt="" />
                Đặt lịch xem nhà
              </button>
              <button class="flex w-full items-center justify-center rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:border-sky-300 hover:text-sky-600">
                <img :src="chatIcon" class="mr-2 h-3.5 w-3.5" alt="" />
                Nhắn tin
              </button>
            </div>
          </section>

          <section v-if="relatedListings.length" class="rounded-[14px] border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-lg font-bold text-slate-800">Tin đăng liên quan</h2>
            <div class="space-y-4">
              <article v-for="item in relatedListings" :key="item.id" class="flex gap-3">
                <img :src="item.image" class="h-[86px] w-[116px] rounded-lg object-cover" alt="" />
                <div class="min-w-0 flex-1">
                  <h3 class="line-clamp-2 text-sm font-bold text-slate-800">{{ item.title }}</h3>
                  <p class="mt-1 text-xs text-slate-500">{{ item.address }}</p>
                  <div class="mt-1 flex items-center gap-2 text-[11px] text-slate-500">
                    <span class="flex items-center gap-1">
                      <img :src="bedIcon" class="h-3 w-3 object-contain opacity-70" alt="" />
                      {{ item.bedrooms }} PN
                    </span>
                    <span class="flex items-center gap-1">
                      <img :src="bathIcon" class="h-3 w-3 object-contain opacity-70" alt="" />
                      {{ item.bathrooms }} WC
                    </span>
                    <span class="flex items-center gap-1">
                      <img :src="areaIcon" class="h-3 w-3 object-contain opacity-70" alt="" />
                      {{ item.area }} m²
                    </span>
                  </div>
                  <p class="mt-1 text-sm font-bold text-sky-500">{{ item.price }}</p>
                  <p class="mt-1 text-xs text-slate-500">{{ item.owner }}</p>
                </div>
              </article>
            </div>
          </section>
        </aside>
      </div>
    </div>

    <AppointmentBookingPopup
      v-if="!previewMode"
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
import AppointmentBookingPopup from '@/components/appointments/AppointmentBookingPopup.vue';
import { buildPropertyAddress, hydratePropertyAddress } from '@/utils/addressFormatter';
import * as L from 'leaflet';
import 'leaflet/dist/leaflet.css';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import realEstateLightStyle from '@/assets/maps/real-estate-light.json';
import favoriteIcon from '@/assets/images/details/favorite.png';
import shareIcon from '@/assets/images/details/share.png';
import callIcon from '@/assets/images/details/call.png';
import calendarIcon from '@/assets/images/details/calander.png';
import chatIcon from '@/assets/images/details/chat.png';
import messagesIcon from '@/assets/images/details/messages.png';
import propertyIcon from '@/assets/images/details/loaibds.png';
import amenitiesIcon from '@/assets/images/details/tienich.png';
import pointIcon from '@/assets/images/details/point.png';
import flagIcon from '@/assets/images/details/flag.png';
import legalIcon from '@/assets/images/details/phaply.png';
import bedIcon from '@/assets/images/details/giuong.png';
import bathIcon from '@/assets/images/details/bontam.png';
import areaIcon from '@/assets/images/details/shape.png';
import directionIcon from '@/assets/images/details/huongnha.png';
import interiorIcon from '@/assets/images/details/noithat.png';
import roadIcon from '@/assets/images/details/duongrongvachieusau.png';
import floorIcon from '@/assets/images/details/sotang.png';

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

const detailRows = computed(() => {
  const property = listing.value?.property || {};
  const rows = [
    { label: 'Loại BĐS', value: property.type ? propertyTypeLabel(property.type) : null, icon: propertyIcon },
    { label: 'Diện tích', value: property.area ? `${property.area} m²` : null, icon: areaIcon },
    { label: 'Phòng ngủ', value: property.bedrooms ? `${property.bedrooms} PN` : null, icon: bedIcon },
    { label: 'Phòng tắm', value: property.bathrooms ? `${property.bathrooms} WC` : null, icon: bathIcon },
    { label: 'Hướng nhà', value: property.direction_code ? directionLabel(property.direction_code) : null, icon: directionIcon },
    { label: 'Hướng ban công', value: property.balcony_direction_code ? directionLabel(property.balcony_direction_code) : null, icon: directionIcon },
    { label: 'Nội thất', value: property.furniture_status ? furnitureLabel(property.furniture_status) : null, icon: interiorIcon },
    { label: 'Pháp lý', value: property.legal_paper_types?.length ? property.legal_paper_types.map((v) => legalPaperLabel(v)).join(', ') : null, icon: legalIcon },
    { label: 'Đường rộng', value: property.road_width ? `${property.road_width} m` : null, icon: roadIcon },
    { label: 'Mặt tiền', value: property.facade_width ? `${property.facade_width} m` : null, icon: roadIcon },
    { label: 'Chiều sâu', value: property.depth ? `${property.depth} m` : null, icon: roadIcon },
    { label: 'Số tầng', value: property.floors ? property.floors : null, icon: floorIcon },
  ];

  return rows
    .filter((item) => item.value !== null && item.value !== undefined && item.value !== '');
});

const reportOptions = [
  'Định giá chưa đúng với thực tế',
  'Địa chỉ của BĐS chưa chính xác',
  'BĐS đã bán/đã thuê/đã sang nhượng',
  'Thông tin chưa chính xác',
  'Không liên lạc được với người đăng tin',
  'Trùng với tin rao khác',
];

const relatedListings = computed(() => {
  if (!displayImages.value.length) return [];
  const image = displayImages.value[0]?.url;

  return Array.from({ length: 4 }, (_, index) => ({
    id: `${listing.value?.id || 'preview'}-${index}`,
    image,
    title: listing.value?.title || 'Tin đăng liên quan',
    address: fullAddress.value || 'Khu vực lân cận',
    price: formatPrice(listing.value?.property?.price),
    owner: listing.value?.owner?.full_name || listing.value?.property?.contact_name || 'Người đăng',
    bedrooms: listing.value?.property?.bedrooms || 0,
    bathrooms: listing.value?.property?.bathrooms || 0,
    area: listing.value?.property?.area || 0,
  }));
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
  if (props.previewMode) {
    loading.value = false;
    if (hasLatLng.value) {
      nextTick(() => setTimeout(() => initMap(), 200));
    }
    return;
  }

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
.detail-card {
  margin-top: 18px;
  border: 1px solid #e2e8f0;
  border-radius: 14px;
  background: #fff;
  padding: 20px;
  box-shadow: 0 10px 28px rgba(15, 23, 42, 0.035);
}

.detail-title {
  margin-bottom: 14px;
  display: flex;
  align-items: center;
  gap: 8px;
  color: #1e293b;
  font-size: 16px;
  font-weight: 800;
}

.detail-title-icon {
  height: 18px;
  width: 18px;
  object-fit: contain;
}

.detail-icon-button {
  display: flex;
  height: 36px;
  width: 36px;
  align-items: center;
  justify-content: center;
  border: 1px solid #e2e8f0;
  border-radius: 9999px;
  background: #fff;
  color: #64748b;
  transition: border-color 0.2s ease, box-shadow 0.2s ease, transform 0.2s ease;
}

.detail-icon-button:hover {
  border-color: #bae6fd;
  box-shadow: 0 8px 18px rgba(14, 165, 233, 0.12);
  transform: translateY(-1px);
}

.detail-action-icon {
  filter: brightness(0) invert(1);
}

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
