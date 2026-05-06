
<template>
	<RouterLink :to="to" class="block no-underline text-inherit">
	<div class="bg-white rounded-2xl p-4 flex gap-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow group">
		<div class="relative w-[220px] h-[220px] shrink-0 rounded-xl overflow-hidden">
			<img :src="image" alt="Property" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500" />

			<div class="absolute top-3 left-3 flex gap-1.5">
				<span
					v-if="badge"
					class="text-white text-[11px] font-bold px-2.5 py-0.5 rounded-full shadow-md"
					:style="{ background: badgeColor || '#94a3b8' }"
				>
					{{ badge }}
				</span>
			</div>

			<div class="absolute top-3 right-3">
				<button class="bg-white/20 backdrop-blur-md hover:bg-white/30 text-white p-2 rounded-full transition-all shadow-sm">
					<Heart class="w-4 h-4" />
				</button>
			</div>

			<div class="absolute bottom-3 right-3 bg-black/50 text-white text-[10px] px-2 py-1 rounded-full backdrop-blur-sm font-medium">
				1/4
			</div>

			<div class="absolute bottom-3 left-1/2 -translate-x-1/2 flex gap-1">
				<div class="w-1.5 h-1.5 rounded-full bg-white shadow-sm"></div>
				<div class="w-1.5 h-1.5 rounded-full bg-white/50"></div>
				<div class="w-1.5 h-1.5 rounded-full bg-white/50"></div>
			</div>
		</div>

		<div class="flex-1 flex flex-col justify-between py-1 min-w-0">
			<div>
				<div class="flex items-center justify-between gap-2 mb-2">
					<div class="flex items-center gap-1.5 flex-wrap">
						<span class="border border-gray-300 text-gray-600 text-[11px] font-medium px-2.5 py-0.5 rounded-full bg-white">
							{{ type }}
						</span>
						<span v-if="verified" class="border border-emerald-400 text-emerald-600 text-[11px] font-semibold px-2.5 py-0.5 rounded-full flex items-center gap-1 bg-emerald-50">
							<CheckCircle class="w-3 h-3" />
							Xác thực
						</span>
					</div>

					<div v-if="rating" class="shrink-0 w-9 h-9 rounded-xl bg-sky-100 text-sky-600 flex items-center justify-center text-sm font-bold border border-sky-200">
						{{ rating }}
					</div>
				</div>

				<h3 class="text-[15px] font-bold text-gray-900 leading-snug mb-2 line-clamp-2 group-hover:text-blue-600 transition-colors">
					{{ title }}
				</h3>

				<p class="text-[13px] text-gray-500 flex items-center gap-4 mb-2.5 flex-wrap">
					<span class="flex items-center gap-1">
						<MapPin class="w-3.5 h-3.5 text-blue-500 shrink-0" />
						<span>{{ location }}</span>
					</span>
					<span class="flex items-center gap-1 text-gray-400">
						<CalendarDays class="w-3.5 h-3.5 shrink-0" />
						{{ timeAgo }}
					</span>
				</p>

				<div class="flex items-baseline gap-1 mb-3">
					<span class="text-xl font-bold text-blue-600">{{ price }}</span>
					<span class="text-sm font-medium text-gray-500" v-if="unit">{{ unit }}</span>
				</div>

				<div class="flex items-center gap-3 text-[13px] text-gray-600">
					<div class="flex items-center gap-1.5">
						<Maximize class="w-4 h-4 text-gray-400" />
						<span><span class="font-semibold text-gray-800">{{ area }}</span> m²</span>
					</div>
					<div class="w-px h-3 bg-gray-200"></div>
					<div class="flex items-center gap-1.5">
						<Bed class="w-4 h-4 text-gray-400" />
						<span><span class="font-semibold text-gray-800">{{ beds }}</span> PN</span>
					</div>
					<div class="w-px h-3 bg-gray-200"></div>
					<div class="flex items-center gap-1.5">
						<Bath class="w-4 h-4 text-gray-400" />
						<span><span class="font-semibold text-gray-800">{{ baths }}</span> WC</span>
					</div>
				</div>
			</div>

			<div class="flex justify-between items-center pt-3 mt-3 border-t border-gray-100">
				<div class="flex items-center gap-2">
					<div class="w-8 h-8 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-xs shrink-0">
						{{ getInitials(author.name) }}
					</div>
					<div class="flex flex-col leading-tight">
						<span class="text-xs font-bold text-gray-800">{{ author.name }}</span>
						<span class="text-[10px] text-gray-500">{{ author.role }}</span>
					</div>
				</div>

				<div class="flex items-center gap-3">
					<div class="flex items-center gap-1 text-[12px] text-gray-500">
						<Eye class="w-3.5 h-3.5" />
						{{ views }}
					</div>
					<button class="bg-blue-500 hover:bg-blue-600 text-white text-[13px] font-semibold px-4 py-1.5 rounded-xl flex items-center gap-1.5 transition-colors shadow-sm shadow-blue-500/20">
						<Phone class="w-3.5 h-3.5" />
						Hiện số
					</button>
				</div>
			</div>
		</div>
	</div>
	</RouterLink>
</template>

<script setup>
import { MapPin, Maximize, Bed, Bath, Heart, Eye, Phone, CheckCircle, CalendarDays } from 'lucide-vue-next';

defineProps({
	to: { type: String, default: '#' },
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
	rating: { type: [Number, String], default: 8 },
	author: {
		type: Object,
		default: () => ({ name: 'Nguyễn Thị Lan', role: 'Môi giới' })
	},
	timeAgo: { type: String, default: '5 giờ trước' },
	badge: { type: String, default: null },
	badgeColor: { type: String, default: null },
	views: { type: [Number, String], default: 215 },
});

function getInitials(name) {
	if (!name) return 'U';
	const parts = name.split(' ');
	if (parts.length > 1) return parts[0][0] + parts[parts.length - 1][0];
	return name.substring(0, 2).toUpperCase();
}
</script>
