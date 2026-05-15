<template>
  <div class="flex min-h-screen flex-col">
    <HeroSection />
    <ListingSection />

    <main class="flex-1">
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
