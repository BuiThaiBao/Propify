<template>
	<RouterLink :to="to" class="block no-underline text-inherit">
	<article class="group overflow-hidden rounded-[22px] bg-white shadow-[0_2px_12px_rgba(15,23,42,0.08)] transition-all hover:-translate-y-0.5 hover:shadow-[0_8px_28px_rgba(15,23,42,0.14)]">
		<div class="relative overflow-hidden rounded-t-[22px] bg-slate-100">
			<div class="relative aspect-[4/3] overflow-hidden">
				<img :src="image" alt="Property" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />
			</div>

			<div class="absolute left-4 top-4 flex items-center gap-1.5">
				<span class="inline-flex items-center rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-semibold text-slate-700 shadow-sm backdrop-blur">
					{{ type }}
				</span>
				<span v-if="verified" class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2.5 py-0.5 text-[10px] font-semibold text-white shadow-sm">
					<CheckCircle2 class="h-3 w-3" />
					Xác thực
				</span>
			</div>

			<button
				type="button"
				class="absolute right-4 top-4 rounded-full bg-white/90 p-1.5 text-slate-600 shadow-sm backdrop-blur hover:bg-white"
				aria-label="Yêu thích"
			>
				<Heart class="h-4 w-4" />
			</button>
		</div>

		<div class="px-3.5 pb-3.5 pt-3">
			<h3 class="card-title text-[15px] font-semibold leading-snug text-slate-900">
				{{ title }}
			</h3>

			<p class="card-location mt-1.5 flex items-center gap-1 text-[12px] text-slate-500">
				<span>{{ location }}</span>
			</p>



			<div class="mt-2.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-[12px] text-slate-600">
				<span class="inline-flex items-center gap-1">
					<Bed class="h-3.5 w-3.5 text-slate-400" />
					{{ beds }} PN
				</span>
				<span class="inline-flex items-center gap-1">
					<Bath class="h-3.5 w-3.5 text-slate-400" />
					{{ baths }} WC
				</span>
				<span class="inline-flex items-center gap-1">
					<Maximize class="h-3.5 w-3.5 text-slate-400" />
					{{ area }} m²
				</span>
			</div>

			<div class="mt-3 flex items-end justify-between border-t border-slate-100 pt-3">
				<div class="flex items-end gap-1">
					<span class="text-[18px] font-bold text-sky-600">{{ price }}</span>
					<span v-if="unit" class="pb-0.5 text-[12px] font-medium text-slate-500">{{ unit }}</span>
				</div>
				<span v-if="timeAgo" class="text-[12px] text-slate-400 pb-0.5">{{ timeAgo }}</span>
			</div>
		</div>
	</article>
	</RouterLink>
</template>

<script setup>
import { Bath, Bed, CheckCircle2, Heart, MapPin, Maximize } from 'lucide-vue-next';

defineProps({
	to: { type: String, default: '#' },
	image: { type: String, default: 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800&auto=format&fit=crop&q=60' },
	verified: { type: Boolean, default: false },
	type: { type: String, default: 'Bất động sản' },
	title: { type: String, default: 'Tiêu đề tin đăng' },
	location: { type: String, default: 'Địa điểm' },
	price: { type: String, default: 'Thỏa thuận' },
	unit: { type: String, default: '' },
	area: { type: [Number, String], default: 0 },
	beds: { type: [Number, String], default: 0 },
	baths: { type: [Number, String], default: 0 },
	timeAgo: { type: String, default: '' },
});
</script>

<style scoped>
.group:hover img {
	transform: scale(1.02);
}

.card-title {
	display: -webkit-box;
	-webkit-line-clamp: 2;
	-webkit-box-orient: vertical;
	overflow: hidden;
}

.card-location {
	display: -webkit-box;
	-webkit-line-clamp: 1;
	-webkit-box-orient: vertical;
	overflow: hidden;
}
</style>
