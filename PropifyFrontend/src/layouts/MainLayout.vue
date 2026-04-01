<template>
  <div class="layout">
    <AppHeader />
    <HeroSection />
    <CategorySection />

    <main class="main-content">
      <slot />
    </main>

    <AppFooter />
  </div>
</template>

<script setup>
import AppHeader from "@/components/common/AppHeader.vue";
import AppFooter from "@/components/common/AppFooter.vue";
import HeroSection from "@/components/common/Home/HeroSection.vue";
import CategorySection from "@/components/common/Home/CategorySection.vue";
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
