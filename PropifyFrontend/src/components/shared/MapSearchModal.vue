<template>
  <div v-if="open" class="fixed top-16 bottom-0 left-0 right-0 z-40 bg-white overflow-hidden">
    <!-- Map Canvas Container (Slides to the right when sidebar is open) -->
    <div
      class="absolute inset-0 w-full h-full bg-slate-50 z-0 transition-transform duration-300 ease-in-out"
      :class="isSidebarOpen ? 'translate-x-0 md:translate-x-[380px]' : 'translate-x-0'"
    >
      <div ref="mapEl" class="h-full w-full"></div>
    </div>

    <!-- Close Button (Static relative to screen) -->
    <button
      class="absolute top-4 z-20 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-md hover:bg-slate-50 transition-all duration-300"
      :class="selectedListing ? 'right-[540px]' : 'right-4'"
      @click="$emit('close')"
    >
      Đóng bản đồ
    </button>

    <!-- Details Aside Panel (Static relative to screen, slides on top of map from right) -->
    <aside
      class="absolute right-0 top-0 bottom-0 z-30 hidden w-full max-w-[520px] overflow-y-auto border-l border-slate-200 bg-white shadow-2xl transition-transform duration-300 md:block"
      :class="selectedListing ? 'translate-x-0' : 'translate-x-full'"
    >
      <div v-if="selectedListing" class="flex h-full flex-col">
        <div class="flex items-center justify-between border-b border-slate-200 p-3">
          <h3 class="text-lg font-bold text-slate-900">Chi tiết tin đăng</h3>
          <button
            class="rounded border border-slate-200 px-2 py-1 text-sm text-slate-600 hover:bg-slate-100"
            @click="selectedListing = null"
          >
            X
          </button>
        </div>
        <iframe
          :src="`/listings/${selectedListing.id}`"
          class="h-full w-full border-0"
          title="Chi tiet tin dang"
        />
      </div>
    </aside>

    <!-- Left Sidebar: Listings list (Rendered third with z-10 overlay) -->
    <aside
      class="absolute top-0 bottom-0 left-0 w-full md:w-[380px] z-10 border-r border-slate-200 bg-slate-50 flex flex-col h-full transition-transform duration-300 ease-in-out shadow-lg"
      :class="isSidebarOpen ? 'translate-x-0' : '-translate-x-full'"
    >
      <!-- Title & Search Header -->
      <div class="p-5 bg-white border-b border-slate-100 shrink-0 flex flex-col gap-4">
        <!-- Title & Subtitle -->
        <div>
          <h1 class="text-[17px] font-bold text-slate-900 leading-snug mb-1">
            {{ headerTitle }}
          </h1>
          <p class="text-xs text-slate-500">
            Hiện có <span class="font-bold text-slate-800">{{ filteredVisibleListings.length.toLocaleString('vi-VN') }}</span> bất động sản
          </p>
        </div>

        <!-- Local Filters (Tabs) -->
        <div class="flex items-center gap-2">
          <button
            @click="localPosterType = 'ALL'"
            type="button"
            class="py-1.5 px-4 text-xs font-bold rounded-lg transition duration-200"
            :class="localPosterType === 'ALL' ? 'bg-[#1E6BFE] text-white' : 'bg-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
          >
            Tất cả
          </button>
          <button
            @click="localPosterType = 'BROKER'"
            type="button"
            class="py-1.5 px-4 text-xs font-bold rounded-lg transition duration-200"
            :class="localPosterType === 'BROKER' ? 'bg-[#1E6BFE] text-white' : 'bg-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
          >
            Môi giới
          </button>
          <button
            @click="localPosterType = 'OWNER'"
            type="button"
            class="py-1.5 px-4 text-xs font-bold rounded-lg transition duration-200"
            :class="localPosterType === 'OWNER' ? 'bg-[#1E6BFE] text-white' : 'bg-transparent text-slate-600 hover:bg-slate-50 hover:text-slate-900'"
          >
            Chính chủ
          </button>
        </div>

        <!-- Sorting Selector -->
        <div id="sort-dropdown-wrapper" class="flex justify-end relative">
          <button
            @click.stop="showSortDropdown = !showSortDropdown"
            type="button"
            class="flex items-center gap-1.5 text-xs text-slate-500 hover:text-slate-800 transition font-medium py-1"
          >
            <span>Sắp xếp:</span>
            <span class="text-[#1E6BFE] font-bold">{{ currentSortLabel }}</span>
            <svg
              xmlns="http://www.w3.org/2000/svg"
              class="w-3.5 h-3.5 text-[#1E6BFE] transition-transform duration-200"
              :class="{ 'rotate-180': showSortDropdown }"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
              stroke-width="2.5"
            >
              <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
            </svg>
          </button>

          <!-- Dropdown Menu -->
          <div
            v-if="showSortDropdown"
            class="absolute right-0 top-8 w-56 bg-white border border-slate-200/70 rounded-2xl shadow-xl py-2 z-50 animate-in fade-in slide-in-from-top-1 duration-200"
          >
            <button
              v-for="opt in sortOptions"
              :key="opt.value"
              @click="selectSort(opt.value)"
              type="button"
              class="flex items-center justify-between w-full px-4 py-2.5 text-xs font-semibold transition"
              :class="sortBy === opt.value ? 'text-[#1E6BFE] hover:bg-sky-50/20' : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800'"
            >
              <span>{{ opt.label }}</span>
              <svg
                v-if="sortBy === opt.value"
                xmlns="http://www.w3.org/2000/svg"
                class="w-4 h-4 text-[#1E6BFE]"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
                stroke-width="2.5"
              >
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Listings List -->
      <div class="flex-1 overflow-y-auto p-3 space-y-3 scrollbar-thin">
        <div v-if="filteredVisibleListings.length === 0" class="flex flex-col items-center justify-center h-48 text-slate-400 text-xs">
          <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mb-2 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          Không có tin đăng trong vùng này
        </div>
        <div
          v-for="item in filteredVisibleListings"
          :key="item.id"
          @click="selectListingCard(item)"
          class="bg-white border rounded-xl p-3 flex gap-3 shadow-sm hover:shadow-md hover:border-sky-300 transition duration-200 cursor-pointer group"
          :class="selectedListing?.id === item.id ? 'border-sky-500 ring-1 ring-sky-500/20' : 'border-slate-200'"
        >
          <img
            :src="item.thumbnail || 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=300'"
            class="w-[110px] h-[82px] object-cover rounded-lg shrink-0 bg-slate-100"
            alt="Listing thumbnail"
          />
          <div class="flex-1 min-w-0 flex flex-col justify-between">
            <div>
              <h4 class="font-bold text-[13px] line-clamp-2 text-slate-900 group-hover:text-sky-600 transition leading-tight mb-2">
                {{ item.title }}
              </h4>
              <div class="flex items-center gap-2.5 text-slate-500 text-[11px] mb-1.5 flex-wrap">
                <span class="inline-flex items-center gap-1">
                  <img :src="areaIcon" class="w-3 h-3 opacity-60 object-contain" alt="" />
                  {{ Number(item.area || 0).toLocaleString('vi-VN') }} m²
                </span>
                <span class="inline-flex items-center gap-1">
                  <img :src="bedIcon" class="w-3 h-3 opacity-60 object-contain" alt="" />
                  {{ item.bedrooms || 0 }} PN
                </span>
                <span class="inline-flex items-center gap-1">
                  <img :src="bathIcon" class="w-3 h-3 opacity-60 object-contain" alt="" />
                  {{ item.bathrooms || 0 }} WC
                </span>
              </div>
            </div>
            <div class="text-[#0DA2E7] font-extrabold text-[13px]">
              {{ formatPrice(item.price) }}
            </div>
          </div>
        </div>
      </div>

      <!-- Toggle Sidebar Button (Desktop only) -->
      <button
        @click="isSidebarOpen = !isSidebarOpen"
        type="button"
        class="hidden md:flex absolute top-32 z-30 w-10 h-10 bg-white border border-slate-200 border-l-0 rounded-r-xl shadow-md hover:bg-slate-50 items-center justify-center translate-x-full right-0 transition duration-200"
      >
        <svg
          v-if="isSidebarOpen"
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5 text-slate-700"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2.5"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7" />
        </svg>
        <svg
          v-else
          xmlns="http://www.w3.org/2000/svg"
          class="w-5 h-5 text-slate-700"
          fill="none"
          viewBox="0 0 24 24"
          stroke="currentColor"
          stroke-width="2.5"
        >
          <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
        </svg>
      </button>
    </aside>
  </div>
</template>

<script setup>
import { nextTick, onMounted, onBeforeUnmount, ref, watch, computed } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import realEstateLightStyle from '@/assets/maps/real-estate-light.json';
import listingService from '@/services/listingService';
import bedIcon from '@/assets/images/details/giuong.png';
import bathIcon from '@/assets/images/details/bontam.png';
import areaIcon from '@/assets/images/details/shape.png';

const props = defineProps({ open: Boolean, filters: { type: Object, default: () => ({}) } });
defineEmits(['close']);

const mapEl = ref(null);
let map = null;
let activeMarkers = [];
let activePopups = [];
let items = [];
const selectedListing = ref(null);

const currentBounds = ref(null);
const localPosterType = ref('ALL'); // 'ALL', 'OWNER', 'BROKER'
const sortBy = ref('DEFAULT');
const showSortDropdown = ref(false);
const isSidebarOpen = ref(true);

const sortOptions = [
  { value: 'DEFAULT', label: 'Thông thường' },
  { value: 'NEWEST', label: 'Tin đăng mới nhất' },
  { value: 'OLDEST', label: 'Tin đăng cũ nhất' },
  { value: 'PRICE_ASC', label: 'Giá tăng dần' },
  { value: 'PRICE_DESC', label: 'Giá giảm dần' },
  { value: 'AREA_ASC', label: 'Diện tích tăng dần' },
  { value: 'AREA_DESC', label: 'Diện tích giảm dần' }
];

const currentSortLabel = computed(() => {
  return sortOptions.find(o => o.value === sortBy.value)?.label || 'Thông thường';
});

const currentMonthYear = computed(() => {
  const now = new Date();
  return `${now.getMonth() + 1}/${now.getFullYear()}`;
});

const headerTitle = computed(() => {
  const timeStr = currentMonthYear.value;
  if (props.filters?.demand_type === 'RENT') {
    return `Cho thuê chung cư giá rẻ tại Việt Nam (${timeStr})`;
  } else {
    return `Mua bán căn hộ chung cư giá rẻ tại Việt Nam (${timeStr})`;
  }
});

function selectSort(val) {
  sortBy.value = val;
  showSortDropdown.value = false;
}

const closeDropdown = (e) => {
  if (showSortDropdown.value) {
    const wrapper = document.getElementById('sort-dropdown-wrapper');
    if (wrapper && !wrapper.contains(e.target)) {
      showSortDropdown.value = false;
    }
  }
};

onMounted(() => {
  window.addEventListener('click', closeDropdown);
});

const visibleListings = computed(() => {
  if (!currentBounds.value || !items.length) return [];
  const bounds = currentBounds.value;
  const north = bounds.getNorth();
  const south = bounds.getSouth();
  const east = bounds.getEast();
  const west = bounds.getWest();

  return items.filter((item) => {
    const lat = Number(item.latitude);
    const lng = Number(item.longitude);
    return lat >= south && lat <= north && lng >= west && lng <= east;
  });
});

const filteredVisibleListings = computed(() => {
  let list = visibleListings.value;

  if (localPosterType.value !== 'ALL') {
    list = list.filter((item) => item.poster_type === localPosterType.value);
  }

  const sortedList = [...list];
  if (sortBy.value === 'NEWEST') {
    sortedList.sort((a, b) => b.id - a.id);
  } else if (sortBy.value === 'OLDEST') {
    sortedList.sort((a, b) => a.id - b.id);
  } else if (sortBy.value === 'PRICE_ASC') {
    sortedList.sort((a, b) => {
      const pA = Number(a.price) || 0;
      const pB = Number(b.price) || 0;
      return pA - pB;
    });
  } else if (sortBy.value === 'PRICE_DESC') {
    sortedList.sort((a, b) => {
      const pA = Number(a.price) || 0;
      const pB = Number(b.price) || 0;
      return pB - pA;
    });
  } else if (sortBy.value === 'AREA_ASC') {
    sortedList.sort((a, b) => {
      const aA = Number(a.area) || 0;
      const aB = Number(b.area) || 0;
      return aA - aB;
    });
  } else if (sortBy.value === 'AREA_DESC') {
    sortedList.sort((a, b) => {
      const aA = Number(a.area) || 0;
      const aB = Number(b.area) || 0;
      return aB - aA;
    });
  }

  return sortedList;
});

function selectListingCard(item) {
  selectedListing.value = item;
  if (map) {
    map.easeTo({
      center: [Number(item.longitude), Number(item.latitude)],
      zoom: 16,
      duration: 800,
    });
  }
}

function formatPrice(value) {
  const num = Number(value || 0);
  if (!num || num <= 0) return 'Thương lượng';
  if (num >= 1000000000) return `${(num / 1000000000).toLocaleString('vi-VN')} tỷ`;
  if (num >= 1000000) return `${(num / 1000000).toLocaleString('vi-VN')} triệu`;
  return `${num.toLocaleString('vi-VN')} đ`;
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

function buildMapStyle() {
  const mapTilerKey = import.meta.env.VITE_MAPTILER_KEY;
  const style = replaceMapTilerKey(realEstateLightStyle, mapTilerKey);
  
  // Set empty data to prevent missing source data errors
  style.sources.property.data = {
    type: 'FeatureCollection',
    features: [],
  };
  style.sources['nearby-radius'].data = {
    type: 'FeatureCollection',
    features: [],
  };
  return style;
}

function toGridClusters(points, pixelSize = 60) {
  const grid = new Map();
  for (const point of points) {
    const layerPoint = map.project([point.longitude, point.latitude]);
    const keyX = Math.floor(layerPoint.x / pixelSize);
    const keyY = Math.floor(layerPoint.y / pixelSize);
    const key = `${keyX}:${keyY}`;
    if (!grid.has(key)) grid.set(key, []);
    grid.get(key).push({ ...point, __x: layerPoint.x, __y: layerPoint.y });
  }
  return [...grid.values()].map((group) => {
    const centerX = group.reduce((sum, p) => sum + p.__x, 0) / group.length;
    const centerY = group.reduce((sum, p) => sum + p.__y, 0) / group.length;
    return { points: group, center: map.unproject([centerX, centerY]) };
  });
}

function singleHtml() {
  return `<div style="background:#1e293b;color:#fff;border-radius:999px;padding:6px 12px;font-weight:700;font-size:13px;box-shadow:0 4px 12px rgba(15,23,42,0.25);border:2px solid #fff;">1</div>`;
}

function clusterHtml(count) {
  const logCount = Math.log10(count);
  const size = Math.round(Math.min(Math.max(38 + logCount * 16, 38), 85));
  const fontSize = Math.round(Math.min(Math.max(12 + logCount * 1.5, 12), 16));
  
  return `<div style="width:${size}px;height:${size}px;background:#1e293b;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:${fontSize}px;box-shadow:0 4px 12px rgba(15,23,42,0.25);border:2px solid #fff;">${count.toLocaleString('vi-VN')}</div>`;
}

function popupHtml(point) {
  return `<div style="width:280px;display:flex;gap:12px;font-family:'Inter',sans-serif;padding:2px;">
    <img src="${point.thumbnail || ''}" style="width:110px;height:82px;object-fit:cover;border-radius:8px;background:#e5e7eb;" />
    <div style="flex:1;min-width:0;display:flex;flex-direction:column;justify-content:space-between;">
      <div>
        <div style="font-weight:700;font-size:13px;line-height:1.4;color:#1e293b;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;text-overflow:ellipsis;">
          ${point.title || ''}
        </div>
        <div style="display:flex;align-items:center;gap:10px;color:#64748b;font-size:12px;margin-bottom:8px;">
          <span style="display:inline-flex;align-items:center;gap:3px;">
            <img src="${areaIcon}" style="width:12px;height:12px;opacity:0.65;object-fit:contain;" />
            ${Number(point.area || 0).toLocaleString('vi-VN')} m²
          </span>
          <span style="display:inline-flex;align-items:center;gap:3px;">
            <img src="${bedIcon}" style="width:12px;height:12px;opacity:0.65;object-fit:contain;" />
            ${point.bedrooms || 0}
          </span>
          <span style="display:inline-flex;align-items:center;gap:3px;">
            <img src="${bathIcon}" style="width:12px;height:12px;opacity:0.65;object-fit:contain;" />
            ${point.bathrooms || 0}
          </span>
        </div>
      </div>
      <div style="color:#0da2e7;font-weight:800;font-size:14px;">
        ${formatPrice(point.price)}
      </div>
    </div>
  </div>`;
}

function clearMarkers() {
  for (const marker of activeMarkers) {
    marker.remove();
  }
  activeMarkers = [];
  for (const popup of activePopups) {
    popup.remove();
  }
  activePopups = [];
}

function renderMarkers() {
  if (!map) return;
  clearMarkers();
  
  const clusters = toGridClusters(items, 60);
  for (const cluster of clusters) {
    const count = cluster.points.length;
    
    const el = document.createElement('div');
    el.style.cursor = 'pointer';
    el.innerHTML = count === 1 ? singleHtml() : clusterHtml(count);
    
    const centerCoords = count === 1 
      ? [cluster.points[0].longitude, cluster.points[0].latitude] 
      : [cluster.center.lng, cluster.center.lat];
      
    const marker = new maplibregl.Marker({ element: el })
      .setLngLat(centerCoords)
      .addTo(map);
      
    activeMarkers.push(marker);

    if (count > 1) {
      el.addEventListener('click', () => {
        map.easeTo({
          center: cluster.center,
          zoom: Math.min(map.getZoom() + 2, 20),
          duration: 450,
        });
      });
    } else {
      const point = cluster.points[0];
      
      const popup = new maplibregl.Popup({
        closeButton: false,
        closeOnClick: false,
        offset: [0, -10],
        maxWidth: '300px',
      }).setHTML(popupHtml(point));

      activePopups.push(popup);

      el.addEventListener('mouseenter', () => {
        popup.setLngLat([point.longitude, point.latitude]).addTo(map);
      });

      el.addEventListener('mouseleave', () => {
        popup.remove();
      });
      
      el.addEventListener('click', () => {
        selectedListing.value = point;
      });
    }
  }
}

async function loadMapData() {
  const res = await listingService.getMapListings(props.filters);
  const rawItems = res?.data?.data?.data ?? res?.data?.data ?? [];
  items = rawItems.filter((x) => x.latitude !== null && x.longitude !== null);
  renderMarkers();
  if (map) {
    currentBounds.value = map.getBounds();
  }
}

watch(() => props.open, async (isOpen) => {
  if (!isOpen) {
    selectedListing.value = null;
    currentBounds.value = null;
    localPosterType.value = 'ALL';
    sortBy.value = 'DEFAULT';
    showSortDropdown.value = false;
    isSidebarOpen.value = true;
    if (map) {
      map.remove();
      map = null;
      clearMarkers();
    }
    return;
  }
  await nextTick();
  if (!map) {
    map = new maplibregl.Map({
      container: mapEl.value,
      style: buildMapStyle(),
      center: [106.4, 16.5],
      zoom: 5.3,
      minZoom: 4.5,
      maxZoom: 20,
    });
    map.on('load', async () => {
      map.on('zoomend', () => {
        renderMarkers();
        currentBounds.value = map.getBounds();
      });
      map.on('moveend', () => {
        renderMarkers();
        currentBounds.value = map.getBounds();
      });
      await loadMapData();
      currentBounds.value = map.getBounds();
    });
  } else {
    map.resize();
    await loadMapData();
    currentBounds.value = map.getBounds();
  }
});

// Resizing watchers are removed because the absolute-positioned map container's layout size never changes when sidebars open/close.

watch(() => props.filters, async () => {
  if (props.open) await loadMapData();
}, { deep: true });

onBeforeUnmount(() => {
  window.removeEventListener('click', closeDropdown);
  if (map) map.remove();
});
</script>
