<template>
  <section class="relative min-h-[85vh] flex items-center overflow-hidden">
    <!-- Background image -->
    <div class="absolute inset-0">
      <img
        src="@/assets/images/hero-property.jpg"
        alt="Modern property"
        class="w-full h-full object-cover"
      />
      <div
        class="absolute inset-0 bg-gradient-to-r from-foreground/80 via-foreground/50 to-transparent"
      ></div>
    </div>

    <div class="container relative z-10 mx-auto px-4 pt-20">
      <div class="max-w-2xl">
        <!-- Subtitle -->
        <p
          class="mb-4 animate-[fadeIn_0.6s_ease_forwards] text-sm font-medium uppercase tracking-wider text-primary-foreground/70"
        >
          Nền tảng cho thuê bất động sản hàng đầu
        </p>

        <!-- Title -->
        <h1
          class="mb-6 animate-[slideUp_0.6s_ease_forwards] text-4xl font-extrabold leading-[1.1] text-primary-foreground sm:text-5xl lg:text-6xl"
        >
          Tìm ngôi nhà <br />
          <span class="text-accent">mơ ước</span> của bạn
        </h1>

        <!-- Description -->
        <p
          class="mb-10 max-w-lg animate-[slideUp_0.6s_ease_forwards] text-lg text-primary-foreground/70"
          :style="{ animationDelay: '100ms' }"
        >
          Khám phá hàng ngàn tin đăng cho thuê được xác thực. Từ căn hộ cao cấp
          đến nhà phố yên tĩnh.
        </p>

        <!-- Search bar -->
        <div
          class="max-w-xl animate-[slideUp_0.6s_ease_forwards] rounded-2xl bg-card p-2 shadow-elevated"
          :style="{ animationDelay: '200ms' }"
        >
          <div class="flex flex-col sm:flex-row gap-2">
            <!-- Input -->
            <div
              class="flex-1 flex items-center gap-2 px-4 py-2 rounded-xl bg-muted/50"
            >
              <MapPin class="w-4 h-4 text-muted-foreground shrink-0" />

              <input
                v-model="searchQuery"
                type="text"
                placeholder="Nhập địa điểm, quận, đường..."
                class="flex-1 bg-transparent outline-none text-sm text-foreground placeholder:text-muted-foreground"
              />
            </div>

            <!-- Buttons -->
            <div class="flex gap-2">
              <button class="shrink-0 rounded-xl border border-border p-2">
                <SlidersHorizontal class="w-4 h-4" />
              </button>

              <button
                @click="goToListings"
                class="hero-gradient text-primary-foreground border-0 rounded-xl px-6 flex items-center hover:opacity-90 active:scale-[0.97] transition-all"
              >
                <Search class="w-4 h-4 mr-2" />
                Tìm kiếm
              </button>
            </div>
          </div>
        </div>

        <!-- Stats -->
        <div
          class="mt-10 flex animate-[slideUp_0.6s_ease_forwards] gap-8"
          :style="{ animationDelay: '350ms' }"
        >
          <div v-for="stat in stats" :key="stat.label">
            <p class="text-2xl font-bold text-primary-foreground">
              {{ stat.value }}
            </p>
            <p class="text-sm text-primary-foreground/60">
              {{ stat.label }}
            </p>
          </div>
        </div>
      </div>
    </div>
  </section>
</template>

<script setup>
import { ref } from "vue";
import { useRouter } from "vue-router";

// icons (cài nếu chưa có: npm i lucide-vue-next)
import { MapPin, Search, SlidersHorizontal } from "lucide-vue-next";

// state
const searchQuery = ref("");

// router
const router = useRouter();

const goToListings = () => {
  router.push({
    path: "/listings",
    query: {
      q: searchQuery.value,
    },
  });
};

// stats data
const stats = [
  { value: "12,847", label: "Tin đăng" },
  { value: "3,291", label: "Chủ nhà" },
  { value: "98.5%", label: "Hài lòng" },
];
</script>
