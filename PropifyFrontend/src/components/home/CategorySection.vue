<template>
  <section ref="sectionRef" class="py-20 lg:py-24 bg-muted/30">
    <div class="container mx-auto px-4">
      <!-- Header -->
      <div
        :class="[
          'text-center mb-12',
          isVisible ? 'scroll-reveal' : 'opacity-0',
        ]"
      >
        <p
          class="text-primary text-sm font-semibold uppercase tracking-wider mb-2"
        >
          Danh mục
        </p>
        <h2 class="text-3xl lg:text-4xl font-bold text-foreground">
          Tìm theo loại hình
        </h2>
      </div>

      <!-- Categories -->
      <div
        :class="[
          'grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4',
          isVisible ? '' : 'opacity-0',
        ]"
      >
        <router-link
          v-for="(cat, i) in categories"
          :key="cat.label"
          to="/listings"
          class="group bg-card rounded-2xl p-6 text-center shadow-card hover:shadow-card-hover transition-all duration-300 hover:-translate-y-1 active:scale-[0.97]"
          :class="isVisible ? 'scroll-reveal' : 'opacity-0'"
          :style="{ animationDelay: `${i * 60}ms` }"
        >
          <!-- Icon -->
          <div
            :class="[
              'w-12 h-12 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform',
              cat.color,
            ]"
          >
            <component :is="cat.icon" class="w-5 h-5" />
          </div>

          <!-- Text -->
          <p class="font-semibold text-foreground text-sm mb-1">
            {{ cat.label }}
          </p>
          <p class="text-xs text-muted-foreground">
            {{ formatNumber(cat.count) }} tin
          </p>
        </router-link>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref, onMounted } from "vue";

// icons (npm i lucide-vue-next nếu chưa có)
import {
  Building2,
  Home,
  Castle,
  DoorOpen,
  Building,
  Trees,
} from "lucide-vue-next";

// state
const sectionRef = ref(null);
const isVisible = ref(false);

// fake scroll reveal (thay cho hook React)
onMounted(() => {
  const observer = new IntersectionObserver(
    ([entry]) => {
      if (entry.isIntersecting) {
        isVisible.value = true;
        observer.disconnect();
      }
    },
    { threshold: 0.2 },
  );

  if (sectionRef.value) {
    observer.observe(sectionRef.value);
  }
});

// data
const categories = [
  {
    icon: Building2,
    label: "Căn hộ",
    count: 4230,
    color: "bg-primary/10 text-primary",
  },
  {
    icon: Home,
    label: "Nhà phố",
    count: 1847,
    color: "bg-accent/10 text-accent",
  },
  {
    icon: Castle,
    label: "Biệt thự",
    count: 892,
    color: "bg-success/10 text-success",
  },
  {
    icon: DoorOpen,
    label: "Phòng trọ",
    count: 3156,
    color: "bg-destructive/10 text-destructive",
  },
  {
    icon: Building,
    label: "Penthouse",
    count: 421,
    color: "bg-primary/10 text-primary",
  },
  {
    icon: Trees,
    label: "Nhà vườn",
    count: 637,
    color: "bg-accent/10 text-accent",
  },
];

// helper
const formatNumber = (num) => {
  return new Intl.NumberFormat("vi-VN").format(num);
};
</script>
