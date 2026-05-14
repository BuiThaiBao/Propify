<template>
	<RouterLink :to="to" class="block no-underline text-inherit">
	<article 
		class="group overflow-hidden rounded-[22px] bg-white shadow-[0_2px_12px_rgba(15,23,42,0.08)] transition-all hover:-translate-y-0.5 hover:shadow-[0_8px_28px_rgba(15,23,42,0.14)]"
		:class="packageBorderClass"
	>
		<div class="relative overflow-hidden rounded-t-[22px] bg-slate-100">
			<div class="relative aspect-[4/3] overflow-hidden">
				<img :src="image" alt="Property" class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105" />

				<div
					:class="[
						'absolute flex min-h-[3.5rem] items-center gap-1.5 z-10',
						packageIcon ? 'left-0 top-0' : 'left-3 top-3'
					]"
				>
					<span v-if="packageIcon" class="relative inline-block">
						<img :src="packageIcon" class="block h-[3.5rem] w-auto" alt="" />
						<span class="absolute top-[30%] right-0 flex h-[37%] items-center justify-center px-2.5 text-[9px] font-extrabold tracking-wide text-white whitespace-nowrap">
							{{ packageLabel }}
						</span>
					</span>
					<span class="inline-flex items-center rounded-full bg-white/95 px-2.5 py-0.5 text-[10px] font-semibold text-slate-700 shadow-sm backdrop-blur">
						{{ type }}
					</span>
					<span v-if="verified" class="inline-flex items-center gap-1 rounded-full bg-emerald-500 px-2.5 py-0.5 text-[10px] font-semibold text-white shadow-sm">
						<CheckCircle2 class="h-3 w-3" />
						Xác thực
					</span>
				</div>
			</div>

			<button
				type="button"
				:class="[
					'absolute right-4 top-4 rounded-full p-1.5 shadow-sm backdrop-blur z-10',
					isFavorite ? 'bg-rose-500 text-white hover:bg-rose-600' : 'bg-white/90 text-slate-600 hover:bg-white'
				]"
				aria-label="Yêu thích"
				@click.prevent.stop="$emit('toggleFavorite')"
			>
				<Heart class="h-4 w-4" :fill="isFavorite ? 'currentColor' : 'none'" />
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
import { computed } from 'vue';
import { Bath, Bed, CheckCircle2, Heart, MapPin, Maximize } from 'lucide-vue-next';

const priorityIconMap = { 2: '/vip.svg', 3: '/premium.svg', 4: '/diamond.svg' };

const props = defineProps({
	listingId: { type: [Number, String], default: null },
	isFavorite: { type: Boolean, default: false },
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
	package: { type: Object, default: null },
});

defineEmits(['toggleFavorite']);

const packageIcon = computed(() => {
	const priority = Number(props.package?.priority || 0);
	return priorityIconMap[priority] || null;
});

const packageLabel = computed(() => {
	return props.package?.badge || props.package?.name || null;
});

const packageBorderClass = computed(() => {
	const slug = String(props.package?.slug || '').toLowerCase();
	const priority = Number(props.package?.priority || 0);
	if (slug === 'ruby') return 'border-package border-package-ruby';
	if (slug === 'gold' || priority === 3) return 'border-package border-package-gold';
	if (slug === 'diamond' || priority === 4) return 'border-package border-package-diamond';
	return '';
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

.border-package {
	position: relative;
	background: #fff;
	background-clip: padding-box;
}

.border-package::after {
	content: "";
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	border-radius: 22px;
	padding: 2.5px; /* Border thickness */
	background: conic-gradient(
		from var(--angle),
		transparent 70%,
		var(--package-border-color) 85%,
		var(--package-border-glow) 95%,
		transparent 100%
	);
	-webkit-mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
	mask: linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
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
