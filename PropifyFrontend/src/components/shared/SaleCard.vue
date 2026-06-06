<template>
  <ListingRowCard
    :listing="listingData"
    :to="to"
    :unit="unit"
    mode="sale"
    :is-favorite="isFavorite"
    :rating="rating"
    @toggle-favorite="$emit('toggleFavorite')"
  />
</template>

<script setup>
import { computed } from "vue";
import ListingRowCard from "@/components/shared/ListingRowCard.vue";

const props = defineProps({
  listingId: { type: [Number, String], default: null },
  isFavorite: { type: Boolean, default: false },
  to: { type: String, default: "#" },
  images: { type: Array, default: () => [] },
  verified: { type: Boolean, default: true },
  type: { type: String, default: "Căn hộ" },
  title: { type: String, default: "Căn hộ view thành phố tầng cao" },
  location: { type: String, default: "Bình Thạnh, TP. Hồ Chí Minh" },
  price: { type: String, default: "18 triệu" },
  unit: { type: String, default: "" },
  area: { type: [Number, String], default: 95 },
  beds: { type: [Number, String], default: 2 },
  baths: { type: [Number, String], default: 2 },
  rating: { type: [Number, String], default: 8 },
  author: {
    type: Object,
    default: () => ({ name: "Nguyễn Thị Lan", role: "Môi giới" }),
  },
  timeAgo: { type: String, default: "5 giờ trước" },
  package: { type: Object, default: null },
  views: { type: [Number, String], default: 215 },
});

defineEmits(["toggleFavorite"]);

const listingData = computed(() => ({
  id: props.listingId,
  title: props.title,
  images: props.images,
  views: props.views,
  package: props.package,
  displayType: props.type,
  formattedPrice: props.price,
  displayLocation: props.location,
  authorInfo: props.author,
  timeAgoLabel: props.timeAgo,
  is_verified: props.verified,
  property: {
    area: props.area,
    bedrooms: props.beds,
    bathrooms: props.baths,
  },
}));
</script>
