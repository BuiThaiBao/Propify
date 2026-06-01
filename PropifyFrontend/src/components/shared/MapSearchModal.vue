<template>
  <div v-if="open" class="fixed top-16 bottom-0 left-0 right-0 z-40 bg-white">
    <div class="relative h-full w-full overflow-hidden bg-white">
      <button
        class="absolute top-4 z-[1000] rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-md hover:bg-slate-50 transition-all duration-300"
        :class="selectedListing ? 'right-[540px]' : 'right-4'"
        @click="$emit('close')"
      >
        Đóng bản đồ
      </button>
      <div class="h-full w-full">
        <div ref="mapEl" class="h-full w-full"></div>
        <aside
          class="absolute right-0 top-0 z-[950] hidden h-full w-full max-w-[520px] overflow-y-auto border-l border-slate-200 bg-white shadow-2xl transition-transform duration-300 md:block"
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
      </div>
    </div>
  </div>
</template>

<script setup>
import { nextTick, onBeforeUnmount, ref, watch } from 'vue';
import maplibregl from 'maplibre-gl';
import 'maplibre-gl/dist/maplibre-gl.css';
import realEstateLightStyle from '@/assets/maps/real-estate-light.json';
import listingService from '@/services/listingService';

const props = defineProps({ open: Boolean, filters: { type: Object, default: () => ({}) } });
defineEmits(['close']);

const mapEl = ref(null);
let map = null;
let activeMarkers = [];
let items = [];
const selectedListing = ref(null);

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
  return `<div style="background:#0da2e7;color:#fff;border-radius:999px;padding:6px 12px;font-weight:700;font-size:13px;box-shadow:0 4px 10px rgba(13,162,231,0.3);border:2px solid #fff;">1</div>`;
}

function clusterHtml(count) {
  const size = count < 10 ? 36 : count < 100 ? 42 : 48;
  return `<div style="width:${size}px;height:${size}px;background:#1e293b;color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:13px;box-shadow:0 4px 12px rgba(15,23,42,0.25);border:2px solid #fff;">${count.toLocaleString('vi-VN')}</div>`;
}

function popupHtml(point) {
  return `<div style="width:260px;display:flex;gap:10px;">
    <img src="${point.thumbnail || ''}" style="width:110px;height:78px;object-fit:cover;border-radius:6px;background:#e5e7eb;" />
    <div style="font-size:14px;line-height:1.3;">
      <div style="font-weight:700;margin-bottom:4px;color:#1e293b;">${point.title || ''}</div>
      <div style="color:#0da2e7;font-weight:700;margin-bottom:4px;">${formatPrice(point.price)}</div>
      <div style="color:#64748b;">${Number(point.area || 0).toLocaleString('vi-VN')} m²</div>
    </div>
  </div>`;
}

function clearMarkers() {
  for (const marker of activeMarkers) {
    marker.remove();
  }
  activeMarkers = [];
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
}

watch(() => props.open, async (isOpen) => {
  if (!isOpen) {
    selectedListing.value = null;
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
      center: [108.2068, 16.0471],
      zoom: 6,
      minZoom: 5,
      maxZoom: 20,
    });
    map.on('zoomend', renderMarkers);
    map.on('moveend', renderMarkers);
  }
  map.resize();
  await loadMapData();
});

watch(selectedListing, () => {
  if (map) {
    map.resize();
    setTimeout(() => {
      if (map) map.resize();
    }, 350);
  }
});

watch(() => props.filters, async () => {
  if (props.open) await loadMapData();
}, { deep: true });

onBeforeUnmount(() => {
  if (map) map.remove();
});
</script>
