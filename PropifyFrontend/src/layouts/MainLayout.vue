<template>
  <div class="layout">
    <HeroSection />
    <ListingSection />

    <main class="main-content">
      <slot />
    </main>

    <AppFooter />
  </div>
</template>

<script setup>
import AppFooter from "@/components/common/AppFooter.vue";
import HeroSection from "@/components/common/Home/HeroSection.vue";
import ListingSection from "@/components/common/Home/ListingSection.vue";
</script>

<style scoped>
.layout {
  display: flex;
  flex-direction: column;
  min-height: 100vh;
}

.main-content {
  flex: 1;
}
</style>
