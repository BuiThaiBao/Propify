<template>
  <ListingRowCard
    :listing="listingData"
    :to="to"
    :unit="unit"
    mode="rent"
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
  title: {
    type: String,
    default: "Căn hộ studio full nội thất trung tâm quận 1",
  },
  location: { type: String, default: "Quận 1, TP. Hồ Chí Minh" },
  price: { type: String, default: "12 triệu" },
  unit: { type: String, default: "/tháng" },
  area: { type: [Number, String], default: 38 },
  beds: { type: [Number, String], default: 1 },
  baths: { type: [Number, String], default: 1 },
  rating: { type: [Number, String], default: 8 },
  author: {
    type: Object,
    default: () => ({ name: "Trần Minh Tuấn", role: "Chủ nhà" }),
  },
  timeAgo: { type: String, default: "2 giờ trước" },
  package: { type: Object, default: null },
  views: { type: [Number, String], default: 312 },
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
