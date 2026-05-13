<template>
  <div class="min-h-screen bg-white text-slate-900">
    <!-- Hero -->
    <section class="px-4 pt-20 pb-8 text-center sm:pt-24">
      <div class="mx-auto max-w-3xl">
        <h1 class="text-3xl font-extrabold leading-tight text-slate-900 sm:text-4xl">
          Bảng giá đăng tin
        </h1>
        <p class="mt-3 text-sm font-medium text-slate-500 sm:text-base">
          Lựa chọn gói tin phù hợp để tăng hiệu quả tiếp cận khách hàng
        </p>
      </div>
    </section>

    <!-- Loading -->
    <div v-if="loading" class="flex flex-col items-center gap-3 px-6 py-16 text-sm text-slate-500">
      <div class="size-9 animate-spin rounded-full border-3 border-slate-200 border-t-primary"></div>
      <p>Đang tải bảng giá...</p>
    </div>

    <!-- Error -->
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

    <!-- Pricing Grid -->
    <section v-else class="px-4 pb-16">
      <div class="mx-auto max-w-7xl overflow-x-auto pb-2">
        <div
          class="grid min-w-[900px]"
          :style="{ 'grid-template-columns': `repeat(${displayPackages.length}, 1fr)` }"
        >
          <article
            v-for="(pkg, idx) in displayPackages"
            :key="pkg.id || pkg.slug"
            :class="[
              'flex flex-col border border-r-0 border-slate-200 bg-white transition-shadow',
              cardTintClass(pkg),
              idx === 0 && 'rounded-l-xl',
              idx === displayPackages.length - 1 && 'rounded-r-xl !border-r',
            ]"
          >
            <!-- Header -->
            <header class="px-4 pt-5 pb-6">
              <!-- Badge -->
              <div class="mb-3 flex h-[5rem] items-start">
                <span v-if="getIconSrc(pkg)" class="relative inline-block">
                  <img :src="getIconSrc(pkg)" class="block h-[5rem] w-auto" alt="" />
                  <span class="absolute top-[30%] right-0 flex h-[37%] items-center justify-center px-3.5 text-[0.9rem] font-extrabold tracking-wide text-white whitespace-nowrap">
                    {{ getBadgeLabel(pkg) }}
                  </span>
                </span>
                <span
                  v-else
                  class="mt-[1.5rem] inline-flex h-8 items-center rounded-full bg-slate-200 px-3.5 text-[0.8rem] font-bold text-slate-600"
                >
                  {{ getBadgeLabel(pkg) }}
                </span>
              </div>

              <!-- Price -->
              <div class="flex items-baseline gap-1">
                <span class="text-[22px] font-extrabold leading-tight text-black">
                  {{ isFreePackage(pkg) ? 'Miễn phí' : `${formatCurrency(pkg.price)}đ` }}
                </span>
                <span v-if="!isFreePackage(pkg)" class="text-base font-bold text-black">/ ngày</span>
              </div>
            </header>

            <!-- Pricing Table -->
            <div class="px-4">
              <div
                v-for="pricing in sortedPricings(pkg)"
                :key="pricing.id || pricing.duration_days"
                class="flex min-h-14 items-center justify-between gap-3 border-b border-dashed border-slate-200 text-[15px] last:border-b-0"
              >
                <span class="whitespace-nowrap text-slate-900">
                  Gói {{ pricing.duration_days }} ngày
                </span>
                <div class="flex min-w-28 flex-col items-end gap-0.5 text-right">
                  <template v-if="isFreePackage(pkg)">
                    <span class="font-semibold text-slate-500 whitespace-nowrap">Miễn phí</span>
                  </template>
                  <template v-else>
                    <span class="font-extrabold text-black whitespace-nowrap">
                      {{ formatCurrency(pricing.price) }}đ
                    </span>
                    <span
                      v-if="originalPriceLabel(pkg, pricing)"
                      class="text-xs font-bold text-slate-400 line-through whitespace-nowrap"
                    >
                      {{ originalPriceLabel(pkg, pricing) }}
                    </span>
                  </template>
                </div>
              </div>

              <!-- Empty state -->
              <div
                v-if="sortedPricings(pkg).length === 0"
                class="flex min-h-14 items-center justify-between gap-3 border-b border-dashed border-slate-200 text-[15px]"
              >
                <span class="text-slate-900">{{ isFreePackage(pkg) ? 'Gói tin thường' : 'Chưa có bảng giá' }}</span>
                <span class="font-semibold text-slate-500">{{ isFreePackage(pkg) ? 'Miễn phí' : '-' }}</span>
              </div>
            </div>

            <!-- Features -->
            <ul class="flex flex-1 flex-col gap-2.5 px-4 py-5 list-none m-0">
              <li
                v-for="feature in getFeatures(pkg)"
                :key="feature.text"
                class="flex items-start gap-2 text-[13px] font-medium leading-5 text-slate-600"
              >
                <span
                  :class="[
                    'mt-0.5 flex size-3.5 shrink-0 items-center justify-center rounded-full text-[10px] font-black text-white',
                    feature.enabled ? 'bg-emerald-500' : 'bg-slate-400',
                  ]"
                >
                  {{ feature.enabled ? '✓' : '✕' }}
                </span>
                <span :class="{ 'text-slate-400': !feature.enabled }">
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
import { computed, onMounted } from 'vue';
import AppFooter from '@/components/common/AppFooter.vue';
import { usePackages } from '@/composables/usePackages';

const { packages, loading, error, fetchPackages } = usePackages();

// Priority → icon mapping: 2=vip, 3=premium, 4=diamond
const priorityIconMap = {
  2: '/vip.svg',
  3: '/premium.svg',
  4: '/dimond.svg',
};

const displayPackages = computed(() => {
  return [...packages.value]
    .filter((pkg) => pkg.is_active !== false)
    .sort((a, b) => (b.priority || 0) - (a.priority || 0));
});

onMounted(() => {
  fetchPackages({ force: true });
});

function formatCurrency(value) {
  return Number(value || 0).toLocaleString('vi-VN');
}

function isFreePackage(pkg) {
  return Number(pkg.price || 0) === 0;
}

function sortedPricings(pkg) {
  return [...(pkg.pricings || [])].sort(
    (a, b) => Number(a.duration_days) - Number(b.duration_days)
  );
}

function originalPriceLabel(pkg, pricing) {
  const original = Number(pkg.price || 0) * Number(pricing.duration_days || 0);
  const current = Number(pricing.price || 0);
  if (!original || !current || current >= original) return '';

  const discount = Math.round(((original - current) / original) * 100);
  return `${formatCurrency(original)}đ (-${discount}%)`;
}

function getBadgeLabel(pkg) {
  return pkg.badge || pkg.name || pkg.slug;
}

function getIconSrc(pkg) {
  const priority = Number(pkg.priority || 0);
  return priorityIconMap[priority] || null;
}

function cardTintClass(pkg) {
  const priority = Number(pkg.priority || 0);
  if (priority >= 4) return 'bg-gradient-to-b from-slate-50 to-white';
  if (priority === 3) return 'bg-gradient-to-b from-rose-50 to-white';
  if (priority === 2) return 'bg-gradient-to-b from-yellow-50 to-white';
  return 'bg-white';
}

function getFeatures(pkg) {
  const priority = Number(pkg.priority || 0);
  const multiplier = Number(pkg.multiplier || 1);

  if (priority >= 4) {
    return [
      { text: 'Gói DUY NHẤT tích hợp 3D', enabled: true },
      { text: 'TOP 1 phủ sóng trên hệ sinh thái', enabled: true },
      { text: 'Giao diện hiển thị chuyên biệt', enabled: true },
      { text: 'Tự động đẩy tin MIỄN PHÍ', enabled: true },
    ];
  }
  if (priority === 3) {
    return [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: `Nhận tag ${getBadgeLabel(pkg)}`, enabled: true },
      { text: `Người xem ước tính X${Math.round(multiplier)}`, enabled: true },
      { text: 'Kích thước lớn, tăng lượt xem', enabled: true },
      { text: 'TOP 2 hiển thị trong danh sách', enabled: true },
    ];
  }
  if (priority === 2) {
    return [
      { text: 'Không tích hợp 3D', enabled: false },
      { text: `Nhận tag ${getBadgeLabel(pkg)}`, enabled: true },
      { text: `Người xem ước tính X${Math.round(multiplier)}`, enabled: true },
      { text: 'TOP 3 hiển thị trong danh sách', enabled: true },
    ];
  }
  return [
    { text: 'Không tích hợp 3D', enabled: false },
    { text: 'Miễn phí hiển thị trong danh sách', enabled: true },
    { text: 'Lượt tiếp cận tự nhiên', enabled: true },
  ];
}
</script>
