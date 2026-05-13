<template>
  <div class="min-h-screen bg-white text-slate-950">
    <section class="px-4 pb-8 pt-20 text-center sm:pt-24">
      <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-extrabold leading-tight text-slate-950 sm:text-4xl">
          Bảng giá đăng tin
        </h1>
        <p class="mt-3 text-sm font-medium text-slate-500 sm:text-base">
          Lựa chọn gói tin phù hợp để tăng hiệu quả tiếp cận khách hàng
        </p>
      </div>
    </section>

    <div v-if="loading" class="flex flex-col items-center gap-3 px-6 py-16 text-sm text-slate-500">
      <div class="h-9 w-9 animate-spin rounded-full border-3 border-slate-200 border-t-primary"></div>
      <p>Đang tải bảng giá...</p>
    </div>

    <div v-else-if="error" class="flex flex-col items-center gap-4 px-6 py-16 text-sm text-red-600">
      <p>{{ error }}</p>
      <button
        type="button"
        class="rounded-lg bg-primary px-5 py-2 text-sm font-bold text-white transition hover:bg-primary/90"
        @click="fetchPackages"
      >
        Thử lại
      </button>
    </div>

    <section v-else class="px-4 pb-16">
      <div class="mx-auto max-w-7xl overflow-x-auto pb-2">
        <div class="grid min-w-[960px] grid-cols-4 gap-4 lg:min-w-0">
          <article
            v-for="pkg in displayPackages"
            :key="pkg.id || pkg.slug"
            :class="[
              'flex min-h-[560px] flex-col overflow-hidden rounded-lg border border-slate-200 bg-white',
              cardTintClass(pkg.slug),
            ]"
          >
            <header class="px-3 pb-5 pt-4">
              <div :class="badgeClass(pkg.slug)">
                <span
                  v-if="slugIcon(pkg.slug)"
                  class="flex h-6 w-8 items-center justify-center rounded-l-md bg-slate-900 text-[11px] font-black text-white"
                >
                  {{ slugIcon(pkg.slug) }}
                </span>
                <span class="px-3 text-xs font-extrabold leading-6">
                  {{ slugLabel(pkg) }}
                </span>
              </div>

              <div class="mt-3 flex items-baseline gap-1 text-[22px] font-extrabold leading-tight text-black">
                <span>{{ isFreePackage(pkg) ? 'Miễn phí' : `${formatCurrency(pkg.price)}đ` }}</span>
                <span v-if="!isFreePackage(pkg)" class="font-bold">/ ngày</span>
              </div>
            </header>

            <div class="px-3">
              <div
                v-for="pricing in sortedPricings(pkg)"
                :key="pricing.id || pricing.duration_days"
                class="flex min-h-16 items-center justify-between gap-3 border-b border-dashed border-slate-200 text-[15px]"
              >
                <span class="whitespace-nowrap text-slate-950">
                  Gói {{ pricing.duration_days }} ngày
                </span>
                <div class="flex min-w-28 flex-col items-end gap-1 text-right">
                  <span class="whitespace-nowrap font-extrabold text-black">
                    {{ formatCurrency(pricing.price) }}đ
                  </span>
                  <span
                    v-if="originalPriceLabel(pkg, pricing)"
                    class="whitespace-nowrap text-xs font-bold text-slate-400 line-through"
                  >
                    {{ originalPriceLabel(pkg, pricing) }}
                  </span>
                </div>
              </div>

              <div
                v-if="sortedPricings(pkg).length === 0"
                class="flex min-h-16 items-center justify-between gap-3 border-b border-dashed border-slate-200 text-[15px]"
              >
                <span class="text-slate-950">{{ isFreePackage(pkg) ? 'Gói tin thường' : 'Chưa có bảng giá' }}</span>
                <span class="font-extrabold text-black">{{ isFreePackage(pkg) ? 'Miễn phí' : '-' }}</span>
              </div>
            </div>

            <ul class="flex flex-1 flex-col gap-3 px-3 py-5">
              <li
                v-for="feature in getFeatures(pkg)"
                :key="feature.text"
                class="flex items-start gap-2 text-[13px] font-medium leading-5 text-slate-600"
              >
                <span
                  :class="[
                    'mt-0.5 flex h-3.5 w-3.5 shrink-0 items-center justify-center rounded-full text-[10px] font-black text-white',
                    feature.enabled ? 'bg-emerald-500' : 'bg-slate-400',
                  ]"
                >
                  {{ feature.enabled ? '✓' : '!' }}
                </span>
                <span>
                  {{ feature.text }}
                </span>
              </li>
            </ul>
          </article>
        </div>
      </div>
    </section>

    <AppFooter />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import packageService from '@/services/packageService';
import AppFooter from '@/components/common/AppFooter.vue';

const loading = ref(true);
const error = ref('');
const packages = ref([]);

const displayPackages = computed(() => {
  const allowedSlugs = ['electron', 'ruby', 'gold', 'free', 'basic'];
  const slugOrder = { electron: 0, ruby: 1, gold: 2, free: 3, basic: 3 };

  return [...packages.value]
    .filter((pkg) => allowedSlugs.includes(pkg.slug))
    .sort((a, b) => (slugOrder[a.slug] ?? 99) - (slugOrder[b.slug] ?? 99));
});

onMounted(() => {
  fetchPackages();
});

async function fetchPackages() {
  loading.value = true;
  error.value = '';
  try {
    const res = await packageService.getPackages();
    packages.value = res?.data?.data || [];
  } catch (err) {
    error.value = 'Không thể tải bảng giá. Vui lòng thử lại.';
    packages.value = [];
  } finally {
    loading.value = false;
  }
}

function formatCurrency(value) {
  return Number(value || 0).toLocaleString('vi-VN');
}

function isFreePackage(pkg) {
  return ['free', 'basic'].includes(pkg.slug) || Number(pkg.price || 0) === 0;
}

function sortedPricings(pkg) {
  return [...(pkg.pricings || [])].sort((a, b) => Number(a.duration_days) - Number(b.duration_days));
}

function originalPriceLabel(pkg, pricing) {
  const original = Number(pkg.price || 0) * Number(pricing.duration_days || 0);
  const current = Number(pricing.price || 0);
  if (!original || !current || current >= original) return '';

  const discount = Math.round(((original - current) / original) * 100);
  return `${formatCurrency(original)}đ (- ${discount}%)`;
}

function slugLabel(pkg) {
  const map = {
    electron: 'Electron',
    ruby: 'Ruby',
    gold: 'Vàng',
    free: 'Tin thường',
    basic: 'Tin thường',
  };
  return pkg.badge || map[pkg.slug] || pkg.name || pkg.slug;
}

function slugIcon(slug) {
  const map = { electron: '◆', ruby: '◆', gold: '◆' };
  return map[slug] || '';
}

function badgeClass(slug) {
  const classes = {
    electron: 'inline-flex h-6 overflow-hidden rounded-md bg-[#0b315f] text-white',
    ruby: 'inline-flex h-6 overflow-hidden rounded-md bg-rose-700 text-white',
    gold: 'inline-flex h-6 overflow-hidden rounded-md bg-yellow-300 text-yellow-950',
    free: 'inline-flex h-6 overflow-hidden rounded-full bg-slate-200 text-slate-600',
    basic: 'inline-flex h-6 overflow-hidden rounded-full bg-slate-200 text-slate-600',
  };
  return classes[slug] || 'inline-flex h-6 overflow-hidden rounded-full bg-slate-200 text-slate-600';
}

function cardTintClass(slug) {
  const classes = {
    electron: 'bg-gradient-to-b from-slate-50 to-white',
    ruby: 'bg-gradient-to-b from-rose-50 to-white',
    gold: 'bg-gradient-to-b from-yellow-50 to-white',
    free: 'bg-white',
    basic: 'bg-white',
  };
  return classes[slug] || 'bg-white';
}

function getFeatures(pkg) {
  const featMap = {
    electron: [
      { text: 'Gói DUY NHẤT tích hợp 3D', enabled: true },
      { text: 'TOP 1 phủ sóng trên hệ sinh thái', enabled: true },
      { text: 'Giao diện hiển thị chuyên biệt', enabled: true },
      { text: 'Tự động đẩy tin miễn phí', enabled: true },
    ],
    ruby: [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: 'Nhận tag Ruby', enabled: true },
      { text: 'Người xem ước tính X10', enabled: true },
      { text: 'Kích thước lớn, tăng lượt xem', enabled: true },
      { text: 'TOP 2 hiển thị trong danh sách', enabled: true },
    ],
    gold: [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: 'Nhận tag Vàng', enabled: true },
      { text: 'Người xem ước tính X6', enabled: true },
      { text: 'TOP 3 hiển thị trong danh sách', enabled: true },
    ],
    free: [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: 'Miễn phí hiển thị trong danh sách', enabled: true },
      { text: 'Lượt tiếp cận tự nhiên', enabled: true },
    ],
    basic: [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: 'Miễn phí hiển thị trong danh sách', enabled: true },
      { text: 'Lượt tiếp cận tự nhiên', enabled: true },
    ],
  };

  return featMap[pkg.slug] || [];
}
</script>
