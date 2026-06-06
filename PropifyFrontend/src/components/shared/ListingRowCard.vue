<template>
  <RouterLink :to="targetTo" class="block no-underline text-inherit">
    <div
      class="group flex h-[210px] gap-4 overflow-hidden rounded bg-white transition-colors duration-200 hover:border-slate-200"
      :class="packageBorderClass"
    >
      <div
        class="flex h-full w-[280px] shrink-0 flex-col gap-2 pt-[1px] pb-[1px] pl-[1px] md:w-[300px]"
      >
        <div class="relative flex-1 overflow-hidden rounded">
          <img
            :src="imageList[activeImgIdx]"
            alt="Property"
            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
          />

          <div v-if="packageIcon" class="absolute top-0 left-0">
            <span class="relative inline-block">
              <img :src="packageIcon" class="block h-[4.3rem] w-auto" alt="" />
              <span
                class="absolute top-[30%] right-1 flex h-[37%] items-center justify-center whitespace-nowrap px-3 text-[10px] font-extrabold tracking-wide text-white"
              >
                {{ packageLabel }}
              </span>
            </span>
          </div>

          <div
            v-if="imageList.length > 1"
            class="pointer-events-none absolute inset-0 z-20 flex items-center justify-between px-2 opacity-0 transition-opacity duration-300 group-hover:opacity-100"
          >
            <button
              type="button"
              class="pointer-events-auto flex h-8 w-8 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm transition-all active:scale-90 hover:bg-black/60"
              aria-label="Ảnh trước"
              @click.prevent.stop="prevImg"
            >
              <ChevronLeft class="h-5 w-5" />
            </button>
            <button
              type="button"
              class="pointer-events-auto flex h-8 w-8 items-center justify-center rounded-full bg-black/40 text-white backdrop-blur-sm transition-all active:scale-90 hover:bg-black/60"
              aria-label="Ảnh sau"
              @click.prevent.stop="nextImg"
            >
              <ChevronRight class="h-5 w-5" />
            </button>
          </div>

          <div class="absolute top-3 right-3 z-20">
            <button
              type="button"
              :class="[
                'rounded-full p-2 shadow-sm backdrop-blur-md transition-all',
                isFavorite
                  ? 'bg-rose-500 text-white hover:bg-rose-600'
                  : 'bg-white/20 text-white hover:bg-white/30',
              ]"
              aria-label="Yêu thích"
              @click.prevent.stop="$emit('toggleFavorite')"
            >
              <Heart
                class="h-4 w-4"
                :fill="isFavorite ? 'currentColor' : 'none'"
              />
            </button>
          </div>

          <div
            v-if="imageList.length > 1"
            class="absolute right-3 bottom-3 z-20 rounded-full bg-black/50 px-2 py-1 text-[10px] font-medium text-white backdrop-blur-sm"
          >
            {{ activeImgIdx + 1 }}/{{ imageList.length }}
          </div>

          <div
            v-if="imageList.length > 1 && !isPremium"
            class="absolute bottom-3 left-1/2 z-20 flex -translate-x-1/2 gap-1"
          >
            <div
              v-for="(img, idx) in imageList"
              :key="idx"
              class="h-1.5 w-1.5 rounded-full shadow-sm transition-all duration-300"
              :class="
                idx === activeImgIdx ? 'scale-110 bg-white' : 'bg-white/50'
              "
            />
          </div>
        </div>

        <div
          v-if="isPremium && imageList.length > 1"
          class="flex h-[50px] w-full shrink-0 gap-1"
        >
          <div
            v-for="(img, idx) in previewImages"
            :key="idx"
            class="relative flex-1 cursor-pointer overflow-hidden rounded-sm bg-slate-100 transition-all"
            :class="
              idx === Math.min(activeImgIdx, 2)
                ? 'ring-2 ring-blue-500 ring-inset'
                : 'hover:opacity-90'
            "
            @click.prevent.stop="activeImgIdx = idx"
          >
            <img :src="img" class="h-full w-full object-cover" alt="" />
            <div
              v-if="idx === 2 && imageList.length > 3"
              class="absolute inset-0 flex items-center justify-center bg-black/60 text-[11px] font-bold text-white"
            >
              +{{ remainingImagesCount }}
            </div>
          </div>
        </div>
      </div>

      <div class="flex min-w-0 flex-1 flex-col justify-between py-3 pr-4">
        <div>
          <div class="mb-1.5 flex items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5">
              <span
                class="rounded-full border border-gray-300 bg-white px-2.5 py-0.5 text-[11px] font-medium text-gray-600"
              >
                {{ propertyType }}
              </span>
              <span
                v-if="verified"
                class="flex items-center gap-1 rounded-full border border-emerald-400 bg-emerald-50 px-2.5 py-0.5 text-[11px] font-semibold text-emerald-600"
              >
                <CheckCircle class="h-3 w-3" />
                Xác thực
              </span>
            </div>

            <div
              v-if="rating"
              class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl border border-sky-200 bg-sky-100 text-sm font-bold text-sky-600"
            >
              {{ rating }}
            </div>
          </div>

          <h3
            class="mb-1.5 line-clamp-2 text-[15px] font-bold leading-snug text-gray-900 transition-colors group-hover:text-blue-600"
          >
            {{ title }}
          </h3>

          <p
            class="mb-1.5 flex min-w-0 items-center gap-4 text-[13px] text-gray-500"
          >
            <span class="flex min-w-0 flex-1 items-center gap-1">
              <MapPin class="h-3.5 w-3.5 shrink-0 text-blue-500" />
              <span class="truncate">{{ location }}</span>
            </span>
          </p>

          <div class="mb-2.5 flex items-baseline gap-1">
            <span class="text-xl font-bold text-blue-600">{{ price }}</span>
            <span v-if="unit" class="text-sm font-medium text-gray-500">{{
              unit
            }}</span>
          </div>

          <div class="flex items-center gap-3 text-[13px] text-gray-600">
            <div class="flex items-center gap-1.5">
              <Maximize class="h-4 w-4 text-gray-400" />
              <span
                ><span class="font-semibold text-gray-800">{{ area }}</span>
                m²</span
              >
            </div>
            <div class="h-3 w-px bg-gray-200" />
            <div class="flex items-center gap-1.5">
              <Bed class="h-4 w-4 text-gray-400" />
              <span
                ><span class="font-semibold text-gray-800">{{ beds }}</span>
                PN</span
              >
            </div>
            <div class="h-3 w-px bg-gray-200" />
            <div class="flex items-center gap-1.5">
              <Bath class="h-4 w-4 text-gray-400" />
              <span
                ><span class="font-semibold text-gray-800">{{ baths }}</span>
                WC</span
              >
            </div>
          </div>
        </div>

        <div
          class="mt-2 flex items-center justify-between border-t border-gray-100 pt-2"
        >
          <div class="flex items-center gap-2">
            <div
              class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-100 text-xs font-bold text-blue-600"
            >
              {{ getInitials(author.name) }}
            </div>
            <div class="flex flex-col leading-tight">
              <span class="text-xs font-bold text-gray-800">{{
                author.name
              }}</span>
              <span class="text-[10px] text-gray-500">{{ author.role }}</span>
            </div>
          </div>

          <div class="flex items-center gap-3">
            <div class="flex items-center gap-1 text-[12px] text-gray-400">
              <CalendarDays class="h-3.5 w-3.5 shrink-0" />
              {{ publishedTimeAgo }}
            </div>
            <div class="flex items-center gap-1 text-[12px] text-gray-500">
              <Eye class="h-3.5 w-3.5" />
              {{ viewCount }}
            </div>
            <button
              type="button"
              class="flex items-center gap-1.5 rounded-xl bg-blue-500 px-4 py-1.5 text-[13px] font-semibold text-white shadow-sm shadow-blue-500/20 transition-colors hover:bg-blue-600"
              @click.prevent.stop="showPhone = !showPhone"
            >
              <Phone class="h-3.5 w-3.5" />
              {{ showPhone ? displayedPhone : "Liên hệ" }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </RouterLink>
</template>

<script setup>
import { computed, ref } from "vue";
import {
  Bath,
  Bed,
  CalendarDays,
  CheckCircle,
  ChevronLeft,
  ChevronRight,
  Eye,
  Heart,
  MapPin,
  Maximize,
  Phone,
} from "lucide-vue-next";
import {
  formatPrice,
  getAuthor,
  getThumb,
  isVerified,
  propertyTypeLabel,
  timeAgo,
} from "@/utils/listingFormatters";

const props = defineProps({
  listing: {
    type: Object,
    required: true,
  },
  unit: {
    type: String,
    default: "",
  },
  mode: {
    type: String,
    default: "sale",
  },
  to: {
    type: String,
    default: "",
  },
  isFavorite: {
    type: Boolean,
    default: false,
  },
  rating: {
    type: [Number, String],
    default: null,
  },
});

defineEmits(["toggleFavorite"]);

const showPhone = ref(false);
const activeImgIdx = ref(0);
const priorityIconMap = { 2: "/vip.svg", 3: "/premium.svg", 4: "/diamond.svg" };

const targetTo = computed(
  () => props.to || `/listings/${props.listing?.id ?? ""}`,
);
const title = computed(() => props.listing?.title || "");
const propertyType = computed(
  () =>
    props.listing?.displayType ||
    propertyTypeLabel(props.listing?.property?.type),
);
const verified = computed(() => isVerified(props.listing));
const price = computed(
  () =>
    props.listing?.formattedPrice ||
    formatPrice(props.listing?.property?.price),
);
const area = computed(() => props.listing?.property?.area || 0);
const beds = computed(() => props.listing?.property?.bedrooms || 0);
const baths = computed(() => props.listing?.property?.bathrooms || 0);
const location = computed(
  () =>
    props.listing?.displayLocation ||
    props.listing?.property?.full_address ||
    props.listing?.property?.address_detail ||
    "",
);
const author = computed(
  () => props.listing?.authorInfo || getAuthor(props.listing),
);
const publishedTimeAgo = computed(
  () =>
    props.listing?.timeAgoLabel ||
    timeAgo(props.listing?.published_at || props.listing?.submitted_at),
);
const viewCount = computed(() => props.listing?.views ?? 0);
const displayedPhone = computed(() => author.value.phone || "0901234567");

const imageList = computed(() => {
  if (Array.isArray(props.listing?.images) && props.listing.images.length > 0) {
    return props.listing.images
      .map((image) => (typeof image === "object" ? image?.url : image))
      .filter(Boolean);
  }

  return [getThumb(props.listing)];
});

const isPremium = computed(() => {
  const slug = String(props.listing?.package?.slug || "").toLowerCase();
  const priority = Number(props.listing?.package?.priority || 0);
  return slug === "diamond" || slug === "ruby" || priority === 4;
});

const previewImages = computed(() => imageList.value.slice(0, 3));
const remainingImagesCount = computed(() => imageList.value.length - 2);
const packageIcon = computed(
  () => priorityIconMap[Number(props.listing?.package?.priority || 0)] || null,
);
const packageLabel = computed(
  () => props.listing?.package?.badge || props.listing?.package?.name || null,
);

const packageBorderClass = computed(() => {
  const slug = String(props.listing?.package?.slug || "").toLowerCase();
  const priority = Number(props.listing?.package?.priority || 0);
  if (slug === "ruby") return "border-package border-package-ruby";
  if (slug === "gold" || priority === 3)
    return "border-package border-package-gold";
  if (slug === "diamond" || priority === 4)
    return "border-package border-package-diamond";
  return "border border-transparent";
});

function nextImg() {
  if (imageList.value.length <= 1) return;
  activeImgIdx.value = (activeImgIdx.value + 1) % imageList.value.length;
}

function prevImg() {
  if (imageList.value.length <= 1) return;
  activeImgIdx.value =
    (activeImgIdx.value - 1 + imageList.value.length) % imageList.value.length;
}

function getInitials(name) {
  if (!name) return "U";
  const parts = name.split(" ");
  if (parts.length > 1) return parts[0][0] + parts[parts.length - 1][0];
  return name.substring(0, 2).toUpperCase();
}
</script>

<style scoped>
.border-package {
  position: relative;
  border: none !important;
  background: #fff;
  background-clip: padding-box;
}

.border-package::after {
  content: "";
  position: absolute;
  inset: 0;
  border-radius: 0.25rem;
  padding: 2px;
  background: conic-gradient(
    from var(--angle),
    transparent 70%,
    var(--package-border-color) 85%,
    var(--package-border-glow) 95%,
    transparent 100%
  );
  -webkit-mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  mask:
    linear-gradient(#fff 0 0) content-box,
    linear-gradient(#fff 0 0);
  -webkit-mask-composite: xor;
  mask-composite: exclude;
  animation: diamond-rotate 2s linear infinite;
  pointer-events: none;
  z-index: 10;
}

.border-package-diamond {
  --package-border-color: #3b82f6;
  --package-border-glow: #60a5fa;
}

.border-package-ruby {
  --package-border-color: #dc2626;
  --package-border-glow: #fb7185;
}

.border-package-gold {
  --package-border-color: #d97706;
  --package-border-glow: #facc15;
}

@property --angle {
  syntax: "<angle>";
  initial-value: 0deg;
  inherits: false;
}

@keyframes diamond-rotate {
  from {
    --angle: 0deg;
  }

  to {
    --angle: 360deg;
  }
}
</style>
