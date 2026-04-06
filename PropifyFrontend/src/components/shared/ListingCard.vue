<template>
  <div class="bg-white rounded-2xl p-4 flex gap-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
    <!-- Left: Image Box -->
    <div class="relative w-1/3 aspect-[4/3] rounded-xl overflow-hidden shrink-0">
      <img :src="image" alt="Property" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />
      
      <!-- Badges -->
      <div class="absolute top-3 left-3 flex gap-2">
        <span v-if="verified" class="bg-emerald-500 text-white text-[10px] font-bold px-2 py-1 rounded-full flex items-center gap-1 uppercase tracking-wide">
          <CheckCircle class="w-3 h-3" />
          Xác thực
        </span>
        <span class="bg-white/90 backdrop-blur-sm text-gray-700 text-[10px] font-bold px-2 py-1 rounded-full border border-gray-100 uppercase tracking-wide">
          {{ type }}
        </span>
      </div>

      <!-- Action Buttons Top Right -->
      <div class="absolute top-3 right-3 flex gap-2">
        <button class="bg-white/80 backdrop-blur-sm hover:bg-white text-gray-600 p-1.5 rounded-full transition-colors">
          <Share2 class="w-4 h-4" />
        </button>
        <button class="bg-white/80 backdrop-blur-sm hover:bg-white text-red-500 p-1.5 rounded-full transition-colors">
          <Heart class="w-4 h-4" />
        </button>
      </div>

      <!-- Pagination Indicator Bottom Right -->
      <div class="absolute bottom-3 right-3 bg-black/50 text-white text-[10px] px-2 py-1 rounded-full backdrop-blur-sm font-medium">
        1/3
      </div>
      
      <!-- Dots Bottom Center -->
      <div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1">
        <div class="w-1.5 h-1.5 rounded-full bg-white shadow-sm"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-white/50"></div>
        <div class="w-1.5 h-1.5 rounded-full bg-white/50"></div>
      </div>
    </div>

    <!-- Right: Content -->
    <div class="flex-1 flex flex-col justify-between py-1">
      <div>
        <h3 class="text-[15px] font-bold text-gray-900 leading-snug mb-1 line-clamp-2 group-hover:text-blue-600 transition-colors">
          {{ title }}
        </h3>
        <p class="text-[13px] text-gray-500 flex items-center gap-1 mb-2">
          <MapPin class="w-3.5 h-3.5 text-blue-500 shrink-0" />
          <span class="truncate">{{ location }}</span>
        </p>

        <!-- Price -->
        <div class="flex items-baseline gap-1 mb-3">
          <span class="text-xl font-bold text-blue-600">{{ price }}</span>
          <span class="text-sm font-medium text-gray-500" v-if="unit">{{ unit }}</span>
        </div>

        <!-- Specs -->
        <div class="flex items-center gap-4 text-sm text-gray-600 mb-4 bg-gray-50/50 w-fit rounded-lg px-2 py-1 border border-gray-100/50">
          <div class="flex items-center gap-1.5">
            <Maximize class="w-4 h-4 text-gray-400" />
            <span class="font-medium font-sans"><span class="font-bold text-gray-900">{{ area }}</span> m²</span>
          </div>
          <div class="w-px h-3 bg-gray-200"></div>
          <div class="flex items-center gap-1.5">
            <Bed class="w-4 h-4 text-gray-400" />
            <span class="font-medium"><span class="font-bold text-gray-900">{{ beds }}</span> PN</span>
          </div>
          <div class="w-px h-3 bg-gray-200"></div>
          <div class="flex items-center gap-1.5">
            <Bath class="w-4 h-4 text-gray-400" />
            <span class="font-medium"><span class="font-bold text-gray-900">{{ baths }}</span> WC</span>
          </div>
        </div>
      </div>

      <!-- Footer line -->
      <div class="flex justify-between items-center pt-3 border-t border-gray-100">
        <!-- Author Info -->
        <div class="flex items-center gap-2">
          <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
            {{ getInitials(author.name) }}
          </div>
          <div class="flex flex-col">
            <span class="text-xs font-bold text-gray-800">{{ author.name }}</span>
            <span class="text-[10px] text-gray-500 font-medium tracking-wide">
              {{ author.role }} <span class="mx-0.5">•</span> {{ timeAgo }}
            </span>
          </div>
        </div>

        <!-- Action / Stats -->
        <div class="flex items-center gap-3">
          <div class="flex items-center gap-1 text-[11px] text-gray-500 font-medium">
            <Eye class="w-3.5 h-3.5" />
            {{ views }}
          </div>
          <button class="bg-blue-500 hover:bg-blue-600 text-white text-xs font-semibold px-3 py-1.5 rounded-lg flex items-center gap-1.5 transition-colors shadow-sm shadow-blue-500/20">
            <Phone class="w-3.5 h-3.5" />
            Đặt lịch
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { MapPin, Maximize, Bed, Bath, Heart, Share2, Eye, Phone, CheckCircle } from 'lucide-vue-next';

const props = defineProps({
  image: { type: String, default: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=60' },
  verified: { type: Boolean, default: true },
  type: { type: String, default: 'Căn hộ' },
  title: { type: String, default: 'Căn hộ view thành phố tầng cao' },
  location: { type: String, default: 'Bình Thạnh, TP. Hồ Chí Minh' },
  price: { type: String, default: '18 triệu' },
  unit: { type: String, default: '/tháng' },
  area: { type: [Number, String], default: 95 },
  beds: { type: [Number, String], default: 2 },
  baths: { type: [Number, String], default: 2 },
  author: { 
    type: Object, 
    default: () => ({ name: 'Nguyễn Thị Lan', role: 'Môi giới' }) 
  },
  timeAgo: { type: String, default: '5 giờ trước' },
  views: { type: [Number, String], default: 215 },
});

function getInitials(name) {
  if (!name) return 'U';
  const parts = name.split(' ');
  if (parts.length > 1) {
    return parts[0][0] + parts[parts.length - 1][0];
  }
  return name.substring(0, 2).toUpperCase();
}
</script>
